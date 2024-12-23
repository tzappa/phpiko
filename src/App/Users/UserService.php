<?php

declare(strict_types=1);

namespace App\Users;

use App\Users\Events\{
    ChangePasswordEvent,
    InvalidPasswordEvent,
    LoginFailEvent,
    LoginEvent,
    LogoutEvent
};
use Psr\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;
use RuntimeException;

final class UserService
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(private UserRepositoryInterface $repository, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?? new NullDispatcher();
    }

    /**
     * Logs in the user.
     * If there is an login error the error message is passed in the $error variable.
     *
     * @param string $username
     * @param string $password
     * @param string $error
     * @return User|null The user object or null on error
     */
    public function login(string $username, string $password, string &$error): User|null
    {
        $username = trim($username);
        $password = trim($password);
        if (!$username || !$password) {
            $error = 'Enter username and password';
            return null;
        }
        $user = $this->repository->find('username', $username);
        if ($user === null) {
            $user = new User([
                'id' => null,
                'username' => $username,
                'password' => password_hash('dummy', PASSWORD_DEFAULT), // dummy password to prevent timing attacks
                'state' => null,
            ]);
            $user->checkPassword($password); // dummy check to prevent timing attacks
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'Invalid credentials'));
            $error = 'Invalid username or password';
            return null;
        }
        $user = new User($user, $this->repository);
        if (!$user->checkPassword($password)) {
            $this->dispatcher->dispatch(new InvalidPasswordEvent($user, 'Invalid password'));
            $error = 'Invalid username or password';
            return null;
        }
        if ($user->state === User::STATE_BLOCKED) {
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'User is blocked'));
            $error = 'Invalid username or password'; // same message as for wrong password to hide that user the password is correct
            return null;
        }
        if ($user->state === User::STATE_NOLOGIN) {
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'Account locked'));
            $error = 'Invalid username or password'; // same message as for wrong password to hide that user the password is correct
            return null;
        }
        if ($user->state === User::STATE_INACTIVE) {
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'User is inactive'));
            $error = 'You need to activate your account first. Please check your email.';
            return null;
        }
        $this->dispatcher->dispatch(new LoginEvent($user));

        return $user;
    }

    /**
     * Checks the logged in user' status.
     * Used to check if the user is still active, blocked or deleted.
     * Users in NOLOGIN state can stay logged in.
     *
     * @param int $id
     * @return User|null The user object or null if the user does not exists or is blocked or inactive
     * @throws InvalidArgumentException
     */
    public function checkLogin(int $id): ?User
    {
        $user = $this->repository->find('id', $id);
        if ($user === null) {
            return null;
        }
        if ($user['state'] === User::STATE_BLOCKED) {
            return null;
        }
        if ($user['state'] === User::STATE_INACTIVE) {
            return null;
        }

        return new User($user, $this->repository);
    }

    /**
     * Logs out the user.
     *
     * @param User $user
     */
    public function logout(User $user): void
    {
        $this->dispatcher->dispatch(new LogoutEvent($user));
    }

    /**
     * Changes the user's password.
     *
     * @param User $user
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @param string $newPassword2 New password repeated
     * @return string|null Error message or null on success
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword, string $newPassword2): string|null
    {
        if ($newPassword !== $newPassword2) {
            return 'New passwords do not match';
        }
        if (!$user->checkPassword($currentPassword)) {
            $this->dispatcher->dispatch(new InvalidPasswordEvent($user, 'Invalid current password'));
            return 'Invalid current password';
        }
        try {
            $user->setPassword($newPassword);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        } catch (RuntimeException $e) {
            return 'Failed to change password';
        }
        $this->dispatcher->dispatch(new ChangePasswordEvent($user));
        return null;
    }
}
