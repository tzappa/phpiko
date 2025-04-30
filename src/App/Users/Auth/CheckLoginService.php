<?php

declare(strict_types=1);

namespace App\Users\Auth;

use App\Users\{
    UserRepositoryInterface,
    User
};
use InvalidArgumentException;

final class CheckLoginService
{
    public function __construct(private UserRepositoryInterface $repository) {}

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
}
