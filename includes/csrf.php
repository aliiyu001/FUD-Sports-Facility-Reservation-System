<?php
/**
 * includes/csrf.php
 * CSRF token helpers. Must be included AFTER session_start() has run
 * (session_check.php / login pages call session_start() before this).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the current session's CSRF token, generating one if needed.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Echoes a hidden input field containing the CSRF token, for use
 * inside <form> tags.
 */
function csrf_field(): void
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verifies a posted CSRF token against the session token using a
 * timing-safe comparison. On failure, stops execution immediately with
 * a generic error rather than letting the form processing continue.
 */
function csrf_verify(?string $token): void
{
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('Invalid request, please try again.');
    }
}
