<?php
/**
 * security/dashboard.php
 * View-only. No actions, no notifications.
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';

if ($_SESSION['role'] !== 'security') {
    header('Location: ../index.php');
    exit;
}

// Only approved reservations, today and upcoming.
$reservations = $conn->query(
    "SELECT u.full_name AS student_name, f.facility_name, r.reservation_date, r.session, r.purpose
     FROM reservations r
     JOIN users u ON u.user_id = r.user_id
     JOIN facilities f ON f.facility_id = r.facility_id
     WHERE r.status = 'approved' AND r.reservation_date >= CURDATE()
     ORDER BY r.reservation_date, r.session"
)->fetch_all(MYSQLI_ASSOC);

$today = (new DateTime('today'))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Dashboard - FUD Sports Facility Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-fud navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">FUD Sports Reservation - Security</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav align-items-lg-center ms-auto">
                <li class="nav-item navbar-user-badge me-3">
                    <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?> (Security)
                </li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="page-title">Security Unit Dashboard</h3>
    <p class="text-muted">View-only. Approved reservations for today and upcoming days.</p>

    <div class="card summary-card p-3">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No upcoming approved reservations.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
                            <?php $isToday = ($r['reservation_date'] === $today); ?>
                            <tr class="<?= $isToday ? 'today-highlight' : '' ?>">
                                <td><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r['reservation_date'], ENT_QUOTES, 'UTF-8') ?><?= $isToday ? ' (Today)' : '' ?></td>
                                <td><?= ucfirst(htmlspecialchars($r['session'], ENT_QUOTES, 'UTF-8')) ?></td>
                                <td><?= htmlspecialchars($r['purpose'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
