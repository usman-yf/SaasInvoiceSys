<?php
// settings.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth();

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    require_once __DIR__ . '/includes/header.php';
    die("<div class='container mt-5'><div class='alert alert-danger'>Access Denied. Admins only.</div></div>");
}

$success = '';

// Handle PHP Mailer config update separately
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mail_settings_update'])) {
    $mail_host = $_POST['mail_host'] ?? '';
    $mail_port = $_POST['mail_port'] ?? 587;
    $mail_encryption = $_POST['mail_encryption'] ?? 'tls';
    $mail_username = $_POST['mail_username'] ?? '';
    $mail_password = $_POST['mail_password'] ?? '';
    $mail_from_address = $_POST['mail_from_address'] ?? '';
    $mail_from_name = $_POST['mail_from_name'] ?? '';

    $mail_config_content = "<?php\n" .
        "// config/mail.php\n\n" .
        "// Mail server configuration for PHPMailer (SMTP)\n" .
        "define('MAIL_HOST', '" . addslashes($mail_host) . "');\n" .
        "define('MAIL_PORT', " . (int)$mail_port . ");\n" .
        "define('MAIL_ENCRYPTION', '" . addslashes($mail_encryption) . "');\n" .
        "define('MAIL_USERNAME', '" . addslashes($mail_username) . "');\n" .
        "define('MAIL_PASSWORD', '" . addslashes($mail_password) . "');\n\n" .
        "define('MAIL_FROM_ADDRESS', '" . addslashes($mail_from_address) . "');\n" .
        "define('MAIL_FROM_NAME', '" . addslashes($mail_from_name) . "');\n" .
        "?>";

    file_put_contents(__DIR__ . '/config/mail.php', $mail_config_content);
    
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'Settings Updated', 'Updated PHP Mailer Configuration.');
    }
    redirect(BASE_URL . '/settings.php?msg=updated');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['mail_settings_update'])) {
    $settings_to_update = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'email_verify_expire_minutes',
        'currency',
        'toast_position',
        'toast_duration',
        'enable_preloader',
        'preloader_duration',
        'enable_remember_me',
        'remember_me_days',
        'enable_2fa',
        '2fa_expiration_minutes'
    ];

    $defaults = [
        'company_name' => 'InvSys',
        'company_email' => 'contact@mycompany.com',
        'company_phone' => '+9230011111111',
        'company_address' => '123 Business Rd, Tech City, USA',
        'email_verify_expire_minutes' => '1440',
        'currency' => 'Rs',
        'toast_position' => 'top-right',
        'toast_duration' => '3000',
        'enable_preloader' => 'yes',
        'preloader_duration' => '500',
        'enable_remember_me' => 'yes',
        'remember_me_days' => '30',
        'enable_2fa' => 'no',
        '2fa_expiration_minutes' => '10'
    ];

    $changed_settings = [];

    foreach ($settings_to_update as $key) {
        if (isset($_POST[$key])) {
            $default_val = isset($defaults[$key]) ? $defaults[$key] : '';
            $old_value = get_setting($conn, $key, $default_val);
            $new_value = $_POST[$key];

            if ((string) $old_value !== (string) $new_value) {
                $friendly_key = ucwords(str_replace('_', ' ', $key));
                $changed_settings[] = $friendly_key;
            }
            set_setting($conn, $key, $new_value);
        }
    }

    if (isset($_SESSION['user_id'])) {
        if (!empty($changed_settings)) {
            $details = "Updated settings: " . implode(', ', $changed_settings) . ".";
            logActivity($conn, $_SESSION['user_id'], 'Settings Updated', $details);
        } else {
            logActivity($conn, $_SESSION['user_id'], 'Settings Updated', "Saved settings (no values changed).");
        }
    }

    redirect(BASE_URL . '/settings.php?msg=updated');
}

// Fetch current values
$company_name = get_setting($conn, 'company_name', 'InvSys');
$company_email = get_setting($conn, 'company_email', 'contact@mycompany.com');
$company_phone = get_setting($conn, 'company_phone', '+9230011111111');
$company_address = get_setting($conn, 'company_address', '123 Business Rd, Tech City, USA');
// Settings fetched here
$email_verify_expire_minutes = get_setting($conn, 'email_verify_expire_minutes', '1440');
$currency = get_setting($conn, 'currency', 'Rs');
$toast_position = get_setting($conn, 'toast_position', 'top-right');
$toast_duration = get_setting($conn, 'toast_duration', '3000');
$enable_preloader = get_setting($conn, 'enable_preloader', 'yes');
$preloader_duration = get_setting($conn, 'preloader_duration', '500');
$enable_remember_me = get_setting($conn, 'enable_remember_me', 'yes');
$remember_me_days = get_setting($conn, 'remember_me_days', '30');
$enable_2fa = get_setting($conn, 'enable_2fa', 'no');
$two_fa_expiration_minutes = get_setting($conn, '2fa_expiration_minutes', '10');

