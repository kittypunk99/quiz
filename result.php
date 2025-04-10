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
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';

// Nutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'], $_POST['csrf_token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    $user_id = $_SESSION['user_id'];
    $answers = $_POST['answers'];
    $correct = 0;
    $total = count($answers);

    foreach ($answers as $question_id => $answer_id) {
        $question_id = (int)$question_id;
        $answer_id = (int)$answer_id;

        // Ist die Antwort korrekt?
        $stmt = $pdo->prepare("SELECT is_correct FROM answer WHERE id = :answer_id AND question_id = :question_id");
        $stmt->execute(['answer_id' => $answer_id, 'question_id' => $question_id]);
        $is_correct = $stmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO result (user_id, question_id, answer_id, is_correct)
                               VALUES (:user_id, :question_id, :answer_id, :is_correct)");
        $stmt->execute([
            'user_id'     => $user_id,
            'question_id' => $question_id,
            'answer_id'   => $answer_id,
            'is_correct'  => (bool)$is_correct,
        ]);

        if ($is_correct) $correct++;
    }

    $percentage = $total > 0 ? $correct / $total * 100 : 0;

    $stmt = $pdo->prepare("INSERT INTO results (user_id, correct_answers, total_questions, percentage)
                           VALUES (:user_id, :correct_answers, :total_questions, :percentage)");
    $stmt->execute([
        'user_id'         => $user_id,
        'correct_answers' => $correct,
        'total_questions' => $total,
        'percentage'      => $percentage
    ]);

    echo "<h2>Auswertung</h2>";
    echo "<p>Richtige Antworten: $correct von $total</p>";
    echo "<a href='quiz.php'>Neues Quiz starten</a>";
    exit;
} else {
    echo "<p>Ungültiger Zugriff oder CSRF-Token ungültig.</p>";
    echo "<a href='quiz.php'>Zurück zum Quiz</a>";
    exit;
}

?>
<link rel="stylesheet" href="style.css">
</body>