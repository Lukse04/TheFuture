
<?php
// Autoload PHPMailer ir naudokite klases viršuje
require 'C:/Users/Lukas/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['email'])) {
    require_once 'dbh.inc.php';

    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50)); // Generate a unique token

    // Update the user's token in the database
    $sql = "UPDATE users SET reset_token = ? WHERE usersEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $token, $email);
    $stmt->execute();

    // Siųsti slaptažodžio atstatymo laišką
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '7f6bbd60b715b5'; // Enter your Mailtrap Username
        $mail->Password   = '3ae0cc46050621'; // Enter your Mailtrap Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipient
        $mail->setFrom('noreply@yourwebsite.com', 'Your Website');
        $mail->addAddress($email);

        // Content
        $url = "http://yourwebsite.com/reset_form.php?token=$token";
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body    = "<p>Click <a href='$url'>here</a> to reset your password.</p>";

        $mail->send();
        header("Location: ../forget_password.php?reset=success");
    } catch (Exception $e) {
        header("Location: ../forget_password.php?reset=failed");
    }
}
?>
