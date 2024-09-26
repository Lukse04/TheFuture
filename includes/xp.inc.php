<?php
// includes/xp.inc.php

// Apibrėžiame XP taškų kiekį už kiekvieną skrynią
if (!defined('XP_PER_CHEST')) {
    define('XP_PER_CHEST', 50); // Pvz., 50 XP už skrynią
}

// Pridedame XP vartotojui
function addXp($conn, $userId, $xpAmount) {
    $stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE usersId = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą (addXp): " . $conn->error);
    }
    $stmt->bind_param('ii', $xpAmount, $userId);
    $stmt->execute();
}

// Patikriname, ar reikia pakelti vartotojo lygį
function checkLevelUp($conn, $userId) {
    $stmt = $conn->prepare("SELECT xp, level FROM users WHERE usersId = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą (checkLevelUp): " . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $xpNeededForNextLevel = $user['level'] * 1000;

    if ($user['xp'] >= $xpNeededForNextLevel) {
        $stmt = $conn->prepare("UPDATE users SET level = level + 1, xp = xp - ? WHERE usersId = ?");
        if (!$stmt) {
            die("Klaida ruošiant užklausą (level up): " . $conn->error);
        }
        $stmt->bind_param('ii', $xpNeededForNextLevel, $userId);
        $stmt->execute();
        return true; // Lygis pakeltas
    }
    return false; // Lygis nepakeistas
}

// Pridedame XP ir patikriname lygį
function addXpAndCheckLevel($conn, $userId, $xpAmount) {
    addXp($conn, $userId, $xpAmount);
    return checkLevelUp($conn, $userId);
}
