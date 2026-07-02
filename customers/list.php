<?php
// customers/list.php
require_once __DIR__ . '/../includes/header.php';

$res = mysqli_query($conn, "SELECT * FROM customers ORDER BY id DESC");
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
        <p class="text-gray-500 mt-1">Manage your client list and details</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= BASE_URL ?>/customers/add.php" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Customer
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between space-y-3 sm:space-y-0">
        <div class="relative w-full sm:w-64">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" placeholder="Search customers..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <button class="flex items-center justify-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
            <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filter
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Customer</th>
                    <th scope="col" class="px-6 py-4 font-medium">Contact</th>
                    <th scope="col" class="px-6 py-4 font-medium">Joined</th>
                    <th scope="col" class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                <tr class="bg-white hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-4 text-gray-900 font-medium">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm uppercase mr-3">
                                <?= substr(htmlspecialchars($row['name']), 0, 2) ?>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="text-xs text-gray-500 font-normal">ID: #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col space-y-1">
                            <?php if(!empty($row['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="flex items-center text-gray-600 hover:text-brand-600 transition-colors">
                                <i data-lucide="mail" class="w-3 h-3 mr-2"></i> <?= htmlspecialchars($row['email']) ?>
                            </a>
                            <?php endif; ?>
                            <?php if(!empty($row['phone'])): ?>
                            <span class="flex items-center text-gray-600">
                                <i data-lucide="phone" class="w-3 h-3 mr-2"></i> <?= htmlspecialchars($row['phone']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <?= date('M d, Y', strtotime($row['created_at'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= $row['id'] ?>" class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </a>
                            <button type="button" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete" onclick="confirmDelete('<?= BASE_URL ?>/customers/delete.php?id=<?= $row['id'] ?>', 'Delete customer <?= htmlspecialchars($row['name'], ENT_QUOTES) ?>?')">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($res) == 0): ?>
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <i data-lucide="users" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <p>No customers found.</p>
                            <a href="<?= BASE_URL ?>/customers/add.php" class="text-brand-600 font-medium hover:underline mt-2">Add your first customer</a>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination placeholder -->
    <div class="p-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <div>Showing <?= mysqli_num_rows($res) ?> customers</div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
