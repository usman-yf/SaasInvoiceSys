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
</style>

<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between space-y-4 md:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 mt-1">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>! Here's what's happening with your business today.</p>
    </div>
    <div class="flex space-x-3">
        <a href="/inv/invoices/create.php" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Invoice
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Stat Cards -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between group hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 group-hover:scale-110 transition-transform">
                <i data-lucide="banknote" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
            <h2 class="text-2xl font-bold text-gray-900 truncate" title="<?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_revenue'], 2) ?>"><?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_revenue'], 2) ?></h2>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between group hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                <i data-lucide="receipt" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Invoices</p>
            <h2 class="text-2xl font-bold text-gray-900 truncate" title="<?= $stats['total_invoices'] ?>"><?= $stats['total_invoices'] ?></h2>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between group hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 mb-1">Unpaid Invoices</p>
            <h2 class="text-2xl font-bold text-gray-900 truncate" title="<?= $stats['unpaid_invoices'] ?>"><?= $stats['unpaid_invoices'] ?></h2>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between group hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform">
                <i data-lucide="trending-down" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 mb-1">Outstanding</p>
            <h2 class="text-2xl font-bold text-gray-900 truncate" title="<?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_remaining'], 2) ?>"><?= htmlspecialchars($global_currency) ?> <?= number_format($stats['total_remaining'], 2) ?></h2>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between group hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Customers</p>
            <h2 class="text-2xl font-bold text-gray-900 truncate" title="<?= $stats['total_customers'] ?>"><?= $stats['total_customers'] ?></h2>
        </div>
    </div>
</div>

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

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Recent Invoices</h3>
        <a href="/inv/invoices/list.php" class="text-brand-600 hover:text-brand-700 text-sm font-medium flex items-center">
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
                            <a href="/inv/invoices/view.php?id=<?= $inv['id'] ?>" class="text-gray-400 hover:text-brand-600 transition-colors" title="View">
                                <i data-lucide="eye" class="w-5 h-5 inline"></i>
                            </a>
                            <?php if ($inv['status'] == 'Unpaid'): ?>
                                <a href="/inv/invoices/edit.php?id=<?= $inv['id'] ?>" class="text-gray-400 hover:text-brand-600 transition-colors" title="Edit">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#9ca3af';
    
    // Enable extremely visible global animations
    Chart.defaults.animation = {
        duration: 2500,
        easing: 'easeOutQuart'
    };

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
    const delayBetweenPoints = totalDuration / dataLength;
    const previousY = (ctx) => ctx.index === 0 ? ctx.chart.scales.y.getPixelForValue(100) : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;
    
    const progressiveAnimation = {
        x: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            from: NaN,
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.xStarted) {
                    return 0;
                }
                ctx.xStarted = true;
                return ctx.index * delayBetweenPoints;
            }
        },
        y: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            from: previousY,
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.yStarted) {
                    return 0;
                }
                ctx.yStarted = true;
                return ctx.index * delayBetweenPoints;
            }
        }
    };

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
            animation: progressiveAnimation,
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
                backgroundColor: ['#10b981', '#f97316', '#ef4444'], // green-500, orange-500, red-500
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
