<?php
/**
 * admin/approve.php
 * Handles POST from the Approve/Reject buttons on requests.php.
 */

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: requests.php');
    exit;
}

csrf_verify($_POST['csrf_token'] ?? null);

$adminId = $_SESSION['user_id'];
$reservationId = (int) ($_POST['reservation_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$comments = trim($_POST['comments'] ?? '');

if ($reservationId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
    $_SESSION['flash_error'] = 'Invalid approval request.';
    header('Location: requests.php');
    exit;
}

// Fetch the reservation to confirm it exists and is still pending, and to
// build the student notification message.
$stmt = $conn->prepare(
    "SELECT r.user_id, r.status, r.reservation_date, r.session, f.facility_name
     FROM reservations r
     JOIN facilities f ON f.facility_id = r.facility_id
     WHERE r.reservation_id = ?"
);
$stmt->bind_param('i', $reservationId);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    $_SESSION['flash_error'] = 'Reservation not found.';
    header('Location: requests.php');
    exit;
}

if ($reservation['status'] !== 'pending') {
    $_SESSION['flash_error'] = 'This reservation has already been decided.';
    header('Location: requests.php');
    exit;
}

// Record the decision.
$stmt = $conn->prepare('INSERT INTO approvals (reservation_id, admin_id, decision, comments) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiss', $reservationId, $adminId, $decision, $comments);
$stmt->execute();
$stmt->close();

// Update the reservation's status.
$stmt = $conn->prepare('UPDATE reservations SET status = ? WHERE reservation_id = ?');
$stmt->bind_param('si', $decision, $reservationId);
$stmt->execute();
$stmt->close();

// Notify the student.
$studentId = $reservation['user_id'];
$facilityName = $reservation['facility_name'];
$date = $reservation['reservation_date'];
$session = $reservation['session'];
$message = "Your request for {$facilityName} on {$date} ({$session}) has been {$decision}.";

$stmt = $conn->prepare('INSERT INTO notifications (user_id, reservation_id, message) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $studentId, $reservationId, $message);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = 'Reservation has been ' . $decision . '.';
header('Location: requests.php');
exit;
