<?php
// includes/mailer.php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

function sendVerificationEmail($to, $name, $token, $expire_mins = 1440)
{
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
        $mail->Subject = 'Verify Your Account - InvSys';

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
        </head>
        <body style='font-family: \"Inter\", Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f8fafc; padding: 50px 0; width: 100%;'>
                <tr>
                    <td align='center'>
                        <table width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; margin: 0 auto;' cellpadding='0' cellspacing='0' border='0'>
                            
                            <tr>
                                <td align='center' style='background-color: #7c3aed; padding: 40px 20px; border-bottom: 4px solid #5b21b6;'>
                                    <!-- A modern icon could be placed here. For now, text -->
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;'>Email Verification</h1>
                                    <p style='color: #ddd6fe; margin: 10px 0 0 0; font-size: 15px;'>Please confirm your email address</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <td style='padding: 40px; color: #475569; line-height: 1.7; font-size: 16px;'>
                                    <h2 style='color: #1e293b; font-size: 22px; margin-top: 0; font-weight: 700;'>Welcome to InvSys, " . htmlspecialchars($name) . "!</h2>
                                    <p>Your profile has been successfully generated. To unlock full access to your dashboard, we just need to quickly verify your email address to ensure your account's security.</p>
                                    
                                    <div style='background-color: #f1f5f9; border-left: 4px solid #7c3aed; padding: 16px 20px; border-radius: 6px; color: #334155; margin: 30px 0; font-size: 15px;'>
                                        <strong style='color: #1e293b;'>Security Notice:</strong> 
                                        For your protection, this verification link will expire in <strong>" . $expire_text . "</strong>.
                                    </div>

                                    <div style='text-align: center; margin: 40px 0;'>
                                        <a href='" . $verifyLink . "' style='display: inline-block; background-color: #7c3aed; color: #ffffff; text-decoration: none; padding: 16px 36px; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.2);'>Verify Email Address</a>
                                    </div>

                                    <p style='color: #64748b; font-size: 14px;'>If you did not request this account or receive this in error, you can safely ignore this email.</p>
                                    
                                    <div style='word-break: break-all; font-size: 13px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;'>
                                        If the button above does not work, copy and paste this link manually:<br>
                                        <a href='" . $verifyLink . "' style='color: #7c3aed; text-decoration: none; margin-top: 5px; display: inline-block;'>" . $verifyLink . "</a>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td align='center' style='padding: 24px 40px; background-color: #f8fafc; color: #94a3b8; font-size: 13px; border-top: 1px solid #f1f5f9;'>
                                    &copy; " . date('Y') . " InvSys Administration. All rights reserved.<br>
                                    This is an automated security message.
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
        return false;
    }
}
?>