// Require mail config to read constants
require_once __DIR__ . '/config/mail.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="settings-2" class="w-6 h-6 mr-3 text-brand-600"></i>
            Application Settings
        </h1>
        <p class="text-gray-500 mt-1">Manage global system configuration and preferences</p>
    </div>
</div>

<!-- Full Width Company Details -->
<div class="mb-8">
    <!-- Company Details Card -->
    <div class="w-full">
        <form action="" method="post">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100">
                    <h3 class="text-lg font-bold flex items-center">
                        <i data-lucide="building-2" class="w-5 h-5 mr-2 text-brand-600"></i> Company Details
                    </h3>
                </div>

                <div class="p-6 flex-grow">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Company Name <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The official registered name of your company."></i> <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="company_name"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_name) ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Company Email <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The primary contact email for your business."></i> <span
                                    class="text-red-500">*</span></label>
                            <input type="email" name="company_email"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_email) ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Company Phone <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The main contact phone number."></i> <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="company_phone"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_phone) ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Company Address <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Your business's physical or registered address."></i> <span
                                    class="text-red-500">*</span></label>
                            <textarea name="company_address"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                rows="3" required><?= htmlspecialchars($company_address) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button type="submit"
                        class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Company Info
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 items-start">
    <!-- Left Column -->
    <div class="space-y-8">
        <!-- Localization Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="globe" class="w-5 h-5 mr-2 text-brand-600"></i> Localization Settings
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Default Currency
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="The currency symbol used across the system for all values."></i>
                                </label>
                                <select name="currency"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <?php
                                    $currencies = [
                                        'Rs',
                                        '$ (USD)',
                                        '€ (EUR)',
                                        '£ (GBP)',
                                        '₹ (INR)',
                                        'PKR (PKR)',
                                        'A$ (AUD)',
                                        'C$ (CAD)',
                                        '¥ (JPY)',
                                        'CHF (CHF)',
                                        '¥ (CNY)',
                                        'kr (SEK)',
                                        'NZ$ (NZD)',
                                        'Mex$ (MXN)',
                                        'S$ (SGD)',
                                        'HK$ (HKD)',
                                        'kr (NOK)',
                                        '₩ (KRW)',
                                        '₺ (TRY)',
                                        '₽ (RUB)',
                                        'R (ZAR)',
                                        'R$ (BRL)',
                                        'RM (MYR)',
                                        '₱ (PHP)',
                                        '฿ (THB)',
                                        'Rp (IDR)',
                                        'د.إ (AED)',
                                        'SAR (SAR)',
                                        'BD (BHD)'
                                    ];
                                    foreach ($currencies as $curr):
                                        $val = explode(' ', $curr)[0]; // Use Rs, $, €, £, ₹, PKR as the actual stored value
                                        ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= $currency === $val ? 'selected' : '' ?>><?= $curr ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-2 text-sm text-gray-500">This symbol will be displayed globally on
                                    dashboards, invoices, and reports.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Localization
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <!-- Premium Toast Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="bell-ring" class="w-5 h-5 mr-2 text-brand-600"></i> Premium Toast Settings
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Toast Position
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="Where the toast notification appears on screen."></i>
                                </label>
                                <select name="toast_position"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="top-right" <?= $toast_position === 'top-right' ? 'selected' : '' ?>>Top
                                        Right</option>
                                    <option value="top-left" <?= $toast_position === 'top-left' ? 'selected' : '' ?>>Top
                                        Left</option>
                                    <option value="top-center" <?= $toast_position === 'top-center' ? 'selected' : '' ?>>
                                        Top Center</option>
                                    <option value="bottom-right" <?= $toast_position === 'bottom-right' ? 'selected' : '' ?>>Bottom Right</option>
                                    <option value="bottom-left" <?= $toast_position === 'bottom-left' ? 'selected' : '' ?>>
                                        Bottom Left</option>
                                    <option value="bottom-center" <?= $toast_position === 'bottom-center' ? 'selected' : '' ?>>Bottom Center</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Auto Dismiss Duration (ms)
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="How long the toast stays visible before disappearing (in milliseconds). Default 3000ms."></i>
                                </label>
                                <input type="number" name="toast_duration"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                    value="<?= htmlspecialchars($toast_duration) ?>" min="1000" max="10000" step="500"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Toast Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Premium Preloader Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="loader" class="w-5 h-5 mr-2 text-brand-600"></i> Premium Preloader Settings
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Show Premium Preloader
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="Display an animated loading overlay before page renders."></i>
                                </label>
                                <select name="enable_preloader"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="yes" <?= $enable_preloader === 'yes' ? 'selected' : '' ?>>Yes, show
                                        preloader</option>
                                    <option value="no" <?= $enable_preloader === 'no' ? 'selected' : '' ?>>No, load
                                        instantly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Minimum Preloader Duration (ms)
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="Minimum time the preloader stays on screen. Default is 500ms."></i>
                                </label>
                                <input type="number" name="preloader_duration"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                    value="<?= htmlspecialchars($preloader_duration) ?>" min="0" max="10000" step="100"
                                    required>
                                <p class="mt-2 text-sm text-gray-500">Wait at least this long before fading out.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Preloader Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-8">
        <!-- Security Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="shield" class="w-5 h-5 mr-2 text-brand-600"></i> Security Settings
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Email Verification Expiration (Minutes)
                                    <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400"
                                        title="How long until a verification link becomes invalid. Default is 1440 mins (24h)."></i>
                                </label>
                                <input type="number" name="email_verify_expire_minutes"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-red-500 focus:border-red-500 block p-3 transition-colors outline-none shadow-sm"
                                    value="<?= htmlspecialchars($email_verify_expire_minutes) ?>" min="5" max="10080"
                                    required>
                                <p class="mt-2 text-sm text-gray-500">How long until a verification link becomes
                                    invalid. Default is 1440 mins (24h).</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Security Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2FA Security Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="shield-alert" class="w-5 h-5 mr-2 text-brand-600"></i> 2-Factor Authentication
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Enable 2-Factor Authentication <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Require an email code when logging in."></i></label>
                                <select name="enable_2fa"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="no" <?= $enable_2fa === 'no' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="yes" <?= $enable_2fa === 'yes' ? 'selected' : '' ?>>Enabled (Email OTP)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    2FA Code Expiration (Minutes) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Please provide the 2fa code expiration."></i></label>
                                <input type="number" name="2fa_expiration_minutes"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                    value="<?= htmlspecialchars($two_fa_expiration_minutes) ?>" min="1" max="60"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end rounded-b-2xl">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save 2FA Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Remember Me Settings Card -->
        <div>
            <form action="" method="post">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full">
                    <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="log-in" class="w-5 h-5 mr-2 text-brand-600"></i> Remember Me Options
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Enable "Remember Me" Option <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Please provide the enable "remember me" option."></i></label>
                                <select name="enable_remember_me"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="yes" <?= $enable_remember_me === 'yes' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="no" <?= $enable_remember_me === 'no' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Remember Me Duration (Days) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="How long a user stays logged in."></i></label>
                                <input type="number" name="remember_me_days"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                    value="<?= htmlspecialchars($remember_me_days) ?>" min="1" max="365"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end rounded-b-2xl">
                        <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Remember Me
                        </button>
                    </div>
                </div>
            </form>
        </div>


    </div>
