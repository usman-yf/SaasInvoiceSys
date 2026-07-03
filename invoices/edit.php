<?php
// invoices/edit.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Fetch invoice
$sql = "SELECT * FROM invoices WHERE id = $id";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) {
    require_once __DIR__ . '/../includes/header.php';
    die("<div class='container mt-5'><div class='alert alert-danger'>Invoice not found.</div></div>");
}
$invoice = mysqli_fetch_assoc($res);

if ($invoice['status'] !== 'Unpaid') {
    require_once __DIR__ . '/../includes/header.php';
    die("<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-lock'></i> Only unpaid invoices can be edited. This invoice has payment records.</div></div>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = (int) $_POST['customer_id'];
    $date = sanitize($conn, $_POST['date']);
    $discount = (float) $_POST['discount'];

    $subtotal = (float) $_POST['subtotal'];
    $tax_total = (float) $_POST['tax_total'];
    $total = (float) $_POST['total'];

    if ($customer_id <= 0) {
        $error = "Please select a customer.";
    } elseif (empty($_POST['product_id'])) {
        $error = "Please add at least one line item.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Update Invoice Summary
            $update_sql = "UPDATE invoices 
                           SET customer_id=$customer_id, date='$date', subtotal=$subtotal, tax=$tax_total, discount=$discount, total=$total 
                           WHERE id=$id";
            mysqli_query($conn, $update_sql);

            // Wipe old items securely
            mysqli_query($conn, "DELETE FROM invoice_items WHERE invoice_id=$id");

            // Insert new Items
            $product_ids = $_POST['product_id'];
            $prices = $_POST['price'];
            $quantities = $_POST['quantity'];
            $item_taxes = $_POST['item_tax'];

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = (int) $product_ids[$i];
                $price = (float) $prices[$i];
                $qty = (float) $quantities[$i];
                $item_total = ($price * $qty) + (($price * $qty) * ($item_taxes[$i] / 100));

                if ($pid > 0 && $qty > 0) {
                    $item_sql = "INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total) 
                                 VALUES ($id, $pid, $qty, $price, $item_total)";
                    mysqli_query($conn, $item_sql);
                }
            }

            mysqli_commit($conn);
            if (isset($_SESSION['user_id'])) {
                logActivity($conn, $_SESSION['user_id'], 'Invoice Updated', "Updated invoice {$invoice['invoice_no']} with new total {$global_currency} " . number_format($total, 2));
            }
            redirect(BASE_URL . '/invoices/list.php?msg=' . urlencode("Invoice '{$invoice['invoice_no']}' successfully updated."));
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to update invoice: " . $e->getMessage();
        }
    }
}

// Prepare products for JS addition dynamically
$products_options = "";
$pres = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");
$all_products = [];
while ($p = mysqli_fetch_assoc($pres)) {
    $products_options .= "<option value='{$p['id']}'>" . htmlspecialchars(addslashes($p['name'])) . "</option>";
    $all_products[] = $p;
}

