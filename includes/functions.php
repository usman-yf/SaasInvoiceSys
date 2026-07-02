<?php
// includes/functions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($location) {
    header("Location: " . $location);
    exit();
}

function checkAuth() {
    global $conn;
    if (!isLoggedIn()) {
        if (isset($_COOKIE['remember_token']) && $conn) {
            $token = sanitize($conn, $_COOKIE['remember_token']);
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) DEFAULT NULL");
            $res = mysqli_query($conn, "SELECT * FROM users WHERE remember_token = '$token' LIMIT 1");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = isset($row['full_name']) ? $row['full_name'] : $row['username'];
                $_SESSION['role'] = $row['role'];
                return;
            }
        }
        redirect('/inv/auth/login.php');
    }
}

function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

function generateInvoiceNo($conn) {
    $ym = date('Ym');
    $result = mysqli_query($conn, "SELECT invoice_no FROM invoices WHERE invoice_no LIKE 'INV-$ym-%' ORDER BY id DESC LIMIT 1");
    
    if ($row = mysqli_fetch_assoc($result)) {
        $parts = explode('-', $row['invoice_no']);
        $lastNum = (int)end($parts);
        $nextId = $lastNum + 1;
    } else {
        $nextId = 1;
    }
    
    return 'INV-' . $ym . '-' . str_pad($nextId, 4, "0", STR_PAD_LEFT);
}

function get_setting($conn, $key, $default = '') {
    $safekey = sanitize($conn, $key);
    $res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$safekey'");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return $row['setting_value'];
    }
    return $default;
}

function set_setting($conn, $key, $value) {
    $safekey = sanitize($conn, $key);
    $safeval = sanitize($conn, $value);
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$safekey', '$safeval') 
            ON DUPLICATE KEY UPDATE setting_value = '$safeval'";
    return mysqli_query($conn, $sql);
}

function logActivity($conn, $user_id, $action, $details = '') {
    $action_clean = sanitize($conn, $action);
    $details_clean = sanitize($conn, $details);
    $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES ('$user_id', '$action_clean', '$details_clean')";
    $result = mysqli_query($conn, $sql);
    
    // Create a notification for admins about this activity
    if ($result && $user_id) {
        $msg = $action_clean . ": " . $details_clean;
        
        $title = $action;
        $desc = $details;
        
        if ($action === 'User Login') {
            // Get username
            $u_res = mysqli_query($conn, "SELECT full_name FROM users WHERE id = $user_id");
            if ($u = mysqli_fetch_assoc($u_res)) {
                $title = "User Login";
                $desc = $u['full_name'] . " has securely logged in.";
            }
        }
        
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
        if ($admin_res) {
            while($admin = mysqli_fetch_assoc($admin_res)) {
                addNotification($conn, $admin['id'], $title, $desc);
            }
        }
    }
    return $result;
}

function addNotification($conn, $user_id, $title, $message = '') {
    $title = sanitize($conn, $title);
    $message = sanitize($conn, $message);
    $sql = "INSERT INTO notifications (user_id, title, message) VALUES ('$user_id', '$title', '$message')";
    return mysqli_query($conn, $sql);
}

function fetch_url($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    } else {
        return @file_get_contents($url);
    }
}

// Global variables initialized after connection
global $conn;
if (isset($conn)) {
    $global_currency = get_setting($conn, 'currency', 'Rs');
}
// Encryption Helpers
function encrypt_data($data, $key = null) {
    if ($key === null) {
        $key = defined('APP_ENCRYPTION_KEY') ? APP_ENCRYPTION_KEY : 'default_fallback_key_123!';
    }
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data($data, $key = null) {
    if ($key === null) {
        $key = defined('APP_ENCRYPTION_KEY') ? APP_ENCRYPTION_KEY : 'default_fallback_key_123!';
    }
    $decoded = base64_decode($data);
    if (strpos($decoded, '::') === false) return false;
    list($encrypted, $iv) = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// 2FA Helper
function send_2fa_email($email, $code) {
    require_once __DIR__ . '/../config/mail.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

    $subject = "Your 2-Factor Authentication Code";
    $message = "
    <html>
    <head>
    <title>2-Factor Authentication</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;'>
        <div style='max-w-md; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; text-align: center; border: 1px solid #f3f4f6;'>
            <h2 style='color: #4f46e5; margin-bottom: 20px;'>Login Verification</h2>
            <p style='color: #4b5563; margin-bottom: 20px;'>Please use the following 6-digit code to complete your login:</p>
            <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #111827; margin-bottom: 20px;'>
                {$code}
            </div>
            <p style='color: #6b7280; font-size: 13px;'>This code is valid for 10 minutes. Do not share it with anyone.</p>
        </div>
    </body>
    </html>
    ";

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = "Your 2-Factor Authentication Code is: $code";

        return $mail->send();
    } catch (\Exception $e) {
        error_log("2FA Email sending failed to $email: " . $e->getMessage());
        return false;
    }
}
?>
