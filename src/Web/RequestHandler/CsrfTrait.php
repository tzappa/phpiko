<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Session\SessionInterface;

/**
 * CSRF protection trait.
 */
trait CsrfTrait
{
    /**
     * The session instance.
     *
     * @var \Clear\Session\SessionInterface
     */
    private SessionInterface $session;

    /**
     * Generate a CSRF token.
     */
    private function generateCsrfToken(): string
    {
        // Check if a token is already set (e.g. when several forms are on the same page,
        // or when the user has multiple tabs open)
        if ($this->session->has('csrf')) {
            return $this->session->get('csrf');
        }
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf', $token);
        return $token;
    }

    /**
     * Check if the CSRF token is valid.
     * Always remove (invalidate) the token from the session after checking.
     */
    private function checkCsrfToken(string $token): bool
    {
        // if the session is expired
        if (!$this->session->has('csrf')) {
            return false;
        }
        $sessionToken = $this->session->get('csrf');
        $this->session->remove('csrf');
        return hash_equals($sessionToken, $token);
    }
}
