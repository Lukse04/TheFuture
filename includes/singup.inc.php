
<?php

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

    // Create the user
    createUser($conn, $username, $email, $pwd);
} else {
    header("location: ../singup.php");
    exit();
}
