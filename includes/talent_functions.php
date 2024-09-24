<?php
// Talentų generavimo funkcija
function generateTalents($chestId, $chestLevel) {
    global $conn;
    $talents = [];

    // Sugeneruojame tris talentus
    for ($i = 0; $i < 3; $i++) {
        $talent = generateTalent($chestLevel);

        // Įrašome talentą į duomenų bazę
        $stmt = $conn->prepare("INSERT INTO talents (chest_id, talent_level, talent_power) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die("Database query failed: " . $conn->error);
        }
        $stmt->bind_param('iii', $chestId, $talent['level'], $talent['power']);
        if (!$stmt->execute()) {
            die("Execution failed: " . $stmt->error);
        }

        // Pridedame talentą į vartotojo inventorių
        addToInventory($chestId, 'talent', $talent['level'], $talent['power']);

        $talents[] = $talent;
    }

    return $talents;
}

// Atsitiktinis talentų generavimas
function generateTalent($chestLevel) {
    // Talentų lygių tikimybės
    $levelChances = [
        1 => 50, // 50% šansas gauti 1 lygio talentą
        2 => 30, // 30% šansas gauti 2 lygio talentą
        3 => 19, // 19% šansas gauti 3 lygio talentą
        4 => 1   // 1% šansas gauti 4 lygio talentą
    ];

    $talentLevel = getRandomTalentLevel($levelChances);
    $talentPower = generateTalentPower($talentLevel);

    return [
        'level' => $talentLevel,
        'power' => $talentPower
    ];
}

// Atsitiktinio talento lygio sugeneravimas pagal tikimybę
function getRandomTalentLevel($levelChances) {
    $rand = rand(1, 100);
    $sum = 0;

    foreach ($levelChances as $level => $chance) {
        $sum += $chance;
        if ($rand <= $sum) {
            return $level;
        }
    }
}

// Talentų stiprumo sugeneravimas pagal lygį
function generateTalentPower($level) {
    // Sugeneruojame atsitiktinį talentų stiprumą pagal lygį
    switch ($level) {
        case 1:
            return rand(1, 30);
        case 2:
            return rand(31, 60);
        case 3:
            return rand(61, 90);
        case 4:
            return rand(91, 100);
    }
}

// Pridedame talentus ar kitus daiktus į vartotojo inventorių, tik jei funkcija dar nebuvo deklaruota
if (!function_exists('addToInventory')) {
    function addToInventory($userId, $itemType, $level, $power = null) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO inventory (user_id, item_type, item_level, item_power) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            die("Database query failed: " . $conn->error);
        }
        $stmt->bind_param('isii', $userId, $itemType, $level, $power);
        if (!$stmt->execute()) {
            die("Execution failed: " . $stmt->error);
        }
    }
}

// Tikrina vartotojo balansą
function checkUserBalance($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['balance'];
}

// Nuskaičiuoja pinigus už pirkimą
function deductUserBalance($userId, $amount) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->bind_param('ii', $amount, $userId);
    $stmt->execute();
}

// Kviesti funkciją, kai vartotojas perka skrynią
function buyChest($userId, $chestLevel, $price) {
    if (checkUserBalance($userId) >= $price) {
        deductUserBalance($userId, $price);
        global $conn;
        
        // Pridedame naują skrynią į duomenų bazę
        $stmt = $conn->prepare("INSERT INTO chests (user_id, chest_level) VALUES (?, ?)");
        $stmt->bind_param('ii', $userId, $chestLevel);
        $stmt->execute();
        $chestId = $stmt->insert_id;

        // Sugeneruojame talentus šiai skryniai
        $talents = generateTalents($chestId, $chestLevel);

        return $talents;
    } else {
        return "Not enough balance";
    }
}
?>
