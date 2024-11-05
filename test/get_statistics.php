<?php
// get_statistics.php
require '../includes/auth.inc.php';
require '../includes/dbh.inc.php';

header('Content-Type: application/json');

check_auth();

$query_limits = "SELECT * FROM currency_limits WHERE currency IN ('Bitcoin', 'Monero', 'Dogecoin')";
$result_limits = mysqli_query($conn, $query_limits);
$currency_limits = mysqli_fetch_all($result_limits, MYSQLI_ASSOC);
mysqli_free_result($result_limits);

$stats = [];

foreach ($currency_limits as $limit) {
    $currency = $limit['currency'];

    $stmt_blocks = mysqli_prepare($conn, "SELECT COUNT(*) FROM blocks WHERE currency = ?");
    mysqli_stmt_bind_param($stmt_blocks, "s", $currency);
    mysqli_stmt_execute($stmt_blocks);
    mysqli_stmt_bind_result($stmt_blocks, $block_count);
    mysqli_stmt_fetch($stmt_blocks);
    mysqli_stmt_close($stmt_blocks);

    $stmt_mined = mysqli_prepare($conn, "SELECT SUM(amount) FROM mining_logs WHERE currency = ?");
    mysqli_stmt_bind_param($stmt_mined, "s", $currency);
    mysqli_stmt_execute($stmt_mined);
    mysqli_stmt_bind_result($stmt_mined, $total_mined);
    mysqli_stmt_fetch($stmt_mined);
    mysqli_stmt_close($stmt_mined);

    $total_mined = $total_mined ?? 0;

    if ($limit['max_mined'] !== null) {
        $remaining = (float)$limit['max_mined'] - (float)$total_mined;
        if ($remaining < 0) $remaining = 0;
    } else {
        $remaining = null;
    }

    $stats[$currency] = [
        'remaining' => $remaining,
        'block_reward' => (float)$limit['block_reward'],
        'current_block_count' => (int)$block_count,
        'difficulty' => (float)$limit['difficulty']
    ];
}

echo json_encode(['stats' => $stats]);
?>
