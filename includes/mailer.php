<?php
class Mailer {
    private $from_email = 'noreply@shenmoapp.atwebpages.com';
    private $from_name  = 'Abacus Academy';
    private $app_url    = 'http://shenmoapp.atwebpages.com';

    public function send($to, $subject, $body) {
        $headers  = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        if (mail($to, $subject, $body, $headers)) {
            return ['success' => true,  'message' => 'Email sent successfully'];
        }
        return ['success' => false, 'message' => 'Failed to send email'];
    }

    public function sendVerificationEmail($to, $name, $token, $type = 'student') {
        $link    = $this->app_url . '/verify_email.php?token=' . $token . '&type=' . $type;
        $subject = 'Verify Your Email - Abacus Academy';
        $body    = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
            <div style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:30px;text-align:center;border-radius:10px 10px 0 0'>
                <h1>🎓 Abacus Academy</h1><h2>Email Verification</h2>
            </div>
            <div style='background:#f9f9f9;padding:30px;border-radius:0 0 10px 10px'>
                <p>Dear $name,</p>
                <p>Thank you for registering! Please verify your email by clicking the button below:</p>
                <div style='text-align:center;margin:25px 0'>
                    <a href='$link' style='padding:15px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;border-radius:8px;font-weight:bold'>Verify Email Address</a>
                </div>
                <p>Or copy this link: <a href='$link' style='color:#667eea'>$link</a></p>
                <p><strong>This link expires in 24 hours.</strong></p>
                <p style='color:#777;font-size:0.9rem'>© 2026 Abacus Academy</p>
            </div>
        </div>";
        return $this->send($to, $subject, $body);
    }

    public function sendWelcomeEmail($to, $name, $type = 'student') {
        $subject = 'Welcome to Abacus Academy!';
        $body    = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
            <div style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:30px;text-align:center;border-radius:10px 10px 0 0'>
                <h1>🎓 Abacus Academy</h1><h2>Welcome!</h2>
            </div>
            <div style='background:#f9f9f9;padding:30px;border-radius:0 0 10px 10px'>
                <p>Dear $name,</p>
                <p>Your account has been verified. You can now log in and access all features.</p>
                <div style='text-align:center;margin:25px 0'>
                    <a href='{$this->app_url}/login.php' style='padding:15px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;border-radius:8px;font-weight:bold'>Login to Your Account</a>
                </div>
                <p style='color:#777;font-size:0.9rem'>© 2026 Abacus Academy</p>
            </div>
        </div>";
        return $this->send($to, $subject, $body);
    }
}
