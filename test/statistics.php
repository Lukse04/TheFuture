<?php
// statistics.php
require '../includes/auth.inc.php';
require '../includes/dbh.inc.php';

check_auth();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Kasimo Statistika</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 900px; margin: auto; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #f4f4f4; }
        .error { color: red; }
        .success { color: green; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1>Kasimo Statistika</h1>
    <table id="statistics-table">
        <thead>
            <tr>
                <th>Valiuta</th>
                <th>Liko Iškasti</th>
                <th>Dabartinis Blokų Apdovanojimas</th>
                <th>Blokų Skaičius</th>
                <th>Sunkumas</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    function updateStatistics() {
        $.ajax({
            url: 'get_statistics.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                var tbody = $('#statistics-table tbody');
                tbody.empty();
                $.each(data.stats, function(currency, stats) {
                    var remaining = stats.remaining !== null ? parseFloat(stats.remaining).toFixed(8) : 'Neribota';
                    var block_reward = parseFloat(stats.block_reward).toFixed(8);
                    var difficulty = parseFloat(stats.difficulty).toFixed(8);
                    var row = '<tr>' +
                        '<td>' + currency + '</td>' +
                        '<td>' + remaining + '</td>' +
                        '<td>' + block_reward + ' ' + currency + '</td>' +
                        '<td>' + stats.current_block_count + '</td>' +
                        '<td>' + difficulty + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });
            },
            error: function(xhr) {
                console.error('Klaida gaunant statistiką:', xhr.responseText);
            }
        });
    }

    updateStatistics();

    setInterval(updateStatistics, 60000);
});
</script>

</body>
</html>
