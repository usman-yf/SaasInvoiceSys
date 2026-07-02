<?php
// reports.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth();

// Date Filtering Logic
$start_date = isset($_GET['start_date']) ? sanitize($conn, $_GET['start_date']) : ''; 
$end_date = isset($_GET['end_date']) ? sanitize($conn, $_GET['end_date']) : '';
$filter_invoice = isset($_GET['invoice_no']) ? sanitize($conn, $_GET['invoice_no']) : '';
$filter_customer = isset($_GET['customer_id']) ? sanitize($conn, $_GET['customer_id']) : '';

$conditions = [];
if (!empty($start_date) && !empty($end_date)) {
    $conditions[] = "i.date BETWEEN '$start_date' AND '$end_date'";
    $date_display = htmlspecialchars($start_date) . " to " . htmlspecialchars($end_date);
} else {
    $date_display = "All Time";
}

if (!empty($filter_invoice)) {
    $conditions[] = "i.invoice_no LIKE '%$filter_invoice%'";
}
if (!empty($filter_customer)) {
    $conditions[] = "i.customer_id = '$filter_customer'";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : "";

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_' . $start_date . '_to_' . $end_date . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Invoice No', 'Customer', 'Date', 'Subtotal', 'Tax', 'Discount', 'Total', 'Status'));
    
    $csv_sql = "SELECT i.invoice_no, c.name as customer_name, i.date, i.subtotal, i.tax, i.discount, i.total, i.status 
                FROM invoices i 
                LEFT JOIN customers c ON i.customer_id = c.id 
                $where_clause ORDER BY i.date DESC";
    $csv_res = mysqli_query($conn, $csv_sql);
    
    while ($row = mysqli_fetch_assoc($csv_res)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

require_once __DIR__ . '/includes/header.php';

// Analytics Queries
$and_prefix = !empty($where_clause) ? " AND " : " WHERE ";

$total_revenue_sql = "SELECT SUM(i.total) as rev FROM invoices i $where_clause $and_prefix i.status != 'Unpaid'";
$total_rev = mysqli_fetch_assoc(mysqli_query($conn, $total_revenue_sql))['rev'] ?? 0;

$total_invoices_sql = "SELECT COUNT(i.id) as cnt FROM invoices i $where_clause";
$total_inv = mysqli_fetch_assoc(mysqli_query($conn, $total_invoices_sql))['cnt'] ?? 0;

$paid_invoices_sql = "SELECT COUNT(i.id) as cnt FROM invoices i $where_clause $and_prefix i.status = 'Paid'";
$paid_inv = mysqli_fetch_assoc(mysqli_query($conn, $paid_invoices_sql))['cnt'] ?? 0;

$unpaid_revenue_sql = "SELECT SUM(i.total) as rev FROM invoices i $where_clause $and_prefix i.status = 'Unpaid'";
$unpaid_rev = mysqli_fetch_assoc(mysqli_query($conn, $unpaid_revenue_sql))['rev'] ?? 0;

// Fetch Customers for Filter Dropdown
$customers_sql = "SELECT id, name FROM customers ORDER BY name ASC";
$customers_res = mysqli_query($conn, $customers_sql);

// Fetch Invoice List
$invoices_sql = "SELECT i.*, c.name as customer_name 
                 FROM invoices i 
                 LEFT JOIN customers c ON i.customer_id = c.id 
                 $where_clause ORDER BY i.date DESC";
$invoices_res = mysqli_query($conn, $invoices_sql);

?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="bar-chart-2" class="w-6 h-6 mr-3 text-brand-600"></i>
            Analytical Reports
        </h1>
        <p class="text-gray-500 mt-1">Financial overview and invoice data</p>
    </div>
    
    <div class="flex space-x-3">
        <!-- Print / PDF generates premium PDF via html2pdf -->
        <button onclick="generatePDF()" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Print / PDF
        </button>
        <a href="/inv/reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&export=csv" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center">
            <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export CSV
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 print:hidden">
    <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No</label>
            <input type="text" name="invoice_no" placeholder="INV-..." value="<?= htmlspecialchars($filter_invoice) ?>" class="w-full h-[42px] bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 outline-none transition-colors">
        </div>
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
            <select name="customer_id" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 py-2.5 transition-colors outline-none shadow-sm">
                <option value="">All Customers</option>
                <?php 
                mysqli_data_seek($customers_res, 0);
                while($c = mysqli_fetch_assoc($customers_res)): 
                ?>
                    <option value="<?= $c['id'] ?>" <?= ($filter_customer == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="w-full h-[42px] bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 outline-none transition-colors">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="w-full h-[42px] bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 outline-none transition-colors">
        </div>
        <div>
            <div class="flex space-x-2">
                <a href="/inv/reports.php" class="flex-shrink-0 w-[42px] h-[42px] bg-white border border-gray-200 hover:bg-gray-50 text-gray-500 hover:text-red-500 rounded-xl shadow-sm transition-colors flex items-center justify-center" title="Clear Filters">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
                <button type="submit" class="flex-grow h-[42px] bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-xl shadow-sm transition-all flex items-center justify-center">
                    <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Apply
                </button>
            </div>
        </div>
    </form>
</div>

<div id="reportContent">
<!-- PDF Header (Hidden in UI, visible in PDF) -->
<div id="pdfHeader" class="hidden mb-6 pb-6 border-b border-gray-100">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Financial Report</h2>
            <p class="text-sm text-gray-500">Generated on: <?= date('M d, Y h:i A') ?></p>
        </div>
        <div class="text-right">
            <h3 class="font-bold text-gray-900 text-lg"><?= htmlspecialchars(get_setting($conn, 'company_name', 'Company Name')) ?></h3>
            <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars(get_setting($conn, 'company_email', 'email@example.com')) ?></p>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars(get_setting($conn, 'company_phone', 'Phone Number')) ?></p>
            <p class="text-gray-500 text-sm mt-1"><?= nl2br(htmlspecialchars(get_setting($conn, 'company_address', 'Company Address'))) ?></p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <span class="bg-brand-50 text-brand-700 px-3 py-1 rounded-lg text-sm font-medium border border-brand-100">Date Range: <?= $date_display ?></span>
        <?php if(!empty($filter_invoice)): ?>
            <span class="bg-gray-50 text-gray-700 px-3 py-1 rounded-lg text-sm font-medium border border-gray-200">Invoice: <?= htmlspecialchars($filter_invoice) ?></span>
        <?php endif; ?>
        <?php if(!empty($filter_customer)): ?>
            <span class="bg-gray-50 text-gray-700 px-3 py-1 rounded-lg text-sm font-medium border border-gray-200">
                Customer: 
                <?php 
                    $c_name = 'Unknown';
                    mysqli_data_seek($customers_res, 0);
                    while($c = mysqli_fetch_assoc($customers_res)) {
                        if($c['id'] == $filter_customer) { $c_name = $c['name']; break; }
                    }
                    mysqli_data_seek($customers_res, 0);
                    echo htmlspecialchars($c_name);
                ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center transition-all hover:shadow-md">
        <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center mr-4">
            <i data-lucide="dollar-sign" class="w-6 h-6 text-green-600"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Collected Revenue</p>
            <h4 class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($global_currency) ?> <?= number_format($total_rev, 2) ?></h4>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center transition-all hover:shadow-md">
        <div class="w-14 h-14 rounded-xl bg-brand-50 flex items-center justify-center mr-4">
            <i data-lucide="file-text" class="w-6 h-6 text-brand-600"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Total Invoices</p>
            <h4 class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($total_inv) ?></h4>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center transition-all hover:shadow-md">
        <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center mr-4">
            <i data-lucide="check-circle" class="w-6 h-6 text-blue-600"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Paid Invoices</p>
            <h4 class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($paid_inv) ?></h4>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center transition-all hover:shadow-md">
        <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center mr-4">
            <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Unpaid Revenue</p>
            <h4 class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($global_currency) ?> <?= number_format($unpaid_rev, 2) ?></h4>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h3 class="text-lg font-bold text-gray-900">Invoices List (<?= $date_display ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50 border-b border-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Invoice No</th>
                    <th scope="col" class="px-6 py-4 font-medium">Date</th>
                    <th scope="col" class="px-6 py-4 font-medium">Customer</th>
                    <th scope="col" class="px-6 py-4 font-medium">Status</th>
                    <th scope="col" class="px-6 py-4 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (mysqli_num_rows($invoices_res) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($invoices_res)): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?= htmlspecialchars($row['invoice_no']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= date('M d, Y', strtotime($row['date'])) ?>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-700">
                            <?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                                $status = $row['status'];
                                $badge_class = 'bg-gray-100 text-gray-800';
                                if($status == 'Paid') $badge_class = 'bg-green-100 text-green-800';
                                elseif($status == 'Unpaid') $badge_class = 'bg-red-100 text-red-800';
                                elseif($status == 'Partial') $badge_class = 'bg-yellow-100 text-yellow-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $badge_class ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900 text-right">
                            <?= htmlspecialchars($global_currency) ?> <?= number_format($row['total'], 2) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <i data-lucide="file-x" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-1">No Invoices Found</h3>
                                <p class="text-sm text-gray-500">There are no invoices in the selected date range.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div> <!-- End #reportContent -->

<style>
@media print {
    .print\:hidden {
        display: none !important;
    }
    body {
        background: white;
    }
    .shadow-sm {
        box-shadow: none !important;
    }
    .border {
        border: 1px solid #ccc !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- html2pdf Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function generatePDF() {
    const element = document.getElementById('reportContent');
    const pdfHeader = document.getElementById('pdfHeader');
    
    // Format current date for filename: YYYY-MM-DD
    const today = new Date();
    const dateStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    const filename = `Financial_Report_${dateStr}.pdf`;
    
    const opt = {
        margin:       0.4,
        filename:     filename,
        image:        { type: 'jpeg', quality: 1.0 },
        html2canvas:  { scale: 4, useCORS: true, scrollX: 0, scrollY: 0, letterRendering: true },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    
    // Add visual loading state to button
    const btn = document.querySelector('button[onclick="generatePDF()"]');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i> Generating...';
    lucide.createIcons();
    
    // Temporarily show the PDF header
    pdfHeader.classList.remove('hidden');
    
    html2pdf().set(opt).from(element).save().then(() => {
        // Restore UI
        pdfHeader.classList.add('hidden');
        btn.innerHTML = originalHtml;
        lucide.createIcons();
    });
}
</script>
