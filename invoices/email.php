<?php
// invoices/email.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

if ($id <= 0) {
    redirect('/inv/invoices/list.php');
}

$inv_sql = "SELECT i.*, c.name, c.email, c.phone, c.address 
            FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = $id";
$res = mysqli_query($conn, $inv_sql);

if (mysqli_num_rows($res) == 0) {
    redirect('/inv/invoices/list.php');
}

$invoice = mysqli_fetch_assoc($res);

// Use the email provided in the form, or fall back to the customer's default email
$to = isset($_POST['email_to']) && !empty($_POST['email_to']) ? sanitize($conn, $_POST['email_to']) : $invoice['email'];

if (empty($to)) {
    redirect('/inv/invoices/view.php?id=' . $id . '&msg=failed');
}

$subject = 'Invoice ' . $invoice['invoice_no'] . ' from InvSys Company';

// Fetch invoice items
$items_sql = "SELECT ii.*, p.name as product_name, p.sku 
              FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = $id";
$items_res = mysqli_query($conn, $items_sql);

// Status Badge Style
$bg_color = '#fee2e2'; // red-100
$text_color = '#b91c1c'; // red-700
if ($invoice['status'] == 'Paid') {
    $bg_color = '#dcfce7'; // green-100
    $text_color = '#15803d'; // green-700
} elseif ($invoice['status'] == 'Partial') {
    $bg_color = '#ffedd5'; // orange-100
    $text_color = '#c2410c'; // orange-700
}
$badge_style = "display: inline-block; padding: 4px 8px; font-size: 10px; font-weight: 700; line-height: 1; color: {$text_color}; text-align: center; white-space: nowrap; border-radius: 4px; background-color: {$bg_color}; text-transform: uppercase; letter-spacing: 0.5px;";

// Get company settings
$company_name = htmlspecialchars(get_setting($conn, 'company_name', 'InvSys Company'));
$company_email = htmlspecialchars(get_setting($conn, 'company_email', 'contact@invsys.com'));
$company_phone = htmlspecialchars(get_setting($conn, 'company_phone', ''));
$company_address = nl2br(htmlspecialchars(get_setting($conn, 'company_address', '')));

// Build comprehensive email HTML body with inline CSS
$body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Invoice " . htmlspecialchars($invoice['invoice_no']) . "</title>
</head>
<body style='font-family: \"Inter\", Arial, sans-serif; background-color: #f8fafc; padding: 40px 20px; color: #1e293b; margin: 0;'>

<div style='max-width: 800px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 40px;'>
        <tr>
            <td width='50%' valign='top'>
                <h2 style='color: #7c3aed; margin-top: 0; margin-bottom: 30px; font-size: 28px; font-weight: 900; letter-spacing: -0.5px; text-transform: uppercase;'>INVOICE</h2>
                <div style='font-weight: 700; color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;'>Billed To:</div>
                <div style='margin-bottom: 4px;'><strong style='font-size: 16px; color: #0f172a;'>" . htmlspecialchars($invoice['name']) . "</strong></div>
                <div style='margin-bottom: 4px; color: #64748b; font-size: 14px; line-height: 1.5; max-width: 200px;'>" . nl2br(htmlspecialchars($invoice['address'])) . "</div>
                <div style='margin-bottom: 4px; color: #64748b; font-size: 14px;'>" . htmlspecialchars($invoice['email']) . "</div>
                <div style='color: #64748b; font-size: 14px;'>" . htmlspecialchars($invoice['phone']) . "</div>
            </td>
            <td width='50%' valign='top' align='right'>
                <h4 style='margin-top: 0; margin-bottom: 8px; font-size: 20px; color: #0f172a; font-weight: 700;'>" . $company_name . "</h4>
                <div style='color: #64748b; margin-bottom: 4px; font-size: 14px; line-height: 1.5;'>" . $company_address . "</div>
                <div style='color: #64748b; margin-bottom: 4px; font-size: 14px;'>" . $company_email . "</div>
                <div style='color: #64748b; margin-bottom: 24px; font-size: 14px;'>" . $company_phone . "</div>
                
                <table width='280' cellpadding='10' cellspacing='0' style='background-color: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; font-size: 13px; text-align: left; float: right;'>
                    <tr>
                        <td style='color: #64748b; border-bottom: 1px solid #f1f5f9;'>Invoice Number</td>
                        <td align='right' style='color: #0f172a; font-weight: 700; border-bottom: 1px solid #f1f5f9;'>" . htmlspecialchars($invoice['invoice_no']) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; border-bottom: 1px solid #f1f5f9;'>Invoice Date</td>
                        <td align='right' style='color: #0f172a; font-weight: 700; border-bottom: 1px solid #f1f5f9;'>" . explode(' ', $invoice['date'])[0] . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; border-bottom: 1px solid #f1f5f9;'>Due Date</td>
                        <td align='right' style='color: #0f172a; font-weight: 700; border-bottom: 1px solid #f1f5f9;'>" . date('Y-m-d', strtotime($invoice['date'] . ' + 14 days')) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; padding-top: 12px;'>Status</td>
                        <td align='right' style='padding-top: 12px;'><span style='{$badge_style}'>" . strtoupper(htmlspecialchars($invoice['status'])) . "</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width='100%' cellpadding='14' cellspacing='0' style='border-collapse: collapse; margin-bottom: 30px; font-size: 14px;'>
        <thead>
            <tr>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: center; width: 5%; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>#</th>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Item Description</th>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: center; width: 10%; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Qty</th>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: right; width: 15%; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Rate</th>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: right; width: 10%; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Tax</th>
                <th style='background-color: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: right; width: 20%; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>Amount</th>
            </tr>
        </thead>
        <tbody>";

