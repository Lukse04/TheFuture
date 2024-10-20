<?php
// includes/edit_user.inc.php

// Įtraukite duomenų bazės prisijungimo failą
require_once 'dbh.inc.php';

require_once 'includes/auth.inc.php';

check_auth();

// Funkcija vartotojo duomenims gauti
function getUserDetails($conn, $userId) {
    $userQuery = "SELECT usersId, usersName, usersEmail, user_type FROM users WHERE usersId = ?";
    $userStmt = $conn->prepare($userQuery);
    if (!$userStmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    return $user;
}

// Funkcija vartotojo piniginėms gauti
function getUserWallets($conn, $userId) {
    $walletQuery = "SELECT currency, balance, wallet_address FROM user_wallets WHERE user_id = ?";
    $walletStmt = $conn->prepare($walletQuery);
    if (!$walletStmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $walletStmt->bind_param('i', $userId);
    $walletStmt->execute();
    $walletResult = $walletStmt->get_result();
    $wallets = [];
    while ($wallet = $walletResult->fetch_assoc()) {
        $wallets[] = $wallet;
    }
    $walletStmt->close();
    return $wallets;
}
