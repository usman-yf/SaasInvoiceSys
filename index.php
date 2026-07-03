<?php
// index.php (Dashboard)
require_once __DIR__ . '/includes/header.php';

// Fetch quick stats
$stats = [
    'total_invoices' => 0,
    'total_revenue' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'unpaid_invoices' => 0
];

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices");
if($row = mysqli_fetch_assoc($res)) $stats['total_invoices'] = $row['c'];

$res = mysqli_query($conn, "SELECT SUM(total) as s FROM invoices WHERE status != 'Unpaid'");
if($row = mysqli_fetch_assoc($res)) $stats['total_revenue'] = $row['s'] ?? 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM customers");
if($row = mysqli_fetch_assoc($res)) $stats['total_customers'] = $row['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM products");
if($row = mysqli_fetch_assoc($res)) $stats['total_products'] = $row['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE status = 'Unpaid'");
if($row = mysqli_fetch_assoc($res)) $stats['unpaid_invoices'] = $row['c'];

$res = mysqli_query($conn, "SELECT SUM(GREATEST(0, i.total - COALESCE((SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id), 0))) as outstanding FROM invoices i");
$stats['total_remaining'] = mysqli_fetch_assoc($res)['outstanding'] ?? 0;

// ZATCA Stats
$stats['zatca_cleared'] = 0;
$stats['zatca_reported'] = 0;
$stats['zatca_failed'] = 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE zatca_status = 'Cleared'");
if($row = mysqli_fetch_assoc($res)) $stats['zatca_cleared'] = $row['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE zatca_status = 'Reported'");
if($row = mysqli_fetch_assoc($res)) $stats['zatca_reported'] = $row['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE zatca_status IN ('Error', 'Rejected')");
if($row = mysqli_fetch_assoc($res)) $stats['zatca_failed'] = $row['c'];

// MoM Growth Logic
$current_month = date('Y-m');
$prev_month = date('Y-m', strtotime('-1 month'));

// Revenue MoM
$rev_curr_sql = mysqli_query($conn, "SELECT SUM(total) as s FROM invoices WHERE DATE_FORMAT(date, '%Y-%m') = '$current_month' AND status != 'Unpaid'");
$rev_curr = mysqli_fetch_assoc($rev_curr_sql)['s'] ?? 0;
$rev_prev_sql = mysqli_query($conn, "SELECT SUM(total) as s FROM invoices WHERE DATE_FORMAT(date, '%Y-%m') = '$prev_month' AND status != 'Unpaid'");
$rev_prev = mysqli_fetch_assoc($rev_prev_sql)['s'] ?? 0;
$rev_growth = $rev_prev > 0 ? (($rev_curr - $rev_prev) / $rev_prev) * 100 : ($rev_curr > 0 ? 100 : 0);

// Invoices MoM
$inv_curr_sql = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE DATE_FORMAT(date, '%Y-%m') = '$current_month'");
$inv_curr = mysqli_fetch_assoc($inv_curr_sql)['c'] ?? 0;
$inv_prev_sql = mysqli_query($conn, "SELECT COUNT(*) as c FROM invoices WHERE DATE_FORMAT(date, '%Y-%m') = '$prev_month'");
$inv_prev = mysqli_fetch_assoc($inv_prev_sql)['c'] ?? 0;
$inv_growth = $inv_prev > 0 ? (($inv_curr - $inv_prev) / $inv_prev) * 100 : ($inv_curr > 0 ? 100 : 0);

// Overdue Invoices Alert (Assumes unpaid and older than 30 days)
$overdue_sql = mysqli_query($conn, "SELECT COUNT(*) as cnt, SUM(total) as sum FROM invoices WHERE status = 'Unpaid' AND date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$overdue_data = $overdue_sql ? mysqli_fetch_assoc($overdue_sql) : null;
$overdue_count = (int)($overdue_data['cnt'] ?? 0);
$overdue_amount = (float)($overdue_data['sum'] ?? 0);

// Activity Stream
$user_id = (int)($_SESSION['user_id'] ?? 0);
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $activities = mysqli_query($conn, "SELECT action as title, details as message, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 6");
} else {
    $activities = mysqli_query($conn, "SELECT action as title, details as message, created_at FROM activity_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 6");
}
// Top Customers
$top_customers = mysqli_query($conn, "
    SELECT c.name, c.email, SUM(i.total) as total_revenue
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    WHERE i.status != 'Unpaid'
    GROUP BY c.id 
    ORDER BY total_revenue DESC 
    LIMIT 4
");

// Top Products
$top_products = mysqli_query($conn, "
    SELECT p.name, SUM(ii.total) as total_revenue
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE i.status != 'Unpaid'
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 4
");

// Recent invoices
$recent_invoices = mysqli_query($conn, "
    SELECT i.*, c.name as customer_name,
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = i.id) as paid_amount 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.date DESC, i.id DESC LIMIT 5
");

// Chart Data - Revenue by Month (Last 6 Months)
$chart_months = [];
$chart_revenue = [];
$chart_outstanding = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $chart_months[] = date('M', strtotime($m . '-01'));
    
    // Revenue (Total of paid/partial invoices)
    $sql = "SELECT SUM(total) as s FROM invoices WHERE DATE_FORMAT(date, '%Y-%m') = '$m' AND status != 'Unpaid'";
    $cres = mysqli_query($conn, $sql);
    $crow = mysqli_fetch_assoc($cres);
    $chart_revenue[] = (float)($crow['s'] ?? 0);
    
    // Outstanding (Total invoiced minus total paid for that month, capped at 0 per invoice)
    $sql_o = "SELECT SUM(GREATEST(0, i.total - COALESCE((SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id), 0))) as outst FROM invoices i WHERE DATE_FORMAT(i.date, '%Y-%m') = '$m'";
    $cres_o = mysqli_query($conn, $sql_o);
    $chart_outstanding[] = (float)(mysqli_fetch_assoc($cres_o)['outst'] ?? 0);
}

// Chart Data - Invoice Status
$chart_status_counts = [0, 0, 0]; // Paid, Partial, Unpaid
$sql = "SELECT status, COUNT(*) as c FROM invoices GROUP BY status";
$cres = mysqli_query($conn, $sql);
while($crow = mysqli_fetch_assoc($cres)) {
    if ($crow['status'] == 'Paid') $chart_status_counts[0] = (int)$crow['c'];
    elseif ($crow['status'] == 'Partial') $chart_status_counts[1] = (int)$crow['c'];
    elseif ($crow['status'] == 'Unpaid') $chart_status_counts[2] = (int)$crow['c'];
}
?>
<style>
@keyframes chartPopIn {
    0% { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-chart-pop {
    animation: chartPopIn 1s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    opacity: 0;
}
@keyframes floatBadge {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.animate-float {
    animation: floatBadge 3s ease-in-out infinite;
}
</style>

<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between space-y-4 md:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 mt-1">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>! Here's what's happening with your business today.</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= BASE_URL ?>/invoices/create.php" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Invoice
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5 mb-8">
    <!-- Total Revenue -->
    <div class="relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-brand-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Total Revenue</p>
            </div>
            <div class="absolute -top-3 right-4 flex items-center text-[10px] sm:text-xs font-bold <?= $rev_growth >= 0 ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100' ?> px-2 py-1 rounded-full shrink-0 shadow-sm animate-float border border-white" title="vs Last Month">
                <i data-lucide="<?= $rev_growth >= 0 ? 'trending-up' : 'trending-down' ?>" class="w-3 h-3 mr-1"></i>
                <?= number_format(abs($rev_growth), 1) ?>%
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_revenue'], 2) ?>">
                <span class="text-sm font-semibold text-gray-400 mr-1"><?= htmlspecialchars($global_currency) ?></span><?= number_format($stats['total_revenue'], 2) ?>
            </h2>
        </div>
    </div>
    
    <!-- Total Invoices -->
    <div class="relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-brand-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Total Invoices</p>
            </div>
            <div class="absolute -top-3 right-4 flex items-center text-[10px] sm:text-xs font-bold <?= $inv_growth >= 0 ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100' ?> px-2 py-1 rounded-full shrink-0 shadow-sm animate-float border border-white" title="vs Last Month">
                <i data-lucide="<?= $inv_growth >= 0 ? 'trending-up' : 'trending-down' ?>" class="w-3 h-3 mr-1"></i>
                <?= number_format(abs($inv_growth), 1) ?>%
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['total_invoices'] ?>">
                <?= $stats['total_invoices'] ?>
            </h2>
        </div>
    </div>
    
    <!-- Unpaid Invoices -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-brand-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Unpaid</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['unpaid_invoices'] ?>">
                <?= $stats['unpaid_invoices'] ?>
            </h2>
        </div>
    </div>
    
    <!-- Outstanding -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-brand-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="trending-down" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Outstanding</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_remaining'], 2) ?>">
                <span class="text-sm font-semibold text-gray-400 mr-1"><?= htmlspecialchars($global_currency) ?></span><?= number_format($stats['total_remaining'], 2) ?>
            </h2>
        </div>
    </div>
    
    <!-- Total Customers -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-brand-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Customers</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['total_customers'] ?>">
                <?= $stats['total_customers'] ?>
            </h2>
        </div>
    </div>
</div>

<!-- ZATCA Analytics -->
<h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
    <i data-lucide="shield-check" class="w-5 h-5 mr-2 text-brand-600"></i> ZATCA Phase 2 Metrics
</h3>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <!-- Cleared Invoices -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-green-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Cleared (Standard)</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['zatca_cleared'] ?>">
                <?= $stats['zatca_cleared'] ?>
            </h2>
        </div>
    </div>
    
    <!-- Reported Invoices -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-blue-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Reported (Simplified)</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['zatca_reported'] ?>">
                <?= $stats['zatca_reported'] ?>
            </h2>
        </div>
    </div>

    <!-- Failed Invoices -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md hover:border-red-200 transition-all">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform shrink-0">
                    <i data-lucide="x-octagon" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold text-gray-500 ml-3">Failed / Rejected</p>
            </div>
        </div>
        <div class="min-w-0 mt-auto">
            <h2 class="text-2xl xl:text-3xl font-extrabold text-gray-900 tracking-tight truncate" title="<?= $stats['zatca_failed'] ?>">
                <?= $stats['zatca_failed'] ?>
            </h2>
        </div>
    </div>
</div>

<?php if($overdue_count > 0): ?>
<!-- Overdue Invoices Alert Panel -->
<div class="bg-red-50 border border-red-100 rounded-2xl p-4 sm:p-5 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between shadow-sm animate-chart-pop">
    <div class="flex items-center mb-3 sm:mb-0">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0 mr-4">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
        </div>
        <div>
            <h3 class="text-red-800 font-bold">Action Required: Overdue Invoices</h3>
            <p class="text-sm text-red-600 mt-0.5">You have <?= $overdue_count ?> invoice<?= $overdue_count > 1 ? 's' : '' ?> that <?= $overdue_count > 1 ? 'are' : 'is' ?> past due, totaling <strong class="font-bold"><?= $global_currency ?> <?= number_format($overdue_amount, 2) ?></strong>.</p>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/invoices/list.php?status=overdue" class="shrink-0 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all text-sm flex items-center">
        Review Now <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
    </a>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Bar Chart -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-2">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-900">Revenue Overview</h3>
            <select class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2">
                <option>Last 6 Months</option>
            </select>
        </div>
        <div class="relative h-64 animate-chart-pop" style="animation-delay: 100ms;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    
    <!-- Doughnut Chart -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-900">Invoice Status</h3>
        </div>
        <div class="relative h-56 flex justify-center items-center animate-chart-pop" style="animation-delay: 300ms;">
            <canvas id="statusChart"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div>
                <p class="text-xs text-gray-500 mb-1">Paid</p>
                <p class="font-semibold text-gray-900"><?= $chart_status_counts[0] ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Partial</p>
                <p class="font-semibold text-gray-900"><?= $chart_status_counts[1] ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Unpaid</p>
                <p class="font-semibold text-gray-900"><?= $chart_status_counts[2] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Recent Invoices -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-2">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Recent Invoices</h3>
        <a href="<?= BASE_URL ?>/invoices/list.php" class="text-brand-600 hover:text-brand-700 text-sm font-medium flex items-center">
            View All <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Invoice #</th>
                    <th scope="col" class="px-6 py-4 font-medium">Customer</th>
                    <th scope="col" class="px-6 py-4 font-medium">Date</th>
                    <th scope="col" class="px-6 py-4 font-medium">Total</th>
                    <th scope="col" class="px-6 py-4 font-medium">Status</th>
                    <th scope="col" class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($inv = mysqli_fetch_assoc($recent_invoices)): ?>
                    <tr class="bg-white border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= htmlspecialchars($inv['invoice_no']) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-700 font-medium">
                            <?= htmlspecialchars($inv['customer_name']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= date('M d, Y', strtotime($inv['date'])) ?>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?= htmlspecialchars($global_currency) ?> <?= number_format($inv['total'], 2) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($inv['status'] == 'Paid'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> Paid
                                </span>
                            <?php elseif ($inv['status'] == 'Partial'): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <span class="w-1.5 h-1.5 bg-orange-500 rounded-full mr-1.5"></span> Partial
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span> Unpaid
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="<?= BASE_URL ?>/invoices/view.php?id=<?= $inv['id'] ?>" class="text-gray-400 hover:text-brand-600 transition-colors" title="View">
                                <i data-lucide="eye" class="w-5 h-5 inline"></i>
                            </a>
                            <?php if ($inv['status'] == 'Unpaid'): ?>
                                <a href="<?= BASE_URL ?>/invoices/edit.php?id=<?= $inv['id'] ?>" class="text-gray-400 hover:text-brand-600 transition-colors" title="Edit">
                                    <i data-lucide="edit" class="w-5 h-5 inline"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($recent_invoices) == 0): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <i data-lucide="inbox" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <p>No invoices yet.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    <!-- Recent Activity Stream -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <?php if (mysqli_num_rows($activities) > 0): ?>
                    <?php while($act = mysqli_fetch_assoc($activities)): 
                        $a_icon = 'bell'; $a_color = 'text-gray-500'; $a_bg = 'bg-gray-100';
                        $t_lower = strtolower($act['title']);
                        
                        if(strpos($t_lower, 'payment') !== false || strpos($t_lower, 'paid') !== false) { 
                            $a_icon = 'banknote'; $a_color = 'text-emerald-600'; $a_bg = 'bg-emerald-50'; 
                        } elseif(strpos($t_lower, 'invoice') !== false) { 
                            $a_icon = 'receipt'; $a_color = 'text-brand-600'; $a_bg = 'bg-brand-50'; 
                        } elseif(strpos($t_lower, 'user') !== false || strpos($t_lower, 'login') !== false) { 
                            $a_icon = 'user-cog'; $a_color = 'text-cyan-600'; $a_bg = 'bg-cyan-50'; 
                        } elseif(strpos($t_lower, 'setting') !== false) { 
                            $a_icon = 'settings'; $a_color = 'text-slate-600'; $a_bg = 'bg-slate-100'; 
                        } elseif(strpos($t_lower, 'customer') !== false) { 
                            $a_icon = 'users'; $a_color = 'text-blue-600'; $a_bg = 'bg-blue-50'; 
                        } elseif(strpos($t_lower, 'zatca') !== false) { 
                            if (strpos($t_lower, 'cleared') !== false || strpos($t_lower, 'reported') !== false) {
                                $a_icon = 'shield-check'; $a_color = 'text-green-600'; $a_bg = 'bg-green-50';
                            } elseif (strpos($t_lower, 'rejected') !== false || strpos($t_lower, 'error') !== false) {
                                $a_icon = 'shield-alert'; $a_color = 'text-red-600'; $a_bg = 'bg-red-50';
                            } else {
                                $a_icon = 'file-code'; $a_color = 'text-purple-600'; $a_bg = 'bg-purple-50';
                            }
                        }
                    ?>
                    <div class="relative flex items-start">
                        <!-- Timeline line -->
                        <div class="absolute top-8 left-4 bottom-[-24px] w-px bg-gray-100 last:hidden"></div>
                        
                        <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center <?= $a_bg ?> shrink-0">
                            <i data-lucide="<?= $a_icon ?>" class="w-4 h-4 <?= $a_color ?>"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($act['title']) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed"><?= htmlspecialchars($act['message']) ?></p>
                            <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wider"><?= date('M d, h:i A', strtotime($act['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-500 text-center py-4">No recent activity.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Customers Leaderboard -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Top Customers</h3>
            <a href="<?= BASE_URL ?>/customers/list.php" class="text-brand-600 hover:text-brand-700 text-sm font-medium flex items-center">
                View All <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
            </a>
        </div>
        <div class="p-4 sm:p-6">
            <div class="space-y-4">
                <?php if (mysqli_num_rows($top_customers) > 0): ?>
                    <?php while($tc = mysqli_fetch_assoc($top_customers)): 
                        $initials = strtoupper(substr($tc['name'], 0, 2));
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 transform transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg hover:border-brand-200 bg-white group cursor-default">
                        <div class="flex items-center space-x-3 overflow-hidden pr-2">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-100 to-brand-50 text-brand-700 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100">
                                <?= $initials ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($tc['name']) ?></p>
                                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($tc['email']) ?></p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-brand-600"><?= htmlspecialchars($global_currency) ?> <?= number_format($tc['total_revenue'], 2) ?></p>
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Revenue</p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-500 text-center py-4">No customer data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Products Leaderboard -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Top Products</h3>
            <a href="<?= BASE_URL ?>/products/list.php" class="text-brand-600 hover:text-brand-700 text-sm font-medium flex items-center">
                View All <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
            </a>
        </div>
        <div class="p-4 sm:p-6">
            <div class="space-y-4">
                <?php if (mysqli_num_rows($top_products) > 0): ?>
                    <?php while($tp = mysqli_fetch_assoc($top_products)): 
                        $initials = strtoupper(substr($tp['name'], 0, 2));
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 transform transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg hover:border-indigo-200 bg-white group cursor-default">
                        <div class="flex items-center space-x-3 overflow-hidden pr-2">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0 border border-indigo-100">
                                <?= $initials ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($tp['name']) ?></p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-indigo-600"><?= htmlspecialchars($global_currency) ?> <?= number_format($tp['total_revenue'], 2) ?></p>
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Revenue</p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-500 text-center py-4">No product data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#9ca3af';
    
    // Enable extremely visible global animations
    Chart.defaults.animation.duration = 2500;
    Chart.defaults.animation.easing = 'easeOutQuart';

    // Revenue Bar Chart
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    
    // Create gradient for Revenue
    const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 400);
    gradientRev.addColorStop(0, 'rgba(139, 92, 246, 0.8)'); // brand-500
    gradientRev.addColorStop(1, 'rgba(139, 92, 246, 0.1)');
    
    // Create gradient for Outstanding
    const gradientOut = ctxRev.createLinearGradient(0, 0, 0, 400);
    gradientOut.addColorStop(0, 'rgba(239, 68, 68, 0.8)'); // red-500
    gradientOut.addColorStop(1, 'rgba(239, 68, 68, 0.1)');
    
    // Progressive line drawing animation logic for Chart.js 4+
    const totalDuration = 2500;
    const dataLength = <?= count($chart_months) ?>;
    // Standard beautiful easing animation will be inherited from defaults

    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_months) ?>,
            datasets: [
                {
                    label: 'Revenue (<?= $global_currency ?>)',
                    data: <?= json_encode($chart_revenue) ?>,
                    backgroundColor: gradientRev,
                    borderColor: '#8b5cf6',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#8b5cf6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Outstanding (<?= $global_currency ?>)',
                    data: <?= json_encode($chart_outstanding) ?>,
                    backgroundColor: gradientOut,
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#ef4444',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 14 },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label.split(' ')[0] + ': <?= $global_currency ?> ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { borderDash: [4, 4], color: '#f3f4f6', drawBorder: false },
                    ticks: { padding: 10 }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { padding: 10 }
                }
            }
        }
    });

    // Status Doughnut Chart
    const ctxStat = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStat, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Partial', 'Unpaid'],
            datasets: [{
                data: <?= json_encode($chart_status_counts) ?>,
                backgroundColor: ['#6ee7b7', '#fdba74', '#fca5a5'], // emerald-300, orange-300, red-300
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    bodyFont: { size: 14 },
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