$counter = 1;
while ($item = mysqli_fetch_assoc($items_res)) {
    $base = $item['price'] * $item['quantity'];
    $tax_val = $item['total'] - $base;
    $tax_pct = ($base > 0) ? round(($tax_val / $base) * 100, 2) : 0;
    
    $sku_html = $item['sku'] ? "<br><span style='font-size: 12px; color: #94a3b8;'>SKU: {$item['sku']}</span>" : "";
    $body .= "
            <tr>
                <td style='text-align: center; border-bottom: 1px solid #f1f5f9; color: #64748b;'>{$counter}</td>
                <td style='border-bottom: 1px solid #f1f5f9; color: #1e293b;'><strong style='font-weight: 600;'>" . htmlspecialchars($item['product_name']) . "</strong>{$sku_html}</td>
                <td style='text-align: center; border-bottom: 1px solid #f1f5f9; color: #64748b;'>" . number_format($item['quantity'], 2) . "</td>
                <td style='text-align: right; border-bottom: 1px solid #f1f5f9; color: #64748b;'><?= htmlspecialchars($global_currency) ?> " . number_format($item['price'], 2) . "</td>
                <td style='text-align: right; border-bottom: 1px solid #f1f5f9; color: #64748b;'>" . $tax_pct . "%</td>
                <td style='text-align: right; border-bottom: 1px solid #f1f5f9; color: #0f172a; font-weight: 500;'><?= htmlspecialchars($global_currency) ?> " . number_format($item['total'], 2) . "</td>
            </tr>";
    $counter++;
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$veri_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/inv/verify.php?token=" . urlencode($invoice['token']) . "&inv=" . urlencode($invoice['invoice_no']);
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&ecc=L&data=" . urlencode($veri_url);

$body .= "
        </tbody>
    </table>

    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 30px;'>
        <tr>
            <td width='50%' valign='top'>
                <div style='color: #64748b; font-size: 13px; line-height: 1.6; padding-right: 40px; margin-bottom: 20px;'>
                    Thank you for your business. Please remit payment within 14 days of receiving this invoice.
                </div>
                <div style='display: inline-block; padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);'>
                    <img src='" . $qr_api_url . "' alt='QR Code' width='80' height='80' style='display: block; margin: 0 auto 5px auto;'>
                    <span style='font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;'>Scan to Verify</span>
                </div>
            </td>
            <td width='50%' valign='top' align='right'>
                <table width='100%' cellpadding='8' cellspacing='0' style='font-size: 14px;'>
                    <tr>
                        <td align='right' style='color: #64748b;' width='50%'>Subtotal</td>
                        <td align='right' style='color: #475569; font-weight: 500; white-space: nowrap;'><?= htmlspecialchars($global_currency) ?> " . number_format($invoice['subtotal'], 2) . "</td>
                    </tr>
                    <tr>
                        <td align='right' style='color: #64748b;'>Tax</td>
                        <td align='right' style='color: #475569; font-weight: 500; white-space: nowrap;'><?= htmlspecialchars($global_currency) ?> " . number_format($invoice['tax'], 2) . "</td>
                    </tr>
                    <tr>
                        <td align='right' style='color: #64748b; padding-bottom: 15px;'>Discount</td>
                        <td align='right' style='color: #475569; font-weight: 500; white-space: nowrap; padding-bottom: 15px;'><?= htmlspecialchars($global_currency) ?> " . number_format($invoice['discount'], 2) . "</td>
                    </tr>
                    <tr>
                        <td align='right' style='border-top: 1px solid #e2e8f0; padding-top: 15px; font-weight: 700; font-size: 16px; color: #0f172a;'>Total</td>
                        <td align='right' style='border-top: 1px solid #e2e8f0; padding-top: 15px; color: #7c3aed; font-weight: 700; font-size: 16px; white-space: nowrap;'><?= htmlspecialchars($global_currency) ?> " . number_format($invoice['total'], 2) . "</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style='text-align: center; margin-top: 50px; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; padding-top: 20px;'>
        <p style='margin: 0 0 5px 0;'>This is a computer-generated document and does not require a signature.</p>
        <p style='margin: 0;'>&copy; " . date('Y') . " " . $company_name . ". All rights reserved.</p>
    </div>

</div>

</body>
</html>
";

// Load config and PHPMailer library
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // Server settings
    // Ignore self signed certs for XAMPP local testing sometimes
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;

    // Recipients
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($to, $invoice['name']); // the customer name extracted from $invoice

    // Content
    $mail->isHTML(true);                                  
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $body));

    $mail->send();
    $redir = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '/inv/invoices/view.php?id=' . $id;
    $redir_char = strpos($redir, '?') !== false ? '&' : '?';
    
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'Invoice Emailed', "Emailed Invoice #{$invoice['invoice_no']} to {$to}");
    }
    
    redirect($redir . $redir_char . 'msg=emailed&to=' . urlencode($to));
} catch (\Exception $e) {
    // Optionally log error $mail->ErrorInfo
    $redir = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '/inv/invoices/view.php?id=' . $id;
    $redir_char = strpos($redir, '?') !== false ? '&' : '?';
    redirect($redir . $redir_char . 'msg=failed&to=' . urlencode($to));
}
?>
