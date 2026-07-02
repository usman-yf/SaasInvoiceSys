<?php
// verify.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Safe sanitization without importing the whole auth system
$token = isset($_GET['token']) ? mysqli_real_escape_string($conn, $_GET['token']) : '';
$passed_inv = isset($_GET['inv']) ? htmlspecialchars($_GET['inv']) : 'Safe Verification';

$inv_sql = "SELECT i.*, c.name, c.email, c.phone, c.address 
            FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.token = '$token'";
$res = mysqli_query($conn, $inv_sql);

$invoice_no = $passed_inv;
if (mysqli_num_rows($res) > 0) {
    // temporary row fetch to get correct invoice number for `<title>`
    $tmpRow = mysqli_fetch_assoc($res);
    $invoice_no = htmlspecialchars($tmpRow['invoice_no']);
    mysqli_data_seek($res, 0); // Reset pointer
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $header_company_name = get_setting($conn, 'company_name', 'Invoice Management System'); ?>
    <title>Verify Invoice <?= htmlspecialchars($invoice_no) ?> - <?= htmlspecialchars($header_company_name) ?></title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='13 2 3 14 12 14 11 22 21 10 12 10 13 2'/%3E%3C/svg%3E" type="image/svg+xml">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        @media print {
            body { background-color: #ffffff; }
            .hide-on-print { display: none !important; }
            #print-area { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen flex flex-col py-10">
    
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-8 hide-on-print flex flex-col items-center justify-center">
            <div class="w-12 h-12 bg-brand-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-brand-200 mb-3">
                <i data-lucide="receipt" class="w-6 h-6"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">InvSys Verification</h2>
        </div>

        <?php if (mysqli_num_rows($res) == 0): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hide-on-print max-w-lg mx-auto">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="x-circle" class="w-8 h-8"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Invalid Verification Link</h4>
                    <p class="text-gray-500 text-sm">The secure token provided is invalid or has expired. We could not find a matching record in our system.</p>
                </div>
            </div>
        <?php else:
            $invoice = mysqli_fetch_assoc($res);
            $id = (int) $invoice['id'];

            $items_sql = "SELECT ii.*, p.name as product_name, p.sku 
                        FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = $id";
            $items_res = mysqli_query($conn, $items_sql);
            
            $company_name = htmlspecialchars(get_setting($conn, 'company_name', 'InvSys Company'));
            $company_email = htmlspecialchars(get_setting($conn, 'company_email', 'contact@invsys.com'));
            $company_phone = htmlspecialchars(get_setting($conn, 'company_phone', ''));
            $company_address = nl2br(htmlspecialchars(get_setting($conn, 'company_address', '')));
        ?>
            
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-8 flex items-center justify-center hide-on-print max-w-2xl mx-auto shadow-sm">
                <i data-lucide="shield-check" class="w-6 h-6 text-green-600 mr-3"></i>
                <div>
                    <h4 class="text-green-800 font-bold text-sm">Verified Authentic</h4>
                    <p class="text-green-600 text-xs mt-0.5">This invoice was officially generated by our secure system.</p>
                </div>
            </div>

            <!-- Full Invoice Template -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 md:p-12" id="print-area">
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
                        <h4 class="text-xl font-bold text-gray-900 mb-2"><?= $company_name ?></h4>
                        <div class="text-sm text-gray-500 space-y-1 mb-6 text-left sm:text-right">
                            <p><?= nl2br($company_address) ?></p>
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

                <div class="flex flex-col md:flex-row justify-between items-start">
                    <div class="w-full md:w-1/2 mb-8 md:mb-0">
                        <p class="text-sm text-gray-500 mb-6 max-w-sm">Thank you for your business. Please remit payment within 14 days of receiving this invoice.</p>
                        <?php
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $veri_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/verify.php?token=" . urlencode($invoice['token']) . "&inv=" . urlencode($invoice['invoice_no']);
                        $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&ecc=H&data=" . urlencode($veri_url);
                        ?>
                        <div class="flex space-x-4">
                            <div class="inline-block p-2 border border-gray-100 rounded-xl bg-white shadow-sm text-center">
                                <img src="<?= htmlspecialchars($qr_api_url) ?>" alt="QR Code"
                                    class="w-28 h-28 mb-1 mx-auto opacity-90" crossorigin="anonymous">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Scan to Verify</span>
                            </div>
                            <?php if (!empty($invoice['qr_code_tlv'])): ?>
                                <div class="inline-block p-2 border border-gray-100 rounded-xl bg-white shadow-sm text-center">
                                    <div id="zatca-qr-container"
                                        class="w-28 h-28 mb-1 mx-auto flex items-center justify-center overflow-hidden"
                                        data-qr="<?= htmlspecialchars($invoice['qr_code_tlv']) ?>"></div>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">ZATCA QR</span>
                                </div>
                            <?php endif; ?>
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

        <?php endif; ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var qrContainer = document.getElementById("zatca-qr-container");
            if (qrContainer) {
                var qrData = qrContainer.getAttribute("data-qr");
                if (qrData) {
                    new QRCode(qrContainer, {
                        text: qrData,
                        width: 256,
                        height: 256,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    
                    var canvas = qrContainer.querySelector('canvas');
                    var img = qrContainer.querySelector('img');
                    if (canvas) { canvas.style.width = '100%'; canvas.style.height = '100%'; }
                    if (img) { img.style.width = '100%'; img.style.height = '100%'; }
                }
            }
        });
    </script>
</body>
</html>