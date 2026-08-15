<?php
date_default_timezone_set('Asia/Manila');

// Reads Railway environment variables in production, falls back to local credentials
$host     = getenv('MYSQLHOST') ?: "127.0.0.1";
$dbname   = getenv('MYSQLDATABASE') ?: "capstone_1";
$username = getenv('MYSQLUSER') ?: "root";
$password = getenv('MYSQLPASSWORD') ?: "BumLQaHKTsNBjFyyAkaGFBQkfcWvpMnv";
$port     = getenv('MYSQLPORT') ?: "3306";

try {
    // Explicitly including port forces TCP/IP connection instead of Unix sockets
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET time_zone = '+08:00';");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function sendRecoveryEmail($toEmail, $toName, $resetLink): bool {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ian.keneth.birondo@gmail.com';             
        $mail->Password   = 'lomu oenu udix rggy';               
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'System Admin');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                <h2 style='color: #d9741e;'>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($toName) . ",</p>
                <p>We received a request to reset your password. Click the button below to proceed. This link will expire in 30 minutes.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $resetLink . "' style='background-color: #f28b30; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold;'>Reset Password</a>
                </div>
                <p style='color: #666; font-size: 12px;'>If you did not request this, please ignore this email.</p>
            </div>
        ";
        
        $mail->AltBody = "Hello " . $toName . ",\n\nReset your password by visiting this link:\n" . $resetLink;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>