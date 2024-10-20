<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$signin_url = '/singin.php';

function check_auth() {
    global $signin_url;
    if (!isset($_SESSION['userid'])) {
        header("Location: " . $signin_url);
        exit;
    }
}

function get_user_id() {
    return $_SESSION['userid'];
}

function get_user_type() {
    return $_SESSION['usertype'];
}

function is_admin() {
    return (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    if ($isValid) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $isValid;
}

function login_user($userId, $userType) {
    session_regenerate_id(true);
    $_SESSION['userid'] = $userId;
    $_SESSION['user_type'] = $userType;
    generate_csrf_token();
}

function logout_user() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"] ?? false, $params["httponly"] ?? false
        );
    }
    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Redirect to the homepage after logout
    header("location: ../singin.php");
    exit();
    }
?>
