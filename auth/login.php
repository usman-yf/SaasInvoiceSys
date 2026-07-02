<?php
// auth/login.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$error = '';
$enable_remember_me = get_setting($conn, 'enable_remember_me', 'yes');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            if ($row['email_verified'] == 0) {
                $error = "Please verify your email address before logging in.";
            } elseif ($row['admin_verified'] == 0) {
                $error = "Your account is pending administrator approval.";
            } else {
                $enable_2fa = get_setting($conn, 'enable_2fa', 'no');

                if ($enable_2fa === 'yes') {
                    // Start 2FA Flow
                    $code = sprintf("%06d", mt_rand(1, 999999));
                    $expire_mins = (int) get_setting($conn, '2fa_expiration_minutes', '10');
                    $expires = date('Y-m-d H:i:s', strtotime("+$expire_mins minutes"));

                    mysqli_query($conn, "UPDATE users SET two_factor_code = '$code', two_factor_expires = '$expires' WHERE id = " . $row['id']);
                    send_2fa_email($email, $code);

                    $_SESSION['pending_2fa_user'] = $row['id'];
                    $_SESSION['pending_remember_me'] = isset($_POST['remember_me']) ? true : false;
                    $_SESSION['pending_password'] = $password;

                    header("Location: " . BASE_URL . "/auth/2fa.php");
                    exit();
                } else {
                    // Normal Login Flow
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = isset($row['full_name']) ? $row['full_name'] : $row['username'];
                    $_SESSION['role'] = $row['role'];

                    if (isset($_POST['remember_me'])) {
                        $days = (int) get_setting($conn, 'remember_me_days', '30');
                        $token = bin2hex(random_bytes(32));
                        mysqli_query($conn, "UPDATE users SET remember_token = '$token' WHERE id = " . $row['id']);

                        setcookie('remember_token', $token, time() + (86400 * $days), "/");
                        setcookie('remember_email', $email, time() + (86400 * $days), "/");
                        setcookie('remember_password', encrypt_data($password), time() + (86400 * $days), "/");
                    } else {
                        setcookie('remember_email', '', time() - 3600, "/");
                        setcookie('remember_password', '', time() - 3600, "/");
                    }

                    logActivity($conn, $row['id'], 'User Login', 'User successfully authenticated via login page');

                    header("Location: " . BASE_URL . "/index.php?msg=Welcome+back");
                    exit();
                }
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center justify-center min-h-screen w-full bg-gray-50">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-brand-700 text-white p-6 text-center border-b border-brand-800">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 mb-4">
                    <i data-lucide="lock" class="w-6 h-6 text-white"></i>
                </div>
                <h4 class="text-xl font-bold">Admin Login</h4>
                <p class="text-brand-100 text-sm mt-1">Sign in to manage your system</p>
            </div>

            <div class="p-6 sm:p-8">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-start text-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-2 shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-5">
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Email Address <i
                                data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                title="The email address for communication and notifications."></i></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input type="email" name="email"
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block transition-colors outline-none shadow-sm"
                                value="<?= isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '' ?>"
                                required <?= isset($_COOKIE['remember_email']) ? '' : 'autofocus' ?>>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Password <i
                                data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                title="A strong, secure password for account access."></i></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="key" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <?php
                            $remembered_password = '';
                            if (isset($_COOKIE['remember_password'])) {
                                $decrypted = decrypt_data($_COOKIE['remember_password']);
                                if ($decrypted !== false) {
                                    $remembered_password = $decrypted;
                                }
                            }
                            ?>
                            <input type="password" id="passwordInput" name="password"
                                class="w-full pl-10 pr-10 py-3 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block transition-colors outline-none"
                                value="<?= htmlspecialchars($remembered_password) ?>" required>
                            <button type="button"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-brand-600 focus:outline-none toggle-password">
                                <i data-lucide="eye" class="w-5 h-5" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($enable_remember_me === 'yes'): ?>
                        <div class="mb-6 flex items-center justify-between">
                            <label class="flex items-center cursor-pointer group">
                                <div class="relative flex items-center justify-center w-5 h-5 mr-3">
                                    <input type="checkbox" name="remember_me" class="peer sr-only"
                                        <?= isset($_COOKIE['remember_email']) ? 'checked' : '' ?>>
                                    <div
                                        class="w-5 h-5 bg-gray-50 border-2 border-gray-300 rounded-md peer-checked:bg-brand-600 peer-checked:border-brand-600 transition-all duration-200 shadow-sm">
                                    </div>
                                    <i data-lucide="check"
                                        class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200 pointer-events-none"></i>
                                </div><span
                                    class="text-sm font-medium text-gray-600 group-hover:text-gray-900 transition-colors">Remember
                                    Me </span> <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                    title="Please provide the remember me."></i>
                            </label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="loginBtn"
                        class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center group">
                        <span id="btnText" class="flex items-center">
                            Sign In
                            <i data-lucide="arrow-right"
                                class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span id="btnLoader" class="hidden flex items-center">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        </span>
                    </button>
                </form>
            </div>

            <script>
                document.querySelector('form').addEventListener('submit', function (e) {
                    if (this.checkValidity()) {
                        document.getElementById('btnText').classList.add('hidden');
                        document.getElementById('btnLoader').classList.remove('hidden');
                        document.getElementById('loginBtn').classList.add('opacity-80', 'cursor-not-allowed');
                    }
                });

                // Reset button state on page restore (BFCache)
                window.addEventListener('pageshow', function (e) {
                    if (e.persisted) {
                        document.getElementById('btnText').classList.remove('hidden');
                        document.getElementById('btnLoader').classList.add('hidden');
                        document.getElementById('loginBtn').classList.remove('opacity-80', 'cursor-not-allowed');
                    }
                });
            </script>

            <div class="bg-gray-50 px-6 py-4 text-center border-t border-gray-100 text-sm text-gray-500">
                Secure access for authorized personnel only.
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('passwordInput');
        const toggleBtn = document.querySelector('.toggle-password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Re-render the icon completely for Lucide
                const iconHtml = type === 'password'
                    ? '<i data-lucide="eye" class="w-5 h-5"></i>'
                    : '<i data-lucide="eye-off" class="w-5 h-5"></i>';
                toggleBtn.innerHTML = iconHtml;

                lucide.createIcons();
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>