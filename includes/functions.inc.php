
<?php

function emptyInputSingup($username, $email, $pwd) {
    return empty($username) || empty($email) || empty($pwd);
}

function invalidUserName($username) {
    return !preg_match("/^[a-zA-Z0-9]*$/", $username);
}

function invalidEmail($email) {
    return !filter_var($email, FILTER_VALIDATE_EMAIL);
}

function uUserNameExists($conn, $username, $email) {
    $sql = "SELECT * FROM users WHERE usersName = ? OR usersEmail = ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("location: ../singup.php?error=stmtfailed");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    
    $resultData = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($resultData)) {
        return $row;
    } else {
        return false;
    }
    
    mysqli_stmt_close($stmt);
}

function createUser($conn, $username, $email, $pwd) {
    $sql = "INSERT INTO users (usersName, usersEmail, userspwd) VALUES (?, ?, ?);";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("location: ../singup.php?error=stmtfailed");
        exit();
    }
    
    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
    
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("location: ../singup.php?error=none");
    exit();
}

function emptyInputSingin($username, $pwd) {
    return empty($username) || empty($pwd);
}

function SinginUser($conn, $username, $pwd) {
    $usernameExists = uUserNameExists($conn, $username, $username);
    
    if ($usernameExists === false) {
        header("location: ../singin.php?error=wrongsingin");
        exit();
    }
    
    $pwdHashed = $usernameExists["userspwd"];
    $checkPwd = password_verify($pwd, $pwdHashed);
    
    if ($checkPwd === false) {
        header("location: ../singin.php?error=wrongsingin");
        exit();
    } else {
        session_start();
        $_SESSION["userid"] = $usernameExists["usersId"];
        $_SESSION["username"] = $usernameExists["usersName"];
        $_SESSION["usertype"] = $usernameExists["user_type"];
        header("location: ../index.php");
        exit();
    }
}

?>
