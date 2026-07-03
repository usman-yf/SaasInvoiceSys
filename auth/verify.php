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

$page_title = 'Account Verification';
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

<div class="flex items-center justify-center min-h-screen w-full bg-slate-50 relative overflow-hidden">
    <!-- Premium background blobs -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-brand-100/50 blur-3xl opacity-50 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-blue-100/50 blur-3xl opacity-50 pointer-events-none"></div>

    <div class="w-full max-w-md px-4 relative z-10 animate-fade-in-up">
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)] border border-white text-center overflow-hidden">
            <div class="p-10 sm:p-12">
                <div class="mb-8 relative flex justify-center">
                    <div class="absolute inset-0 bg-<?= $twColor ?>-200 rounded-full blur-xl opacity-40 animate-pulse"></div>
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-<?= $twColor ?>-50 to-white flex items-center justify-center shadow-inner relative z-10 border border-<?= $twColor ?>-100/50">
                        <i data-lucide="<?= $twIcon ?>" class="w-10 h-10 text-<?= $twColor ?>-500 drop-shadow-sm"></i>
                    </div>
                </div>
                
                <h3 class="text-2xl font-extrabold text-slate-800 mb-3 tracking-tight"><?= ucfirst($status) ?></h3>
                <p class="text-slate-500 text-base leading-relaxed mb-10"><?= htmlspecialchars($message) ?></p>
                
                <div>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="inline-flex items-center justify-center w-full px-6 py-3.5 rounded-2xl font-semibold text-white bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-700 hover:to-brand-600 shadow-[0_4px_14px_0_rgba(124,58,237,0.39)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.23)] hover:-translate-y-0.5 transition-all duration-200 group">
                        <i data-lucide="log-in" class="w-5 h-5 mr-2 group-hover:animate-pulse"></i> Continue to Login
                    </a>
                </div>
            </div>
            
            <?php if($status !== 'success' && $status !== 'info'): ?>
            <div class="bg-slate-50/50 backdrop-blur-md px-6 py-4 border-t border-slate-100 flex items-center justify-center text-xs font-medium text-slate-500">
                <i data-lucide="shield-alert" class="w-4 h-4 mr-1.5 text-slate-400"></i> InvSys Security Protocol
            </div>
            <?php else: ?>
            <div class="bg-slate-50/50 backdrop-blur-md px-6 py-4 border-t border-slate-100 flex items-center justify-center text-xs font-medium text-slate-500">
                <i data-lucide="shield-check" class="w-4 h-4 mr-1.5 text-green-500"></i> Verified by InvSys Security
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
