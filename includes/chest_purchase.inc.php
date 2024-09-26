<?php
// includes/chest_purchase.inc.php

require_once 'dbh.inc.php';
require_once 'inventory_functions.inc.php'; // Įtraukiame inventoriaus funkcijas
require_once 'xp.inc.php'; // XP funkcijos

// Patikriname vartotojo kortelės balansą iš card_numbers lentelės
function checkCardBalance($conn, $userId) {
    $stmt = $conn->prepare("SELECT account_balance FROM card_numbers WHERE user_id = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą (checkCardBalance): " . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['account_balance'] : 0;
}

// Nuskaičiuojame pinigus už pirkimą iš card_numbers lentelės
function deductCardBalance($conn, $userId, $amount) {
    $stmt = $conn->prepare("UPDATE card_numbers SET account_balance = account_balance - ? WHERE user_id = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą (deductCardBalance): " . $conn->error);
    }
    $stmt->bind_param('di', $amount, $userId);
    $stmt->execute();
}

// Funkcija, kai vartotojas perka skrynią
function buyChest($conn, $userId, $chestLevel, $price) {
    // Patikriname, ar vartotojas turi pakankamai lėšų
    $balance = checkCardBalance($conn, $userId);

    if ($balance >= $price) {
        // Nuskaičiuojame lėšas
        deductCardBalance($conn, $userId, $price);

        // Pridedame skrynią į vartotojo inventorių
        addToInventory($conn, $userId, 'chest', $chestLevel);

        // Pridedame XP ir patikriname lygį
        $xpGained = XP_PER_CHEST;
        $levelUp = addXpAndCheckLevel($conn, $userId, $xpGained);

        return [
            'success' => true,
            'xpGained' => $xpGained,
            'levelUp' => $levelUp
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Nepakankamas lėšų kiekis.'
        ];
    }
}

// Įsitikinkite, kad funkcija addXpAndCheckLevel() nėra deklaruota šiame faile
