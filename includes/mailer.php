<?php
// includes/mailer.php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

function sendVerificationEmail($to, $name, $token, $expire_mins = 1440)
{
    global $conn;
    $company_name = 'InvoicePro';
    if (isset($conn) && function_exists('get_setting')) {
        $company_name = get_setting($conn, 'company_name', 'InvoicePro');
    }
    
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
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . $company_name . '! Please confirm your email';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $verifyLink = "$protocol://$host" . BASE_URL . "/auth/verify.php?token=$token";

        // Calculate hours/mins for display
        $expire_text = ($expire_mins >= 60) ? floor($expire_mins / 60) . ' hours' : $expire_mins . ' minutes';
        if ($expire_mins % 60 > 0 && $expire_mins >= 60) {
            $expire_text = floor($expire_mins / 60) . ' hours and ' . ($expire_mins % 60) . ' minutes';
        }

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f9fafb; padding: 24px 16px; width: 100%;'>
                <tr>
                    <td align='center'>
                        <table width='100%' style='max-width: 520px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); overflow: hidden; margin: 0 auto;' cellpadding='0' cellspacing='0' border='0'>
                            
                            <tr>
                                <td style='padding: 24px 32px 16px 32px; text-align: center; border-bottom: 1px solid #f3f4f6;'>
                                    <div style='margin-bottom: 8px;'>
                                        <span style='color: #7c3aed; font-weight: 900; font-size: 28px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; letter-spacing: -0.5px;'>" . htmlspecialchars($company_name) . "</span>
                                    </div>
                                    <h1 style='color: #111827; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;'>Welcome to " . htmlspecialchars($company_name) . "!</h1>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='padding: 24px 32px; color: #4b5563; line-height: 1.6; font-size: 15px;'>
                                    <p style='margin-top: 0; margin-bottom: 16px;'>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                                    <p style='margin-top: 0; margin-bottom: 16px;'>We're thrilled to have you on board. Your new account has been successfully provisioned and is almost ready to go.</p>
                                    
                                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;'>
                                        <p style='margin: 0; color: #334155; font-size: 14px; line-height: 1.6;'>To secure your account and gain full access to your dashboard, please confirm your email address by clicking the button below.</p>
                                    </div>

                                    <div style='text-align: center; margin: 24px 0;'>
                                        <a href='" . $verifyLink . "' style='display: inline-block; background-color: #7c3aed; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.2); transition: background-color 0.2s;'>Confirm Email Address</a>
                                    </div>

                                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;'>
                                        <p style='color: #475569; font-size: 14px; margin-top: 0; margin-bottom: 8px; font-weight: 500;'>This secure link will expire in " . $expire_text . ".</p>
                                        <p style='color: #64748b; font-size: 13px; margin: 0; line-height: 1.5;'>If you did not request this account, no further action is required. You can safely ignore this email.</p>
                                    </div>
                                    
                                    <div style='word-break: break-all; font-size: 12px; color: #9ca3af; margin-top: 24px; border-top: 1px solid #f3f4f6; padding-top: 16px;'>
                                        Having trouble clicking the button? Copy and paste this URL into your browser:<br>
                                        <a href='" . $verifyLink . "' style='color: #7c3aed; text-decoration: none; margin-top: 8px; display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;'>" . $verifyLink . "</a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <table width='100%' style='max-width: 520px; margin: 0 auto;' cellpadding='0' cellspacing='0' border='0'>
                            <tr>
                                <td align='center' style='padding: 16px 0; color: #9ca3af; font-size: 12px; line-height: 1.5;'>
                                    &copy; " . date('Y') . " " . htmlspecialchars($company_name) . ". All rights reserved.<br>
                                    This is an automated system message. Please do not reply directly to this email.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $body));

        return $mail->send();
    } catch (\Exception $e) {
        error_log("Email sending failed to $to: " . $e->getMessage());
    }
}

function send_2fa_email($email, $code)
{
    global $conn;
    $company_name = 'InvoicePro';
    $expire_mins = 10;
    if (isset($conn) && function_exists('get_setting')) {
        $company_name = get_setting($conn, 'company_name', 'InvoicePro');
        $expire_mins = (int) get_setting($conn, '2fa_expiration_minutes', '10');
    }
    
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
        $mail->Subject = 'Your ' . $company_name . ' Security Code';

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Security Code</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f9fafb; padding: 24px 16px; width: 100%;'>
                <tr>
                    <td align='center'>
                        <table width='100%' style='max-width: 520px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); overflow: hidden; margin: 0 auto;' cellpadding='0' cellspacing='0' border='0'>
                            
                            <tr>
                                <td style='padding: 24px 32px 16px 32px; text-align: center; border-bottom: 1px solid #f3f4f6;'>
                                    <div style='margin-bottom: 8px;'>
                                        <span style='color: #7c3aed; font-weight: 900; font-size: 28px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; letter-spacing: -0.5px;'>" . htmlspecialchars($company_name) . "</span>
                                    </div>
                                    <h1 style='color: #111827; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;'>Security Code</h1>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='padding: 24px 32px; color: #4b5563; line-height: 1.6; font-size: 15px;'>
                                    <p style='margin-top: 0; margin-bottom: 16px; text-align: center;'>You recently attempted to sign in. To complete the login process, please enter the authentication code below.</p>
                                    
                                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 24px; text-align: center;'>
                                        <span style=\"font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #7c3aed; margin-left: 8px;\">
                                            {$code}
                                        </span>
                                    </div>

                                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;'>
                                        <p style='color: #475569; font-size: 14px; margin-top: 0; margin-bottom: 8px; font-weight: 500;'>This code will expire in " . $expire_mins . " minutes.</p>
                                        <p style='color: #64748b; font-size: 13px; margin: 0; line-height: 1.5;'>If you didn't request this code, you can safely ignore this email.</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <table width='100%' style='max-width: 520px; margin: 0 auto;' cellpadding='0' cellspacing='0' border='0'>
                            <tr>
                                <td align='center' style='padding: 16px 0; color: #9ca3af; font-size: 12px; line-height: 1.5;'>
                                    &copy; " . date('Y') . " " . htmlspecialchars($company_name) . ". All rights reserved.<br>
                                    This is an automated system message. Please do not reply directly to this email.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Your authentication code is: $code";

        return $mail->send();
    } catch (\Exception $e) {
        error_log("2FA Email sending failed to $email: " . $e->getMessage());
        return false;
    }
}
?>