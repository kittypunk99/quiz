<head>
    <title>SuperQuiz: Ergebnisse</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<header>
    <h2>Ergebnisse</h2>
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

// Nutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ergebnisse abrufen
$stmt = $pdo->prepare("SELECT COUNT(*) AS correct FROM result WHERE user_id = :user_id AND is_correct = 1  ");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$correct = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM result WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$total = $stmt->fetchColumn();
unset($_SESSION['category_id']);
try {
    $percentage = $correct / $total * 100;
} catch (DivisionByZeroError $e) {
    $percentage = 0;
}
$stmt = $pdo->prepare("INSERT INTO results (user_id, correct_answers, total_questions, percentage) 
                       VALUES (:user_id, :correct_answers, :total_questions, :percentage)");
$stmt->execute(['user_id' => $_SESSION['user_id'], 'correct_answers' => $correct, 'total_questions' => $total, 'percentage' => $percentage]);
// Ergebnis anzeigen
echo "<h2>Auswertung</h2>";
echo "<p>Richtige Antworten: $correct von $total</p>";
echo "<a href='quiz.php'>Neues Quiz starten</a>";
?>
<link rel="stylesheet" href="style.css">
</body>