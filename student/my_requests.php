<?php
/**
 * student/my_requests.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';

if ($_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$successMessage = '';
if (!empty($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$stmt = $conn->prepare(
    'SELECT r.reservation_id, f.facility_name, r.reservation_date, r.session, r.purpose, r.status, r.submitted_at
     FROM reservations r
     JOIN facilities f ON f.facility_id = r.facility_id
     WHERE r.user_id = ?
     ORDER BY r.submitted_at DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title>My Requests - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="my_requests.php">My Requests</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item navbar-user-badge me-3">
                    <?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?> (Student)
                </li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="page-title">My Requests</h3>

    <?php if ($successMessage): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card summary-card p-3">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="6" class="text-center text-muted">You have not submitted any requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r['reservation_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= ucfirst(htmlspecialchars($r['session'], ENT_QUOTES, 'UTF-8')) ?></td>
                                <td><?= htmlspecialchars($r['purpose'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td><?= htmlspecialchars($r['submitted_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
