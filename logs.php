<?php
// logs.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth();

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_all') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        mysqli_query($conn, "TRUNCATE TABLE activity_logs");
        $success_msg = "All logs have been cleared successfully.";
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$start_date = isset($_GET['start_date']) ? sanitize($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($conn, $_GET['end_date']) : '';

$where_clause = "WHERE 1=1";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $uid = (int)$_SESSION['user_id'];
    $where_clause .= " AND l.user_id = $uid";
}

if ($start_date) {
    $where_clause .= " AND l.created_at >= '$start_date 00:00:00'";
}
if ($end_date) {
    $where_clause .= " AND l.created_at <= '$end_date 23:59:59'";
}

$total_sql = "SELECT COUNT(*) as cnt FROM activity_logs l $where_clause";
$total_res = mysqli_query($conn, $total_sql);
$total_logs = mysqli_fetch_assoc($total_res)['cnt'];
$total_pages = ceil($total_logs / $per_page);

$logs_sql = "SELECT l.*, u.full_name as user_name 
             FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             $where_clause
             ORDER BY l.created_at DESC 
             LIMIT $per_page OFFSET $offset";
$logs_res = mysqli_query($conn, $logs_sql);

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i data-lucide="activity" class="w-6 h-6 mr-3 text-brand-600"></i>
            System Logs
        </h1>
        <p class="text-gray-500 mt-1">Monitor all user activities and system events</p>
    </div>
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="flex items-center space-x-3">
        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to clear all logs? This cannot be undone.');">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 font-medium py-2 px-4 rounded-xl shadow-sm transition-all flex items-center border border-red-200">
                <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i> Clear All Logs
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($success_msg)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center">
        <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success_msg ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-gray-900">Activity History</h3>
        
        <form method="GET" action="" class="flex items-center space-x-3">
            <div>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 py-2 outline-none shadow-sm" placeholder="Start Date">
            </div>
            <span class="text-gray-400">to</span>
            <div>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block px-3 py-2 outline-none shadow-sm" placeholder="End Date">
            </div>
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
                Filter
            </button>
            <?php if($start_date || $end_date): ?>
                <a href="logs.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50 border-b border-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Timestamp</th>
                    <th scope="col" class="px-6 py-4 font-medium">User</th>
                    <th scope="col" class="px-6 py-4 font-medium">Action</th>
                    <th scope="col" class="px-6 py-4 font-medium">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (mysqli_num_rows($logs_res) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($logs_res)): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">
                            <?= htmlspecialchars($row['user_name'] ?? 'System') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-medium">
                                <?= htmlspecialchars($row['action']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 min-w-[300px]">
                            <?= htmlspecialchars($row['details']) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <i data-lucide="clipboard-list" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-1">No Logs Found</h3>
                                <p class="text-sm text-gray-500">System activity will appear here.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="p-6 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
        <p class="text-sm text-gray-500">
            Showing <span class="font-medium text-gray-900"><?= $offset + 1 ?></span> to <span class="font-medium text-gray-900"><?= min($offset + $per_page, $total_logs) ?></span> of <span class="font-medium text-gray-900"><?= $total_logs ?></span> entries
        </p>
        <div class="flex space-x-2">
            <?php
            $query_params = $_GET;
            ?>
            <?php if($page > 1): ?>
                <?php $query_params['page'] = $page - 1; ?>
                <a href="?<?= http_build_query($query_params) ?>" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 bg-white hover:bg-gray-50 transition-colors">Previous</a>
            <?php endif; ?>
            <?php if($page < $total_pages): ?>
                <?php $query_params['page'] = $page + 1; ?>
                <a href="?<?= http_build_query($query_params) ?>" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 bg-white hover:bg-gray-50 transition-colors">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
