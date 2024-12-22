<?php

declare(strict_types=1);

namespace App\Users;

use App\Users\Events\{
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
                'password' => null,
                'state' => null,
            ]);
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'User does not exists'));
            $error = 'Invalid username or password';
            return null;
        }
        $user = new User($user, $this->repository);
        if (!$user->checkPassword($password)) {
            $this->dispatcher->dispatch(new LoginFailEvent($user, 'Invalid password'));
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
        if ($user['state'] === User::STATE_NOLOGIN) {
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
     * @param string $password
     * @return string|true Error message or true on success
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword, string $newPassword2): string|true
    {
        if (!$user->checkPassword($oldPassword)) {
            return 'Invalid current password';
        }
        if ($newPassword !== $newPassword2) {
            return 'Passwords do not match';
        }
        try {
            $user->setPassword($newPassword);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        } catch (RuntimeException $e) {
            return 'Failed to change password';
        }
        return true;
    }
}
