<?php
// includes/talent_functions.inc.php

require_once 'inventory_functions.inc.php'; // Įtraukiame inventoriaus funkcijas

// Talentų generavimo funkcija
function generateTalents($conn, $userId, $chestLevel) {
    $talents = [];

    // Sugeneruojame tris talentus
    for ($i = 0; $i < 3; $i++) {
        $talent = generateTalent($chestLevel);

        // Pridedame talentą į vartotojo inventorių
        addToInventory($conn, $userId, 'talent', $talent['level'], $talent['power']);

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
    return 1; // Jei kažkas negerai, grąžiname 1 lygį
}

// Talentų stiprumo sugeneravimas pagal lygį
function generateTalentPower($level) {
    switch ($level) {
        case 1:
            return rand(1, 30);
        case 2:
            return rand(31, 60);
        case 3:
            return rand(61, 90);
        case 4:
            return rand(91, 100);
        default:
            return rand(1, 30);
    }
}

// Įsitikinkite, kad funkcija addToInventory() nėra deklaruota šiame faile
