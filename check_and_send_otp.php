<?php
// check_and_send_otp.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$email = trim($payload['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok'=>false, 'msg'=>'Invalid email address.']);
    exit;
}

try {
    // 1) Check if email exists
    $stmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['ok'=>false, 'msg'=>'Email not found in our records.']);
        exit;
    }

    // OPTIONAL: rate limit (e.g., 1 request per 60s)
    $stmt2 = $pdo->prepare("SELECT reset_token_expires_at FROM janitors WHERE email = ?");
    $stmt2->execute([$email]);
    $row2 = $stmt2->fetch();
    if ($row2 && isset($row2['reset_token_expires_at'])) {
        // nothing strict here — you can add checks if needed
    }

    // 2) Generate OTP
    $otp = random_int(100000, 999999); // 6-digit numeric
    $otpHash = password_hash((string)$otp, PASSWORD_DEFAULT);
    $expiry = date('Y-m-d H:i:s', time() + 10 * 60); // 10 minutes

    // 3) Save hashed OTP and expiry
    $upd = $pdo->prepare("UPDATE janitors SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
    $upd->execute([$otpHash, $expiry, $email]);

    // 4) Send OTP email (PHPMailer)
    $mail = new PHPMailer(true);
    // SMTP config: use values from your includes/config.php or hardcode here
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username = 'smartrashbin.system@gmail.com';
    $mail->Password = 'svqjvkmdkdedjbia'; // dito mo ilagay ang app password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

$mail->setFrom('no-reply@smarttrashbin.com', 'Smart Trashbin System');
$mail->addAddress($email);
$mail->isHTML(true);
$mail->Subject = 'One-Time Password (OTP) for Smart Trashbin System';
$mail->Body = "
<p>Dear User,</p>
<p>We have received a request to reset your password for your Smart Trashbin System account. Please use the following One-Time Password (OTP) to proceed with resetting your password:</p>
<p style='font-size:18px; font-weight:bold;'>$otp</p>
<p>This OTP is valid for 10 minutes. For your security, do not share this code with anyone.</p>
<p>If you did not request a password reset, please ignore this email.</p>
<p>Best regards,<br>Smart Trashbin System Team</p>
";


    $mail->send();

    echo json_encode(['ok'=>true, 'msg'=>'OTP sent to your email.']);

} catch (Exception $e) {
    error_log("[OTP send error] " . $e->getMessage());
    echo json_encode(['ok'=>false, 'msg'=>'Failed to send OTP. Please try again later.']);
}
