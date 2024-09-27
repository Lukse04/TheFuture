<?php
// includes/wallet.inc.php

require_once 'dbh.inc.php';

// Funkcija, kuri generuoja piniginės adresą pagal valiutą
function generateWalletAddress($currency) {
    switch ($currency) {
        case 'Bitcoin':
            $prefix = (rand(0, 1) == 0) ? '1' : '3';
            return $prefix . bin2hex(random_bytes(20)); // Maždaug 34 simboliai
        case 'Ethereum':
            return '0x' . bin2hex(random_bytes(20)); // Iš viso 42 simboliai
        case 'Tether':
            $prefix = (rand(0, 1) == 0) ? '1' : '3';
            return $prefix . bin2hex(random_bytes(20));
        case 'Dogecoin':
            return 'D' . bin2hex(random_bytes(20)); // Maždaug 34 simboliai
        case 'Monero':
            $prefix = (rand(0, 1) == 0) ? '4' : '8';
            return $prefix . bin2hex(random_bytes(47)); // Maždaug 95 simboliai
        default:
            return null;
    }
}

// Funkcija, kuri gauna vartotojo piniginių adresus ir balansus
function fetchUserWallets($conn, $userId) {
    $queryBalances = "SELECT currency, balance, wallet_address FROM user_wallets WHERE user_id = ?";
    $stmt = $conn->prepare($queryBalances);
    if (!$stmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $resultBalances = $stmt->get_result();
    $wallets = [];
    while ($rowBalance = $resultBalances->fetch_assoc()) {
        $wallets[] = $rowBalance;
    }
    $stmt->close();
    return $wallets;
}

// Funkcija, kuri sukuria pradines pinigines vartotojui
function createInitialWallets($conn, $userId) {
    $currencies = ['Bitcoin', 'Ethereum', 'Tether', 'Dogecoin', 'Monero'];
    $insertBalanceQuery = "INSERT INTO user_wallets (user_id, currency, balance, wallet_address) VALUES (?, ?, ?, ?)";
    foreach ($currencies as $currency) {
        $zeroBalance = '0.000000000';
        $walletAddress = generateWalletAddress($currency);
        $stmt = $conn->prepare($insertBalanceQuery);
        if (!$stmt) {
            die("Klaida ruošiant užklausą: " . $conn->error);
        }
        $stmt->bind_param('isss', $userId, $currency, $zeroBalance, $walletAddress);
        if (!$stmt->execute()) {
            die("Klaida vykdant užklausą: " . $stmt->error);
        }
        $stmt->close();
    }
}
?>
