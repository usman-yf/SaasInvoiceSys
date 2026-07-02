<?php
// products/edit.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($conn, $_POST['name']);
    $sku = sanitize($conn, $_POST['sku']);
    $price = (float)$_POST['price'];
    $tax = (float)$_POST['tax'];

    if(empty($name)) {
        $error = "Name is required.";
    } else {
        $sql = "UPDATE products SET name='$name', sku='$sku', price=$price, tax=$tax WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            if (isset($_SESSION['user_id'])) {
                logActivity($conn, $_SESSION['user_id'], 'Product Updated', "Updated product {$name}");
            }
            redirect(BASE_URL . '/products/list.php?msg=' . urlencode("Product '{$name}' successfully updated."));
        } else {
            $error = "Error updating product.";
        }
    }
}

$res = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
if(mysqli_num_rows($res) == 0) {
    require_once __DIR__ . '/../includes/header.php';
    echo "<div class='bg-red-50 text-red-600 p-4 rounded-xl mt-4'>Product not found.</div>";
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
            Edit Product
        </h1>
        <p class="text-gray-500 mt-1">Update product or service details</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= BASE_URL ?>/products/list.php" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to List
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8 max-w-3xl">
    <form action="" method="post">
        <div class="p-6 sm:p-8">
            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                <i data-lucide="box" class="w-5 h-5 mr-2 text-brand-600"></i> Product Details
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Product Name <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A clear, descriptive name for what you're selling."></i> <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($row['name']) ?>" required>
                </div>
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">SKU (Stock Keeping Unit) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Stock Keeping Unit, a unique identifier for this item."></i></label>
                    <input type="text" name="sku" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm font-mono placeholder-gray-400" value="<?= htmlspecialchars($row['sku']) ?>">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Price (<?= htmlspecialchars($global_currency) ?>) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The base selling price before any taxes."></i> <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($global_currency) ?></span>
                        </div>
                        <input type="number" step="0.01" name="price" class="w-full pl-9 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= $row['price'] ?>" required>
                    </div>
                </div>
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Tax Rate (%) <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The percentage of tax applicable to this item."></i></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i data-lucide="percent" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="number" step="0.01" name="tax" class="w-full pr-9 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= $row['tax'] ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-6 sm:px-8 py-5 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center">
                <i data-lucide="save" class="w-4 h-4 mr-2"></i> Update Product
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
