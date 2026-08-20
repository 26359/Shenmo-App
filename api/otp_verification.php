<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action == 'send_otp') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Valid email is required']);
        exit;
    }
    
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expiry'] = time() + 300;
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP sent successfully',
        'otp' => $otp
    ]);
    exit;
}

if ($action == 'verify_otp') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        exit;
    }
    
    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email']) || !isset($_SESSION['otp_expiry'])) {
        echo json_encode(['success' => false, 'message' => 'OTP session expired. Please request a new OTP.']);
        exit;
    }
    
    if ($_SESSION['otp_email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'Email mismatch. Please request a new OTP.']);
        exit;
    }
    
    if (time() > $_SESSION['otp_expiry']) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new OTP.']);
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
        exit;
    }
    
    if ($_SESSION['otp'] !== $otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        exit;
    }
    
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP verified successfully'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
