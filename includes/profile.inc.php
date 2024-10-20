<?php
// includes/profile.inc.php

require_once 'auth.inc.php';

check_auth();

// Tikriname CSRF žetoną
if (!isset($_POST['csrf_token']) || !check_csrf_token($_POST['csrf_token'])) {
    header("Location: ../profile.php?error=invalidcsrf");
    exit();
}

require_once 'profile_functions.inc.php';

$user_id = $_SESSION["userid"];

// Patikriname, ar forma buvo pateikta
if (isset($_POST['update_profile'])) {
    // Gauname ir išvalome įvestus duomenis
    $new_username = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']);
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Atnaujiname vartotojo profilį
    $updateResult = updateUserProfile($conn, $user_id, $new_username, $new_email, $old_password, $new_password);

    if ($updateResult === true) {
        // Atnaujiname sesijos kintamuosius, jei reikia
        $_SESSION['username'] = $new_username;

        // Grįžtame su sėkmės pranešimu
        $_SESSION['success'] = "Profilis sėkmingai atnaujintas!";
        header("Location: ../profile.php");
        exit();
    } else {
        // Jei yra klaidų, grįžtame su klaidų pranešimais
        $_SESSION['errors'] = $updateResult;
        header("Location: ../profile.php");
        exit();
    }

} else {
    // Jei prie failo prieinama be formos pateikimo
    header("Location: ../profile.php");
    exit();
}
