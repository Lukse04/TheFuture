<?php
// my_facilities.php
require '../includes/auth.inc.php';
require '../includes/dbh.inc.php';

check_auth();

$user_id = get_user_id();

$stmt = mysqli_prepare($conn, "
    SELECT uf.id as user_facility_id, f.name, f.type, uf.start_date, uf.end_date, uf.status
    FROM user_facilities uf
    JOIN facilities f ON uf.facility_id = f.id
    WHERE uf.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_facilities = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Mano Patalpos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 900px; margin: auto; padding: 20px; }
        h1 { color: #333; }
        .facility {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        }
        .facility h2 { margin-top: 0; }
        .button { padding: 10px 15px; background-color: #007bff; color: #fff; border: none; cursor: pointer; }
        .button:hover { background-color: #0056b3; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1>Mano Patalpos</h1>
    <?php foreach ($user_facilities as $facility): ?>
        <div class="facility">
            <h2><?php echo htmlspecialchars($facility['name']); ?></h2>
            <p>Tipas: <?php echo htmlspecialchars($facility['type']); ?></p>
            <p>Pradžios data: <?php echo htmlspecialchars($facility['start_date']); ?></p>
            <p>Pabaigos data: <?php echo htmlspecialchars($facility['end_date'] ?? 'Nėra'); ?></p>
            <p>Statusas: <?php echo htmlspecialchars($facility['status']); ?></p>
            <?php if ($facility['status'] === 'active' && $facility['type'] === 'rent'): ?>
                <button class="button cancel-button" data-user-facility-id="<?php echo $facility['user_facility_id']; ?>">Nutraukti Nuomą</button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
$(document).ready(function() {
    $('.cancel-button').click(function() {
        var user_facility_id = $(this).data('user-facility-id');
        var csrf_token = '<?php echo $csrf_token; ?>';

        if (!confirm('Ar tikrai norite nutraukti nuomos sutartį?')) {
            return;
        }

        $.ajax({
            url: 'cancel_rental.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                user_facility_id: user_facility_id,
                csrf_token: csrf_token
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.success);
                    location.reload();
                } else if (response.error) {
                    alert('Klaida: ' + response.error);
                }
            },
            error: function(xhr) {
                var error = 'Nepavyko nutraukti nuomos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    error = xhr.responseJSON.error;
                }
                alert('Klaida: ' + error);
            }
        });
    });
});
</script>

</body>
</html>
