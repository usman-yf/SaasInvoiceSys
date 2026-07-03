<?php
// users/list.php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='container mt-5'><div class='alert alert-danger'>Access Denied. Admins only.</div></div>");
}

$sql = "SELECT id, full_name, email, phone, role, email_verified, admin_verified FROM users ORDER BY id ASC";
$res = mysqli_query($conn, $sql);
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
        <p class="text-gray-500 mt-1">Manage system users, roles, and permissions</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= BASE_URL ?>/users/create.php" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center">
            <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Add New User
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between space-y-3 sm:space-y-0">
        <div class="relative w-full sm:w-64">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" placeholder="Search users..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <button class="flex items-center justify-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
            <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filter
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">User</th>
                    <th scope="col" class="px-6 py-4 font-medium">Contact</th>
                    <th scope="col" class="px-6 py-4 font-medium">Role</th>
                    <th scope="col" class="px-6 py-4 font-medium">Status</th>
                    <th scope="col" class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                <tr class="bg-white hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-4 text-gray-900 font-medium">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm uppercase mr-3">
                                <?= substr(htmlspecialchars($row['full_name']), 0, 2) ?>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 flex items-center">
                                    <?= htmlspecialchars($row['full_name']) ?>
                                    <?php if($row['id'] == $_SESSION['user_id']): ?>
                                        <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">You</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500 font-normal">ID: #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col space-y-1">
                            <?php if(!empty($row['email'])): ?>
                            <span class="flex items-center text-gray-600">
                                <i data-lucide="mail" class="w-3 h-3 mr-2"></i> <?= htmlspecialchars($row['email']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if(!empty($row['phone'])): ?>
                            <span class="flex items-center text-gray-600">
                                <i data-lucide="phone" class="w-3 h-3 mr-2"></i> <?= htmlspecialchars($row['phone']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $row['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                            <?= strtoupper($row['role']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col space-y-2">
                            <div id="status-email-<?= $row['id'] ?>" class="flex items-center text-xs">
                                <?php if($row['email_verified']): ?>
                                    <span class="inline-flex items-center text-green-600 font-medium">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 mr-1"></i> Email
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center text-yellow-600 font-medium">
                                        <i data-lucide="clock" class="w-4 h-4 mr-1"></i> Email
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div id="status-admin-<?= $row['id'] ?>" class="flex items-center text-xs">
                                <?php if($row['admin_verified']): ?>
                                    <span class="inline-flex items-center text-green-600 font-medium">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 mr-1"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center text-red-600 font-medium">
                                        <i data-lucide="clock" class="w-4 h-4 mr-1"></i> Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="<?= BASE_URL ?>/users/edit.php?id=<?= $row['id'] ?>" class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Edit User">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </a>
                            
                            <?php if($row['id'] != $_SESSION['user_id']): ?>
                                <a href="<?= BASE_URL ?>/users/verify_admin.php?id=<?= $row['id'] ?>" class="p-2 text-gray-400 hover:text-<?= $row['admin_verified'] ? 'yellow' : 'green' ?>-600 hover:bg-<?= $row['admin_verified'] ? 'yellow' : 'green' ?>-50 rounded-lg transition-colors" title="<?= $row['admin_verified'] ? 'Revoke Admin Approval' : 'Grant Admin Approval' ?>">
                                    <i data-lucide="<?= $row['admin_verified'] ? 'x-circle' : 'check-circle' ?>" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                            
                            <div id="action-email-<?= $row['id'] ?>" class="flex">
                            <?php if($row['email_verified'] == 0): ?>
                                <a href="<?= BASE_URL ?>/users/resend_verify.php?id=<?= $row['id'] ?>" class="flex items-center justify-center p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Resend Verification Email" onclick="showLoadingOverlay()">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                            </div>

                            <?php if($row['id'] != $_SESSION['user_id']): ?>
                                <button type="button" onclick="confirmDelete('<?= BASE_URL ?>/users/delete.php?id=<?= $row['id'] ?>', 'Delete user <?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>?')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete User">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="p-2 text-gray-300 cursor-not-allowed rounded-lg" disabled title="Cannot delete yourself">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($res) == 0): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <i data-lucide="users" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <p>No users found.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
// No need for Bootstrap tooltips, native title attributes are used with Lucide icons

function showLoadingOverlay() {
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}
</script>

<script>
// Auto-refresh logic for verification statuses
let currentStatuses = {};

// Store initial states so we know if they change
<?php
mysqli_data_seek($res, 0);
$initial = [];
while($r = mysqli_fetch_assoc($res)) {
    $initial[$r['id']] = [
        'email' => (int)$r['email_verified'],
        'admin' => (int)$r['admin_verified']
    ];
}
?>
let knownStatuses = <?= json_encode($initial) ?>;

setInterval(() => {
    fetch(BASE_URL + '/users/ajax_status.php')
        .then(response => response.json())
        .then(data => {
            if(data.error) return; // Ignore on auth failure

            for (let id in data) {
                if (knownStatuses[id]) {
                    // Check for Email Verification Change
                    if (data[id].email_verified !== knownStatuses[id].email) {
                        knownStatuses[id].email = data[id].email_verified;
                        
                        let emailStatusTd = document.getElementById('status-email-' + id);
                        let emailActionSpan = document.getElementById('action-email-' + id);
                        
                        if (emailStatusTd) {
                            if (knownStatuses[id].email === 1) {
                                emailStatusTd.innerHTML = '<span class="inline-flex items-center text-green-600 font-medium"><i data-lucide="check-circle-2" class="w-4 h-4 mr-1"></i> Email</span>';
                            } else {
                                emailStatusTd.innerHTML = '<span class="inline-flex items-center text-yellow-600 font-medium"><i data-lucide="clock" class="w-4 h-4 mr-1"></i> Email</span>';
                            }
                            lucide.createIcons();
                        }
                        
                        if (emailActionSpan) {
                            if (knownStatuses[id].email === 1) {
                                emailActionSpan.innerHTML = '';
                            } else {
                                emailActionSpan.innerHTML = '<a href="<?= BASE_URL ?>/users/resend_verify.php?id=' + id + '" class="flex items-center justify-center p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Resend Verification Email" onclick="showLoadingOverlay()"><i data-lucide="send" class="w-4 h-4"></i></a>';
                                lucide.createIcons();
                            }
                        }
                    }

                    // Check for Admin Verification Change
                    if (data[id].admin_verified !== knownStatuses[id].admin) {
                        knownStatuses[id].admin = data[id].admin_verified;
                        
                        let adminStatusTd = document.getElementById('status-admin-' + id);
                        if (adminStatusTd) {
                            if (knownStatuses[id].admin === 1) {
                                adminStatusTd.innerHTML = '<span class="inline-flex items-center text-green-600 font-medium"><i data-lucide="check-circle-2" class="w-4 h-4 mr-1"></i> Admin</span>';
                            } else {
                                adminStatusTd.innerHTML = '<span class="inline-flex items-center text-red-600 font-medium"><i data-lucide="clock" class="w-4 h-4 mr-1"></i> Admin</span>';
                            }
                            lucide.createIcons();
                        }
                    }
                }
            }
        })
        .catch(err => console.error('Status poll error:', err));
}, 3000);
</script>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(255, 255, 255, 0.7); backdrop-filter: blur(5px); z-index: 10000; align-items: center; justify-content: center; flex-direction: column;">
    <div class="animate-spin rounded-full h-12 w-12 border-4 border-brand-500 border-t-transparent mb-4"></div>
    <div class="text-brand-600 font-bold text-lg flex items-center">
        <i data-lucide="send" class="w-5 h-5 mr-2"></i> Resending Verification Email...
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
