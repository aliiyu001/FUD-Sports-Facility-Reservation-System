<?php
/**
 * admin/report.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Summary grouped by facility.
$facilitySummary = $conn->query(
    "SELECT f.facility_name,
            COUNT(r.reservation_id) AS total,
            SUM(r.status = 'approved') AS approved,
            SUM(r.status = 'rejected') AS rejected,
            SUM(r.status = 'pending') AS pending
     FROM facilities f
     LEFT JOIN reservations r ON r.facility_id = f.facility_id
     GROUP BY f.facility_id, f.facility_name
     ORDER BY f.facility_name"
)->fetch_all(MYSQLI_ASSOC);

// Full list of approved reservations.
$approvedList = $conn->query(
    "SELECT u.full_name AS student_name, f.facility_name, r.reservation_date, r.session
     FROM reservations r
     JOIN users u ON u.user_id = r.user_id
     JOIN facilities f ON f.facility_id = r.facility_id
     WHERE r.status = 'approved'
     ORDER BY r.reservation_date, r.session"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="report.php">Report</a></li>
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
    <h3 class="page-title">Reservation Report</h3>

    <h5 class="mb-3">Summary by Facility</h5>
    <div class="card summary-card p-3 mb-4">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Total</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Pending</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facilitySummary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['total'] ?></td>
                        <td><?= (int) $row['approved'] ?></td>
                        <td><?= (int) $row['rejected'] ?></td>
                        <td><?= (int) $row['pending'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h5 class="mb-3">All Approved Reservations</h5>
    <div class="card summary-card p-3">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Session</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($approvedList)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No approved reservations yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($approvedList as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['reservation_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ucfirst(htmlspecialchars($r['session'], ENT_QUOTES, 'UTF-8')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
