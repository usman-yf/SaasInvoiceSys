<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $user_id = $_SESSION['user_id'];
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = (int)$_POST['id'];
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND id = $id";
    } else {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo 'success';
    } else {
        echo 'error';
    }
}
?>
