<?php
/**
 * student/facilities.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';

if ($_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$facilities = $conn->query('SELECT facility_id, facility_name, location, status FROM facilities ORDER BY facility_name')
    ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facilities - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="facilities.php">Facilities</a></li>
                <li class="nav-item"><a class="nav-link" href="submit_request.php">Submit Request</a></li>
                <li class="nav-item"><a class="nav-link" href="my_requests.php">My Requests</a></li>
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
    <h3 class="page-title">Sports Facilities</h3>

    <div class="card summary-card p-3">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Facility Name</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facilities)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No facilities found.</td></tr>
                <?php else: ?>
                    <?php foreach ($facilities as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['facility_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($f['location'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($f['status'] === 'available'): ?>
                                    <span class="facility-available">Available</span>
                                <?php else: ?>
                                    <span class="facility-unavailable">Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
