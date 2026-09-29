<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$studentHash = password_hash('123456', PASSWORD_DEFAULT);
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);

$db->prepare('UPDATE student_accounts SET password_hash = ?')->execute([$studentHash]);
$db->prepare('UPDATE admin_users SET password_hash = ?')->execute([$adminHash]);

echo "SUCCESS: Updated password hashes.\n";
echo "Student hash: " . $studentHash . "\n";
echo "Admin hash: " . $adminHash . "\n";
