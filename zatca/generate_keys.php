<?php
// zatca/generate_keys.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/zatca/CryptoService.php';

checkAuth();

// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect(BASE_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crypto = new ZATCA_CryptoService();
    
    // Generate Private Key
    $privateKey = $crypto->generatePrivateKey();
    
    // Get Company info for CSR
    $company_name = get_setting($conn, 'company_name', 'Default Company');
    $company_vat = get_setting($conn, 'company_vat', '300000000000003');
    $company_cr = get_setting($conn, 'company_cr', '1010010000');
    $company_city = get_setting($conn, 'company_city', 'Riyadh');
    $env = get_setting($conn, 'zatca_env', 'sandbox');
    
    $companyData = [
        'name' => $company_name,
        'common_name' => 'TST-886431145', // ZATCA test common name standard
        'branch' => 'Main Branch',
        'env' => $env == 'sandbox' ? 'PREZATCA' : 'ZATCA',
        'postal_code' => get_setting($conn, 'company_postal', '12222'),
        'cr' => $company_cr,
        'uuid' => '12345678-1234-1234-1234-123456789012', // Unique terminal ID
        'vat' => $company_vat,
        'city' => $company_city,
        'street' => get_setting($conn, 'company_street', 'Business Street')
    ];
    
    // Generate CSR
    $csr = $crypto->generateCsr($privateKey, $companyData);
    
    // Save to settings
    update_setting($conn, 'zatca_private_key', $privateKey);
    update_setting($conn, 'zatca_csr', $csr);
    
    logActivity($conn, $_SESSION['user_id'], 'ZATCA Setup', 'Generated new ECDSA Private Key and CSR');
    
    redirect(BASE_URL . '/settings.php?msg=' . urlencode('Cryptographic Keys generated successfully.'));
} else {
    redirect(BASE_URL . '/settings.php');
}
