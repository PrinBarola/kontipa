<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$payload = json_decode(file_get_contents('php://input'), true);
$email = trim($payload['email'] ?? '');
$newPassword = trim($payload['new_password'] ?? '');

if (!$email || !$newPassword) {
    echo json_encode(['ok'=>false,'msg'=>'Missing fields.']);
    exit;
}

if (
    strlen($newPassword) < 6 ||
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/\d/', $newPassword) ||
    !preg_match('/[\W_]/', $newPassword)
) {
    echo json_encode([
      'ok' => false,
      'msg' => 'Password must be at least 6 characters long and contain uppercase, lowercase, number, and special character.'
    ]);
    exit;
}

try {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE janitors 
        SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL
        WHERE email = ?");
    $stmt->execute([$hashed, $email]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok'=>false,'msg'=>'Email not found or password not updated.']);
        exit;
    }

    // --- SEND CONFIRMATION EMAIL ---
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'smartrashbin.system@gmail.com';
        $mail->Password = 'svqjvkmdkdedjbia'; // dito mo ilagay ang app password
        $mail->SMTPSecure = 'tls'; 
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('smartrashbin.system@gmail.com', 'Smart Trashbin');
        $mail->addAddress($email);

    // Content
$mail->isHTML(true);
$mail->Subject = 'Smart Trashbin System: Password Change Confirmation';
$mail->Body = "
    <p>Dear User,</p>
    <p>This is to notify you that your password for your Smart Trashbin System account has been successfully updated.</p>
    <p>If you did not authorize this change, please contact our support team immediately at <strong>smartrashbin.system@gmail.com</strong> to secure your account.</p>
    <p>We recommend keeping your password confidential and updating it regularly for security purposes.</p>
    <p>Sincerely,<br><strong>Smart Trashbin System Team</strong></p>";

        $mail->send();
    } catch (Exception $e) {
        // You can log the error but still return success to user
        error_log('Mail error: ' . $mail->ErrorInfo);
    }

    echo json_encode(['ok'=>true,'msg'=>'Password updated successfully! A confirmation email has been sent.']);

} catch (Exception $e) {
    echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
}
