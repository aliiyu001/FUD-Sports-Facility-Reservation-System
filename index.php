<?php
/**
 * index.php
 * Landing page with login form. Also the redirect target for
 * "session expired" / "please log in" messages from session_check.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in with a valid (non-expired) session, go straight
// to the correct dashboard instead of showing the login form again.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) <= 15 * 60) {
        $_SESSION['last_activity'] = time();
        switch ($_SESSION['role']) {
            case 'student':
                header('Location: student/dashboard.php');
                exit;
            case 'admin':
                header('Location: admin/dashboard.php');
                exit;
            case 'security':
                header('Location: security/dashboard.php');
                exit;
        }
    }
}

$errors = [];
$successMessage = '';

// Pull any flash messages set elsewhere (e.g. session_check.php expiry,
// register.php success).
if (!empty($_SESSION['flash_error'])) {
    $errors[] = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
if (!empty($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

const MAX_ATTEMPTS = 5;
const LOCKOUT_WINDOW_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Count recent failed attempts for this email within the lockout window.
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS failed_count FROM login_attempts
             WHERE email = ? AND successful = 0
               AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $lockoutWindowMinutes = LOCKOUT_WINDOW_MINUTES;
        $stmt->bind_param('si', $email, $lockoutWindowMinutes);
        $stmt->execute();
        $failedCount = (int) $stmt->get_result()->fetch_assoc()['failed_count'];
        $stmt->close();

        if ($failedCount >= MAX_ATTEMPTS) {
            $errors[] = "Too many failed login attempts for this email. Please try again in {$LOCKOUT_WINDOW_MINUTES} minutes.";
        } else {
            // Look up the user.
            $stmt = $conn->prepare('SELECT user_id, full_name, password, role, is_privileged FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            $loginOk = $user && password_verify($password, $user['password']);

            // Log this attempt regardless of outcome.
            $logStmt = $conn->prepare('INSERT INTO login_attempts (email, successful) VALUES (?, ?)');
            $successFlag = $loginOk ? 1 : 0;
            $logStmt->bind_param('si', $email, $successFlag);
            $logStmt->execute();
            $logStmt->close();

            if ($loginOk) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_privileged'] = (int) $user['is_privileged'];
                $_SESSION['last_activity'] = time();

                switch ($user['role']) {
                    case 'student':
                        header('Location: student/dashboard.php');
                        exit;
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        exit;
                    case 'security':
                        header('Location: security/dashboard.php');
                        exit;
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FUD Sports Facility Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-5">
            <div class="card summary-card p-4">
                <h3 class="page-title text-center">FUD Sports Facility<br>Reservation System</h3>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success py-2"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php" novalidate>
                    <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100" style="background-color: var(--fud-green); border-color: var(--fud-green);">Log In</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Student or staff? <a href="register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
