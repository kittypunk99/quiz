<head>
    <title>SuperQuiz: Login</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<header>
    <h2>Login</h2>
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
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password FROM user WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: quiz.php");
        exit;
    } else {
        echo "Fehler: Falsche Zugangsdaten!";
    }
}
?>
<link rel='stylesheet' href='style.css'>
<form method="post">
    Benutzername: <input type="text" name="username" required>
    Passwort: <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
<p class="redir">Kein Account? <a href="register.php">Hier</a> registrieren!</p>
</body>