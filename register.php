<?php
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';
if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token'])) {
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
}
?>
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
            <li><a href="admin.php">Admin</a></li>
            <li><a href="register.php">Registrieren</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><?php
                if (isset($_SESSION['user_id'])) {
                    echo(htmlspecialchars($_SESSION['username']));
                } else {
                    echo "Nicht angemeldet!";
                } ?></li>

        </ul>
    </nav>
</header>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF-Token ungültig!");
    }
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!empty($username) && !empty($_POST['password'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO user (username, password) VALUES (:username, :password)");
            $stmt->execute(['username' => $username, 'password' => $password]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = false;
            session_regenerate_id(true);
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
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    Benutzername: <input type="text" name="username" required>
    Passwort: <input type="password" name="password" required>
    <button type="submit">Registrieren</button>
</form>
<p class="redir">Sie haben bereits einen Account? <a href="login.php">Hier</a> anmelden!</p>
<footer>
    Ein Quiz von Linus Freistetter
</footer>
</body>