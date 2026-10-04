<?php
/**
 * generate_seed_hashes.php
 *
 * One-time helper. Run this from the command line:
 *     php generate_seed_hashes.php
 * or open it in a browser once (e.g. http://localhost/fud_sports/generate_seed_hashes.php).
 *
 * It prints ready-to-run SQL UPDATE statements containing real
 * password_hash() values for the two seed accounts (admin, security).
 * Bcrypt hashes are salted differently every time they are generated,
 * so they cannot be safely hand-written into database.sql — this
 * script generates them for you to paste into phpMyAdmin / the MySQL
 * CLI right after importing database.sql.
 *
 * Delete this file (or at least move it out of the web root) once you
 * have run the UPDATE statements — it has no login/session protection
 * and is only meant to be used once during setup.
 */

$accounts = [
    'admin@fud.edu.ng'    => 'Admin@1234',
    'security@fud.edu.ng' => 'Security@1234',
];

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>\n";

echo $isCli ? "" : "<pre>";
echo "-- Run these statements against the fud_sports_reservation database" . $nl;
echo "-- (e.g. in phpMyAdmin's SQL tab, or via the mysql CLI) right after" . $nl;
echo "-- importing database.sql." . $nl . $nl;

foreach ($accounts as $email => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $emailEscaped = addslashes($email);
    $hashEscaped = addslashes($hash);
    echo "UPDATE users SET password = '{$hashEscaped}' WHERE email = '{$emailEscaped}';" . $nl;
}

echo $nl . "-- Plaintext passwords for reference (do not keep this output around):" . $nl;
foreach ($accounts as $email => $plainPassword) {
    echo "--   {$email} / {$plainPassword}" . $nl;
}
echo $isCli ? "" : "</pre>";
