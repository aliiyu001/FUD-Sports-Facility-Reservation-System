<?php
/**
 * includes/session_check.php
 * Included at the very top of every protected page.
 *
 * Responsibilities (and ONLY these — role checks stay in each page):
 *  - Ensure a PHP session is active.
 *  - Ensure the user is actually logged in (user_id present).
 *  - Enforce a 15-minute inactivity timeout, destroying the session
 *    and redirecting to index.php with a "session expired" message
 *    if exceeded.
 *  - On a valid session, refresh last_activity to now.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const SESSION_TIMEOUT_SECONDS = 15 * 60; // 15 minutes

// Figure out the correct relative path back to index.php from wherever
// this file is included (root, student/, admin/, security/).
function fud_index_redirect_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    // If the current script lives in a subfolder of fud_sports (student/,
    // admin/, security/), go up one level to reach index.php.
    if (preg_match('#/(student|admin|security)/[^/]+$#', $script)) {
        return '../index.php';
    }
    return 'index.php';
}

$notLoggedIn = empty($_SESSION['user_id']);
$expired = false;

if (!$notLoggedIn) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
        $expired = true;
    }
}

if ($notLoggedIn || $expired) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    session_start();
    $_SESSION['flash_error'] = $expired
        ? 'Your session has expired due to inactivity. Please log in again.'
        : 'Please log in to continue.';

    header('Location: ' . fud_index_redirect_path());
    exit;
}

// Valid session — refresh the activity timestamp.
$_SESSION['last_activity'] = time();
