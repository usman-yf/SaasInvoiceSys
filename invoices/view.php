<?php
// invoices/view.php
require_once __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$inv_sql = "SELECT i.*, c.name, c.email, c.phone, c.address 
            FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = $id";
$res = mysqli_query($conn, $inv_sql);

if (mysqli_num_rows($res) == 0) {
    echo "<div class='alert alert-danger mt-4'>Invoice not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$invoice = mysqli_fetch_assoc($res);

$items_sql = "SELECT ii.*, p.name as product_name, p.sku 
              FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = $id";
$items_res = mysqli_query($conn, $items_sql);

// Fetch Company Details
$company_name = get_setting($conn, 'company_name', 'Tech Solutions Ltd.');
$company_email = get_setting($conn, 'company_email', 'info@techsolutions.com');
$company_phone = get_setting($conn, 'company_phone', '+92 300 1234567');
$company_address = get_setting($conn, 'company_address', "123 Business Street\nLahore, Pakistan");
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0 d-print-none">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Invoice Preview</h1>
        <p class="text-gray-500 mt-1">Review and manage this invoice</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($invoice['status'] == 'Unpaid'): ?>
            <a href="<?= BASE_URL ?>/invoices/edit.php?id=<?= $id ?>" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center text-sm shadow-sm">
                <i data-lucide="edit" class="w-4 h-4 mr-2 text-gray-500"></i> Edit
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/payments/payments.php?invoice_id=<?= $id ?>" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center text-sm shadow-sm">
            <i data-lucide="credit-card" class="w-4 h-4 mr-2 text-gray-500"></i> Payments
        </a>
        <button type="button" onclick="openEmailModal(<?= $id ?>, '<?= htmlspecialchars($invoice['email']) ?>')" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center text-sm shadow-sm">
            <i data-lucide="mail" class="w-4 h-4 mr-2 text-gray-500"></i> Email
        </button>
        <button onclick="window.print()" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center text-sm shadow-sm">
            <i data-lucide="printer" class="w-4 h-4 mr-2 text-gray-500"></i> Print
        </button>
        <button onclick="downloadPDF()" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center text-sm shadow-sm">
            <i data-lucide="download" class="w-4 h-4 mr-2 text-gray-500"></i> Download PDF
        </button>
        <a href="<?= BASE_URL ?>/invoices/list.php" class="px-3 py-2 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors flex items-center text-sm ml-2">
            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Close
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-float border border-gray-100 overflow-hidden mb-8" id="print-area">
    <div class="p-8 sm:p-12">
        <div class="flex justify-between items-start mb-12 flex-col sm:flex-row">
            <div>
                <h2 class="text-3xl font-black text-brand-600 tracking-tight mb-8 uppercase">INVOICE</h2>
                <div class="text-sm text-gray-500 space-y-1">
                    <p class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2">Billed To:</p>
                    <p class="font-bold text-gray-900 text-base"><?= htmlspecialchars($invoice['name']) ?></p>
                    <p class="max-w-[200px]"><?= nl2br(htmlspecialchars($invoice['address'])) ?></p>
                    <p><?= htmlspecialchars($invoice['email']) ?></p>
                    <p><?= htmlspecialchars($invoice['phone']) ?></p>
                </div>
            </div>

            <div class="text-left sm:text-right w-full sm:w-auto mt-8 sm:mt-0 flex flex-col sm:items-end">
                <h4 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($company_name) ?></h4>
                <div class="text-sm text-gray-500 space-y-1 mb-6 text-left sm:text-right">
                    <p><?= nl2br(htmlspecialchars($company_address)) ?></p>
                    <p><?= htmlspecialchars($company_email) ?></p>
                    <p><?= htmlspecialchars($company_phone) ?></p>
                </div>
                
                <div class="bg-gray-50/80 rounded-2xl p-5 border border-gray-100 w-full sm:w-80 text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Invoice Number</span>
                        <span class="font-bold text-gray-900"><?= htmlspecialchars($invoice['invoice_no']) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Invoice Date</span>
                        <span class="font-bold text-gray-900"><?= date('Y-m-d', strtotime($invoice['date'])) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Due Date</span>
                        <span class="font-bold text-gray-900"><?= date('Y-m-d', strtotime($invoice['date'] . ' + 14 days')) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 pt-3">
                        <span class="text-gray-500">Status</span>
                        <?php if ($invoice['status'] == 'Paid'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 uppercase tracking-wide">Paid</span>
                        <?php elseif ($invoice['status'] == 'Partial'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 uppercase tracking-wide">Partial</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 uppercase tracking-wide">Unpaid</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 mb-10 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 font-medium text-center w-12">#</th>
                        <th class="px-6 py-4 font-medium">Item Description</th>
                        <th class="px-6 py-4 font-medium text-center">Qty</th>
                        <th class="px-6 py-4 font-medium text-right">Rate</th>
                        <th class="px-6 py-4 font-medium text-right">Tax</th>
                        <th class="px-6 py-4 font-medium text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    <?php
                    $counter = 1;
                    while ($item = mysqli_fetch_assoc($items_res)):
                        $base = $item['price'] * $item['quantity'];
                        $tax_val = $item['total'] - $base;
                        $tax_pct = ($base > 0) ? round(($tax_val / $base) * 100, 2) : 0;
                    ?>
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 text-center text-gray-400"><?= $counter++ ?></td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900 text-base"><?= htmlspecialchars($item['product_name']) ?></p>
                                <?= $item['sku'] ? "<p class='text-xs text-gray-500 mt-0.5'>SKU: {$item['sku']}</p>" : "" ?>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700"><?= number_format($item['quantity'], 2) ?></td>
                            <td class="px-6 py-4 text-right text-gray-700"><?= htmlspecialchars($global_currency) ?> <?= number_format($item['price'], 2) ?></td>
                            <td class="px-6 py-4 text-right text-gray-500 text-xs"><?= $tax_pct ?>%</td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900"><?= htmlspecialchars($global_currency) ?> <?= number_format($item['total'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-end">
            <div class="w-full md:w-1/2 mb-8 md:mb-0">
                <p class="text-sm text-gray-500 mb-6 max-w-sm">Thank you for your business. Please remit payment within 14 days of receiving this invoice.</p>
                <?php
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $veri_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/verify.php?token=" . urlencode($invoice['token']) . "&inv=" . urlencode($invoice['invoice_no']);
                $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&ecc=L&data=" . urlencode($veri_url);
                ?>
                <div class="inline-block p-2 border border-gray-100 rounded-xl bg-white shadow-sm text-center">
                    <img src="<?= htmlspecialchars($qr_api_url) ?>" alt="QR Code" class="w-20 h-20 mb-1 mx-auto opacity-90" crossorigin="anonymous">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Scan to Verify</span>
                </div>
            </div>
            
            <div class="w-full md:w-5/12 text-sm">
                <div class="flex justify-between py-2 text-gray-500">
                    <span>Subtotal</span>
                    <span class="text-gray-900"><?= htmlspecialchars($global_currency) ?> <?= number_format($invoice['subtotal'], 2) ?></span>
                </div>
                <div class="flex justify-between py-2 text-gray-500">
                    <span>Tax</span>
                    <span class="text-gray-900"><?= htmlspecialchars($global_currency) ?> <?= number_format($invoice['tax'], 2) ?></span>
                </div>
                <div class="flex justify-between py-2 text-gray-500">
                    <span>Discount</span>
                    <span class="text-gray-900">-<?= htmlspecialchars($global_currency) ?> <?= number_format($invoice['discount'], 2) ?></span>
                </div>
                <div class="flex justify-between py-4 mt-2 border-t border-gray-100">
                    <span class="text-base font-bold text-gray-900">Total</span>
                    <span class="text-xl font-bold text-brand-500"><?= htmlspecialchars($global_currency) ?> <?= number_format($invoice['total'], 2) ?></span>
                </div>
                <?php if ($invoice['status'] == 'Paid'): ?>
                    <div class="flex justify-between items-center py-3 px-4 mt-2 bg-green-50/50 rounded-xl border border-green-100/50 text-green-700">
                        <span class="flex items-center text-sm font-medium">
                            <i data-lucide="check-circle-2" class="w-4 h-4 mr-2"></i> Amount Paid
                        </span>
                        <span class="font-bold text-sm"><?= htmlspecialchars($global_currency) ?> <?= number_format($invoice['total'], 2) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-16 pt-8 border-t border-gray-100 text-center space-y-1">
            <p class="text-xs text-gray-400 font-medium">This is a computer-generated document and does not require a signature.</p>
            <p class="text-xs text-gray-400 font-medium">&copy; <?= date('Y') ?> <?= htmlspecialchars($company_name) ?>. All rights reserved.</p>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background-color: #fff !important;
        }
        aside, header, .d-print-none, #globalToast {
            display: none !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
        #print-area {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.getElementById('print-area');
        const opt = {
            margin: 0,
            filename: '<?= $invoice['invoice_no'] ?>.pdf',
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 3, useCORS: true, letterRendering: true, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    <?php if (isset($_GET['download_pdf']) && $_GET['download_pdf'] == '1'): ?>
        document.addEventListener("DOMContentLoaded", function () {
            // Give image time to load before capturing
            setTimeout(function () {
                downloadPDF();
                setTimeout(function () {
                    window.location.href = BASE_URL + '/invoices/list.php';
                }, 1000);
            }, 1000); 
        });
    <?php endif; ?>
</script>

<!-- Premium Tailwind Email Modal -->
<div id="emailModal" class="fixed inset-0 z-[100] hidden">
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity opacity-0" id="emailModalBackdrop"></div>
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
            
            <form action="<?= BASE_URL ?>/invoices/email.php" method="POST" onsubmit="showLoadingOverlay()">
                <div class="p-6">
                    <input type="hidden" name="id" id="modal_invoice_id" value="<?= $id ?>">
                    <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/invoices/view.php?id=<?= $id ?>">
                    
                    <div class="mb-4">
                        <label for="modal_email_to" class="flex items-center text-sm font-medium text-gray-700 mb-2">Recipient Email Address <i data-lucide="info" class="w-4 h-4 ml-2 text-gray-400" title="The email address for communication and notifications."></i> <span class="text-red-500">*</span></label>
                        <input type="email" id="modal_email_to" name="email_to" value="<?= htmlspecialchars($invoice['email']) ?>" required 
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
        void modal.offsetWidth;
        
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