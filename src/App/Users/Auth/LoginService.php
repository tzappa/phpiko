<?php

declare(strict_types=1);

namespace App\Users\Auth;

use App\Users\{
    UserRepositoryInterface,
    NullDispatcher,
    User
};
use App\Users\Events\{
    InvalidPasswordEvent,
    LoginFailEvent,
    LoginEvent
};
use Psr\EventDispatcher\EventDispatcherInterface;

final class LoginService
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(private UserRepositoryInterface $repository, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?? new NullDispatcher();
    }

    /**
     * Logs in the user.
     * If there is a login error the error message is passed in the $error variable.
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
}
