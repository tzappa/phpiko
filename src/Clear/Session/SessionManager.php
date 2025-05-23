<?php 

declare(strict_types=1);

namespace Clear\Session;

use RuntimeException;

use function session_status;
use function session_start;
use function session_destroy;
use function session_unset;
use function ini_set;

/**
 * Session Manager
 */
class SessionManager implements SessionInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */    
    public function clear(): void
    {
        $this->start();
        session_unset();
    }
    
    /**
     * Start the session
     */
    public function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Set session cookie parameters for security
            ini_set('session.cookie_httponly', 1);
            // ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Strict');

            if (session_start() === false) {
                throw new RuntimeException('Failed to start the session: ' . error_get_last()['message']);
            }
        }
    }

    /**
     * Destroy the session
     */
    public function destroy()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
            $_SESSION = [];
        }
    }
}