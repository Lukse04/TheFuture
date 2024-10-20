<?php
// includes/update_user.inc.php

// Įtraukite duomenų bazės prisijungimo failą
require_once 'dbh.inc.php';

// Pradėkite sesiją, jei dar nepradėta
require_once 'auth.inc.php';

check_auth();

$userId = get_user_id();

// Patikrinkite, ar forma buvo pateikta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF tokeno tikrinimas
    if (!isset($_POST['csrf_token']) || !check_csrf_token($_POST['csrf_token'])) {
        die("Klaida: neteisingas CSRF žetonas.");
    }

    // Gaukite ir validuokite įvesties duomenis
    $usersId = intval($_POST['usersId']);
    $usersName = trim($_POST['usersName']);
    $usersEmail = trim($_POST['usersEmail']);
    $user_type = trim($_POST['user_type']);

    // Papildoma įvesties duomenų validacija
    if (empty($usersName) || empty($usersEmail) || empty($user_type)) {
        die("Klaida: Užpildykite visus laukus.");
    }

    if (!filter_var($usersEmail, FILTER_VALIDATE_EMAIL)) {
        die("Klaida: Netinkamas el. pašto formatas.");
    }

    // Atnaujinkite vartotojo duomenis
    $updateUserQuery = "UPDATE users SET usersName = ?, usersEmail = ?, user_type = ? WHERE usersId = ?";
    $updateUserStmt = $conn->prepare($updateUserQuery);
    if (!$updateUserStmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $updateUserStmt->bind_param('sssi', $usersName, $usersEmail, $user_type, $usersId);
    $updateUserStmt->execute();
    $updateUserStmt->close();

    // Atnaujinkite vartotojo piniginių balansus
    if (isset($_POST['wallet_address'], $_POST['balance'])) {
        $walletAddresses = $_POST['wallet_address'];
        $balances = $_POST['balance'];

        foreach ($walletAddresses as $index => $walletAddress) {
            $balance = floatval($balances[$index]);

            // Atnaujinkite piniginės balansą
            $updateWalletQuery = "UPDATE user_wallets SET balance = ? WHERE wallet_address = ? AND user_id = ?";
            $updateWalletStmt = $conn->prepare($updateWalletQuery);
            if (!$updateWalletStmt) {
                die("Klaida ruošiant užklausą: " . $conn->error);
            }
            $updateWalletStmt->bind_param('dsi', $balance, $walletAddress, $usersId);
            $updateWalletStmt->execute();
            $updateWalletStmt->close();
        }
    }

    // Nukreipkite atgal į administratoriaus panelę su pranešimu apie sėkmingą atnaujinimą
    header('Location: ../admin_panel.php?message=success');
    exit();
} else {
    die("Klaida: Neteisingas užklausos metodas.");
}
