<?php
require_once 'includes/auth.inc.php';

check_auth();

$user_id = get_user_id();

// Gauti vartotojo inventoriaus daiktus
require_once 'includes/dbh.inc.php';

$stmt = $conn->prepare('SELECT id, item_name FROM inventory WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

// Gauti kitus vartotojus
$stmt = $conn->prepare('SELECT usersId, usersName FROM users WHERE usersId != ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dovanoti daiktą</title>
</head>
<body>
    <h1>Dovanoti daiktą</h1>
    <form action="includes/api/items/gift_item.inc.php" method="POST">
        <label for="item_id">Pasirinkite daiktą:</label>
        <select name="item_id" id="item_id" required>
            <?php foreach ($items as $item): ?>
                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="recipient_id">Pasirinkite gavėją:</label>
        <select name="recipient_id" id="recipient_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['usersId']; ?>"><?php echo htmlspecialchars($user['usersName']); ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Dovanoti</button>
    </form>
</body>
</html>
