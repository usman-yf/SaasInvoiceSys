<?php
require 'config/db.php';
$id = 8;
$sql = "SELECT two_factor_expires FROM users WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$expires_time = strtotime($user['two_factor_expires']);
$current_time = time();
$remaining_seconds = max(0, $expires_time - $current_time);

echo "DB expires string: " . $user['two_factor_expires'] . "\n";
echo "Expires timestamp: $expires_time\n";
echo "Current timestamp: $current_time\n";
echo "Remaining: $remaining_seconds\n";
