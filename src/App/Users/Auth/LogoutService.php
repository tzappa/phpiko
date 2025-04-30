<?php

declare(strict_types=1);

namespace App\Users\Auth;

use App\Users\{
    UserRepositoryInterface,
    NullDispatcher,
    User
};
use App\Users\Events\LogoutEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

final class LogoutService
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(private UserRepositoryInterface $repository, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?? new NullDispatcher();
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
}
