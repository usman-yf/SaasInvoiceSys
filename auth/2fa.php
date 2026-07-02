<?php
// auth/2fa.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is in the 2FA flow
if (!isset($_SESSION['pending_2fa_user'])) {
    header("Location: /inv/auth/login.php");
    exit();
}

$user_id = $_SESSION['pending_2fa_user'];
$error = '';
$success = '';

// Fetch user details for verification, resend, and timer display
$sql = "SELECT * FROM users WHERE id = '$user_id' LIMIT 1";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: /inv/auth/login.php");
    exit();
}


// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify') {
        $code = trim($_POST['code'] ?? '');
        
        if (empty($code)) {
            $error = "Please enter the 6-digit code.";
        } elseif ($code !== $user['two_factor_code']) {
            $error = "Invalid verification code.";
        } elseif (strtotime($user['two_factor_expires']) < time()) {
            $error = "This code has expired. Please request a new one.";
        } else {
            // Success! Complete the login process
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = isset($user['full_name']) ? $user['full_name'] : $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Clear 2FA data
            mysqli_query($conn, "UPDATE users SET two_factor_code = NULL, two_factor_expires = NULL WHERE id = '$user_id'");
            
            // Handle Remember Me if it was checked on the previous screen
            if (isset($_SESSION['pending_remember_me']) && $_SESSION['pending_remember_me']) {
                $days = (int)get_setting($conn, 'remember_me_days', '30');
                $token = bin2hex(random_bytes(32));
                mysqli_query($conn, "UPDATE users SET remember_token = '$token' WHERE id = '$user_id'");
                
                $email = $user['email'];
                $password = $_SESSION['pending_password'] ?? '';
                
                setcookie('remember_token', $token, time() + (86400 * $days), "/");
                setcookie('remember_email', $email, time() + (86400 * $days), "/");
                if (!empty($password)) {
                    setcookie('remember_password', encrypt_data($password), time() + (86400 * $days), "/");
                }
            } else {
                setcookie('remember_email', '', time() - 3600, "/");
                setcookie('remember_password', '', time() - 3600, "/");
            }
            
            // Clean up temporary pending sessions
            unset($_SESSION['pending_2fa_user']);
            unset($_SESSION['pending_remember_me']);
            unset($_SESSION['pending_password']);
            
            logActivity($conn, $user['id'], 'User Login', 'User successfully authenticated via 2FA');
            
            header("Location: /inv/index.php?msg=Welcome+back");
            exit();
        }
    } elseif ($action === 'resend') {
        // Generate new code
        $new_code = sprintf("%06d", mt_rand(1, 999999));
        $expire_mins = (int)get_setting($conn, '2fa_expiration_minutes', '10');
        $expires = date('Y-m-d H:i:s', strtotime("+$expire_mins minutes"));
        
        mysqli_query($conn, "UPDATE users SET two_factor_code = '$new_code', two_factor_expires = '$expires' WHERE id = '$user_id'");
        
        // Update user array in memory so the new timer displays correctly
        $user['two_factor_expires'] = $expires;
        
        // Send email
        $email = $user['email'];
        send_2fa_email($email, $new_code);
        
        $success = "A new verification code has been sent to your email.";
    }
}

