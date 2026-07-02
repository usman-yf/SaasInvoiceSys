<?php
// auth/verify.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$token = isset($_GET['token']) ? sanitize($conn, $_GET['token']) : '';

$status = '';
$message = '';
$icon = '';
$color = '';

if (empty($token)) {
    $status = 'error';
    $message = "Invalid or missing verification token.";
    $icon = "fas fa-exclamation-triangle";
    $color = "danger";
} else {
    $sql = "SELECT id, email_verified, token_expires_at FROM users WHERE verification_token = '$token'";
    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) == 0) {
        $status = 'error';
        $message = "Your verification link is invalid or has already been used and processed successfully.";
        $icon = "fas fa-times-circle";
        $color = "danger";
    } else {
        $user = mysqli_fetch_assoc($res);

        if ($user['email_verified'] == 1) {
            $status = 'info';
            $message = "Your email is already verified! You may log in if your account is approved.";
            $icon = "fas fa-info-circle";
            $color = "info";
        } elseif (strtotime($user['token_expires_at']) < time()) {
            $status = 'expired';
            $message = "Unfortunately, your verification link has expired. Please contact an administrator to resend a new activation email.";
            $icon = "fas fa-clock";
            $color = "warning";
        } else {
            $id = $user['id'];
            $update = "UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = $id";
            mysqli_query($conn, $update);

            $status = 'success';
            $message = "Excellent! Your email address has been successfully verified. Please wait for an administrator to approve your account before logging in.";
            $icon = "fas fa-check-circle";
            $color = "success";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php
// Determine Tailwind colors/icons based on status
$twColor = "gray";
$twIcon = "circle";

if ($status == 'error') {
    $twColor = "red";
    $twIcon = "x-circle";
} elseif ($status == 'info') {
    $twColor = "blue";
    $twIcon = "info";
} elseif ($status == 'expired') {
    $twColor = "yellow";
    $twIcon = "clock";
} elseif ($status == 'success') {
    $twColor = "green";
    $twIcon = "check-circle-2";
}
?>

<div class="flex items-center justify-center min-h-screen w-full bg-gray-50">
    <div class="w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 text-center overflow-hidden">
            <div class="p-8 sm:p-12">
                <div class="mb-6 flex justify-center">
                    <div class="w-24 h-24 rounded-full bg-<?= $twColor ?>-50 flex items-center justify-center">
                        <i data-lucide="<?= $twIcon ?>" class="w-12 h-12 text-<?= $twColor ?>-500"></i>
                    </div>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-900 mb-3"><?= ucfirst($status) ?></h3>
                <p class="text-gray-500 text-lg mb-8"><?= htmlspecialchars($message) ?></p>
                
                <div>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm hover:shadow transition-all group">
                        <i data-lucide="log-in" class="w-5 h-5 mr-2"></i> Go to Login
                    </a>
                </div>
            </div>
            
            <?php if($status !== 'success' && $status !== 'info'): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-center text-sm text-gray-500">
                <i data-lucide="shield" class="w-4 h-4 mr-1.5 text-gray-400"></i> InvSys Security System
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
