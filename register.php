<?php
/**
 * register.php
 * Student/staff self-registration. role is always 'student',
 * is_privileged always defaults to 0. Admin and security accounts are
 * created manually in the database, never through this form.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/csrf.php';

// Already logged in? No need to register again.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) <= 15 * 60) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $old['full_name'] = $fullName;
    $old['email'] = $email;

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'All fields are required.';
    }

    if ($fullName !== '' && (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100)) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
    }

    if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (empty($errors) && $email !== '') {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';
        $isPrivileged = 0;

        $stmt = $conn->prepare(
            'INSERT INTO users (full_name, email, password, role, is_privileged) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssi', $fullName, $email, $hashedPassword, $role, $isPrivileged);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Registration successful! You can now log in.';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - FUD Sports Facility Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-6">
            <div class="card summary-card p-4">
                <h3 class="page-title text-center">Student / Staff Registration</h3>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>

                <form method="POST" action="register.php" novalidate>
                    <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required
                               value="<?= htmlspecialchars($old['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Minimum 8 characters, with at least one uppercase letter, one number, and one special character.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100" style="background-color: var(--fud-green); border-color: var(--fud-green);">Register</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Already have an account? <a href="index.php">Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
