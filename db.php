<?php
/**
 * db.php
 * Central mysqli database connection for the FUD Sports Facility
 * Reservation and Approval Management System.
 *
 * Every other file that needs the database does: require_once 'db.php';
 * (adjusting the relative path as needed) and then uses the $conn
 * mysqli object with prepared statements.
 */

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: ''; // XAMPP default: empty password
$DB_NAME = getenv('DB_NAME') ?: 'fud_sports_reservation';
$DB_PORT = (int) (getenv('DB_PORT') ?: 3306);

// Use exceptions for mysqli errors so problems surface immediately
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Do not leak detailed DB errors to end users in a real production
    // deployment; for this academic project we show a clear message.
    die('Database connection failed. Please contact the system administrator. (' . $e->getMessage() . ')');
}
