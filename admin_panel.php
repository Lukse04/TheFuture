<?php
// admin_panel.php

// Įtraukite funkcijų failą
require_once 'includes/admin_panel.inc.php';
?>

<!DOCTYPE HTML>
<html lang="lt">
<head>
    <?php
    $titel = 'Administratoriaus panelė';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/admin_panel.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>

    <section class="admin-panel">
        <h1>Administratoriaus panelė</h1>

        <?php if (isset($_GET['message']) && $_GET['message'] == 'success'): ?>
            <p class="success-message">Vartotojo duomenys sėkmingai atnaujinti.</p>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Vartotojo vardas</th>
                <th>El. paštas</th>
                <th>Vartotojo tipas</th>
                <th>Veiksmas</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['usersId']); ?></td>
                    <td><?php echo htmlspecialchars($user['usersName']); ?></td>
                    <td><?php echo htmlspecialchars($user['usersEmail']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                    <td>
                        <a href="edit_user.php?id=<?php echo urlencode($user['usersId']); ?>">Redaguoti</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
</body>
</html>
