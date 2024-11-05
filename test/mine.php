<?php
// mine.php
require '../includes/dbh.inc.php';

function get_network_hashrate($conn, $currency) {
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

function get_miners_hashrate($conn, $currency) {
    $stmt = mysqli_prepare($conn, "
        SELECT um.user_id, SUM(sm.hash_rate * um.quantity) AS user_hash_rate
        FROM user_mining um
        JOIN shop_items sm ON um.item_id = sm.id
        WHERE sm.cryptocurrency = ?
        GROUP BY um.user_id
    ");
    mysqli_stmt_bind_param($stmt, "s", $currency);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $miners = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $miners;
}

$query_limits = "SELECT * FROM currency_limits WHERE currency IN ('Bitcoin', 'Monero', 'Dogecoin')";
$result_limits = mysqli_query($conn, $query_limits);
$currency_limits = mysqli_fetch_all($result_limits, MYSQLI_ASSOC);
mysqli_free_result($result_limits);

foreach ($currency_limits as $limit) {
    $currency = $limit['currency'];
    $block_reward = (float)$limit['block_reward'];
    $difficulty = (float)$limit['difficulty'];
    $target_block_time = (int)$limit['block_time_seconds'];
    $accumulated_work = (float)$limit['accumulated_work'];
    $max_mined = $limit['max_mined'] !== null ? (float)$limit['max_mined'] : null;
    $halving_interval = $limit['halving_interval'];
    $next_halving_block = $limit['next_halving_block'];
    $tail_emission_reward = isset($limit['tail_emission_reward']) ? (float)$limit['tail_emission_reward'] : null;

    $required_work = $difficulty * pow(2, 32);

    $network_hashrate = get_network_hashrate($conn, $currency);

    $interval_seconds = 60;

    $work_done = $network_hashrate * $interval_seconds;

    $accumulated_work += $work_done;

    $blocks_mined = floor($accumulated_work / $required_work);

    if ($blocks_mined >= 1) {
        $accumulated_work -= $blocks_mined * $required_work;

        $miners = get_miners_hashrate($conn, $currency);

        $total_hashrate = array_sum(array_column($miners, 'user_hash_rate'));

        for ($i = 0; $i < $blocks_mined; $i++) {
            if ($max_mined !== null && $currency !== 'Monero' && $currency !== 'Dogecoin') {
                $stmt_total_mined = mysqli_prepare($conn, "SELECT SUM(amount) FROM mining_logs WHERE currency = ?");
                mysqli_stmt_bind_param($stmt_total_mined, "s", $currency);
                mysqli_stmt_execute($stmt_total_mined);
                mysqli_stmt_bind_result($stmt_total_mined, $total_mined);
                mysqli_stmt_fetch($stmt_total_mined);
                mysqli_stmt_close($stmt_total_mined);

                $total_mined = $total_mined ?? 0;

                if ($total_mined >= $max_mined) {
                    break;
                }
            }

            foreach ($miners as $miner) {
                $user_id = $miner['user_id'];
                $user_hashrate = $miner['user_hash_rate'];

                $user_share = $user_hashrate / $total_hashrate;

                $user_reward = $user_share * $block_reward;

                if ($max_mined !== null && $currency !== 'Monero' && $currency !== 'Dogecoin') {
                    $stmt_user_mined = mysqli_prepare($conn, "SELECT SUM(amount) FROM mining_logs WHERE user_id = ? AND currency = ?");
                    mysqli_stmt_bind_param($stmt_user_mined, "is", $user_id, $currency);
                    mysqli_stmt_execute($stmt_user_mined);
                    mysqli_stmt_bind_result($stmt_user_mined, $user_total_mined);
                    mysqli_stmt_fetch($stmt_user_mined);
                    mysqli_stmt_close($stmt_user_mined);

                    $user_total_mined = $user_total_mined ?? 0;

                    $remaining_user = $max_mined - $user_total_mined;
                    if ($remaining_user <= 0) {
                        continue;
                    }

                    if ($user_reward > $remaining_user) {
                        $user_reward = $remaining_user;
                    }
                }

                if ($user_reward > 0) {
                    $stmt_update_wallet = mysqli_prepare($conn, "
                        INSERT INTO user_wallets (user_id, currency, balance, wallet_address)
                        VALUES (?, ?, ?, 'generated_wallet_address')
                        ON DUPLICATE KEY UPDATE balance = balance + ?
                    ");
                    mysqli_stmt_bind_param($stmt_update_wallet, "isdd", $user_id, $currency, $user_reward, $user_reward);
                    mysqli_stmt_execute($stmt_update_wallet);
                    mysqli_stmt_close($stmt_update_wallet);

                    $stmt_log = mysqli_prepare($conn, "
                        INSERT INTO mining_logs (user_id, currency, amount, timestamp)
                        VALUES (?, ?, ?, NOW())
                    ");
                    mysqli_stmt_bind_param($stmt_log, "isd", $user_id, $currency, $user_reward);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }
            }

            $stmt_block = mysqli_prepare($conn, "
                INSERT INTO blocks (currency, user_id, hash_rate, block_reward, timestamp)
                VALUES (?, 0, ?, ?, NOW())
            ");
            mysqli_stmt_bind_param($stmt_block, "sdd", $currency, $network_hashrate, $block_reward);
            mysqli_stmt_execute($stmt_block);
            mysqli_stmt_close($stmt_block);

            $stmt_count_blocks = mysqli_prepare($conn, "SELECT COUNT(*) FROM blocks WHERE currency = ?");
            mysqli_stmt_bind_param($stmt_count_blocks, "s", $currency);
            mysqli_stmt_execute($stmt_count_blocks);
            mysqli_stmt_bind_result($stmt_count_blocks, $block_count);
            mysqli_stmt_fetch($stmt_count_blocks);
            mysqli_stmt_close($stmt_count_blocks);

            if ($currency === 'Dogecoin' || $currency === 'Monero') {
                $stmt_block_time = mysqli_prepare($conn, "
                    SELECT timestamp FROM blocks WHERE currency = ? ORDER BY id DESC LIMIT 2
                ");
                mysqli_stmt_bind_param($stmt_block_time, "s", $currency);
                mysqli_stmt_execute($stmt_block_time);
                $result_block_time = mysqli_stmt_get_result($stmt_block_time);
                $timestamps = mysqli_fetch_all($result_block_time, MYSQLI_ASSOC);
                mysqli_stmt_close($stmt_block_time);

                if (count($timestamps) == 2) {
                    $latest_time = strtotime($timestamps[0]['timestamp']);
                    $previous_time = strtotime($timestamps[1]['timestamp']);
                    $actual_time = $latest_time - $previous_time;
                    if ($actual_time == 0) {
                        $actual_time = 1;
                    }
                    $new_difficulty = $difficulty * ($target_block_time / $actual_time);
                    if ($new_difficulty < 1) {
                        $new_difficulty = 1;
                    }
                    $difficulty = $new_difficulty;
                }
            } else {
                if ($block_count % 2016 == 0) {
                    $stmt_block_time = mysqli_prepare($conn, "
                        SELECT MIN(timestamp) as min_time, MAX(timestamp) as max_time FROM (
                            SELECT timestamp FROM blocks WHERE currency = ? ORDER BY id DESC LIMIT 2016
                        ) as recent_blocks
                    ");
                    mysqli_stmt_bind_param($stmt_block_time, "s", $currency);
                    mysqli_stmt_execute($stmt_block_time);
                    mysqli_stmt_bind_result($stmt_block_time, $min_time, $max_time);
                    mysqli_stmt_fetch($stmt_block_time);
                    mysqli_stmt_close($stmt_block_time);

                    $actual_time = strtotime($max_time) - strtotime($min_time);
                    if ($actual_time == 0) {
                        $actual_time = 1;
                    }
                    $target_time = $target_block_time * 2016;

                    $new_difficulty = $difficulty * ($actual_time / $target_time);
                    if ($new_difficulty < 1) {
                        $new_difficulty = 1;
                    }

                    $difficulty = $new_difficulty;
                }
            }

            if ($currency === 'Monero') {
                $stmt_total_mined = mysqli_prepare($conn, "SELECT SUM(amount) FROM mining_logs WHERE currency = ?");
                mysqli_stmt_bind_param($stmt_total_mined, "s", $currency);
                mysqli_stmt_execute($stmt_total_mined);
                mysqli_stmt_bind_result($stmt_total_mined, $total_mined);
                mysqli_stmt_fetch($stmt_total_mined);
                mysqli_stmt_close($stmt_total_mined);

                $total_mined = $total_mined ?? 0;

                if ($total_mined < 18400000) {
                    $block_reward = (18400000 - $total_mined) / 218750;
                    if ($block_reward < 0.6) {
                        $block_reward = 0.6;
                    }
                } else {
                    $block_reward = 0.6;
                }
            } elseif ($currency !== 'Dogecoin' && $halving_interval !== null && $next_halving_block !== null) {
                if ($block_count >= $next_halving_block) {
                    $new_block_reward = $block_reward / 2;
                    if ($new_block_reward < 0.00000001) {
                        $new_block_reward = 0.00000001;
                    }
                    $block_reward = $new_block_reward;
                    $next_halving_block += $halving_interval;
                }
            }
        }
    }

    $stmt_update_work = mysqli_prepare($conn, "
        UPDATE currency_limits SET accumulated_work = ?, difficulty = ?, block_reward = ?, next_halving_block = ? WHERE currency = ?
    ");
    mysqli_stmt_bind_param($stmt_update_work, "dddis", $accumulated_work, $difficulty, $block_reward, $next_halving_block, $currency);
    mysqli_stmt_execute($stmt_update_work);
    mysqli_stmt_close($stmt_update_work);
}

echo "Kasimo procesas baigtas " . date('Y-m-d H:i:s') . "\n";
?>
