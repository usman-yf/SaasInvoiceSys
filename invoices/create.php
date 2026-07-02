<?php
// invoices/create.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = (int) $_POST['customer_id'];
    $date = sanitize($conn, $_POST['date']);
    $invoice_no = generateInvoiceNo($conn); // Use function to auto-generate
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
            // Insert Invoice
            $token = bin2hex(random_bytes(20));
            $sql = "INSERT INTO invoices (invoice_no, customer_id, date, subtotal, tax, discount, total, status, token) 
                    VALUES ('$invoice_no', $customer_id, '$date', $subtotal, $tax_total, $discount, $total, 'Unpaid', '$token')";

            mysqli_query($conn, $sql);
            $invoice_id = mysqli_insert_id($conn);

            // Insert Items
            $product_ids = $_POST['product_id'];
            $prices = $_POST['price'];
            $quantities = $_POST['quantity'];
            $item_taxes = $_POST['item_tax'];

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = (int) $product_ids[$i];
                $price = (float) $prices[$i];
                $qty = (float) $quantities[$i];
                $item_total = ($price * $qty) + (($price * $qty) * ($item_taxes[$i] / 100)); // Total per row tax + amount

                if ($pid > 0 && $qty > 0) {
                    $item_sql = "INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total) 
                                 VALUES ($invoice_id, $pid, $qty, $price, $item_total)";
                    mysqli_query($conn, $item_sql);
                }
            }

            mysqli_commit($conn);
            if (isset($_SESSION['user_id'])) {
                logActivity($conn, $_SESSION['user_id'], 'Invoice Created', "Created invoice {$invoice_no} with total {$global_currency} " . number_format($total, 2));
            }
            redirect('/inv/invoices/list.php?msg=' . urlencode("Invoice '{$invoice_no}' successfully created."));
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to create invoice: " . $e->getMessage();
        }
    }
}

// Prepare products for JS addition
$products_options = "";
$pres = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");
while ($p = mysqli_fetch_assoc($pres)) {
    $products_options .= "<option value='{$p['id']}'>" . htmlspecialchars(addslashes($p['name'])) . "</option>";
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
        <p class="text-gray-500 mt-1">Draft a new invoice for your customer</p>
    </div>
    <div class="flex space-x-3">
        <a href="/inv/invoices/list.php" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to List
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <form action="" method="post" id="invoiceForm">
        <div class="p-6 sm:p-8 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Invoice Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer <span class="text-red-500">*</span></label>
                    <select name="customer_id" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" required>
                        <option value="">Select Customer...</option>
                        <?php
                        $cres = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
                        while ($c = mysqli_fetch_assoc($cres)) {
                            echo "<option value='{$c['id']}'>{$c['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
                    <input type="text" class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl block p-3 shadow-sm cursor-not-allowed" value="<?= generateInvoiceNo($conn) ?>" readonly disabled>
                    <p class="mt-1 text-xs text-gray-500">Auto-generated identifier</p>
                </div>
            </div>
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
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <select name="product_id[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 product-select transition-colors outline-none" required>
                                    <option value="">Select Product...</option>
                                    <?= str_replace("\n", "", $products_options) ?>
                                </select>
                            </td>
                            <td class="p-4"><input type="number" step="0.01" name="price[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 price transition-colors outline-none" required></td>
                            <td class="p-4"><input type="number" step="1" name="quantity[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 qty transition-colors outline-none" value="1" required></td>
                            <td class="p-4"><input type="number" step="0.01" name="item_tax[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 tax transition-colors outline-none" value="0"></td>
                            <td class="p-4"><input type="text" name="item_total[]" class="w-full bg-gray-100 border border-gray-200 text-gray-900 text-sm rounded-xl block p-2.5 total" readonly></td>
                            <td class="p-4 text-center">
                                <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors removeRow">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" id="addRow" class="text-brand-600 hover:text-brand-700 hover:bg-brand-50 font-medium py-2 px-4 rounded-xl transition-colors flex items-center mb-8 border border-brand-200 border-dashed w-full justify-center bg-brand-50/30">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Another Item
            </button>

            <!-- Totals -->
            <div class="flex flex-col md:flex-row justify-between items-start border-t border-gray-100 pt-8">
                <div class="w-full md:w-1/2 mb-6 md:mb-0 pr-0 md:pr-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes or Terms</label>
                    <textarea class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none" rows="4" placeholder="Enter any notes for the customer..."></textarea>
                </div>
                
                <div class="w-full md:w-1/3 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($global_currency) ?> <span id="subtotal_view">0.00</span></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-500">Tax</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($global_currency) ?> <span id="tax_view">0.00</span></span>
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
                                <input type="number" step="0.01" name="discount" id="discount" class="w-full pl-8 bg-white border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2 text-right outline-none" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total</span>
                        <span class="text-2xl font-bold text-brand-600"><?= htmlspecialchars($global_currency) ?> <span id="grand_total_view">0.00</span></span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="subtotal" id="subtotal" value="0">
            <input type="hidden" name="tax_total" id="tax_total" value="0">
            <input type="hidden" name="total" id="total" value="0">

            <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center text-lg">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i> Save Invoice
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Make productOptions available globally for the JS file
    window.productOptions = `<?= str_replace("\n", "", $products_options) ?>`;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>