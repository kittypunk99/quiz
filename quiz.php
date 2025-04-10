<?php
session_start();
session_regenerate_id(true);
require_once 'db.php';
global $pdo;

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function get_subcategories($parent_id, $pdo): array
{
    $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id = :parent_id ORDER BY name");
    $stmt->execute(['parent_id' => $parent_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_category_path($category_id, $pdo): array
{
    $path = [];
    while ($category_id) {
        $stmt = $pdo->prepare("SELECT id, name, parent_id FROM category WHERE id = :id");
        $stmt->execute(['id' => $category_id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cat) break;
        array_unshift($path, $cat);  // prepend
        $category_id = $cat['parent_id'];
    }
    return $path;
}

// Kategorieauswahl auslesen
$selected_path = [];
$selected_category_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $val) {
        if (substr($key, 0, 4) === 'cat_' && is_numeric($val)) {
            $selected_path[] = (int)$val;
        }
    }

    if (isset($_POST['start_quiz']) && count($selected_path) > 0) {
        $selected_category_id = end($selected_path);
    }
}

// Root-Kategorie ("Hauptkategorie") ermitteln
$stmt = $pdo->prepare("SELECT id FROM category WHERE name = 'Hauptkategorie' LIMIT 1");
$stmt->execute();
$root_id = $stmt->fetchColumn();

$current_parent_id = $root_id;
$category_selects = [];

foreach ($selected_path as $depth => $cat_id) {
    $subcats = get_subcategories($current_parent_id, $pdo);
    if (count($subcats) === 0) break;

    $category_selects[] = ['name' => "cat_$depth", 'selected' => $cat_id, 'options' => $subcats,];
    $current_parent_id = $cat_id;
}

// Letzten Level nachladen, wenn es noch Unterkategorien gibt
$next_subcategories = get_subcategories($current_parent_id, $pdo);
if (count($next_subcategories) > 0) {
    $category_selects[] = ['name' => 'cat_' . count($category_selects), 'selected' => null, 'options' => $next_subcategories,];
}

// Fragen laden, falls Quiz gestartet wurde
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
$_SESSION['quiz_questions'] = $questions;
$_SESSION['current_question'] = 0;

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
            <li><a href="register.php">Registrieren</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="admin.php">Admin</a></li>
        </ul>
    </nav>
</header>
<main>

    <?php if (!$selected_category_id): ?>
        <h3>Kategorie wählen</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <?php foreach ($category_selects as $select): ?>
                <label class="category">
                    <select name="<?= htmlspecialchars($select['name']) ?>" onchange="this.form.submit()">
                        <option value="">-- Kategorie wählen --</option>
                        <?php foreach ($select['options'] as $option): ?>
                            <option value="<?= $option['id'] ?>" <?= ($option['id'] == $select['selected'] ? 'selected' : '') ?>>
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
            <noscript><input type="submit" value="Weiter"></noscript>
        </form>

    <?php elseif ($questions): ?>

        <form method="post" action="result.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="category_id" value="<?= htmlspecialchars($selected_category_id) ?>">
            <?php
            if (isset($_POST['next'])) {
                $_SESSION['current_question']++;
            } elseif (isset($_POST['prev'])) {
                $_SESSION['current_question'] = max(0, $_SESSION['current_question'] - 1);
            }

            $index = $_SESSION['current_question'];
            if (isset($_SESSION['quiz_questions'][$index])) {
                $current_question = $_SESSION['quiz_questions'][$index];
                echo "<h3>Frage " . ($index + 1) . ":</h3>";
                echo "<p>" . htmlspecialchars($current_question['text']) . "</p>";

                // Antworten abrufen
                $stmt = $pdo->prepare("SELECT id, text FROM answer WHERE question_id = :qid ORDER BY RAND()");
                $stmt->execute(['qid' => $current_question['id']]);
                $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Antworten anzeigen
                foreach ($answers as $answer) {
                    echo "<label>";
                    echo "<input type='checkbox' name='answers[" . htmlspecialchars($current_question['id']) . "]' value='" . htmlspecialchars($answer['id']) . "' required>";
                    echo htmlspecialchars($answer['text']);
                    echo "</label><br>";
                }
                if ($index > 0) {
                    echo "<button type='submit' name='prev'>Zurück</button>";
                } elseif ($index < count($answers)) {
                    echo "<button type='submit' name='next'>Weiter</button>";
                } else {
                    echo "<input type='submit' value='Abschicken'>";
                }
            }
            ?>

        </form>
    <?php else: ?>
        <p>Keine Fragen in dieser Kategorie vorhanden.</p>
        <a href="quiz.php">Zurück</a>
    <?php endif; ?>

</main>
</body>
</html>