</div>

<!-- PHP Mailer Settings Full Width -->
<div class="mb-8">
    <div class="w-full">
        <form action="" method="post">
            <input type="hidden" name="mail_settings_update" value="1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="bg-brand-50 text-brand-700 px-6 py-4 border-b border-brand-100">
                    <h3 class="text-lg font-bold flex items-center">
                        <i data-lucide="mail" class="w-5 h-5 mr-2 text-brand-600"></i> PHP Mailer Configuration
                    </h3>
                </div>

                <div class="p-6 flex-grow">
                    <!-- User Guide Alert -->
                    <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start space-x-3">
                        <div class="shrink-0">
                            <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-blue-900 mb-1">Configuration Guide</h4>
                            <p class="text-xs text-blue-700 leading-relaxed">
                                To send emails securely, you need to configure your SMTP settings. If you are using <strong>Gmail</strong> or <strong>Google Workspace</strong>:
                                <ul class="list-disc list-inside mt-1 ml-1 space-y-0.5">
                                    <li>Set <strong>Host</strong> to <code class="bg-blue-100 px-1 py-0.5 rounded text-blue-800">smtp.gmail.com</code> and <strong>Port</strong> to <code class="bg-blue-100 px-1 py-0.5 rounded text-blue-800">587</code> with <strong>TLS</strong> encryption.</li>
                                    <li>For the password, do not use your regular login password. You must generate an <strong>App Password</strong> in your Google Account settings (requires 2-Step Verification to be enabled).</li>
                                </ul>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Mail Host <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The SMTP server address (e.g., smtp.gmail.com)."></i> <span class="text-red-500">*</span></label>
                            <input type="text" name="mail_host" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_HOST') ? MAIL_HOST : '') ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Mail Port <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The port used for SMTP (usually 587 or 465)."></i> <span class="text-red-500">*</span></label>
                            <input type="number" name="mail_port" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_PORT') ? MAIL_PORT : '587') ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Mail Encryption <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The encryption method (TLS or SSL)."></i></label>
                            <select name="mail_encryption" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                <option value="tls" <?= (defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION === 'tls') ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= (defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION === 'ssl') ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= (defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION === '') ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Mail Username <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A unique name used for logging in."></i> <span class="text-red-500">*</span></label>
                            <input type="text" name="mail_username" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_USERNAME') ? MAIL_USERNAME : '') ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Mail Password <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A strong, secure password for account access."></i> <span class="text-red-500">*</span></label>
                            <input type="password" name="mail_password" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '') ?>" required>
                        </div>
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">From Address <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The billing or physical address."></i> <span class="text-red-500">*</span></label>
                            <input type="email" name="mail_from_address" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : '') ?>" required>
                        </div>
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">From Name <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Please provide the from name."></i> <span class="text-red-500">*</span></label>
                            <input type="text" name="mail_from_name" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Mail Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