// Fetch existing items for rendering
$items_sql = "SELECT * FROM invoice_items WHERE invoice_id=$id";
$items_res = mysqli_query($conn, $items_sql);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="edit" class="w-6 h-6 mr-3 text-brand-600"></i>
            Edit Invoice <?= htmlspecialchars($invoice['invoice_no']) ?>
        </h1>
        <p class="text-gray-500 mt-1">Update invoice details and items</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= BASE_URL ?>/invoices/view.php?id=<?= $id ?>" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Cancel
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <form action="" method="post" id="invoiceForm">
        <div class="p-6 sm:p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Invoice Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Customer <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The customer this invoice belongs to."></i> <span class="text-red-500">*</span></label>
                    <select name="customer_id" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" required>
                        <option value="">Select Customer...</option>
                        <?php
                        $cres = mysqli_query($conn, "SELECT * FROM customers ORDER BY name ASC");
                        $customersData = [];
                        while ($c = mysqli_fetch_assoc($cres)) {
                            $customersData[$c['id']] = $c;
                            $sel = ($c['id'] == $invoice['customer_id']) ? 'selected' : '';
                            echo "<option value='{$c['id']}' $sel>" . htmlspecialchars($c['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Date <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Please provide the date."></i> <span class="text-red-500">*</span></label>
                    <input type="date" name="date" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= htmlspecialchars($invoice['date']) ?>" required>
                </div>
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Invoice Number <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="A unique identifier for this invoice."></i></label>
                    <input type="text" class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl block p-3 shadow-sm cursor-not-allowed" value="<?= htmlspecialchars($invoice['invoice_no']) ?>" readonly disabled>
                    <p class="mt-1 text-xs text-gray-500">Cannot be changed</p>
                </div>
            </div>
            
            <!-- Customer Details Card (Animated) -->
            <div id="customerDetailsWrapper" class="max-h-0 opacity-0 overflow-hidden transition-all duration-500 ease-in-out mt-0">
                <div class="bg-gradient-to-r from-gray-50 to-white rounded-2xl border border-brand-200 p-4 shadow-sm mt-4">
                    <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                        <i data-lucide="user-check" class="w-5 h-5 mr-2 text-brand-500"></i> Selected Customer Details
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email</span>
                            <span id="cd_email" class="font-semibold text-gray-800 truncate block"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Phone Number</span>
                            <span id="cd_phone" class="font-semibold text-gray-800 truncate block"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">VAT Number</span>
                            <span id="cd_vat" class="font-semibold text-gray-800 truncate block"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">CR Number</span>
                            <span id="cd_cr" class="font-semibold text-gray-800 truncate block"></span>
                        </div>
                        <div class="md:col-span-4 pt-3 border-t border-brand-100">
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Address & Location</span>
                            <span id="cd_address" class="font-medium text-gray-600 leading-relaxed"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                const customersData = <?= json_encode($customersData ?? []) ?>;
                document.addEventListener('DOMContentLoaded', function() {
                    const customerSelect = document.querySelector('select[name="customer_id"]');
                    const wrapper = document.getElementById('customerDetailsWrapper');
                    
                    function updateCustomerDetails() {
                        const id = customerSelect.value;
                        if (id && customersData[id]) {
                            const c = customersData[id];
                            document.getElementById('cd_email').textContent = c.email || 'N/A';
                            document.getElementById('cd_phone').textContent = c.phone || 'N/A';
                            document.getElementById('cd_vat').textContent = c.vat_number || 'N/A';
                            document.getElementById('cd_cr').textContent = c.cr_number || 'N/A';
                            
                            // Build address
                            let addrParts = [];
                            if(c.building_no) addrParts.push(c.building_no);
                            if(c.street) addrParts.push(c.street);
                            if(c.district) addrParts.push(c.district);
                            if(c.city) addrParts.push(c.city);
                            if(c.postal_code) addrParts.push(c.postal_code);
                            if(c.country) addrParts.push(c.country);
                            
                            document.getElementById('cd_address').textContent = addrParts.length > 0 ? addrParts.join(', ') : (c.address || 'N/A');
                            
                            // Show wrapper
                            wrapper.classList.remove('max-h-0', 'opacity-0', 'mt-0');
                            wrapper.classList.add('max-h-[500px]', 'opacity-100');
                        } else {
                            // Hide wrapper
                            wrapper.classList.add('max-h-0', 'opacity-0', 'mt-0');
                            wrapper.classList.remove('max-h-[500px]', 'opacity-100');
                        }
                    }
                    
                    customerSelect.addEventListener('change', updateCustomerDetails);
                    // Initial check
                    updateCustomerDetails();
                });
            </script>
        </div>

        <!-- Invoice Items -->
        <div class="p-6 sm:p-8">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Line Items</h3>
            <div class="overflow-visible rounded-xl border border-gray-200 mb-4">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium w-1/3">Product / Service</th>
                            <th scope="col" class="px-6 py-4 font-medium">Unit Price (<?= htmlspecialchars($global_currency) ?>)</th>
                            <th scope="col" class="px-6 py-4 font-medium">Qty</th>
                            <th scope="col" class="px-6 py-4 font-medium">Tax (%)</th>
                            <th scope="col" class="px-6 py-4 font-medium">Total (<?= htmlspecialchars($global_currency) ?>)</th>
                            <th scope="col" class="px-6 py-4 font-medium text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItems" class="divide-y divide-gray-100">
                        <?php while($item = mysqli_fetch_assoc($items_res)): ?>
                        <?php
                            $base_val = $item['price'] * $item['quantity'];
                            $tax_diff = $item['total'] - $base_val;
                            $tax_rate = ($base_val > 0) ? round(($tax_diff / $base_val) * 100, 2) : 0;
                        ?>
                        <tr class="hover:bg-gray-50/50 transition-colors" data-amount="<?= $base_val ?>" data-tax="<?= $tax_diff ?>">
                            <td class="p-4">
                                <select name="product_id[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 product-select transition-colors outline-none" required>
                                    <option value="">Select Product...</option>
                                    <?php foreach($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($p['id'] == $item['product_id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="p-4"><input type="number" step="0.01" name="price[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 price transition-colors outline-none" value="<?= htmlspecialchars($item['price']) ?>" required></td>
                            <td class="p-4"><input type="number" step="1" name="quantity[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 qty transition-colors outline-none" value="<?= htmlspecialchars($item['quantity']) ?>" required></td>
                            <td class="p-4"><input type="number" step="0.01" name="item_tax[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 tax transition-colors outline-none" value="<?= htmlspecialchars($tax_rate) ?>"></td>
                            <td class="p-4"><input type="text" name="item_total[]" class="w-full bg-gray-100 border border-gray-200 text-gray-900 text-sm rounded-xl block p-2.5 total" value="<?= htmlspecialchars($item['total']) ?>" readonly></td>
                            <td class="p-4 text-center">
                                <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors removeRow">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <button type="button" id="addRow" class="text-brand-600 hover:text-brand-700 hover:bg-brand-50 font-medium py-2 px-4 rounded-xl transition-colors flex items-center mb-8 border border-brand-200 border-dashed w-full justify-center bg-brand-50/30">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Another Item
            </button>

            <!-- Totals -->
            <div class="flex flex-col md:flex-row justify-between items-start border-t border-gray-100 pt-8">
                <div class="w-full md:w-1/2 mb-6 md:mb-0 pr-0 md:pr-8">
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">Notes or Terms <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="Additional information or payment instructions."></i></label>
                    <textarea class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none" rows="4" placeholder="Enter any notes for the customer..."></textarea>
                </div>
                
                <div class="w-full md:w-1/3 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($global_currency) ?> <span id="subtotal_view"><?= htmlspecialchars(number_format($invoice['subtotal'],2,'.','')) ?></span></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-500">Tax</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($global_currency) ?> <span id="tax_view"><?= htmlspecialchars(number_format($invoice['tax'],2,'.','')) ?></span></span>
                    </div>
                    <div class="flex justify-between items-center mb-6 pb-6 border-b border-gray-200">
                        <span class="text-gray-500 flex items-center">
                            Discount
                        </span>
                        <div class="w-32">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm"><?= htmlspecialchars($global_currency) ?></span>
                                </div>
                                <input type="number" step="0.01" name="discount" id="discount" class="w-full pl-8 bg-white border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2 text-right outline-none" value="<?= htmlspecialchars(number_format($invoice['discount'],2,'.','')) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total</span>
                        <span class="text-2xl font-bold text-brand-600"><?= htmlspecialchars($global_currency) ?> <span id="grand_total_view"><?= htmlspecialchars(number_format($invoice['total'],2,'.','')) ?></span></span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="subtotal" id="subtotal" value="<?= htmlspecialchars(number_format($invoice['subtotal'],2,'.','')) ?>">
            <input type="hidden" name="tax_total" id="tax_total" value="<?= htmlspecialchars(number_format($invoice['tax'],2,'.','')) ?>">
            <input type="hidden" name="total" id="total" value="<?= htmlspecialchars(number_format($invoice['total'],2,'.','')) ?>">

            <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center text-lg">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    window.productOptions = `<?= str_replace("\n", "", $products_options) ?>`;
    
    // Simulate initial calculation on load just to ensure JS state binds properly
    document.addEventListener("DOMContentLoaded", function() {
        if(typeof calculateTotals === 'function') {
            calculateTotals(); 
        } else {
            setTimeout(() => {
                if(typeof calculateTotals === 'function') calculateTotals();
            }, 300);
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
