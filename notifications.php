<?php
/**
 * notifications.php
 * Shared mark-as-read handler used by the AJAX behind the notification
 * bell on both the student and admin dashboards. Security unit accounts
 * get no notifications and are rejected here.
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/csrf.php';

// Only students and admins have notifications.
if (!in_array($_SESSION['role'], ['student', 'admin'], true)) {
    http_response_code(403);
    die('Access denied.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

csrf_verify($_POST['csrf_token'] ?? null);

$userId = $_SESSION['user_id'];

if (!empty($_POST['mark_all'])) {
    // Mark all of this user's notifications as read.
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'marked' => 'all']);
    exit;
}

if (!empty($_POST['notification_id'])) {
    $notificationId = (int) $_POST['notification_id'];

    // Ensure the notification belongs to the logged-in user before updating.
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $notificationId, $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['success' => $affected > 0, 'marked' => $notificationId]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No notification specified.']);
