<?php
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';
?>
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
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo "<p>Ungültiger Zugriff oder CSRF-Token ungültig.</p>";
    echo "<a href='quiz.php'>Zurück zum Quiz</a>";
    exit;
}
if ($_POST['csrf_token'] && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    unset($_SESSION['quiz_category'], $_SESSION['quiz_questions'], $_SESSION['quiz_answers']);
    echo "<h1>Ergebnisse</h1>";
    $user_id = $_SESSION['user_id'];
    $answers = isset($_SESSION['answers']) ? array_merge($_SESSION['answers'], $_POST['answers']) : $_POST['answers'];
    $correct = 0;
    $total = count($answers);
    foreach ($answers as $question_id => $answer_id) {
        $stmt = $pdo->prepare("
            SELECT a.is_correct, q.text AS question_text, a.text AS answer_text
            FROM answer a
            JOIN question q ON q.id = a.question_id
            WHERE a.id = :answer_id
        ");
        $stmt->execute(['answer_id' => (int)$answer_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['is_correct']) $correct++;
        echo "<p class='" . ($result['is_correct'] ? "correct" : "incorrect") . "'>
    Frage: " . htmlspecialchars($result['question_text']) . ": Antwort " . htmlspecialchars($result['answer_text']) . " ist " . ($result['is_correct'] ? "richtig" : "falsch") . "</p>";
    }
    $percentage = $total > 0 ? ($correct / $total) * 100 : 0;
    $stmt = $pdo->prepare("
        INSERT INTO leaderboard (user_id, correct_answers, total_questions, percentage)
        VALUES (:user_id, :correct, :total, :percentage)
        ON DUPLICATE KEY UPDATE
            correct_answers = correct_answers + VALUES(correct_answers),
            total_questions = total_questions + VALUES(total_questions),
            percentage = (correct_answers + VALUES(correct_answers)) / NULLIF(total_questions + VALUES(total_questions), 0) * 100,
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute(['user_id' => $user_id, 'correct' => $correct, 'total' => $total, 'percentage' => $percentage]);
    echo "<p>Richtige Antworten: $correct von $total</p>";
    echo "<a href='quiz.php'>Neues Quiz starten</a>";
} else {
    echo "<p>Ungültiger Zugriff oder CSRF-Token ungültig.</p>";
    echo "<a href='quiz.php'>Zurück zum Quiz</a>";
}
unset($_SESSION['answers']);
exit;
?>
<footer>
    Ein Quiz von Linus Freistetter
</footer>
<link rel="stylesheet" href="style.css">