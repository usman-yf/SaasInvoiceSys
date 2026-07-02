<?php
// users/verify_admin.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

if ($_SESSION['role'] !== 'admin') {
    redirect('/inv/users/list.php?msg=failed');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    if ($id == $_SESSION['user_id']) {
        redirect('/inv/users/list.php?msg=' . urlencode("You are already verified."));
    }
    
    $res = mysqli_query($conn, "SELECT admin_verified, full_name FROM users WHERE id=$id");
    if ($row = mysqli_fetch_assoc($res)) {
        $status = $row['admin_verified'] == 1 ? 0 : 1;
        $msg = $status == 1 ? "approved" : "unapproved";
        mysqli_query($conn, "UPDATE users SET admin_verified = $status WHERE id=$id");
        $log_action = $status == 1 ? 'Admin Verification Approved' : 'Admin Verification Revoked';
        $professional_msg = $status == 1 ? "The account for {$row['full_name']} has been successfully verified." : "The admin privileges for {$row['full_name']} have been revoked.";
        logActivity($conn, $_SESSION['user_id'], $log_action, $professional_msg);
        redirect('/inv/users/list.php?msg=' . urlencode("User {$row['full_name']} has been $msg."));
    }
}

redirect('/inv/users/list.php');
?>
