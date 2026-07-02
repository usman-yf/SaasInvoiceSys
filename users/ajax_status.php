<?php
// users/ajax_status.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

$sql = "SELECT id, email_verified, admin_verified FROM users";
$res = mysqli_query($conn, $sql);

$statuses = [];
while ($row = mysqli_fetch_assoc($res)) {
    $statuses[$row['id']] = [
        'email_verified' => (int)$row['email_verified'],
        'admin_verified' => (int)$row['admin_verified']
    ];
}

header('Content-Type: application/json');
echo json_encode($statuses);
?>
