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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    redirect('/inv/settings.php?msg=updated');
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
                <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700">
                    <h3 class="text-lg font-bold flex items-center">
                        <i data-lucide="building-2" class="w-5 h-5 mr-2 text-brand-200"></i> Company Details
                    </h3>
                </div>

                <div class="p-6 flex-grow">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="company_name"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_name) ?>" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Email <span
                                    class="text-red-500">*</span></label>
                            <input type="email" name="company_email"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_email) ?>" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="company_phone"
                                class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm"
                                value="<?= htmlspecialchars($company_phone) ?>" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Address <span
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="globe" class="w-5 h-5 mr-2 text-brand-200"></i> Localization Settings
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="bell-ring" class="w-5 h-5 mr-2 text-brand-200"></i> Premium Toast Settings
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="loader" class="w-5 h-5 mr-2 text-brand-200"></i> Premium Preloader Settings
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="shield" class="w-5 h-5 mr-2 text-brand-200"></i> Security Settings
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="shield-alert" class="w-5 h-5 mr-2 text-brand-200"></i> 2-Factor Authentication
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Enable 2-Factor Authentication
                                </label>
                                <select name="enable_2fa"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="no" <?= $enable_2fa === 'no' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="yes" <?= $enable_2fa === 'yes' ? 'selected' : '' ?>>Enabled (Email OTP)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    2FA Code Expiration (Minutes)
                                </label>
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
                    <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-2xl">
                        <h3 class="text-lg font-bold flex items-center">
                            <i data-lucide="log-in" class="w-5 h-5 mr-2 text-brand-200"></i> Remember Me Options
                        </h3>
                    </div>

                    <div class="p-6 flex-grow">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Enable "Remember Me" Option
                                </label>
                                <select name="enable_remember_me"
                                    class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm">
                                    <option value="yes" <?= $enable_remember_me === 'yes' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="no" <?= $enable_remember_me === 'no' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Remember Me Duration (Days)
                                </label>
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


<?php require_once __DIR__ . '/includes/footer.php'; ?>