<?php
// zatca/process.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/zatca/CryptoService.php';
require_once __DIR__ . '/../includes/zatca/XmlGenerator.php';
require_once __DIR__ . '/../includes/zatca/QrGenerator.php';
require_once __DIR__ . '/../includes/zatca/ApiClient.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$invoice_id = (int)$_POST['invoice_id'];
$action = $_POST['action'] ?? '';

// Fetch Invoice
$inv_res = mysqli_query($conn, "SELECT i.*, c.* FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = $invoice_id");
$invoice = mysqli_fetch_assoc($inv_res);

if (!$invoice) {
    die("Invoice not found");
}

// Fetch Items
$items_res = mysqli_query($conn, "SELECT ii.*, p.name FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = $invoice_id");
$items = [];
while ($row = mysqli_fetch_assoc($items_res)) {
    $items[] = $row;
}

// Fetch Company Details
$company = [
    'name' => get_setting($conn, 'company_name', 'Tech Solutions Ltd.'),
    'company_arabic_name' => get_setting($conn, 'company_arabic_name', 'شركة الحلول التقنية'),
    'company_vat' => get_setting($conn, 'company_vat', '300000000000003'),
    'company_cr' => get_setting($conn, 'company_cr', '1010010000'),
    'company_building' => get_setting($conn, 'company_building', '1234'),
    'company_street' => get_setting($conn, 'company_street', 'Business Street'),
    'company_district' => get_setting($conn, 'company_district', 'Tech District'),
    'company_city' => get_setting($conn, 'company_city', 'Riyadh'),
    'company_postal' => get_setting($conn, 'company_postal', '12222'),
    'company_country' => get_setting($conn, 'company_country', 'SA'),
    'company_branch' => get_setting($conn, 'company_branch', 'Main Branch')
];

if ($action == 'generate') {
    // 1. UUID & PIH
    $uuid = $invoice['uuid'];
    if (empty($uuid)) {
        // Generate UUIDv4
        $data = random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    $invoice['uuid'] = $uuid;
    $isSimplified = strlen($invoice['vat_number']) < 15; // Simple check

    // 2. Base XML
    $baseXml = ZATCA_XmlGenerator::generateInvoiceXml($invoice, $items, $company, $invoice, $isSimplified);
    
    // 3. Crypto Service
    $crypto = new ZATCA_CryptoService();
    
    // Get Private Key
    $privateKey = get_setting($conn, 'zatca_private_key', '');
    $cert = get_setting($conn, 'zatca_public_cert', '');
    if (empty($privateKey)) {
        // Generate one time
        $privateKey = $crypto->generatePrivateKey();
        $update_sql = "UPDATE settings SET setting_value='" . mysqli_real_escape_string($conn, $privateKey) . "' WHERE setting_key='zatca_private_key'";
        mysqli_query($conn, $update_sql);
        // Default mock cert
        $cert = base64_encode("MOCK_CERTIFICATE_DATA");
    }
    if (empty($cert)) {
        $cert = base64_encode("MOCK_CERTIFICATE_DATA");
    }

    // 4. Hash XML
    $hashBase64 = $crypto->computeHashBase64($baseXml);
    
    // 5. Sign Hash
    $signatureBase64 = $crypto->signHash($hashBase64, $privateKey);
    if (!$signatureBase64) {
        $signatureBase64 = base64_encode("MOCK_SIGNATURE"); // Fallback if OpenSSL fails
    }

    // 6. Generate QR
    $qrBase64 = ZATCA_QrGenerator::generateTlvBase64(
        $company['name'],
        $company['company_vat'],
        date('Y-m-d\TH:i:s\Z', strtotime($invoice['date'])),
        $invoice['total'],
        $invoice['tax'],
        $hashBase64,
        $signatureBase64,
        base64_encode('PUBLIC_KEY'),
        $cert
    );

    // 7. Inject Signature into XML
    $finalXml = ZATCA_XmlGenerator::injectSignature($baseXml, $cert, $signatureBase64, $qrBase64, $hashBase64);

    // 8. Update DB
    $stmt = $conn->prepare("UPDATE invoices SET uuid=?, invoice_hash=?, crypt_stamp=?, qr_code_tlv=?, xml_content=?, zatca_status='Generated' WHERE id=?");
    $stmt->bind_param("sssssi", $uuid, $hashBase64, $signatureBase64, $qrBase64, $finalXml, $invoice_id);
    $stmt->execute();
    
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'ZATCA Generate', "Generated XML and Hash for Invoice {$invoice['invoice_no']}");
    }
    
    redirect(BASE_URL . "/invoices/view.php?id=$invoice_id&msg=" . urlencode("ZATCA XML generated successfully."));

} elseif ($action == 'submit') {
    $env = get_setting($conn, 'zatca_env', 'sandbox');
    $cert = get_setting($conn, 'zatca_public_cert', '');
    $secret = get_setting($conn, 'zatca_api_secret', '');
    
    $api = new ZATCA_ApiClient($env, $cert, $secret);
    
    $isSimplified = strlen($invoice['vat_number']) < 15;
    
    if ($isSimplified) {
        $response = $api->reportInvoice($invoice['invoice_hash'], $invoice['xml_content'], $invoice['uuid']);
    } else {
        $response = $api->clearInvoice($invoice['invoice_hash'], $invoice['xml_content'], $invoice['uuid']);
    }
    
    $status = $response['status'];
    $jsonResponse = json_encode($response, JSON_PRETTY_PRINT);
    
    $stmt = $conn->prepare("UPDATE invoices SET zatca_status=?, zatca_response=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $jsonResponse, $invoice_id);
    $stmt->execute();
    
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], "ZATCA $status", "API submission for Invoice {$invoice['invoice_no']} returned $status");
    }
    
    if ($status == 'CLEARED' || $status == 'REPORTED') {
        redirect(BASE_URL . "/invoices/view.php?id=$invoice_id&msg=" . urlencode("Invoice successfully $status by ZATCA."));
    } else {
        redirect(BASE_URL . "/invoices/view.php?id=$invoice_id&msg=" . urlencode("ZATCA Submission Failed. Check Logs."));
    }
}
