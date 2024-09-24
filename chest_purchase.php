<?php
require_once 'includes/dbh.inc.php';
require_once 'includes/talent_functions.php'; // Talentų funkcijos

// Patikriname vartotojo kortelės balansą iš card_numbers lentelės
function checkCardBalance($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT account_balance FROM card_numbers WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['account_balance'];
}

// Nuskaičiuojame pinigus už pirkimą iš card_numbers lentelės
function deductCardBalance($userId, $amount) {
    global $conn;
    $stmt = $conn->prepare("UPDATE card_numbers SET account_balance = account_balance - ? WHERE user_id = ?");
    $stmt->bind_param('di', $amount, $userId);
    $stmt->execute();
}


// Apibrėžiame XP taškų kiekį už kiekvieną skrynią
define('XP_PER_CHEST', 50); // Pvz., 50 XP už skrynią

// Pridedame XP ir atnaujiname vartotojo lygį
function addXpAndCheckLevel($userId) {
    global $conn;

    // Pridedame XP už nusipirktą skrynią
    echo "Pridedame XP vartotojui su ID: $userId<br>";
    $xpAmount = XP_PER_CHEST; // Apsaugome nuo tiesioginių verčių
    $stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE usersId = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $xpAmount, $userId);
        if ($stmt->execute()) {
            echo "XP pridėti sėkmingai<br>";
        } else {
            echo "Nepavyko pridėti XP: " . $stmt->error . "<br>";
        }
    } else {
        echo "Klaida ruošiant užklausą: " . $conn->error . "<br>";
    }

    // Patikriname, ar reikia pakelti vartotojo lygį
    echo "Tikriname vartotojo lygį<br>";
    $stmt = $conn->prepare("SELECT xp, level FROM users WHERE usersId = ?");
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        echo "Dabartiniai vartotojo XP: " . $user['xp'] . ", lygis: " . $user['level'] . "<br>";

        $xpNeededForNextLevel = $user['level'] * 1000;

        if ($user['xp'] >= $xpNeededForNextLevel) {
            echo "Vartotojas pasiekė naują lygį<br>";
            $stmt = $conn->prepare("UPDATE users SET level = level + 1, xp = xp - ? WHERE usersId = ?");
            $stmt->bind_param('ii', $xpNeededForNextLevel, $userId);
            if ($stmt->execute()) {
                echo "Lygis padidintas<br>";
            } else {
                echo "Nepavyko atnaujinti lygio<br>";
            }
        } else {
            echo "Vartotojui reikia " . ($xpNeededForNextLevel - $user['xp']) . " XP norint pasiekti kitą lygį<br>";
        }
    } else {
        echo "Nepavyko gauti vartotojo informacijos<br>";
    }
}
?>

