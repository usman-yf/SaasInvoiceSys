<?php
// customers/edit.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($conn, $_POST['name']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);

    if (empty($name) || empty($email) || empty($phone)) {
        $error = "Name, Email and Phone Number are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^\+923[0-9]{9}$/', str_replace(' ', '', $phone))) {
        $error = "Invalid Pakistan phone number format. Must be +923XXXXXXXXX.";
    } else {
        // Simple check if email is taken by another customer
        $check = mysqli_query($conn, "SELECT id FROM customers WHERE email='$email' AND id!=$id");
        if(mysqli_num_rows($check) > 0) {
            $error = "Email already exists for another customer.";
        } else {
            $sql = "UPDATE customers SET name='$name', email='$email', phone='$phone', address='$address' WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                if (isset($_SESSION['user_id'])) {
                    logActivity($conn, $_SESSION['user_id'], 'Customer Updated', "Updated customer {$name}");
                }
                redirect('/inv/customers/list.php?msg=' . urlencode("Customer '{$name}' successfully updated."));
            } else {
                $error = "Error updating customer.";
            }
        }
    }
}

$res = mysqli_query($conn, "SELECT * FROM customers WHERE id=$id");
if(mysqli_num_rows($res) == 0) {
    require_once __DIR__ . '/../includes/header.php';
    echo "<div class='bg-red-50 text-red-600 p-4 rounded-xl mt-4'>Customer not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$row = mysqli_fetch_assoc($res);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="edit" class="w-6 h-6 mr-3 text-brand-600"></i>
            Edit Customer
        </h1>
        <p class="text-gray-500 mt-1">Update client details and contact info</p>
    </div>
    <div class="flex space-x-3">
        <a href="/inv/customers/list.php" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to List
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8 max-w-3xl">
    <form action="" method="post">
        <div class="p-6 sm:p-8">
            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                <i data-lucide="user" class="w-5 h-5 mr-2 text-brand-600"></i> Personal Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($row['name']) ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($row['email']) ?>" required>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="phone" class="w-full pl-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars(!empty($row['phone']) ? $row['phone'] : '+92') ?>" placeholder="+923001234567" pattern="^\+923[0-9]{9}$" title="Must start with +923 and be exactly 13 characters long" minlength="13" maxlength="13" required>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Format: +923XXXXXXXXX</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address / Company <span class="text-red-500">*</span></label>
                    <textarea name="address" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" rows="3" required><?= htmlspecialchars($row['address']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="px-6 sm:px-8 py-5 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                <i data-lucide="save" class="w-4 h-4 mr-2"></i> Update Customer
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
