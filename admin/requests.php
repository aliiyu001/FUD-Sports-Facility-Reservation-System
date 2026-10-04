<?php
/**
 * admin/requests.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$successMessage = '';
if (!empty($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$requests = $conn->query(
    "SELECT r.reservation_id, u.full_name AS student_name, u.is_privileged,
            f.facility_name, r.reservation_date, r.session, r.purpose, r.status, r.submitted_at
     FROM reservations r
     JOIN users u ON u.user_id = r.user_id
     JOIN facilities f ON f.facility_id = r.facility_id
     ORDER BY (r.status = 'pending') DESC, r.submitted_at DESC"
)->fetch_all(MYSQLI_ASSOC);

function statusBadge(string $status): string
{
    $class = 'status-' . $status;
    $label = ucfirst($status);
    return '<span class="status-badge ' . $class . '">' . $label . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Requests - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="requests.php">All Requests</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_facilities.php">Manage Facilities</a></li>
                <li class="nav-item"><a class="nav-link" href="report.php">Report</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item navbar-user-badge me-3">
                    <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?> (Admin)
                </li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="page-title">All Reservation Requests</h3>

    <?php if ($successMessage): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

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
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No reservation requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ((int) $r['is_privileged'] === 1): ?>
                                        <span class="priority-badge ms-1">Priority</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r['reservation_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= ucfirst(htmlspecialchars($r['session'], ENT_QUOTES, 'UTF-8')) ?></td>
                                <td><?= htmlspecialchars($r['purpose'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td><?= htmlspecialchars($r['submitted_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" action="approve.php" class="d-inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="reservation_id" value="<?= (int) $r['reservation_id'] ?>">
                                            <input type="hidden" name="decision" value="approved">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="approve.php" class="d-inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="reservation_id" value="<?= (int) $r['reservation_id'] ?>">
                                            <input type="hidden" name="decision" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Decision recorded</span>
                                    <?php endif; ?>
                                </td>
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
