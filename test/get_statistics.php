<?php
// get_statistics.php
require '../includes/auth.inc.php'; // Autentifikacijos failas
require '../includes/dbh.inc.php';   // Duomenų bazės prisijungimo failas

header('Content-Type: application/json');

// Patikrinkite, ar vartotojas yra prisijungęs
check_auth();

// Funkcija gauti dabartinį blokų skaičių tam tikrai kriptovaliutai
function get_current_block_count($conn, $currency) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM blocks WHERE currency = ?");
    mysqli_stmt_bind_param($stmt, "s", $currency);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count;
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

// Gauti bendrą kasintą sumą per valandą, per dieną ir per savaitę visiems vartotojams
foreach ($currencies as $currency) {
    $weekly = 0;
    $daily = 0;
    $hourly = 0;
    $remaining = 0;
    $block_reward = 0;
    $current_block_count = 0;
    $next_halving_block = 0;

    if (isset($limits[$currency])) {
        // Gauti kasintas sumas visiems vartotojams
        $stmt = mysqli_prepare($conn, "
            SELECT 
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 WEEK THEN amount ELSE 0 END) as weekly_mined,
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 DAY THEN amount ELSE 0 END) as daily_mined,
                SUM(CASE WHEN timestamp >= NOW() - INTERVAL 1 HOUR THEN amount ELSE 0 END) as hourly_mined
            FROM mining_logs 
            WHERE currency = ?
        ");
        mysqli_stmt_bind_param($stmt, "s", $currency);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $weekly_mined, $daily_mined, $hourly_mined);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $weekly = $weekly_mined ?? 0;
        $daily = $daily_mined ?? 0;
        $hourly = $hourly_mined ?? 0;

        // Gauti bendrą iškastą sumą ir likusią sumą iki maksimalios
        $stmt_total_mined = mysqli_prepare($conn, "
            SELECT SUM(amount) as total_mined_all_users
            FROM mining_logs
            WHERE currency = ?
        ");
        mysqli_stmt_bind_param($stmt_total_mined, "s", $currency);
        mysqli_stmt_execute($stmt_total_mined);
        mysqli_stmt_bind_result($stmt_total_mined, $total_mined_all_users);
        mysqli_stmt_fetch($stmt_total_mined);
        mysqli_stmt_close($stmt_total_mined);

        $remaining = $limits[$currency]['max_mined'] - $total_mined_all_users;
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

// Apskaičiuoti bendrą hash rate kiekvienai kriptovaliutai visiems vartotojams
$query_mining_stats = "
    SELECT sm.cryptocurrency, SUM(sm.hash_rate * um.quantity) as total_hash_rate
    FROM user_mining um
    JOIN shop_items sm ON um.item_id = sm.id
    WHERE sm.cryptocurrency IN ('Bitcoin', 'Monero', 'Dogecoin')
    GROUP BY sm.cryptocurrency
";
$result_mining_stats = mysqli_query($conn, $query_mining_stats);
$total_hash_rate = [
    'Monero' => 0,
    'Bitcoin' => 0,
    'Dogecoin' => 0
];
while ($row = mysqli_fetch_assoc($result_mining_stats)) {
    $currency = $row['cryptocurrency'];
    $hash_rate = $row['total_hash_rate'];
    if (isset($total_hash_rate[$currency])) {
        $total_hash_rate[$currency] = $hash_rate;
    }
}
mysqli_free_result($result_mining_stats);

// Gauti bendrą pinigų sumą iš kortelių visiems vartotojams
$query_total_balance = "
    SELECT SUM(account_balance) as total_balance
    FROM card_numbers
";
$result_total_balance = mysqli_query($conn, $query_total_balance);
$row = mysqli_fetch_assoc($result_total_balance);
$total_balance = $row['total_balance'] ?? 0;
mysqli_free_result($result_total_balance);

$additional_stats = [
    'Bendra Pinigų Suma' => $total_balance,
    'Bendra Iškasta Suma' => []
];

// Gauti bendrą iškastą sumą visiems vartotojams pagal kriptovaliutą
$stmt_total_mined_all = mysqli_prepare($conn, "
    SELECT currency, SUM(amount) as total_mined_all_users
    FROM mining_logs
    GROUP BY currency
");
mysqli_stmt_execute($stmt_total_mined_all);
$result_total_mined_all = mysqli_stmt_get_result($stmt_total_mined_all);
while ($row = mysqli_fetch_assoc($result_total_mined_all)) {
    $currency = $row['currency'];
    $total_mined_all_users = $row['total_mined_all_users'];
    $additional_stats['Bendra Iškasta Suma'][$currency] = $total_mined_all_users;
}
mysqli_stmt_close($stmt_total_mined_all);

// Paruošti atsakymą
$response = [
    'stats' => $stats,
    'total_hash_rate' => $total_hash_rate,
    'additional_stats' => $additional_stats
];

echo json_encode($response);
?>
