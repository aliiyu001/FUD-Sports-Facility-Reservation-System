<?php
/**
 * student/submit_request.php
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$isPrivileged = (int) $_SESSION['is_privileged'];

$errors = [];
$old = ['facility_id' => '', 'reservation_date' => '', 'session' => '', 'purpose' => ''];

// Only available facilities can be booked.
$facilities = $conn->query("SELECT facility_id, facility_name FROM facilities WHERE status = 'available' ORDER BY facility_name")
    ->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $facilityId = (int) ($_POST['facility_id'] ?? 0);
    $reservationDate = trim($_POST['reservation_date'] ?? '');
    $session = $_POST['session'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');

    $old = [
        'facility_id' => $facilityId,
        'reservation_date' => $reservationDate,
        'session' => $session,
        'purpose' => $purpose,
    ];

    // --- Validate facility ---
    $facilityValid = false;
    foreach ($facilities as $f) {
        if ((int) $f['facility_id'] === $facilityId) {
            $facilityValid = true;
            break;
        }
    }
    if (!$facilityValid) {
        $errors[] = 'Please select a valid, available facility.';
    }

    // --- Validate date (must be a real date, and must be Fri/Sat/Sun) ---
    $dateObj = DateTime::createFromFormat('Y-m-d', $reservationDate);
    $dateValid = $dateObj && $dateObj->format('Y-m-d') === $reservationDate;

    if (!$dateValid) {
        $errors[] = 'Please select a valid date.';
    } else {
        $dayOfWeek = (int) $dateObj->format('N'); // 1 = Monday ... 7 = Sunday
        // Friday = 5, Saturday = 6, Sunday = 7
        if (!in_array($dayOfWeek, [5, 6, 7], true)) {
            $errors[] = 'Reservations are only allowed on Friday, Saturday, or Sunday. Monday-Thursday is reserved for the University Sports Team.';
        }

        $today = new DateTime('today');
        if ($dateObj < $today) {
            $errors[] = 'You cannot make a reservation for a past date.';
        }
    }

    // --- Validate session ---
    if (!in_array($session, ['morning', 'evening'], true)) {
        $errors[] = 'Please select a valid session (morning or evening).';
    }

    // --- Validate purpose ---
    if ($purpose === '') {
        $errors[] = 'Please provide a purpose for this reservation.';
    } elseif (mb_strlen($purpose) > 500) {
        $errors[] = 'Purpose must be 500 characters or fewer.';
    }

    if (empty($errors)) {
        // Check for an existing APPROVED reservation for this facility/date/session.
        $stmt = $conn->prepare(
            "SELECT reservation_id FROM reservations
             WHERE facility_id = ? AND reservation_date = ? AND session = ? AND status = 'approved'
             LIMIT 1"
        );
        $stmt->bind_param('iss', $facilityId, $reservationDate, $session);
        $stmt->execute();
        $conflict = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($conflict && !$isPrivileged) {
            $errors[] = 'This facility is already booked for that date and session.';
        } else {
            $finalPurpose = $purpose;
            if ($conflict && $isPrivileged) {
                $finalPurpose .= ' [PRIORITY REQUEST]';
            }

            $stmt = $conn->prepare(
                "INSERT INTO reservations (user_id, facility_id, reservation_date, session, purpose, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->bind_param('iisss', $userId, $facilityId, $reservationDate, $session, $finalPurpose);
            $stmt->execute();
            $reservationId = $stmt->insert_id;
            $stmt->close();

            // Notify every admin.
            $facilityNameStmt = $conn->prepare('SELECT facility_name FROM facilities WHERE facility_id = ?');
            $facilityNameStmt->bind_param('i', $facilityId);
            $facilityNameStmt->execute();
            $facilityName = $facilityNameStmt->get_result()->fetch_assoc()['facility_name'] ?? 'Unknown Facility';
            $facilityNameStmt->close();

            $studentName = $_SESSION['full_name'];
            $message = "New reservation request from {$studentName} for {$facilityName} on {$reservationDate} ({$session}).";

            $admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin'")->fetch_all(MYSQLI_ASSOC);
            $notifStmt = $conn->prepare('INSERT INTO notifications (user_id, reservation_id, message) VALUES (?, ?, ?)');
            foreach ($admins as $admin) {
                $adminId = $admin['user_id'];
                $notifStmt->bind_param('iis', $adminId, $reservationId, $message);
                $notifStmt->execute();
            }
            $notifStmt->close();

            $_SESSION['flash_success'] = 'Your reservation request has been submitted successfully.';
            header('Location: my_requests.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit Request - FUD Sports Facility Reservation System</title>
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
                <li class="nav-item"><a class="nav-link active" href="submit_request.php">Submit Request</a></li>
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
    <h3 class="page-title">Submit a Reservation Request</h3>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <div class="card summary-card p-4" style="max-width: 600px;">
        <form method="POST" action="submit_request.php" id="reservationForm" novalidate>
            <?php csrf_field(); ?>

            <div class="mb-3">
                <label for="facility_id" class="form-label">Facility</label>
                <select class="form-select" id="facility_id" name="facility_id" required>
                    <option value="">-- Select a facility --</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?= (int) $f['facility_id'] ?>"
                            <?= ((string) $old['facility_id'] === (string) $f['facility_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['facility_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="reservation_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="reservation_date" name="reservation_date" required
                       value="<?= htmlspecialchars($old['reservation_date'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text">Only Fridays, Saturdays, and Sundays are selectable. Monday-Thursday is reserved for the University Sports Team.</div>
                <div class="text-danger" id="dateClientError" style="display:none;">Please select a Friday, Saturday, or Sunday.</div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Session</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="session" id="session_morning" value="morning"
                        <?= ($old['session'] === 'morning') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="session_morning">Morning</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="session" id="session_evening" value="evening"
                        <?= ($old['session'] === 'evening') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="session_evening">Evening</label>
                </div>
            </div>

            <div class="mb-3">
                <label for="purpose" class="form-label">Purpose</label>
                <textarea class="form-control" id="purpose" name="purpose" rows="3" maxlength="500" required><?= htmlspecialchars($old['purpose'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100" style="background-color: var(--fud-green); border-color: var(--fud-green);">Submit Request</button>
        </form>
    </div>
</div>

<script>
// Client-side restriction: only allow Fri/Sat/Sun to be picked.
// PHP re-validates this on submit regardless.
const dateInput = document.getElementById('reservation_date');
const dateError = document.getElementById('dateClientError');
const form = document.getElementById('reservationForm');

function isAllowedDay(dateStr) {
    if (!dateStr) return false;
    const parts = dateStr.split('-').map(Number);
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    const day = d.getDay(); // 0 = Sunday, 5 = Friday, 6 = Saturday
    return day === 0 || day === 5 || day === 6;
}

dateInput.addEventListener('change', function () {
    if (this.value && !isAllowedDay(this.value)) {
        dateError.style.display = 'block';
        this.setCustomValidity('Only Friday, Saturday, or Sunday allowed.');
    } else {
        dateError.style.display = 'none';
        this.setCustomValidity('');
    }
});

form.addEventListener('submit', function (e) {
    if (dateInput.value && !isAllowedDay(dateInput.value)) {
        e.preventDefault();
        dateError.style.display = 'block';
    }
});
</script>

</body>
</html>
