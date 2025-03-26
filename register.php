<head>
    <title>SuperQuiz: Registrierung</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<header>
    <h2>Register</h2>
    <nav>
        <ul>
            <li><a href="quiz.php">Quiz</a></li>
            <li><a href="leaderboard.php">Leaderboard</a></li>
            <li><a href="register.php">Registrieren</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="admin.php">Admin</a></li>
        </ul>
    </nav>
</header>
<?php
global $pdo;
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!empty($username) && !empty($_POST['password'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO user (username, password) VALUES (:username, :password)");
            $stmt->execute(['username' => $username, 'password' => $password]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header("Location: quiz.php");
            exit;
        } catch (PDOException $e) {
            echo "Fehler: Benutzername bereits vergeben!";
        }
    } else {
        echo "Fehler: Alle Felder ausfüllen!";
    }
}
?>
<link rel="stylesheet" href="style.css">
<form method="post">
    Benutzername: <input type="text" name="username" required>
    Passwort: <input type="password" name="password" required>
    <button type="submit">Registrieren</button>
</form>
<p class="redir">Sie haben bereits einen Account? <a href="login.php">Hier</a> anmelden!</p>

</body>