

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
    <?php
    session_start();
    session_regenerate_id(true);
    require_once 'db.php';

    // CSRF-Token erzeugen
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];

    // Auswahlpfad auslesen
    $selected_path = [];
    foreach ($_POST as $key => $val) {
        if (substr($key, 0, 4) === 'cat_' && is_numeric($val)) {
            $selected_path[] = (int)$val;
        }
    }
    $selected_category = end($selected_path) ?: null;

    // Kategoriepfad laden
    function load_category_level($pdo, $parent_id) {
        $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id = :parent_id ORDER BY name");
        $stmt->execute(['parent_id' => $parent_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hauptkategorie-ID ermitteln
    $stmt = $pdo->prepare("SELECT id FROM category WHERE name = 'Hauptkategorie' LIMIT 1");
    $stmt->execute();
    $root_category_id = $stmt->fetchColumn();

    $levels = [];
    $parent_id = $root_category_id;
    foreach ($selected_path as $cat_id) {
        $children = load_category_level($pdo, $parent_id);
        $levels[] = [
            'parent' => $parent_id,
            'selected' => $cat_id,
            'options' => $children,
        ];
        $parent_id = $cat_id;
    }
    $next_level = load_category_level($pdo, $parent_id);
    if ($next_level) {
        $levels[] = [
            'parent' => $parent_id,
            'selected' => null,
            'options' => $next_level,
        ];
    }

    // Fragen laden, wenn Kategorie ausgewählt wurde
    if (!empty($_POST['start_quiz']) && $selected_category) {
        $stmt = $pdo->prepare("
        SELECT q.id, q.text
        FROM question q
        WHERE q.category_id = :category_id
          AND EXISTS (SELECT 1 FROM answer a WHERE a.question_id = q.id AND a.is_correct = 1)
          AND EXISTS (SELECT 1 FROM answer a WHERE a.question_id = q.id AND a.is_correct = 0)
    ");
        $stmt->execute(['category_id' => $selected_category]);
        $_SESSION['quiz_questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['quiz_category'] = $selected_category;
        $_SESSION['current_question'] = 0;
        header("Location: quiz.php");
        exit;
    }

    // Navigation verarbeiten
    if (isset($_POST['next'])) {
        $_SESSION['current_question']++;
    } elseif (isset($_POST['prev'])) {
        $_SESSION['current_question'] = max(0, $_SESSION['current_question'] - 1);
    }

    $current_question = null;
    if (!empty($_SESSION['quiz_questions'])) {
        $index = $_SESSION['current_question'] ?? 0;
        $current_question = $_SESSION['quiz_questions'][$index] ?? null;
    }
    ?>

    <h2>Quiz</h2>

    <?php if (!$current_question): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <?php foreach ($levels as $i => $level): ?>
                <label for="cat_<?= $i ?>">Kategorie:</label>
                <select name="cat_<?= $i ?>" id="cat_<?= $i ?>" onchange="this.form.submit()">
                    <option value="">-- Wählen --</option>
                    <?php foreach ($level['options'] as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $level['selected'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><input type="submit" value="Laden"></noscript>
            <?php endforeach; ?>
            <?php if ($selected_category): ?>
                <button type="submit" name="start_quiz">Quiz starten</button>
            <?php endif; ?>
        </form>
    <?php else: ?>
        <form method="post" action="<?= ($_SESSION['current_question'] < count($_SESSION['quiz_questions']) - 1) ? 'quiz.php' : 'result.php' ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <fieldset>
                <legend><?= htmlspecialchars($current_question['text']) ?></legend>
                <?php
                $stmt = $pdo->prepare("SELECT id, text FROM answer WHERE question_id = :qid ORDER BY RAND()");
                $stmt->execute(['qid' => $current_question['id']]);
                $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php foreach ($answers as $a): ?>
                    <label>
                        <input type="radio" name="answers[<?= $current_question['id'] ?>]" value="<?= $a['id'] ?>" required>
                        <?= htmlspecialchars($a['text']) ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>
            <?php if ($_SESSION['current_question'] > 0): ?>
                <button type="submit" name="prev">Zurück</button>
            <?php endif; ?>
            <?php if ($_SESSION['current_question'] < count($_SESSION['quiz_questions']) - 1): ?>
                <button type="submit" name="next">Weiter</button>
            <?php else: ?>
                <input type="submit" value="Abschicken">
            <?php endif; ?>
        </form>
    <?php endif; ?>

</main>
</body>
</html>
