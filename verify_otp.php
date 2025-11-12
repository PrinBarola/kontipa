<?php
// verify_otp.php
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$email = trim($payload['email'] ?? '');
$otpInput = trim($payload['otp'] ?? '');

if (!$email || !$otpInput) {
    echo json_encode(['ok'=>false,'msg'=>'Missing fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT janitor_id, reset_token_hash, reset_token_expires_at FROM janitors WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['reset_token_hash']) {
        echo json_encode(['ok'=>false,'msg'=>'No OTP request found.']);
        exit;
    }

    if (strtotime($user['reset_token_expires_at']) < time()) {
        echo json_encode(['ok'=>false,'msg'=>'OTP expired. Please request a new code.']);
        exit;
    }

    if (!password_verify($otpInput, $user['reset_token_hash'])) {
        // Optionally increment attempts and lock after N tries (not implemented here)
        echo json_encode(['ok'=>false,'msg'=>'Invalid OTP.']);
        exit;
    }

    // OTP correct. Optionally clear reset_token_hash or keep until password reset.
    // We'll mark it verified by returning success.
    echo json_encode(['ok'=>true,'msg'=>'OTP verified. Proceed to reset password.']);

} catch (Exception $e) {
    error_log("[OTP verify error] " . $e->getMessage());
    echo json_encode(['ok'=>false,'msg'=>'Server error verifying OTP.']);
}
