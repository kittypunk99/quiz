<head>
    <title>SuperQuiz: Admin</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<header>
    <h2>Admin</h2>
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
session_start();
session_regenerate_id(true);
require 'db.php';

require_once 'error_handler.php';

// Prüfen, ob der Nutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Admin-Rechte prüfen
if ($_SESSION['is_admin'] = false) {
    die("Zugriff verweigert!");
}

// Neue Kategorie hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_category'])) {
    $name = trim($_POST['category_name']);
    $parent_id = $_POST['parent_id'] ?? null;

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO category (name, parent_id) VALUES (:name, :parent_id)");
        $stmt->execute(['name' => $name, 'parent_id' => $parent_id]);
    }
}

// Kategorie löschen (nur wenn keine Fragen enthalten sind)

if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM question WHERE category_id = :category_id ");
    $stmt->execute(['category_id' => $category_id]);


    if ($stmt->fetchColumn() == 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM category WHERE id = :category_id");
            $stmt->execute(['category_id' => $category_id]);
        } catch (PDOException $e) {
            // Wenn der Fehler durch den Trigger ausgelöst wurde
            if ($e->getCode() == 45000) {
                echo "<div class='error-message'>Die Hauptkategorie kann nicht gelöscht werden!</div>";
            } else {
                echo "<div class='error-message'>Ein Fehler ist beim Löschen der Kategorie aufgetreten. Bitte versuche es später erneut.</div>";
            }

        }
    } else {
        echo "<div class='error-message'>Fehler: Kategorie enthält Fragen und kann nicht gelöscht werden!</div>";
    }
}

// Neue Frage hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_question'])) {
    $category_id = $_POST['category_id'];
    $text = trim($_POST['question_text']);

    if (!empty($text)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO question (category_id, text) VALUES (:category_id, :text)");
            $stmt->execute(['category_id' => $category_id, 'text' => $text]);
        } catch (PDOException $e) {
            if ($e->getCode() == 45000) {
                echo "<div class='error-message'>Die Hauptkategorie kann keine Fragen enthalten!</div>";
            } else {
                echo "<div class='error-message'>Ein Fehler ist beim Erstellen der Frage aufgetreten. Bitte versuche es später erneut.</div>";
            }

        }

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
$categories_usable = $pdo->query("SELECT * FROM category WHERE parent_id IS NOT NULL")->fetchAll();

// Fragen abrufen
$questions = $pdo->query("SELECT q.id, q.text, c.name FROM question q JOIN category c ON q.category_id = c.id")->fetchAll();

// Antworten abrufen
$answers = $pdo->query("SELECT a.id, a.text, q.text AS question FROM answer a JOIN question q ON a.question_id = q.id")->fetchAll();

// Fetch users
$users = $pdo->query("SELECT id, username, is_admin FROM user")->fetchAll();

// Make user admin
if (isset($_POST['make_admin'])) {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE user SET is_admin = TRUE WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
}

function getCategories($parent_id = null, $prefix = "")
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id " . (is_null($parent_id) ? "IS NULL" : "= :parent_id") . " ORDER BY id ASC");
    if (!is_null($parent_id)) {
        $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($categories);
    foreach ($categories as $index => $category) {
        $isLast = ($index === $count - 1);
        echo sprintf("%s%s%s<br>", $prefix, $isLast ? ($parent_id != null ? " └ " : "") : "   ├ ", $category['name']);
        getCategories($category['id'], $prefix . ($isLast ? "   " : "│  "));
    }
}

?>
<link rel="stylesheet" href="style.css">

<h2>Admin-Bereich</h2>
<div class="admin-section">
    <h3>Kategorien</h3>
    <?php getCategories(); ?>
</div>
<div class="admin-section">
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
</div>

<div class="admin-section">

    <!-- Kategorie löschen -->
    <h3>Kategorie löschen</h3>
    <form method="post">
        <select name="category_id" required>
            <?php foreach ($categories_usable as $cat) {
                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
            } ?>
        </select>
        <button type="submit" name="delete_category">Löschen</button>
    </form>
</div>
<div class="admin-section">

    <!-- Frage hinzufügen -->
    <h3>Neue Frage</h3>
    <form method="post">
        <select name="category_id" required>
            <?php foreach ($categories_usable as $cat) {
                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
            } ?>
        </select>
        <input type="text" name="question_text" placeholder="Fragetext" required>
        <button type="submit" name="new_question">Hinzufügen</button>
    </form>
</div>
<div class="admin-section">

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
</div>
<div class="admin-section">

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
</div>
<div class="admin-section">

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
</div>
<div class="admin-section">
    <!-- Make user admin -->
    <h3>Make User Admin</h3>
    <form method="post">
        <select name="user_id" required>
            <?php foreach ($users as $user) {
                echo "<option value='{$user['id']}'>{$user['username']} (Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . ")</option>";
            } ?>
        </select>
        <button type="submit" name="make_admin">Make Admin</button>
    </form>
</div>
<a href="quiz.php">Zum Quiz</a>
</body>