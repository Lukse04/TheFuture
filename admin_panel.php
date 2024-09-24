
<!DOCTYPE HTML>

<html lang="lt" >
    <head>
        <?php
        $titel = 'Here is the future';
        include_once 'include_once/header.php';
        ?>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
        <link rel="stylesheet" type="text/css" href="css/profile.css">
    </head>
    <body>
        <?php
        session_start();

        // CSRF token generation
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Ensure the user is an admin
        if (isset($_SESSION['usertype'])) {
            $mysqliType = $_SESSION['usertype'];

            if ($mysqliType !== 'admin') {
                header('Location: index.php');
                exit();
            }
        } else {
            header('Location: index.php');
            exit();
        }

        include_once 'include_once/navbar.php';
        include 'includes/dbh.inc.php';
        
        // Retrieve users data securely
        $query = "SELECT usersId, usersName, usersEmail, user_type FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<table>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>User Type</th>
                  <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['usersId']}</td>
                    <td>{$row['usersName']}</td>
                    <td>{$row['usersEmail']}</td>
                    <td>{$row['user_type']}</td>
                    <td>
                        <a href='edit_user.php?id={$row['usersId']}&csrf_token={$_SESSION['csrf_token']}'>Edit</a>
                    </td>
                  </tr>";
        }

        echo "</table>";
        ?>
    </body>
</html>
