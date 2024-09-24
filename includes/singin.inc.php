
<?php

if (isset($_POST["submit"])) {
    session_start();

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("location: ../singin.php?error=invalidcsrf");
        exit();
    }

    $username = $_POST["uid"];
    $pwd = $_POST["pwd"];
    
    require_once 'dbh.inc.php';
    require_once 'functions.inc.php';
    
    if(emptyInputSingin($username, $pwd) !== false) {
        header("location: ../singin.php?error=emptyinput");
        exit();
    }
    
    SinginUser($conn, $username, $pwd);
} else {
    header("location: ../singin.php");
    exit();
}
