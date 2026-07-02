<?php
// users/delete.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == $_SESSION['user_id']) {
    redirect(BASE_URL . '/users/list.php?err=' . urlencode('Cannot delete your own account.'));
}

$check = mysqli_query($conn, "SELECT role, full_name FROM users WHERE id = $id");
if(mysqli_num_rows($check) > 0) {
    $user = mysqli_fetch_assoc($check);
    if($user['role'] == 'admin') {
        $adminCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='admin'");
        $adminCount = mysqli_fetch_assoc($adminCheck)['count'];
        if($adminCount <= 1) {
            redirect(BASE_URL . '/users/list.php?err=' . urlencode('Cannot delete the last administrator account.'));
        }
    }
    
    $full_name = $user['full_name'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    logActivity($conn, $_SESSION['user_id'], 'User Deleted', "Deleted user account for $full_name.");
    redirect(BASE_URL . '/users/list.php?msg=' . urlencode("User '{$full_name}' successfully deleted."));
} else {
    redirect(BASE_URL . '/users/list.php');
}
?>
