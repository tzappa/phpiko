<?php declare(strict_types=1);

namespace PHPiko\Session;

use RuntimeException;

use function session_status;
use function session_start;
use function session_destroy;
use function session_unset;
use function ini_set;

class SessionManager implements SessionInterface
{
    public function __construct()
    {
        $this->start();
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    public function clear(): void
    {
        session_unset();
    }
    
    private function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Set session cookie parameters for security
            ini_set('session.cookie_httponly', 1);
            // ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Strict');

            if (session_start() === false) {
                throw new RuntimeException('Failed to start the session');
            }
        }
    }

    private function destroy()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
            $_SESSION = [];
        }
    }
}