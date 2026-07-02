<?php
// auth/logout.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    @mysqli_query($conn, "UPDATE users SET remember_token = NULL WHERE id = $userId");
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");
}

session_unset();
session_destroy();
header("Location: " . BASE_URL . "/auth/login.php?msg=Logged+out+successfully");
exit();
?>
