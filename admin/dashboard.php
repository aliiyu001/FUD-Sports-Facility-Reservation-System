<?php
/**
 * admin/dashboard.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$adminId = $_SESSION['user_id'];

$summary = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
     FROM reservations"
)->fetch_assoc();

$total = (int) ($summary['total'] ?? 0);
$pending = (int) ($summary['pending'] ?? 0);
$approved = (int) ($summary['approved'] ?? 0);
$rejected = (int) ($summary['rejected'] ?? 0);

$stmt = $conn->prepare(
    'SELECT notification_id, message, is_read, created_at
     FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
);
$stmt->bind_param('i', $adminId);
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
    <title>Admin Dashboard - FUD Sports Facility Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-fud navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">FUD Sports Reservation - Admin</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="requests.php">All Requests</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_facilities.php">Manage Facilities</a></li>
                <li class="nav-item"><a class="nav-link" href="report.php">Report</a></li>
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
                    <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?> (Admin)
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
        <a href="requests.php" class="btn btn-success" style="background-color: var(--fud-green); border-color: var(--fud-green);">View All Requests</a>
        <a href="manage_facilities.php" class="btn btn-outline-secondary">Manage Facilities</a>
        <a href="report.php" class="btn btn-outline-secondary">Generate Report</a>
    </div>
</div>

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
            const data = new FormData(markReadForm);
            fetch(markReadForm.action, { method: 'POST', body: data })
                .then(() => {
                    if (badge) badge.remove();
                    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                })
                .catch(() => {});
        }
    });

    document.addEventListener('click', function () {
        dropdown.classList.add('d-none');
    });
});
</script>
</body>
</html>
