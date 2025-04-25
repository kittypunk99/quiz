<?php
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
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
            <li><a href="admin.php">Admin</a></li>
            <li><a href="register.php">Registrieren</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><?php
                if (isset($_SESSION['user_id'])) {
                    echo htmlspecialchars($_SESSION['username']);
                } else {
                    echo "Nicht angemeldet!";
                } ?></li>
        </ul>
    </nav>
</header>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF-Token ungültig!");
    }
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT id, password, is_admin FROM user WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $user['is_admin'] == 1;
        session_regenerate_id(true);
        header("Location: quiz.php");
        exit;
    } else {
        echo "Fehler: Falsche Zugangsdaten!";
    }
}
?>
<link rel='stylesheet' href='style.css'>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    Benutzername: <input type="text" name="username" required>
    Passwort: <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
<p class="redir">Kein Account? <a href="register.php">Hier</a> registrieren!</p>
<footer>
    Ein Quiz von Linus Freistetter
</footer>
</body>