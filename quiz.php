<?php
session_start();
session_regenerate_id(true);
require_once 'db.php';
require_once 'error_handler.php';
if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token'])) {
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
function get_subcategories($parent_id, $pdo)
{
    $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id = :parent_id ORDER BY name");
    $stmt->execute(['parent_id' => $parent_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['start_quiz'])) {
    unset($_SESSION['answers'], $_SESSION['current_question']);
}
$selected_path = [];
$selected_category_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "<div class='error-message'>Ungültiger CSRF-Token!</div>";
        exit;
    }
    foreach ($_POST as $key => $val) {
        if (substr($key, 0, 4) === 'cat_' && filter_var($val, FILTER_VALIDATE_INT) !== false) {
            $selected_path[] = (int)$val;
        }

    }
    if (isset($_POST['start_quiz']) && count($selected_path) > 0) {
        $selected_category_id = filter_var(end($selected_path), FILTER_VALIDATE_INT);
    } elseif (isset($_POST['next']) || isset($_POST['prev'])) {
        $_SESSION['current_question'] += isset($_POST['next']) ? 1 : -1;
        $_SESSION['current_question'] = max(0, $_SESSION['current_question']);
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $qid => $aid) {
                $_SESSION['answers'][(int)$qid] = (int)$aid;
            }
        }
    }
}
if (!isset($_SESSION['quiz_category'])) {
    $stmt = $pdo->prepare("SELECT id FROM category WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => 'Hauptkategorie']);
    $root_id = $stmt->fetchColumn();
    $current_parent_id = $root_id;
    $category_selects = [];
    foreach ($selected_path as $depth => $cat_id) {
        $subcats = get_subcategories($current_parent_id, $pdo);
        if (count($subcats) === 0) break;

        $category_selects[] = ['name' => "cat_$depth", 'selected' => $cat_id, 'options' => $subcats,];
        $current_parent_id = $cat_id;
    }
    $next_subcategories = get_subcategories($current_parent_id, $pdo);
    if (count($next_subcategories) > 0) {
        $category_selects[] = ['name' => 'cat_' . count($category_selects), 'selected' => null, 'options' => $next_subcategories,];
    }
    $questions = [];
    $stmt = $pdo->prepare("
    SELECT q.id, q.text
    FROM question q
    WHERE q.category_id = :category_id
      AND EXISTS (
          SELECT 1 FROM answer a WHERE a.question_id = q.id AND a.is_correct = 1
      )
      AND EXISTS (
          SELECT 1 FROM answer a WHERE a.question_id = q.id AND a.is_correct = 0
      )
");
    $stmt->execute(['category_id' => $selected_category_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $_SESSION['quiz_category'] = $selected_category_id;
    $_SESSION['quiz_questions'] = $questions;
    $_SESSION['current_question'] = 0;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>SuperQuiz</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h2>SuperQuiz</h2>
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
<main>
    <?php if (!$_SESSION['quiz_category']): ?>
        <h3>Kategorie wählen</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <?php foreach ($category_selects as $select): ?>
                <label class="category">
                    <select name="<?= htmlspecialchars($select['name']) ?>" onchange="this.form.submit()">
                        <option value="">-- Kategorie wählen --</option>
                        <?php foreach ($select['options'] as $option): ?>
                            <option value="<?= $option['id'] ?>" <?= $option['id'] == $select['selected'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <br>
            <?php endforeach; ?>
            <?php if (!empty($selected_path)): ?>
                <input type="submit" name="start_quiz" value="Quiz starten">
            <?php endif; ?>
        </form>
    <?php elseif ($_SESSION['quiz_questions']): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <?php
            $index = $_SESSION['current_question'];
            $total_questions = count($_SESSION['quiz_questions']);
            $current_question = $_SESSION['quiz_questions'][$index];
            echo "<h3 class='ans'>Frage " . ($index + 1) . " von $total_questions:</h3>";
            echo "<p>" . htmlspecialchars($current_question['text']) . "</p>";
            $stmt = $pdo->prepare("SELECT id, text FROM answer WHERE question_id = :qid ORDER BY RAND()");
            $stmt->execute(['qid' => $current_question['id']]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $answer) {
                $is_checked = isset($_SESSION['answers'][$current_question['id']]) && $_SESSION['answers'][$current_question['id']] == $answer['id'];
                echo "<label class='ans'><input type='radio' name='answers[{$current_question['id']}]' value='{$answer['id']}' " . ($is_checked ? "checked" : "") . " required>";
                echo htmlspecialchars($answer['text']) . "</label><br>";
            }
            var_dump($_SESSION['answers']);
            if ($index > 0) echo "<button type='submit' name='prev'>Zurück</button>";
            if ($index < $total_questions - 1) echo "<button type='submit' name='next'>Weiter</button>"; else echo "<button type='submit' formaction='result.php' name='finish_quiz'>Fertig</button>";
            ?>
        </form>
    <?php else:
        unset($_SESSION['quiz_category'], $_SESSION['quiz_questions'], $_SESSION['current_question']);
        ?>
        <p>Keine Fragen in dieser Kategorie vorhanden.</p>
        <a href="quiz.php">Zurück</a>
    <?php endif; ?>
</main>
<footer>
    Ein Quiz von Linus Freistetter
</footer>
</body>
</html>