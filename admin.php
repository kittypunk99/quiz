<?php
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';
if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token'])) {
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
}
$csrf_token = $_SESSION['csrf_token'];
?>
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
function validate_csrf_token()
{
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_category'])) {
    if (validate_csrf_token()) {
        $name = trim($_POST['category_name']);
        $parent_id = $_POST['parent_id'] !== "" ? filter_var($_POST['parent_id'], FILTER_VALIDATE_INT) : null;
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO category (name, parent_id) VALUES (:name, :parent_id)");
            $stmt->execute(['name' => $name, 'parent_id' => $parent_id]);
        }
    } else {
        echo "<div class='error-message'>Fehler: Alle Felder ausfüllen!</div>";
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_category']) && validate_csrf_token()) {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM question WHERE category_id = :category_id");
    $stmt->execute(['category_id' => $category_id]);
    if ($stmt->fetchColumn() == 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM category WHERE id = :category_id");
            $stmt->execute(['category_id' => $category_id]);
        } catch (PDOException $e) {
            if ($e->getCode() == 45000) {
                echo "<div class='error-message'>Die Hauptkategorie kann nicht gelöscht werden!</div>";
            } else {
                echo "<div class='error-message'>Fehler beim Löschen der Kategorie.</div>";
            }
        }
    } else {
        echo "<div class='error-message'>Kategorie enthält Fragen und kann nicht gelöscht werden!</div>";
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_question']) && validate_csrf_token()) {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $text = trim($_POST['question_text']);
    if (!empty($text)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO question (category_id, text) VALUES (:category_id, :text)");
            $stmt->execute(['category_id' => $category_id, 'text' => $text]);
        } catch (PDOException $e) {
            echo "<div class='error-message'>Fehler beim Erstellen der Frage.</div>";
        }
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_question']) && validate_csrf_token()) {
    $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM answer WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $stmt = $pdo->prepare("DELETE FROM question WHERE id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    $pdo->commit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_answer']) && validate_csrf_token()) {
    $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
    $text = trim($_POST['answer_text']);
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;
    if (!empty($text)) {
        $stmt = $pdo->prepare("INSERT INTO answer (question_id, text, is_correct) VALUES (:question_id, :text, :is_correct)");
        $stmt->execute(['question_id' => $question_id, 'text' => $text, 'is_correct' => $is_correct]);
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_answer']) && validate_csrf_token()) {
    $answer_id = filter_input(INPUT_POST, 'answer_id', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("DELETE FROM answer WHERE id = :answer_id");
    $stmt->execute(['answer_id' => $answer_id]);
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['make_admin']) && validate_csrf_token()) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("UPDATE user SET is_admin = TRUE WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
}
$categories = $pdo->query("SELECT * FROM category")->fetchAll();
$categories_usable = $pdo->query("SELECT * FROM category WHERE parent_id IS NOT NULL")->fetchAll();
$questions = $pdo->query("SELECT q.id, q.text, c.name FROM question q JOIN category c ON q.category_id = c.id")->fetchAll();
$answers = $pdo->query("SELECT a.id, a.text, q.text AS question FROM answer a JOIN question q ON a.question_id = q.id")->fetchAll();
$users = $pdo->query("SELECT id, username, is_admin FROM user")->fetchAll();
function h($str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

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
    <h3>Kategorie löschen</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        <select name="category_id" required>
            <?php foreach ($categories_usable as $cat) {
                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
            } ?>
        </select>
        <button type="submit" name="delete_category">Löschen</button>
    </form>
</div>
<div class="admin-section">
    <h3>Neue Frage</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

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
    <h3>Frage löschen</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        <select name="question_id" required>
            <?php foreach ($questions as $q) {
                echo "<option value='{$q['id']}'>{$q['text']} ({$q['name']})</option>";
            } ?>
        </select>
        <button type="submit" name="delete_question">Löschen</button>
    </form>
</div>
<div class="admin-section">
    <h3>Neue Antwort</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
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
    <h3>Antwort löschen</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        <select name="answer_id" required>
            <?php foreach ($answers as $a) {
                echo "<option value='{$a['id']}'>{$a['text']} (Frage: {$a['question']})</option>";
            } ?>
        </select>
        <button type="submit" name="delete_answer">Löschen</button>
    </form>
</div>
<div class="admin-section">
    <h3>Make User Admin</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        <select name="user_id" required>
            <?php foreach ($users as $user) {
                echo "<option value='{$user['id']}'>{$user['username']} (Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . ")</option>";
            } ?>
        </select>
        <button type="submit" name="make_admin">Make Admin</button>
    </form>
</div>
<footer>
    Ein Quiz von Linus Freistetter
    <a href="quiz.php">Zum Quiz</a>
</footer>
</body>
