<?php
global $pdo;
session_start();
require 'db.php';

// Nutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kategorie auswählen
if (!isset($_SESSION['category_id']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    $categories = $pdo->query("SELECT id, name FROM category")->fetchAll();
    echo "<form method='post'>";
    echo "<label>Kategorie wählen:</label>";
    echo "<select name='category_id'>";
    foreach ($categories as $category) {
        echo "<option value='{$category['id']}'>{$category['name']}</option>";
    }
    echo "</select><button type='submit'>Start</button></form>";
    exit;
}

// Kategorie setzen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_id'])) {
    $_SESSION['category_id'] = $_POST['category_id'];
    $_SESSION['question_index'] = 0;
}

// Fragen abrufen
$stmt = $pdo->prepare("SELECT id, text FROM question WHERE category_id = :category_id");
$stmt->execute(['category_id' => $_SESSION['category_id']]);
$questions = $stmt->fetchAll();

// Quiz beendet?
if (empty($questions) || $_SESSION['question_index'] >= count($questions)) {
    header("Location: result.php");
    exit;
}

// Aktuelle Frage
$question = $questions[$_SESSION['question_index']];
$stmt = $pdo->prepare("SELECT id, text FROM answer WHERE question_id = :question_id");
$stmt->execute(['question_id' => $question['id']]);
$answers = $stmt->fetchAll();

// Antwort speichern
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer_id'])) {
    $answer_id = $_POST['answer_id'];
    $stmt = $pdo->prepare("SELECT is_correct FROM answer WHERE id = :answer_id");
    $stmt->execute(['answer_id' => $answer_id]);
    $is_correct = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO result (user_id, question_id, answer_id, is_correct) VALUES (:user_id, :question_id, :answer_id, :is_correct)");
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'question_id' => $question['id'],
        'answer_id' => $answer_id,
        'is_correct' => $is_correct
    ]);

    $_SESSION['question_index']++;
    header("Location: quiz.php");
    exit;
}

// Frage anzeigen
echo "<form method='post'>";
echo "<p>{$question['text']}</p>";
foreach ($answers as $answer) {
    echo "<label><input type='radio' name='answer_id' value='{$answer['id']}' required> {$answer['text']}</label><br>";
}
echo "<button type='submit'>Weiter</button>";
echo "</form>";
?>
<link rel="stylesheet" href="style.css">

