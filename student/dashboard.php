<?php
/**
 * student/dashboard.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Summary counts
$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
     FROM reservations WHERE user_id = ?"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int) ($summary['total'] ?? 0);
$pending = (int) ($summary['pending'] ?? 0);
$approved = (int) ($summary['approved'] ?? 0);
$rejected = (int) ($summary['rejected'] ?? 0);

// Notifications (latest 20, newest first)
$stmt = $conn->prepare(
    'SELECT notification_id, message, is_read, created_at
     FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) {
        $unreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - FUD Sports Facility Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-fud navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">FUD Sports Reservation</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="facilities.php">Facilities</a></li>
                <li class="nav-item"><a class="nav-link" href="submit_request.php">Submit Request</a></li>
                <li class="nav-item"><a class="nav-link" href="my_requests.php">My Requests</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item me-3">
                    <div class="notif-bell-wrapper" id="notifBellWrapper">
                        <span style="font-size:1.3rem;">&#128276;</span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-count-badge" id="notifCountBadge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                        <div class="notif-dropdown card position-absolute d-none" id="notifDropdown" style="right:0; z-index: 1000;">
                            <?php if (empty($notifications)): ?>
                                <div class="notif-empty">No notifications yet.</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                                        <?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?>
                                        <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <li class="nav-item navbar-user-badge me-3">
                    <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?> (Student)
                </li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="page-title">Welcome, <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>

    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3 text-center">
                <div class="summary-number"><?= $total ?></div>
                <div>Total Requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3 text-center">
                <div class="summary-number" style="color:#e67e22;"><?= $pending ?></div>
                <div>Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3 text-center">
                <div class="summary-number" style="color:#27ae60;"><?= $approved ?></div>
                <div>Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3 text-center">
                <div class="summary-number" style="color:#c0392b;"><?= $rejected ?></div>
                <div>Rejected</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="submit_request.php" class="btn btn-success" style="background-color: var(--fud-green); border-color: var(--fud-green);">Submit a New Request</a>
        <a href="facilities.php" class="btn btn-outline-secondary">View Facilities</a>
        <a href="my_requests.php" class="btn btn-outline-secondary">My Requests</a>
    </div>
</div>

<!-- Hidden form used by JS to mark notifications as read via notifications.php -->
<form id="markReadForm" action="../notifications.php" method="POST" class="d-none">
    <?php csrf_field(); ?>
    <input type="hidden" name="mark_all" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('notifBellWrapper');
    const dropdown = document.getElementById('notifDropdown');
    const badge = document.getElementById('notifCountBadge');
    const markReadForm = document.getElementById('markReadForm');
    let opened = false;

    wrapper.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('d-none');

        if (!opened && !dropdown.classList.contains('d-none')) {
            opened = true;
            // Mark all as read via fetch (AJAX), then remove the badge locally.
            const data = new FormData(markReadForm);
            fetch(markReadForm.action, { method: 'POST', body: data })
                .then(() => {
                    if (badge) badge.remove();
                    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                })
                .catch(() => { /* fail silently, next page load will still show correct state */ });
        }
    });

    document.addEventListener('click', function () {
        dropdown.classList.add('d-none');
    });
});
</script>
</body>
</html>
