<?php
// get_mining_info.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

// Patikrinkite, ar vartotojas yra prisijungęs
check_auth();

$user_id = get_user_id();

// Funkcijų apibrėžimai
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
 * Funkcija gauti jau iškastą sumą per vartotoją ir kriptovaliutą
 */
function get_total_mined($conn, $user_id, $currency) {
    $stmt = mysqli_prepare($conn, "SELECT SUM(amount) as total_mined FROM mining_logs WHERE user_id = ? AND currency = ?");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $currency);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_mined);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $total_mined ?? 0;
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
        'next_halving_block' => $limit['next_halving_block']
    ];
}

$currencies = ['Monero', 'Bitcoin', 'Dogecoin'];
$stats = [];

foreach ($currencies as $currency) {
    $weekly = 0;
    $daily = 0;
    $hourly = 0;
    $remaining = 0;
    $block_reward = 0;
    $current_block_count = 0;
    $next_halving_block = 0;

    if (isset($limits[$currency])) {
        // Gauti uždirbtas sumas
        $stmt = mysqli_prepare($conn, "
            SELECT 
                SUM(amount) as weekly_mined_total,
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 WEEK THEN amount ELSE 0 END) as weekly_mined,
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 DAY THEN amount ELSE 0 END) as daily_mined,
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 HOUR THEN amount ELSE 0 END) as hourly_mined
            FROM mining_logs 
            WHERE user_id = ? AND currency = ?
        ");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $currency);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $weekly_mined_total, $weekly_mined, $daily_mined, $hourly_mined);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $weekly = $weekly_mined ?? 0; // Pastarųjų savaitės uždirbtos sumos
        $daily = $daily_mined ?? 0;
        $hourly = $hourly_mined ?? 0;

        // Gauti likusią sumą iki maksimalios
        $total_mined = get_total_mined($conn, $user_id, $currency);
        $remaining = $limits[$currency]['max_mined'] - $total_mined;
        if ($remaining < 0) $remaining = 0;

        // Gauti blokų apdovanojimą ir blokų skaičių
        $block_reward = $limits[$currency]['block_reward'];
        $current_block_count = get_current_block_count($conn, $currency);
        $next_halving_block = $limits[$currency]['next_halving_block'];
    }

    $stats[$currency] = [
        'weekly' => $weekly,
        'daily' => $daily,
        'hourly' => $hourly,
        'remaining' => $remaining,
        'block_reward' => $block_reward,
        'current_block_count' => $current_block_count,
        'next_halving_block' => $next_halving_block
    ];
}

// Gauti vartotojo kasimo įrangą su item_name
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
$total_hash_rates = [
    'Monero' => 0,
    'Bitcoin' => 0,
    'Dogecoin' => 0
];

foreach ($mining_items as $item) {
    if (isset($total_hash_rates[$item['cryptocurrency']])) {
        $total_hash_rates[$item['cryptocurrency']] += $item['hash_rate'] * $item['quantity'];
    }
}

// Paruošti atsakymą
$response = [
    'total_hash_rates' => $total_hash_rates,
    'mining_items' => $mining_items,
    'block_rewards' => [],
    'mining_algorithms' => [],
    'block_times' => []
];

foreach ($currencies as $currency) {
    if (isset($limits[$currency])) {
        $response['block_rewards'][$currency] = $limits[$currency]['block_reward'];
        // Gauti kitas reikšmes iš duomenų bazės
        $stmt = mysqli_prepare($conn, "SELECT mining_algorithm, block_time_seconds FROM currency_limits WHERE currency = ?");
        mysqli_stmt_bind_param($stmt, "s", $currency);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $mining_algorithm, $block_time_seconds);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $response['mining_algorithms'][$currency] = $mining_algorithm;
        $response['block_times'][$currency] = $block_time_seconds;
    }
}

echo json_encode($response);
?>
