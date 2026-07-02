<?php
// payments/payments.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$error = '';
$success = '';

// Process Payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'pay') {
    $amount = (float)$_POST['amount'];
    $method = sanitize($conn, $_POST['method']);
    $date = sanitize($conn, $_POST['payment_date']);

    if($amount > 0) {
        // Record payment
        $sql = "INSERT INTO payments (invoice_id, amount, method, payment_date) VALUES ($invoice_id, $amount, '$method', '$date')";
        if(mysqli_query($conn, $sql)) {
            // Check status
            $res = mysqli_query($conn, "SELECT total FROM invoices WHERE id = $invoice_id");
            $invRow = mysqli_fetch_assoc($res);
            $total_invoice = (float)$invRow['total'];

            $res_pay = mysqli_query($conn, "SELECT SUM(amount) as paid FROM payments WHERE invoice_id = $invoice_id");
            $payRow = mysqli_fetch_assoc($res_pay);
            $total_paid = (float)$payRow['paid'];

            $new_status = 'Unpaid';
            if($total_paid >= $total_invoice) {
                $new_status = 'Paid';
            } elseif($total_paid > 0) {
                $new_status = 'Partial';
            }

            mysqli_query($conn, "UPDATE invoices SET status = '$new_status' WHERE id = $invoice_id");
            
            // Log this activity
            if (isset($_SESSION['user_id'])) {
                $inv_no_res = mysqli_query($conn, "SELECT invoice_no FROM invoices WHERE id = $invoice_id");
                $inv_no_row = mysqli_fetch_assoc($inv_no_res);
                $inv_no_str = $inv_no_row ? $inv_no_row['invoice_no'] : $invoice_id;
                logActivity($conn, $_SESSION['user_id'], 'Payment Recorded', "Payment of " . htmlspecialchars($global_currency) . " " . number_format($amount, 2) . " via $method for Invoice #{$inv_no_str}");
            }
            
            $success = "Payment recorded successfully.";
        } else {
            $error = "Error recording payment: " . mysqli_error($conn);
        }
    } else {
        $error = "Payment amount must be greater than zero.";
    }
}

require_once __DIR__ . '/../includes/header.php';

// Ensure invoice exists
$res = mysqli_query($conn, "SELECT * FROM invoices WHERE id = $invoice_id");
if(mysqli_num_rows($res) == 0) {
    echo "<div class='alert alert-danger mt-4'>Invoice not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$invoice = mysqli_fetch_assoc($res);

// Get Payments
$pay_sql = "SELECT * FROM payments WHERE invoice_id = $invoice_id ORDER BY payment_date DESC, id DESC";
$payments = mysqli_query($conn, $pay_sql);

// Calculate Balance
$total_paid = 0;
$temp_payments = mysqli_query($conn, $pay_sql);
while($p = mysqli_fetch_assoc($temp_payments)) {
    $total_paid += $p['amount'];
}
$balance = $invoice['total'] - $total_paid;
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="credit-card" class="w-6 h-6 mr-3 text-brand-600"></i>
            Payments for Invoice #<?= htmlspecialchars($invoice['invoice_no']) ?>
        </h1>
        <p class="text-gray-500 mt-1">Manage and record payments for this invoice</p>
    </div>
    <div>
        <a href="/inv/invoices/view.php?id=<?= $invoice_id ?>" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Invoice
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center">
        <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i> <?= $error ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center">
        <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
    <!-- Payment Form -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full">
            <div class="bg-brand-600 text-white px-6 py-4 border-b border-brand-700 rounded-t-[15px]">
                <h3 class="text-lg font-bold flex items-center">
                    <i data-lucide="plus-circle" class="w-5 h-5 mr-2 text-brand-200"></i> Add Payment
                </h3>
            </div>
            
            <div class="p-6 flex-grow">
                <?php if($balance > 0): ?>
                <form action="" method="post" class="space-y-5">
                    <input type="hidden" name="action" value="pay">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 py-2.5 transition-colors outline-none shadow-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Due (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" step="0.01" class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl block p-3 shadow-sm font-medium cursor-not-allowed" value="<?= number_format($balance, 2, '.', '') ?>" disabled>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Amount (<?= htmlspecialchars($global_currency) ?>) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm font-medium"><?= htmlspecialchars($global_currency) ?></span>
                            </div>
                            <input type="number" step="0.01" name="amount" class="w-full pl-10 bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm font-bold text-brand-600" value="<?= number_format($balance, 2, '.', '') ?>" max="<?= number_format($balance, 2, '.', '') ?>" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                        <select name="method" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none shadow-sm" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-3 px-6 rounded-xl shadow-sm hover:shadow-md transition-all flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> Record Payment
                        </button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-center py-8">
                        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-4 border border-green-100">
                            <i data-lucide="check" class="w-10 h-10 text-green-500"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 mb-2">Invoice is Fully Paid</h4>
                        <p class="text-gray-500">No further payments can be recorded.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payments List -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                    <i data-lucide="history" class="w-5 h-5 mr-2 text-brand-600"></i> Payment History
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">Date</th>
                            <th scope="col" class="px-6 py-4 font-medium">Method</th>
                            <th scope="col" class="px-6 py-4 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php 
                        mysqli_data_seek($payments, 0); // Reset pointer
                        if(mysqli_num_rows($payments) > 0): 
                            while($row = mysqli_fetch_assoc($payments)): 
                        ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">
                                <?= date('M d, Y', strtotime($row['payment_date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg text-xs font-medium inline-flex items-center">
                                    <i data-lucide="wallet" class="w-3 h-3 mr-1.5"></i> <?= htmlspecialchars($row['method']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-green-600">
                                <?= htmlspecialchars($global_currency) ?> <?= number_format($row['amount'], 2) ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                    No payments recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if($total_paid > 0): ?>
                    <tfoot class="bg-gray-50/50 border-t border-gray-100">
                        <tr>
                            <th colspan="2" class="px-6 py-4 text-right font-medium text-gray-700">Total Paid:</th>
                            <th class="px-6 py-4 text-right text-base font-bold text-gray-900"><?= htmlspecialchars($global_currency) ?> <?= number_format($total_paid, 2) ?></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="px-6 py-4 text-right font-medium text-gray-700 border-t border-gray-200">Balance Due:</th>
                            <th class="px-6 py-4 text-right text-base font-bold <?= $balance > 0 ? 'text-red-600' : 'text-green-600' ?> border-t border-gray-200"><?= htmlspecialchars($global_currency) ?> <?= number_format($balance, 2) ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
