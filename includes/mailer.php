<?php
require_once __DIR__ . '/PHPMailer/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

class Mailer {
    private $from_email;
    private $from_name;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $use_smtp;
    private $app_url;
    
    public function __construct() {
        $config = require_once __DIR__ . '/../config/mail.php';
        $app_config = require_once __DIR__ . '/../config/app.php';
        
        $this->from_email = $config['from_email'];
        $this->from_name = $config['from_name'];
        $this->use_smtp = $config['use_smtp'];
        $this->smtp_host = $config['smtp_host'];
        $this->smtp_port = $config['smtp_port'];
        $this->smtp_username = $config['smtp_username'];
        $this->smtp_password = $config['smtp_password'];
        $this->smtp_encryption = $config['smtp_encryption'];
        $this->app_url = $app_config['app_url'];
    }
    
    public function send($to, $subject, $message, $is_html = true) {
        if ($this->use_smtp) {
            return $this->sendViaSMTP($to, $subject, $message, $is_html);
        } else {
            return $this->sendViaMail($to, $subject, $message, $is_html);
        }
    }
    
    private function sendViaMail($to, $subject, $message, $is_html = true) {
        $headers = [];
        $headers[] = 'From: ' . $this->from_name . ' <' . $this->from_email . '>';
        $headers[] = 'Reply-To: ' . $this->from_email;
        $headers[] = 'MIME-Version: 1.0';
        
        if ($is_html) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        $header_string = implode("\r\n", $headers);
        
        $sent = mail($to, $subject, $message, $header_string);
        
        if ($sent) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email. Please check your PHP mail configuration.'];
        }
    }
    
    private function sendViaSMTP($to, $subject, $message, $is_html = true) {
        try {
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            
            if ($this->smtp_encryption == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = $this->smtp_port;
            
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
        }
    }
    
    public function sendVerificationEmail($to, $name, $token, $type = 'student') {
        $verification_link = $this->app_url . '/verify_email.php?token=' . $token . '&type=' . $type;
        
        $subject = 'Verify Your Email - Abacus Academy';
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #777; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 Abacus Academy</h1>
                    <h2>Email Verification</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>Thank you for registering with Abacus Academy! To complete your registration, please verify your email address by clicking the button below:</p>
                    
                    <div style='text-align: center;'>
                        <a href='$verification_link' class='button'>Verify Email Address</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #667eea;'>$verification_link</p>
                    
                    <p><strong>This link will expire in 24 hours.</strong></p>
                    
                    <p>If you did not create an account with us, please ignore this email.</p>
                    
                    <div class='footer'>
                        <p>© 2026 Abacus Academy. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $message, true);
    }
    
    public function sendWelcomeEmail($to, $name, $type = 'student') {
        $subject = 'Welcome to Abacus Academy!';
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #777; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 Abacus Academy</h1>
                    <h2>Welcome!</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>Welcome to Abacus Academy! We're excited to have you on board.</p>
                    <p>Your account has been verified and you can now access all our features.</p>
                    
                    <div style='text-align: center;'>
                            <a href='<?php echo $this->app_url; ?>/login.php' class='button'>Login to Your Account</a>
                    </div>
                    
                    <p>If you have any questions, feel free to contact us.</p>
                    
                    <div class='footer'>
                        <p>© 2026 Abacus Academy. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $message, true);
    }
}
