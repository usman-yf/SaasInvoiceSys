<?php
// auth/2fa.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is in the 2FA flow
if (!isset($_SESSION['pending_2fa_user'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
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
    header("Location: " . BASE_URL . "/auth/login.php");
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
                $days = (int) get_setting($conn, 'remember_me_days', '30');
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

            header("Location: " . BASE_URL . "/index.php?msg=Welcome+back");
            exit();
        }
    } elseif ($action === 'resend') {
        // Generate new code
        $new_code = sprintf("%06d", mt_rand(1, 999999));
        $expire_mins = (int) get_setting($conn, '2fa_expiration_minutes', '10');
        $expires = date('Y-m-d H:i:s', strtotime("+$expire_mins minutes"));

        mysqli_query($conn, "UPDATE users SET two_factor_code = '$new_code', two_factor_expires = '$expires' WHERE id = '$user_id'");

        // Update user array in memory so the new timer displays correctly
        $user['two_factor_expires'] = $expires;

        // Send email
        $email = $user['email'];
        send_2fa_email($email, $new_code);

        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'A new verification code has been sent to your email.',
                'remaining_seconds' => $expire_mins * 60
            ]);
            exit();
        }

        $success = "A new verification code has been sent to your email.";
    }
}

// Calculate remaining time for JS timer (in seconds)
$expires_time = strtotime($user['two_factor_expires']);
$current_time = time();
$remaining_seconds = max(0, $expires_time - $current_time);

