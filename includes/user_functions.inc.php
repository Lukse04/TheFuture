<?php
// includes/user_functions.inc.php

// Funkcija, kuri gauna vartotojo lygio ir XP informaciją
function getUserLevelInfo($conn, $userId) {
    $stmt = $conn->prepare("SELECT xp, level FROM users WHERE usersId = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą (getUserLevelInfo): " . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $currentXP = $user['xp'];
        $currentLevel = $user['level'];
        $xpForNextLevel = $currentLevel * 1000; // Pvz., 1000 XP per lygį
        $xpPercentage = ($currentXP / $xpForNextLevel) * 100;

        return [
            'currentXP' => $currentXP,
            'currentLevel' => $currentLevel,
            'xpForNextLevel' => $xpForNextLevel,
            'xpPercentage' => $xpPercentage,
        ];
    } else {
        die("Vartotojas nerastas.");
    }
}

// Funkcija atnaujinti vartotojo lygio informaciją po pirkimo
function updateUserLevelInfo($conn, $userId) {
    return getUserLevelInfo($conn, $userId);
}
