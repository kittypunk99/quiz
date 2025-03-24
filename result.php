<?php
global $pdo;
session_start();
require 'db.php';

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
$percentage = $correct / $total * 100;
$stmt = $pdo->prepare("INSERT INTO results (user_id, correct_answers, total_questions, percentage) 
                       VALUES (:user_id, :correct_answers, :total_questions, :percentage)");
$stmt->execute([
    'user_id' => $_SESSION['user_id'],
    'correct_answers' => $correct,
    'total_questions' => $total,
    'percentage' => $percentage
]);
// Ergebnis anzeigen
echo "<h2>Auswertung</h2>";
echo "<p>Richtige Antworten: $correct von $total</p>";
echo "<a href='quiz.php'>Neues Quiz starten</a>";
?>
<link rel="stylesheet" href="style.css">
