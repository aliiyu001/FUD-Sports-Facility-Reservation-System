<?php
/**
 * admin/manage_facilities.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$successMessage = '';

if (!empty($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $facilityId = (int) ($_POST['facility_id'] ?? 0);

    $stmt = $conn->prepare('SELECT status FROM facilities WHERE facility_id = ?');
    $stmt->bind_param('i', $facilityId);
    $stmt->execute();
    $facility = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$facility) {
        $errors[] = 'Facility not found.';
    } else {
        $newStatus = ($facility['status'] === 'available') ? 'unavailable' : 'available';
        $stmt = $conn->prepare('UPDATE facilities SET status = ? WHERE facility_id = ?');
        $stmt->bind_param('si', $newStatus, $facilityId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = 'Facility status updated successfully.';
        header('Location: manage_facilities.php');
        exit;
    }
}

$facilities = $conn->query('SELECT facility_id, facility_name, location, status FROM facilities ORDER BY facility_name')
    ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Facilities - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="manage_facilities.php">Manage Facilities</a></li>
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
    <h3 class="page-title">Manage Facilities</h3>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card summary-card p-3">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Facility Name</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
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
                        <td>
                            <form method="POST" action="manage_facilities.php" class="d-inline">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="facility_id" value="<?= (int) $f['facility_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    Mark as <?= $f['status'] === 'available' ? 'Unavailable' : 'Available' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
