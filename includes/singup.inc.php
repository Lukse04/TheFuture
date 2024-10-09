<?php

// Autoload PHPMailer ir naudokite klases viršuje
require 'C:/Users/Lukas/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST["submit"])) {
    session_start();

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("location: ../singup.php?error=invalidcsrf");
        exit();
    }

    $username = $_POST["username"];
    $email = $_POST["email"];
    $pwd = $_POST["pwd"];

    require_once 'dbh.inc.php';
    require_once 'functions.inc.php';

    // Validate inputs
    if (emptyInputSingup($username, $email, $pwd) !== false) {
        header("location: ../singup.php?error=emptyinput");
        exit();
    }
    if (invalidUserName($username) !== false) {
        header("location: ../singup.php?error=invalidUserName");
        exit();
    }
    if (invalidEmail($email) !== false) {
        header("location: ../singup.php?error=invalidEmail");
        exit();
    }
    if (uUserNameExists($conn, $username, $email) !== false) {
        header("location: ../singup.php?error=usernametaken");
        exit();
    }

    // Generate email verification token
    $token = bin2hex(random_bytes(50));

    // Store the user with unverified status
    $sql = "INSERT INTO users (usersName, usersEmail, userspwd, token, is_verified) VALUES (?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $username, $email, $hashedPwd, $token);
    $stmt->execute();

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

        //Recipients
        $mail->setFrom('tavo@gmail.com', 'Your App');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body    = 'Click <a href="http://localhost/verify.php?token=' . $token . '">here</a> to verify your email.';

        $mail->send();
        header("location: ../singup.php?success=emailsent");
    } catch (Exception $e) {
        header("location: ../singup.php?error=mailerror");
    }
} else {
    header("location: ../singup.php");
    exit();
}
