<?php
// config/db.php

// Define dynamic BASE_URL
$doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$base_dir = str_replace('\\', '/', dirname(__DIR__));
$base_url = str_replace($doc_root, '', $base_dir);
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "invoice_db";

// Disable default explicit exceptions in PHP 8.1+ to use our own error handling check
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . ". Have you imported the database.sql file?");
}

// Ensure settings table exists
$settings_sql = "CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $settings_sql);

// Seed default settings if empty
$check_settings = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM settings");
if ($check_settings) {
    $row = mysqli_fetch_assoc($check_settings);
    if ($row['cnt'] == 0) {
        $defaults = [
            'company_name' => 'My Company',
            'company_email' => 'contact@mycompany.com',
            'company_phone' => '+1 123 456 7890',
            'company_address' => '123 Business Rd, Tech City, USA',
            'toast_position' => 'top-0 end-0',
            'toast_bg_color' => '#198754',
            'toast_text_color' => '#ffffff'
        ];
        foreach ($defaults as $k => $v) {
            $kval = mysqli_real_escape_string($conn, $k);
            $vval = mysqli_real_escape_string($conn, $v);
            mysqli_query($conn, "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$kval', '$vval')");
        }
    }
}

// Ensure 2FA columns exist
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN two_factor_code VARCHAR(10) DEFAULT NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN two_factor_expires DATETIME DEFAULT NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) DEFAULT NULL");

// Encryption Key for secure cookies
if (!defined('APP_ENCRYPTION_KEY')) {
    define('APP_ENCRYPTION_KEY', 'xZ$9^mK8!wP2@vQ5#rY7*tN4&bC1(fG3');
}
?>
