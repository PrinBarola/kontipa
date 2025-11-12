<?php
require "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Optional DB config to insert notification after successful send
require_once __DIR__ . '/includes/config.php';

// ✅ Custom validation function
function validatex($data) {
    $data = trim($data);              // Remove whitespace
    $data = stripslashes($data);      // Remove backslashes
    $data = htmlspecialchars($data);  // Escape HTML special characters
    return $data;
}

// ✅ Safely collect POST data
$name    = validatex($_POST['name'] ?? '');
$email   = validatex($_POST['email'] ?? '');
$subject = validatex($_POST['subject'] ?? '');
$message = validatex($_POST['message'] ?? '');

// ✅ Basic validation checks
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    die("Error: All fields are required.");
}

// ✅ Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.");
}

$mail = new PHPMailer(true);

try {
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Use your app password (not your regular Gmail password)
    $mail->Username = "smartrashbin.system@gmail.com";
    $mail->Password = "svqjvkmdkdedjbia";

    // ✅ Additional security: sanitize email headers
    $safe_name = preg_replace('/[\r\n]+/', ' ', $name);
    $safe_email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // NOTE: some SMTP providers require setFrom to be the verified sender.
    // You already have it functional; keeping user's email as From as in your current code.
    $mail->setFrom($safe_email, $safe_name);
    $mail->addAddress("smartrashbin.system@gmail.com", "Smart Trashbin");

    // Prevent email header injection
    $safe_subject = preg_replace("/[\r\n]+/", " ", $subject);
    $mail->Subject = $safe_subject;
    $mail->Body = nl2br($message);
    $mail->AltBody = strip_tags($message);

    // Send email
    $mail->send();

    // ---------------------------
    // Insert notification row
    // ---------------------------
    // Create a concise notification so admins see contact submissions in the admin UI.
    // This is non-fatal: if the notifications table or DB is missing, we log and continue.
    $notifTitle = !empty($subject) ? "Contact: " . mb_substr($subject, 0, 200) : "New contact message";
    $notifMessage = trim("From: {$name}" . (!empty($email) ? " <{$email}>" : "") . "\n\n" . mb_substr($message, 0, 1500));
    $notifType = 'info';

    try {
        // PDO path
        if (isset($pdo) && $pdo instanceof PDO) {
            $r = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($r && $r->rowCount() > 0) {
                $stmtN = $pdo->prepare("
                    INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at)
                    VALUES (:admin_id, NULL, NULL, :type, :title, :message, 0, NOW())
                ");
                $stmtN->execute([
                    ':admin_id' => null,
                    ':type' => $notifType,
                    ':title' => $notifTitle,
                    ':message' => $notifMessage
                ]);
            }
        }
        // mysqli path
        elseif (isset($conn)) {
            $res = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($res && $res->num_rows > 0) {
                $stmtN = $conn->prepare("
                    INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at)
                    VALUES (?, NULL, NULL, ?, ?, ?, 0, NOW())
                ");
                if ($stmtN) {
                    // bind_param requires variables; adminParam is NULL
                    $adminParam = null;
                    $typeParam = $notifType;
                    $titleParam = $notifTitle;
                    $messageParam = $notifMessage;
                    $stmtN->bind_param("isss", $adminParam, $typeParam, $titleParam, $messageParam);
                    $stmtN->execute();
                    $stmtN->close();
                }
            }
        }
    } catch (Exception $e) {
        // non-fatal: log and continue
        error_log('[send-email] notification insert failed: ' . $e->getMessage());
    }

    // Redirect to confirmation page
    header("Location: sent.php");
    exit;

} catch (Exception $e) {
    // Provide more helpful debug info in server logs
    error_log('[send-email] PHPMailer Exception: ' . $e->getMessage());
    if (isset($mail) && property_exists($mail, 'ErrorInfo')) {
        error_log('[send-email] PHPMailer ErrorInfo: ' . $mail->ErrorInfo);
    }

    // Show a user-friendly message (you may redirect back instead)
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    exit;
}
?>