// Calculate remaining time for JS timer (in seconds)
$expires_time = strtotime($user['two_factor_expires']);
$current_time = time();
$remaining_seconds = max(0, $expires_time - $current_time);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center justify-center min-h-screen w-full bg-gray-50">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-brand-700 text-white p-6 text-center border-b border-brand-800">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 mb-4">
                    <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                </div>
                <h4 class="text-xl font-bold">Two-Factor Authentication</h4>
                <p class="text-brand-100 text-sm mt-1">Check your email for the 6-digit code</p>
            </div>
            
            <div class="p-6 sm:p-8">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-start text-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-2 shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 flex items-start text-sm">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2 shrink-0"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <div class="text-center mb-6">
                    <p class="text-sm text-gray-500 font-medium">Code expires in <span id="timerDisplay" class="text-brand-600 font-bold tabular-nums ml-1">00:00</span></p>
                </div>

                <form action="" method="post" id="verifyForm">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="code" id="actualCode">
                    
                    <div class="mb-5">
                        <div class="flex justify-center gap-2 sm:gap-3" id="otpContainer">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autofocus autocomplete="off">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autocomplete="off">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autocomplete="off">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autocomplete="off">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autocomplete="off">
                            <input type="text" maxlength="1" class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm" autocomplete="off">
                        </div>
                    </div>
                    
                    <button type="submit" id="verifyBtn" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center group mb-5">
                        <span id="btnText" class="flex items-center">
                            Verify & Login
                            <i data-lucide="arrow-right" class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span id="btnLoader" class="hidden flex items-center">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        </span>
                    </button>
                </form>
                
                <form action="" method="post" id="resendForm">
                    <input type="hidden" name="action" value="resend">
                    <p class="text-center text-sm text-gray-600 flex flex-col items-center justify-center gap-2">
                        <span>Didn't receive the code?</span>
                        <button type="submit" id="resendBtn" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed group">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2 text-gray-500 group-hover:rotate-180 transition-transform duration-500"></i>
                            Resend Code
                        </button>
                    </p>
                </form>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Timer Logic
                let timeLeft = <?= $remaining_seconds ?>;
                const timerDisplay = document.getElementById('timerDisplay');
                const resendBtn = document.getElementById('resendBtn');
                
                function updateTimer() {
                    if (timeLeft <= 0) {
                        timerDisplay.textContent = "00:00";
                        timerDisplay.classList.remove('text-brand-600');
                        timerDisplay.classList.add('text-red-500');
                        resendBtn.removeAttribute('disabled');
                        return;
                    }
                    
                    resendBtn.setAttribute('disabled', 'true');
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerDisplay.textContent = 
                        (minutes < 10 ? '0' : '') + minutes + ':' + 
                        (seconds < 10 ? '0' : '') + seconds;
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                }
                
                updateTimer();

                // Premium OTP Inputs Logic
                const container = document.getElementById('otpContainer');
                const inputs = container.querySelectorAll('input');
                const hiddenInput = document.getElementById('actualCode');
                const verifyForm = document.getElementById('verifyForm');
                
                function updateHiddenInput() {
                    let code = '';
                    inputs.forEach(input => code += input.value);
                    hiddenInput.value = code;
                }

                inputs.forEach((input, index) => {
                    // Only allow numbers
                    input.addEventListener('input', function(e) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        if (this.value !== '') {
                            // Move to next input
                            if (index < inputs.length - 1) {
                                inputs[index + 1].focus();
                            }
                        }
                        updateHiddenInput();
                    });
                    
                    // Handle Backspace
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && this.value === '') {
                            // Move to previous input
                            if (index > 0) {
                                inputs[index - 1].focus();
                            }
                        }
                    });
                    
                    // Handle Paste
                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                        
                        if (pastedData) {
                            const chars = pastedData.split('');
                            inputs.forEach((inp, i) => {
                                if (chars[i]) {
                                    inp.value = chars[i];
                                }
                            });
                            
                            // Focus the right input
                            const focusIndex = Math.min(chars.length, inputs.length - 1);
                            if(chars.length === 6) {
                                inputs[5].focus();
                            } else {
                                inputs[focusIndex].focus();
                            }
                            updateHiddenInput();
                        }
                    });
                });
                
                // Form submit loading state
                verifyForm.addEventListener('submit', function(e) {
                    updateHiddenInput();
                    if(hiddenInput.value.length === 6) {
                        document.getElementById('btnText').classList.add('hidden');
                        document.getElementById('btnLoader').classList.remove('hidden');
                        document.getElementById('verifyBtn').classList.add('opacity-80', 'cursor-not-allowed');
                    } else {
                        e.preventDefault();
                        // Shake animation or focus first empty
                        for(let i=0; i<inputs.length; i++) {
                            if(!inputs[i].value) {
                                inputs[i].focus();
                                break;
                            }
                        }
                    }
                });
            });
            </script>
            
            <div class="bg-gray-50 px-6 py-4 text-center border-t border-gray-100 text-sm">
                <a href="/inv/auth/login.php" class="text-gray-500 hover:text-gray-700 flex items-center justify-center transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
