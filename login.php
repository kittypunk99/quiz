<?php
global $pdo;
session_start();
require 'db.php';

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
<form method="post">
    Benutzername: <input type="text" name="username" required>
    Passwort: <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
