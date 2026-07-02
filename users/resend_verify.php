<?php
// users/resend_verify.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

checkAuth();

if ($_SESSION['role'] !== 'admin') {
    redirect(BASE_URL . '/users/list.php?msg=failed');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $res = mysqli_query($conn, "SELECT full_name, email, email_verified FROM users WHERE id=$id");
    if ($row = mysqli_fetch_assoc($res)) {
        if ($row['email_verified'] == 1) {
            redirect(BASE_URL . '/users/list.php?msg=' . urlencode("User '{$row['full_name']}' is already verified."));
        } else {
            // Generate new token and expiration
            $token = bin2hex(random_bytes(32));
            
            $expire_minutes = get_setting($conn, 'email_verify_expire_minutes', '1440');
            $expires = date('Y-m-d H:i:s', strtotime("+$expire_minutes minutes"));
            
            mysqli_query($conn, "UPDATE users SET verification_token = '$token', token_expires_at = '$expires' WHERE id=$id");
            
            // Send email
            sendVerificationEmail($row['email'], $row['full_name'], $token, $expire_minutes);
            
            logActivity($conn, $_SESSION['user_id'], 'Resent Verification', "Resent verification email to {$row['full_name']}.");
            redirect(BASE_URL . '/users/list.php?msg=' . urlencode("Verification email resent to '{$row['full_name']}'."));
        }
    }
}

redirect(BASE_URL . '/users/list.php');
?>
