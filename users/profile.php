<?php
// users/profile.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$id = $_SESSION['user_id'];
$sql = "SELECT id, full_name, email, phone, role FROM users WHERE id = $id";
$res = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($res);

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($conn, $_POST['full_name']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($full_name) || empty($email)) {
        $error = "Full Name and Email are required.";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $full_name)) {
        $error = "Full Name can only contain alphabetic characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($phone) && !preg_match('/^\+923[0-9]{9}$/', str_replace(' ', '', $phone))) {
        $error = "Invalid Pakistan phone number format. Must be +923XXXXXXXXX.";
    } elseif (!empty($password) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters long, using a mix of uppercase, lowercase, numbers, and symbols.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $id");
        if(mysqli_num_rows($check) > 0) {
            $error = "Email already in use.";
        } else {
            if(!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone', password='$hash' WHERE id=$id";
            } else {
                $update_sql = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone' WHERE id=$id";
            }
            
            if (mysqli_query($conn, $update_sql)) {
                $_SESSION['username'] = $full_name;
                $_SESSION['email'] = $email;
                $success = "Profile updated successfully.";
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['phone'] = $phone;
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="user-circle" class="w-6 h-6 mr-3 text-brand-600"></i>
            My Profile
        </h1>
        <p class="text-gray-500 mt-1">Manage your account settings and preferences</p>
    </div>
</div>

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <form method="POST" action="">
            <div class="p-6 sm:p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                    <i data-lucide="settings" class="w-5 h-5 mr-2 text-brand-600"></i> Update Settings
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Full Name <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The complete name of the user."></i> <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($user['full_name']) ?>" required autocomplete="new-password" pattern="^[A-Za-z\s]+$" title="Only alphabet characters allowed">
                    </div>
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Email <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The email address for communication and notifications."></i> <span class="text-red-500">*</span></label>
                        <input type="email" name="email" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required autocomplete="new-password">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Phone Number <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A contact number for this entity."></i></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="phone" class="w-full pl-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(!empty($user['phone']) ? $user['phone'] : '+92') ?>" placeholder="+923001234567" pattern="^\+923[0-9]{9}$" title="Must start with +923 and be exactly 13 characters long" minlength="13" maxlength="13">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Role <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The permission level assigned to this user."></i></label>
                        <input type="text" class="w-full bg-gray-50 border border-gray-200 text-gray-500 text-sm rounded-xl block p-3 shadow-sm font-medium cursor-not-allowed" value="<?= strtoupper($user['role']) ?>" disabled>
                        <p class="mt-2 text-xs text-gray-500">Administrator approval is required to modify account role.</p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">New Password (Leave blank to keep current) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A strong, secure password for account access."></i></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="password" id="passwordInput" name="password" class="w-full pl-10 pr-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" placeholder="Leave blank to keep current password" autocomplete="new-password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$" title="Minimum 8 characters, at least one uppercase letter, one lowercase letter, one number and one special character">
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-brand-600 focus:outline-none toggle-password">
                            <i data-lucide="eye" class="w-4 h-4 icon-show"></i>
                            <i data-lucide="eye-off" class="w-4 h-4 icon-hide hidden"></i>
                        </button>
                    </div>
                    
                    <div class="mt-3 bg-gray-50 p-4 rounded-xl border border-gray-100 hidden" id="passwordChecklist">
                        <ul class="space-y-2 text-xs">
                            <li id="req-length" class="flex items-center text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> At least 8 characters</li>
                            <li id="req-upper" class="flex items-center text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> One uppercase letter</li>
                            <li id="req-lower" class="flex items-center text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> One lowercase letter</li>
                            <li id="req-number" class="flex items-center text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> One number</li>
                            <li id="req-special" class="flex items-center text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> One special character</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="px-6 sm:px-8 py-5 border-t border-gray-100 bg-gray-50 flex justify-end">
                <button type="submit" id="submitBtn" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                    <i data-lucide="save" class="w-4 h-4 mr-2" id="submitIcon"></i> <span id="submitText">Save Profile</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const icon = document.getElementById('submitIcon');
            const text = document.getElementById('submitText');
            if (btn && icon && text) {
                btn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                icon.setAttribute('data-lucide', 'loader-2');
                icon.classList.add('animate-spin');
                text.innerText = 'Saving Profile...';
                lucide.createIcons();
            }
        });
    }

    const passwordInput = document.getElementById('passwordInput');
    const toggleBtn = document.querySelector('.toggle-password');
    const checklist = document.getElementById('passwordChecklist');
    
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const iconShow = toggleBtn.querySelector('.icon-show');
            const iconHide = toggleBtn.querySelector('.icon-hide');
            if (iconShow && iconHide) {
                if (type === 'password') {
                    iconShow.classList.remove('hidden');
                    iconHide.classList.add('hidden');
                } else {
                    iconShow.classList.add('hidden');
                    iconHide.classList.remove('hidden');
                }
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const pwd = this.value;
            if (pwd.length > 0) {
                checklist.classList.remove('hidden');
            } else {
                checklist.classList.add('hidden');
            }
            checkCondition('req-length', pwd.length >= 8);
            checkCondition('req-upper', /[A-Z]/.test(pwd));
            checkCondition('req-lower', /[a-z]/.test(pwd));
            checkCondition('req-number', /\d/.test(pwd));
            checkCondition('req-special', /[\W_]/.test(pwd));
        });
    }

    function checkCondition(id, valid) {
        const el = document.getElementById(id);
        if (!el) return;
        
        let text = el.innerText.trim();
        if (valid) {
            el.className = 'flex items-center text-green-600 transition-colors';
            el.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5 mr-1.5"></i> ' + text;
        } else {
            el.className = 'flex items-center text-red-500 transition-colors';
            el.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> ' + text;
        }
        lucide.createIcons();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