// Toast settings
$toast_position_pref = get_setting($conn, 'toast_position', 'top-right');
$toast_position_class = 'top-6 right-6';
$toast_anim_in = 'translate-x-full';
switch ($toast_position_pref) {
    case 'top-left':
        $toast_position_class = 'top-6 left-6';
        $toast_anim_in = '-translate-x-full';
        break;
    case 'top-center':
        $toast_position_class = 'top-6 left-1/2 -translate-x-1/2';
        $toast_anim_in = '-translate-y-full';
        break;
    case 'bottom-right':
        $toast_position_class = 'bottom-6 right-6';
        $toast_anim_in = 'translate-x-full';
        break;
    case 'bottom-left':
        $toast_position_class = 'bottom-6 left-6';
        $toast_anim_in = '-translate-x-full';
        break;
    case 'bottom-center':
        $toast_position_class = 'bottom-6 left-1/2 -translate-x-1/2';
        $toast_anim_in = 'translate-y-full';
        break;
}

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
            </div>

            <div class="p-6 sm:p-8">
                <!-- Premium Info Box -->
                <div
                    class="bg-brand-50 border border-brand-100 rounded-xl p-3 mb-4 flex items-center justify-center text-left max-w-sm mx-auto shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0 mr-3">
                        <i data-lucide="mail" class="w-4 h-4 text-brand-600"></i>
                    </div>
                    <p class="text-sm text-brand-800 leading-tight">
                        We sent a Two-Factor Authentication <strong class="font-bold text-brand-900">6-digit
                            code</strong>
                        to your email
                        <strong
                            class="font-bold text-brand-900"><?= htmlspecialchars(substr($user['email'], 0, 3) . '***@' . explode('@', $user['email'])[1]) ?></strong>
                    </p>
                </div>

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
                    <p class="text-sm text-gray-500 font-medium">Code expires in <span id="timerDisplay"
                            class="text-brand-600 font-bold tabular-nums ml-1">00:00</span></p>
                </div>

                <form action="" method="post" id="verifyForm">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="code" id="actualCode">

                    <div class="mb-5">
                        <div class="flex justify-center gap-2 sm:gap-3" id="otpContainer">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autofocus autocomplete="off">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autocomplete="off">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autocomplete="off">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autocomplete="off">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autocomplete="off">
                            <input type="text" maxlength="1"
                                class="w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-bold bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white outline-none transition-all shadow-sm"
                                autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" id="verifyBtn"
                        class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center group mb-5">
                        <span id="btnText" class="flex items-center">
                            Verify & Login
                            <i data-lucide="arrow-right"
                                class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span id="btnLoader" class="hidden flex items-center">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        </span>
                    </button>
                </form>

                <div id="resendWrapper" class="hidden opacity-0 translate-y-4 transition-all duration-500 ease-out">
                    <p class="text-center text-sm text-gray-600 flex flex-col items-center justify-center gap-2">
                        <span>Didn't receive the code?</span>
                        <button type="button" id="resendBtn"
                            class="inline-flex items-center justify-center px-4 py-2 bg-brand-50 border border-brand-100 shadow-sm text-brand-700 rounded-lg hover:bg-brand-100 hover:text-brand-800 font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-300 disabled:opacity-50 disabled:cursor-not-allowed group">
                            <i data-lucide="refresh-cw" id="resendIcon"
                                class="w-4 h-4 mr-2 text-brand-600 group-hover:rotate-180 transition-transform duration-500"></i>
                            <span id="resendText">Resend Code</span>
                        </button>
                    </p>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    let timeLeft = <?= $remaining_seconds ?>;
                    const timerDisplay = document.getElementById('timerDisplay');
                    const resendBtn = document.getElementById('resendBtn');
                    const resendIcon = document.getElementById('resendIcon');
                    const resendWrapper = document.getElementById('resendWrapper');
                    let timerInterval;

                    function updateTimer() {
                        if (timeLeft <= 0) {
                            timerDisplay.textContent = "00:00";
                            timerDisplay.classList.remove('text-brand-600');
                            timerDisplay.classList.add('text-red-500');
                            
                            if (resendWrapper.classList.contains('hidden')) {
                                resendWrapper.classList.remove('hidden');
                                // Force a reflow so the transition applies
                                void resendWrapper.offsetWidth;
                                resendWrapper.classList.remove('opacity-0', 'translate-y-4');
                                resendWrapper.classList.add('opacity-100', 'translate-y-0');
                            }
                            return;
                        }

                        if (!resendWrapper.classList.contains('hidden') && resendWrapper.classList.contains('opacity-100')) {
                            resendWrapper.classList.remove('opacity-100', 'translate-y-0');
                            resendWrapper.classList.add('opacity-0', 'translate-y-4');
                            setTimeout(() => {
                                if (timeLeft > 0) resendWrapper.classList.add('hidden');
                            }, 500);
                        }
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        timerDisplay.textContent =
                            (minutes < 10 ? '0' : '') + minutes + ':' +
                            (seconds < 10 ? '0' : '') + seconds;
                        timeLeft--;
                        timerInterval = setTimeout(updateTimer, 1000);
                    }

                    updateTimer();

                    resendBtn.addEventListener('click', function () {
                        resendBtn.setAttribute('disabled', 'true');
                        const svgIcon = resendBtn.querySelector('svg');
                        if (svgIcon) svgIcon.classList.add('animate-spin');
                        document.getElementById('resendText').innerText = "Sending...";

                        fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=resend&ajax=1'
                        })
                            .then(res => {
                                if (!res.ok) throw new Error('Network response was not ok');
                                return res.json();
                            })
                            .then(data => {
                                resendBtn.removeAttribute('disabled');
                                if (svgIcon) svgIcon.classList.remove('animate-spin');
                                document.getElementById('resendText').innerText = "Resend Code";
                                if (data.status === 'success') {
                                    timeLeft = data.remaining_seconds;
                                    timerDisplay.classList.add('text-brand-600');
                                    timerDisplay.classList.remove('text-red-500');
                                    clearTimeout(timerInterval);
                                    updateTimer();

                                    // Dynamic Toast
                                    const existingToast = document.getElementById('ajaxToast');
                                    if (existingToast) existingToast.remove();

                                    const toastHTML = `
                                <div id="ajaxToast" class="fixed <?= $toast_position_class ?> z-50 transform <?= $toast_anim_in ?> opacity-0">
                                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 min-w-[320px] max-w-sm flex items-start space-x-4 overflow-hidden relative">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                                                <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-sm font-semibold text-gray-900">Success</h3>
                                            <p class="text-sm text-gray-500 mt-1">${data.message}</p>
                                        </div>
                                        <button onclick="this.closest('#ajaxToast').remove()" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none flex-shrink-0">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                        <div class="absolute bottom-0 left-0 h-1 bg-gray-100 w-full">
                                            <div class="h-full bg-green-500" style="width: 100%; transition: all 3000ms linear;"></div>
                                        </div>
                                    </div>
                                </div>
                            `;
                                    document.body.insertAdjacentHTML('beforeend', toastHTML);
                                    lucide.createIcons();

                                    const toast = document.getElementById('ajaxToast');
                                    const progress = toast.querySelector('.bg-green-500');

                                    // Force reflow
                                    void toast.offsetWidth;

                                    setTimeout(() => {
                                        toast.classList.add('transition-all', 'duration-300');
                                        toast.classList.remove('<?= $toast_anim_in ?>', 'opacity-0');
                                        progress.style.width = '0%';
                                    }, 10);

                                    setTimeout(() => {
                                        toast.classList.add('<?= $toast_anim_in ?>', 'opacity-0');
                                        setTimeout(() => toast.remove(), 300);
                                    }, 3000);
                                }
                            })
                            .catch(err => {
                                console.error('Fetch error:', err);
                                resendBtn.removeAttribute('disabled');
                                const svgIcon = resendBtn.querySelector('svg');
                                if (svgIcon) svgIcon.classList.remove('animate-spin');
                                document.getElementById('resendText').innerText = "Resend Code";
                                
                                // Show an error toast
                                const existingToast = document.getElementById('ajaxToast');
                                if (existingToast) existingToast.remove();
                                const errorToastHTML = `
                                <div id="ajaxToast" class="fixed <?= $toast_position_class ?> z-50 transition-all duration-300 opacity-100">
                                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 min-w-[320px] max-w-sm flex items-start space-x-4 overflow-hidden relative">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                                                <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-sm font-semibold text-gray-900">Error</h3>
                                            <p class="text-sm text-gray-500 mt-1">Failed to resend the verification code. Please check your mail settings.</p>
                                        </div>
                                        <button onclick="this.closest('#ajaxToast').remove()" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none flex-shrink-0">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                        <div class="absolute bottom-0 left-0 h-1 bg-gray-100 w-full">
                                            <div class="h-full bg-red-500" style="width: 0%; transition: all 3000ms linear;"></div>
                                        </div>
                                    </div>
                                </div>`;
                                document.body.insertAdjacentHTML('beforeend', errorToastHTML);
                                lucide.createIcons();
                                const toast = document.getElementById('ajaxToast');
                                const progress = toast.querySelector('.bg-red-500');
                                void toast.offsetWidth;
                                progress.style.width = '100%';
                                setTimeout(() => {
                                    toast.classList.add('opacity-0');
                                    setTimeout(() => toast.remove(), 300);
                                }, 3000);
                            });
                    });

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
                        input.addEventListener('input', function (e) {
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
                        input.addEventListener('keydown', function (e) {
                            if (e.key === 'Backspace' && this.value === '') {
                                // Move to previous input
                                if (index > 0) {
                                    inputs[index - 1].focus();
                                }
                            }
                        });

                        // Handle Paste
                        input.addEventListener('paste', function (e) {
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
                                if (chars.length === 6) {
                                    inputs[5].focus();
                                } else {
                                    inputs[focusIndex].focus();
                                }
                                updateHiddenInput();
                            }
                        });
                    });

                    // Form submit loading state
                    verifyForm.addEventListener('submit', function (e) {
                        updateHiddenInput();
                        if (hiddenInput.value.length === 6) {
                            document.getElementById('btnText').classList.add('hidden');
                            document.getElementById('btnLoader').classList.remove('hidden');
                            document.getElementById('verifyBtn').classList.add('opacity-80', 'cursor-not-allowed');
                        } else {
                            e.preventDefault();

                            // Show Error Toast
                            const existingToast = document.getElementById('ajaxToast');
                            if (existingToast) existingToast.remove();

                            const toastHTML = `
                            <div id="ajaxToast" class="fixed <?= $toast_position_class ?> z-50 transform <?= $toast_anim_in ?> opacity-0">
                                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 min-w-[320px] max-w-sm flex items-start space-x-4 overflow-hidden relative">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-gray-900">Validation Error</h3>
                                        <p class="text-sm text-gray-500 mt-1">Please enter the full 6-digit code.</p>
                                    </div>
                                    <button onclick="this.closest('#ajaxToast').remove()" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none flex-shrink-0">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                    <div class="absolute bottom-0 left-0 h-1 bg-gray-100 w-full">
                                        <div class="h-full bg-red-500" style="width: 100%; transition: all 3000ms linear;"></div>
                                    </div>
                                </div>
                            </div>
                        `;
                            document.body.insertAdjacentHTML('beforeend', toastHTML);
                            lucide.createIcons();

                            const toast = document.getElementById('ajaxToast');
                            const progress = toast.querySelector('.bg-red-500');
                            
                            // Force reflow
                            void toast.offsetWidth;

                            setTimeout(() => {
                                toast.classList.add('transition-all', 'duration-300');
                                toast.classList.remove('<?= $toast_anim_in ?>', 'opacity-0');
                                progress.style.width = '0%';
                            }, 10);

                            setTimeout(() => {
                                toast.classList.add('<?= $toast_anim_in ?>', 'opacity-0');
                                setTimeout(() => toast.remove(), 300);
                            }, 3000);

                            // Shake animation or focus first empty
                            for (let i = 0; i < inputs.length; i++) {
                                if (!inputs[i].value) {
                                    inputs[i].focus();
                                    break;
                                }
                            }
                        }
                    });

                    // Reset button state on page restore (BFCache)
                    window.addEventListener('pageshow', function (e) {
                        if (e.persisted) {
                            document.getElementById('btnText').classList.remove('hidden');
                            document.getElementById('btnLoader').classList.add('hidden');
                            document.getElementById('verifyBtn').classList.remove('opacity-80', 'cursor-not-allowed');
                        }
                    });
                });
            </script>

            <div class="bg-gray-50 px-6 py-4 text-center border-t border-gray-100 text-sm">
                <a href="<?= BASE_URL ?>/auth/login.php"
                    class="text-gray-500 hover:text-gray-700 flex items-center justify-center transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>