<?php

declare(strict_types=1);

namespace App\Users\Password;

use App\Users\{
    UserRepositoryInterface,
    NullDispatcher,
    User
};
use App\Users\Events\{
    ChangePasswordEvent,
    InvalidPasswordEvent
};
use Psr\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;
use RuntimeException;

final class ChangePasswordService
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(private UserRepositoryInterface $repository, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?? new NullDispatcher();
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
