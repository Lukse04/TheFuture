<?php
// mine.php
require '../includes/auth.inc.php'; // Autentifikacijos failas
require '../includes/dbh.inc.php';   // Duomenų bazės prisijungimo failas

// Šis skriptas turėtų būti vykdomas per cron job arba kitokiu būdu periodiškai.

header('Content-Type: text/plain'); // Nesvarbu, galime palikti tekstinį atsakymą

// Funkcijų apibrėžimai

/**
 * Gauti bendrą hash rate kiekvienai kriptovaliutai
 */
function get_total_hash_rate($conn, $currency) {
    $stmt = mysqli_prepare($conn, "
        SELECT SUM(sm.hash_rate * um.quantity) AS total_hash_rate
        FROM user_mining um
        JOIN shop_items sm ON um.item_id = sm.id
        WHERE sm.cryptocurrency = ?
    ");
    mysqli_stmt_bind_param($stmt, "s", $currency);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_hash_rate);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $total_hash_rate ?? 0;
}

/**
 * Funkcija apskaičiuoti sudėtingumą pagal tinklo galią ir atnaujinti jį
 */
function adjust_difficulty($conn, &$limits, $currency) {
    // Gauti bendrą hash rate tinkle
    $total_hash_rate = get_total_hash_rate($conn, $currency);

    // Tikslinis blokų generavimo laikas
    $target_block_time = $limits[$currency]['block_time_seconds'];

    // Nustatyti bazinį sudėtingumą pagal algoritmą
    switch ($limits[$currency]['mining_algorithm']) {
        case 'SHA-256':
            $base_difficulty = 1.0;
            break;
        case 'RandomX':
            $base_difficulty = 1.5;
            break;
        case 'Scrypt':
            $base_difficulty = 0.8;
            break;
        default:
            $base_difficulty = 1.0;
    }

    // Pritaikyti formulę pagal tinklo hash rate ir tikslinį blokų laiką
    // 1,000,000 yra skalės faktorius, pritaikykite pagal poreikį
    $new_difficulty = ($total_hash_rate * $target_block_time) / ($base_difficulty * 1000000);

    // Riboti sudėtingumą tam tikram diapazonui
    if ($new_difficulty < 1.0) $new_difficulty = 1.0;
    if ($new_difficulty > 1000.0) $new_difficulty = 1000.0;

    // Atnaujinti sudėtingumą `currency_limits` lentelėje
    $stmt_update_diff = mysqli_prepare($conn, "
        UPDATE currency_limits 
        SET difficulty = ? 
        WHERE currency = ?
    ");
    mysqli_stmt_bind_param($stmt_update_diff, "ds", $new_difficulty, $currency);
    mysqli_stmt_execute($stmt_update_diff);
    mysqli_stmt_close($stmt_update_diff);

    // Atnaujinti `limits` masyvą
    $limits[$currency]['difficulty'] = $new_difficulty;

    // Pridėti log'ą apie sudėtingumo pakeitimą
    file_put_contents('difficulty_log.txt', date('Y-m-d H:i:s') . " - " . $currency . " sudėtingumas pakeistas į " . $new_difficulty . "\n", FILE_APPEND);
}

/**
 * Funkcija apskaičiuoti, ar blokas iškasta pilnas ar dalinis
 */
function determine_block_completion($earned, $block_reward) {
    if ($earned >= $block_reward) {
        return ['complete' => true, 'earned' => $block_reward];
    } else {
        return ['complete' => false, 'earned' => $earned];
    }
}

/**
 * Funkcija apskaičiuoti, ar reikalingas halvingas ir jį pritaikyti
 */
function check_and_apply_halving($conn, &$limits, $currency) {
    $current_block_count = get_current_block_count($conn, $currency);

    if ($current_block_count >= $limits[$currency]['next_halving_block']) {
        // Atlikti halvingą: sumažinti blokų apdovanojimą per pusę
        $new_block_reward = $limits[$currency]['block_reward'] / 2;

        // Atnaujinti `currency_limits` lentelę
        $stmt_update_limit = mysqli_prepare($conn, "
            UPDATE currency_limits 
            SET block_reward = ?, 
                next_halving_block = next_halving_block + halving_interval
            WHERE currency = ?
        ");
        mysqli_stmt_bind_param($stmt_update_limit, "ds", $new_block_reward, $currency);
        mysqli_stmt_execute($stmt_update_limit);
        mysqli_stmt_close($stmt_update_limit);

        // Atnaujinti `limits` masyvą
        $limits[$currency]['block_reward'] = $new_block_reward;
        $limits[$currency]['next_halving_block'] += $limits[$currency]['halving_interval'];

        // Pridėti log'ą apie halvingą
        file_put_contents('halving_log.txt', date('Y-m-d H:i:s') . " - " . $currency . " blokų apdovanojimas sumažintas per pusę iki " . $new_block_reward . "\n", FILE_APPEND);
    }
}

/**
 * Gauti dabartinį blokų skaičių tam tikrai kriptovaliutai
 */
function get_current_block_count($conn, $currency) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM blocks WHERE currency = ?");
    mysqli_stmt_bind_param($stmt, "s", $currency);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count;
}

/**
 * Funkcija apskaičiuoti uždirbtą sumą pagal hash rate ir sudėtingumą
 */
function calculate_earned_amount($total_hash_rate, $block_reward, $difficulty) {
    // Pritaikyta formulė, kurią galite koreguoti pagal poreikį
    // Ši formulė gali būti paprasta simuliacija
    // Pvz., (hash_rate / difficulty) * block_reward per ciklą
    return ($total_hash_rate / $difficulty) * $block_reward;
}

// Gauti visų kriptovaliutų kasimo limitus ir sunkumus
$query_limits = "SELECT * FROM currency_limits WHERE currency IN ('Bitcoin', 'Monero', 'Dogecoin')";
$result_limits = mysqli_query($conn, $query_limits);
$currency_limits = mysqli_fetch_all($result_limits, MYSQLI_ASSOC);
mysqli_free_result($result_limits);

$limits = [];
foreach ($currency_limits as $limit) {
    $limits[$limit['currency']] = [
        'max_mined' => $limit['max_mined'],
        'block_reward' => $limit['block_reward'],
        'halving_interval' => $limit['halving_interval'],
        'next_halving_block' => $limit['next_halving_block'],
        'mining_algorithm' => $limit['mining_algorithm'],
        'block_time_seconds' => $limit['block_time_seconds'],
        'difficulty' => $limit['difficulty']
    ];
}

// Gauti visus vartotojus
$query_users = "SELECT usersId FROM users";
$result_users = mysqli_query($conn, $query_users);
$users = mysqli_fetch_all($result_users, MYSQLI_ASSOC);
mysqli_free_result($result_users);

// Iteruoti per visus vartotojus
foreach ($users as $user) {
    $user_id = $user['usersId'];

    // Gauti vartotojo kasimo įrangą
    $stmt_mining = mysqli_prepare($conn, "
        SELECT sm.item_name, sm.hash_rate, um.quantity, sm.cryptocurrency 
        FROM user_mining um
        JOIN shop_items sm ON um.item_id = sm.id
        WHERE um.user_id = ? AND sm.cryptocurrency IN ('Bitcoin', 'Monero', 'Dogecoin')
    ");
    mysqli_stmt_bind_param($stmt_mining, "i", $user_id);
    mysqli_stmt_execute($stmt_mining);
    $result_mining = mysqli_stmt_get_result($stmt_mining);
    $mining_items = mysqli_fetch_all($result_mining, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_mining);

    // Apskaičiuoti bendrą hash rate kiekvienai kriptovaliutai
    $user_hash_rates = [
        'Monero' => 0,
        'Bitcoin' => 0,
        'Dogecoin' => 0
    ];

    foreach ($mining_items as $item) {
        if (isset($user_hash_rates[$item['cryptocurrency']])) {
            $user_hash_rates[$item['cryptocurrency']] += $item['hash_rate'] * $item['quantity'];
        }
    }

    foreach ($user_hash_rates as $currency => $total_hash_rate) {
        if ($total_hash_rate > 0 && isset($limits[$currency])) {
            $block_reward = $limits[$currency]['block_reward'];
            $difficulty = $limits[$currency]['difficulty'];

            // Apskaičiuokite uždirbtą sumą
            $earned = calculate_earned_amount($total_hash_rate, $block_reward, $difficulty);

            // Gauti jau iškasta suma
            $stmt_mined = mysqli_prepare($conn, "
                SELECT SUM(amount) as total_mined 
                FROM mining_logs 
                WHERE user_id = ? AND currency = ?
            ");
            mysqli_stmt_bind_param($stmt_mined, "is", $user_id, $currency);
            mysqli_stmt_execute($stmt_mined);
            mysqli_stmt_bind_result($stmt_mined, $total_mined);
            mysqli_stmt_fetch($stmt_mined);
            mysqli_stmt_close($stmt_mined);

            $total_mined = $total_mined ?? 0;

            // Patikrinti, ar dar galima kasinti
            if ($total_mined >= $limits[$currency]['max_mined']) {
                continue; // Pasiekta maksimali suma
            }

            // Apskaičiuoti, kiek galima kasinti
            $remaining = $limits[$currency]['max_mined'] - $total_mined;
            if ($earned > $remaining) {
                $earned = $remaining;
            }

            // Apskaičiuoti, ar blokas iškasta pilnas ar dalinis
            $block_info = determine_block_completion($earned, $block_reward);
            $block_complete = $block_info['complete'];
            $earned_amount = $block_info['earned'];

            // Priskirti iškasėtą sumą
            if ($earned_amount > 0) {
                mysqli_begin_transaction($conn);

                try {
                    // Atnaujinti vartotojo piniginę
                    $stmt_update_wallet = mysqli_prepare($conn, "
                        UPDATE user_wallets 
                        SET balance = balance + ? 
                        WHERE user_id = ? AND currency = ?
                    ");
                    mysqli_stmt_bind_param($stmt_update_wallet, "dis", $earned_amount, $user_id, $currency);
                    mysqli_stmt_execute($stmt_update_wallet);
                    mysqli_stmt_close($stmt_update_wallet);

                    // Įrašyti kasimo įrašą
                    $stmt_log = mysqli_prepare($conn, "
                        INSERT INTO mining_logs (user_id, currency, amount, timestamp) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    mysqli_stmt_bind_param($stmt_log, "isd", $user_id, $currency, $earned_amount);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);

                    // Jei blokas iškasta pilnas, įrašyti bloką į `blocks` lentelę
                    if ($block_complete) {
                        $stmt_block = mysqli_prepare($conn, "
                            INSERT INTO blocks (currency, user_id, hash_rate, block_reward, timestamp)
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        mysqli_stmt_bind_param($stmt_block, "sidd", $currency, $user_id, $total_hash_rate, $earned_amount);
                        mysqli_stmt_execute($stmt_block);
                        mysqli_stmt_close($stmt_block);

                        // Patikrinti ir pritaikyti halvingą, jei reikia
                        check_and_apply_halving($conn, $limits, $currency);
                    }

                    // Dinaminio sudėtingumo reguliavimas
                    adjust_difficulty($conn, $limits, $currency);

                    // Pridėti log'ą apie kasimą
                    file_put_contents('mining_log.txt', date('Y-m-d H:i:s') . " - User ID: $user_id, Currency: $currency, Earned: $earned_amount, Block Complete: " . ($block_complete ? 'Yes' : 'No') . "\n", FILE_APPEND);

                    mysqli_commit($conn);
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    // Pridėti klaidų log'ą
                    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - Mining failed for User ID: $user_id, Currency: $currency. Error: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }
    }
}

echo "Kasimo procesas baigtas " . date('Y-m-d H:i:s') . "\n";
?>
