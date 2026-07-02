<?php
// invoices/list.php
require_once __DIR__ . '/../includes/header.php';

$sql = "SELECT i.*, c.name as customer_name, c.email as customer_email,
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = i.id) as paid_amount
        FROM invoices i 
        JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.id DESC";
$res = mysqli_query($conn, $sql);
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <p class="text-gray-500 mt-1">Manage and track all your invoices</p>
    </div>
    <div class="flex space-x-3">
        <a href="/inv/invoices/create.php" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm hover:shadow transition-all flex items-center">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Invoice
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between space-y-3 sm:space-y-0">
        <div class="relative w-full sm:w-64">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" placeholder="Search invoices..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <button class="flex items-center justify-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
            <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filter
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Invoice #</th>
                    <th scope="col" class="px-6 py-4 font-medium">Customer</th>
                    <th scope="col" class="px-6 py-4 font-medium">Date</th>
                    <th scope="col" class="px-6 py-4 font-medium">Total</th>
                    <th scope="col" class="px-6 py-4 font-medium">Due</th>
                    <th scope="col" class="px-6 py-4 font-medium">Status</th>
                    <th scope="col" class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while($row = mysqli_fetch_assoc($res)): 
                    $due = $row['total'] - $row['paid_amount'];
                ?>
                <tr class="bg-white hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-4 font-medium text-brand-600 whitespace-nowrap">
                        <?= htmlspecialchars($row['invoice_no']) ?>
                    </td>
                    <td class="px-6 py-4 text-gray-900 font-medium flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-xs uppercase mr-3">
                            <?= substr(htmlspecialchars($row['customer_name']), 0, 1) ?>
                        </div>
                        <?= htmlspecialchars($row['customer_name']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= date('M d, Y', strtotime($row['date'])) ?>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <?= htmlspecialchars($global_currency) ?> <?= number_format($row['total'], 2) ?>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        <?= htmlspecialchars($global_currency) ?> <?= number_format($due, 2) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($row['status'] == 'Paid'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                Paid
                            </span>
                        <?php elseif ($row['status'] == 'Partial'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                Partial
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                Unpaid
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="/inv/invoices/view.php?id=<?= $row['id'] ?>" class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="View">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <?php if ($row['status'] == 'Unpaid'): ?>
                                <a href="/inv/invoices/edit.php?id=<?= $row['id'] ?>" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            <?php else: ?>
                                <span class="p-2 text-gray-300" title="Locked: Payment Received">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </span>
                            <?php endif; ?>
                            <a href="/inv/invoices/view.php?id=<?= $row['id'] ?>&download_pdf=1" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Download PDF">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </a>
                            <button type="button" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Email" onclick="openEmailModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['customer_email']) ?>')">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </button>
                            <button type="button" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete" onclick="confirmDelete('/inv/invoices/delete.php?id=<?= $row['id'] ?>', 'Delete invoice <?= htmlspecialchars($row['invoice_no'], ENT_QUOTES) ?>?')">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($res) == 0): ?>
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <i data-lucide="inbox" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <p>No invoices found.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination placeholder -->
    <div class="p-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
        <div>Showing <?= mysqli_num_rows($res) ?> invoices</div>
    </div>
</div>

<!-- Premium Tailwind Email Modal -->
<div id="emailModal" class="fixed inset-0 z-[100] hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity opacity-0" id="emailModalBackdrop"></div>
    
    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-float w-full max-w-md transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="emailModalContent">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                    <i data-lucide="send" class="w-5 h-5 mr-2 text-brand-600"></i> Send Invoice
                </h3>
                <button type="button" onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form action="/inv/invoices/email.php" method="POST" onsubmit="showLoadingOverlay()">
                <div class="p-6">
                    <input type="hidden" name="id" id="modal_invoice_id" value="">
                    <!-- Redirect back to list -->
                    <input type="hidden" name="redirect_to" value="/inv/invoices/list.php">
                    
                    <div class="mb-4">
                        <label for="modal_email_to" class="block text-sm font-medium text-gray-700 mb-2">Recipient Email Address <span class="text-red-500">*</span></label>
                        <input type="email" id="modal_email_to" name="email_to" value="" required 
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-3 transition-colors outline-none">
                        <p class="mt-2 text-xs text-gray-500">The invoice PDF will be attached to this email.</p>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50 rounded-b-3xl">
                    <button type="button" onclick="closeEmailModal()" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-600 text-white font-medium rounded-xl hover:bg-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 flex items-center">
                        <i data-lucide="paperclip" class="w-4 h-4 mr-2"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Premium Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 z-[200] hidden items-center justify-center bg-white/80 backdrop-blur-sm">
    <div class="flex flex-col items-center bg-white p-8 rounded-3xl shadow-float border border-gray-100">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-brand-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <div class="text-gray-900 font-bold text-lg">Sending Email...</div>
        <p class="text-sm text-gray-500 mt-1">Please wait while we generate the PDF.</p>
    </div>
</div>

<script>
    function openEmailModal(id, email) {
        document.getElementById('modal_invoice_id').value = id;
        document.getElementById('modal_email_to').value = email;
        
        const modal = document.getElementById('emailModal');
        const backdrop = document.getElementById('emailModalBackdrop');
        const content = document.getElementById('emailModalContent');
        
        modal.classList.remove('hidden');
        void modal.offsetWidth; // trigger reflow
        
        backdrop.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }
    
    function closeEmailModal() {
        const modal = document.getElementById('emailModal');
        const backdrop = document.getElementById('emailModalBackdrop');
        const content = document.getElementById('emailModalContent');
        
        backdrop.classList.add('opacity-0');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    
    function showLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        closeEmailModal();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
