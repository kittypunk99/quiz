<?php
global $pdo;
session_start();
require 'db.php';

// Prüfen, ob der Nutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Admin-Rechte prüfen (hier nur ID = 1 als Beispiel)
if ($_SESSION['user_id'] != 1) {
    die("Zugriff verweigert!");
}

// Neue Kategorie hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_category'])) {
    $name = trim($_POST['category_name']);
    $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO category (name, parent_id) VALUES (:name, :parent_id)");
        $stmt->execute(['name' => $name, 'parent_id' => $parent_id]);
    }
}

// Kategorie löschen (nur wenn keine Fragen enthalten sind)

if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM question WHERE category_id = :category_id");
    $stmt->execute(['category_id' => $category_id]);


    if ($stmt->fetchColumn() == 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM category WHERE id = :category_id");
            $stmt->execute(['category_id' => $category_id]);
        } catch (PDOException $e) {
            // Wenn der Fehler durch den Trigger ausgelöst wurde
            if ($e->getCode() == 45000) {
                echo "<div class='error-message'>Fehler: " . $e->getMessage() . "</div>";
            } else {
                // Allgemeine Fehlerbehandlung
                echo "<div class='error-message'>Ein Fehler ist beim Löschen der Kategorie aufgetreten. Bitte versuche es später erneut.</div>";
            }

        }
    } else {
        echo "Fehler: Kategorie enthält Fragen und kann nicht gelöscht werden!";
    }
}

// Neue Frage hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_question'])) {
    $category_id = $_POST['category_id'];
    $text = trim($_POST['question_text']);

    if (!empty($text)) {
        $stmt = $pdo->prepare("INSERT INTO question (category_id, text) VALUES (:category_id, :text)");
        $stmt->execute(['category_id' => $category_id, 'text' => $text]);
    }
}

// Frage löschen (löscht auch alle Antworten zur Frage)
if (isset($_POST['delete_question'])) {
    $question_id = $_POST['question_id'];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM answer WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);

    $stmt = $pdo->prepare("DELETE FROM question WHERE id = :question_id");
    $stmt->execute(['question_id' => $question_id]);

    $pdo->commit();
}

// Neue Antwort hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_answer'])) {
    $question_id = $_POST['question_id'];
    $text = trim($_POST['answer_text']);
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;

    if (!empty($text)) {
        $stmt = $pdo->prepare("INSERT INTO answer (question_id, text, is_correct) VALUES (:question_id, :text, :is_correct)");
        $stmt->execute(['question_id' => $question_id, 'text' => $text, 'is_correct' => $is_correct]);
    }
}

// Antwort löschen
if (isset($_POST['delete_answer'])) {
    $answer_id = $_POST['answer_id'];
    $stmt = $pdo->prepare("DELETE FROM answer WHERE id = :answer_id");
    $stmt->execute(['answer_id' => $answer_id]);
}

// Kategorien abrufen
$categories = $pdo->query("SELECT * FROM category")->fetchAll();

// Fragen abrufen
$questions = $pdo->query("SELECT q.id, q.text, c.name FROM question q JOIN category c ON q.category_id = c.id")->fetchAll();

// Antworten abrufen
$answers = $pdo->query("SELECT a.id, a.text, q.text AS question FROM answer a JOIN question q ON a.question_id = q.id")->fetchAll();
?>
<link rel="stylesheet" href="style.css">

<h2>Admin-Bereich</h2>
<!-- Kategorie hinzufügen -->
<h3>Neue Kategorie</h3>
<form method="post">
    <input type="text" name="category_name" placeholder="Kategoriename" required>
    <select name="parent_id">
        <?php foreach ($categories as $cat) {
            echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
        } ?>
    </select>
    <button type="submit" name="new_category">Hinzufügen</button>
</form>

<!-- Kategorie löschen -->
<h3>Kategorie löschen</h3>
<form method="post">
    <select name="category_id" required>
        <?php foreach ($categories as $cat) {
            echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
        } ?>
    </select>
    <button type="submit" name="delete_category">Löschen</button>
</form>

<!-- Frage hinzufügen -->
<h3>Neue Frage</h3>
<form method="post">
    <select name="category_id" required>
        <?php foreach ($categories as $cat) {
            echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
        } ?>
    </select>
    <input type="text" name="question_text" placeholder="Fragetext" required>
    <button type="submit" name="new_question">Hinzufügen</button>
</form>

<!-- Frage löschen -->
<h3>Frage löschen</h3>
<form method="post">
    <select name="question_id" required>
        <?php foreach ($questions as $q) {
            echo "<option value='{$q['id']}'>{$q['text']} ({$q['name']})</option>";
        } ?>
    </select>
    <button type="submit" name="delete_question">Löschen</button>
</form>

<!-- Antwort hinzufügen -->
<h3>Neue Antwort</h3>
<form method="post">
    <select name="question_id" required>
        <?php foreach ($questions as $q) {
            echo "<option value='{$q['id']}'>{$q['text']} ({$q['name']})</option>";
        } ?>
    </select>
    <input type="text" name="answer_text" placeholder="Antworttext" required>
    <label><input type="checkbox" name="is_correct"> Korrekt</label>
    <button type="submit" name="new_answer">Hinzufügen</button>
</form>

<!-- Antwort löschen -->
<h3>Antwort löschen</h3>
<form method="post">
    <select name="answer_id" required>
        <?php foreach ($answers as $a) {
            echo "<option value='{$a['id']}'>{$a['text']} (Frage: {$a['question']})</option>";
        } ?>
    </select>
    <button type="submit" name="delete_answer">Löschen</button>
</form>

<a href="quiz.php">Zum Quiz</a>
