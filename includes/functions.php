<?php
// includes/functions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function redirect($location)
{
    header("Location: " . $location);
    exit();
}

function checkAuth()
{
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
        redirect(BASE_URL . '/auth/login.php');
    }
}

function sanitize($conn, $input)
{
    return mysqli_real_escape_string($conn, trim($input));
}

function generateInvoiceNo($conn)
{
    $ym = date('Ym');
    $result = mysqli_query($conn, "SELECT invoice_no FROM invoices WHERE invoice_no LIKE 'INV-$ym-%' ORDER BY id DESC LIMIT 1");

    if ($row = mysqli_fetch_assoc($result)) {
        $parts = explode('-', $row['invoice_no']);
        $lastNum = (int) end($parts);
        $nextId = $lastNum + 1;
    } else {
        $nextId = 1;
    }

    return 'INV-' . $ym . '-' . str_pad($nextId, 4, "0", STR_PAD_LEFT);
}

function get_setting($conn, $key, $default = '')
{
    $safekey = sanitize($conn, $key);
    $res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$safekey'");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return $row['setting_value'];
    }
    return $default;
}

function set_setting($conn, $key, $value)
{
    $safekey = sanitize($conn, $key);
    $safeval = sanitize($conn, $value);
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$safekey', '$safeval') 
            ON DUPLICATE KEY UPDATE setting_value = '$safeval'";
    return mysqli_query($conn, $sql);
}

function logActivity($conn, $user_id, $action, $details = '')
{
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
            while ($admin = mysqli_fetch_assoc($admin_res)) {
                addNotification($conn, $admin['id'], $title, $desc);
            }
        }
    }
    return $result;
}

function addNotification($conn, $user_id, $title, $message = '')
{
    $title = sanitize($conn, $title);
    $message = sanitize($conn, $message);
    $sql = "INSERT INTO notifications (user_id, title, message) VALUES ('$user_id', '$title', '$message')";
    return mysqli_query($conn, $sql);
}

function fetch_url($url)
{
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
function encrypt_data($data, $key = null)
{
    if ($key === null) {
        $key = defined('APP_ENCRYPTION_KEY') ? APP_ENCRYPTION_KEY : 'default_fallback_key_123!';
    }
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data($data, $key = null)
{
    if ($key === null) {
        $key = defined('APP_ENCRYPTION_KEY') ? APP_ENCRYPTION_KEY : 'default_fallback_key_123!';
    }
    $decoded = base64_decode($data);
    if (strpos($decoded, '::') === false)
        return false;
    list($encrypted, $iv) = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// 2FA Helper
function send_2fa_email($email, $code)
{
    global $conn;
    require_once __DIR__ . '/../config/mail.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

    $company_name = 'Invoice Management System';
    $company_email = '';
    $company_phone = '';
    $company_address = '';
    $expire_mins = 10;
    if (isset($conn) && $conn) {
        $company_name = htmlspecialchars(get_setting($conn, 'company_name', 'Invoice Management System'), ENT_QUOTES);
        $company_email = htmlspecialchars(get_setting($conn, 'company_email', ''), ENT_QUOTES);
        $company_phone = htmlspecialchars(get_setting($conn, 'company_phone', ''), ENT_QUOTES);
        $company_address = nl2br(htmlspecialchars(get_setting($conn, 'company_address', ''), ENT_QUOTES));
        $expire_mins = (int) get_setting($conn, '2fa_expiration_minutes', '10');
    }

    $subject = "Your 2-Factor Authentication Code";
    $year = date("Y");
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>2-Factor Authentication</title>
    </head>
    <body style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; padding: 40px 20px; margin: 0; color: #111827;\">
        <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='background-color: #f4f5f7; width: 100%; margin: 0; padding: 0;'>
            <tr>
                <td align='center'>
                    <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);'>
                        <tr>
                            <td style='padding: 40px;'>
                                <div style='text-align: center; margin-bottom: 24px;'>
                                    <div style='display: inline-block; padding: 14px; background-color: #f5f3ff; border-radius: 16px;'>
                                        <img src='https://api.iconify.design/lucide/shield-check.svg?color=%237c3aed&width=32&height=32' alt='Security' style='display: block; width: 32px; height: 32px;'>
                                    </div>
                                </div>
                                <h2 style='font-size: 22px; font-weight: 700; text-align: center; margin-top: 0; margin-bottom: 12px; color: #111827;'>Verify your identity</h2>
                                <p style='font-size: 15px; line-height: 24px; color: #4b5563; margin-top: 0; margin-bottom: 32px; text-align: center;'>
                                    You recently attempted to sign in. To complete the login process, please enter the authentication code below.
                                </p>
                                <div style='background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 32px;'>
                                    <span style=\"font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 34px; font-weight: 700; letter-spacing: 10px; color: #7c3aed; margin-left: 10px;\">
                                        {$code}
                                    </span>
                                </div>
                                <p style='font-size: 14px; line-height: 20px; color: #6b7280; text-align: center; margin: 0;'>
                                    This code will expire in {$expire_mins} minutes.<br>If you didn't request this, you can safely ignore this email.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 24px 40px; text-align: center; background-color: #f9fafb; border-top: 1px solid #e5e7eb;'>
                                <p style='font-size: 13px; color: #4b5563; font-weight: 600; margin: 0 0 8px 0;'>
                                    {$company_name}
                                </p>
                                <p style='font-size: 12px; color: #6b7280; margin: 0 0 6px 0;'>
                                    {$company_address}
                                </p>
                                <p style='font-size: 12px; color: #6b7280; margin: 0 0 16px 0;'>
                                    <a href='mailto:{$company_email}' style='color: #7c3aed; text-decoration: none;'>{$company_email}</a> 
                                    " . ($company_phone ? "&bull; {$company_phone}" : "") . "
                                </p>
                                <p style='font-size: 12px; color: #9ca3af; margin: 0;'>
                                    &copy; {$year} {$company_name}. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
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