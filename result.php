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
if ($_SERVER['REQUEST_METHOD'] === 'POST'  // <- Nur wenn "Fertig" geklickt wurde
    && isset($_SESSION['answers'])){
    unset($_SESSION['quiz_category']);
    unset($_SESSION['quiz_questions']);
    unset($_SESSION['quiz_answers']);
    echo "<h1>Ergebnisse</h1>";


    $user_id = $_SESSION['user_id'];
    $answers = $_SESSION['answers'];
    $correct = 0;
    $total = count($answers);

    foreach ($answers as $question_id => $answer_id) {
        $question_id = (int)$question_id;
        $answer_id = (int)$answer_id;

        // Ist die Antwort korrekt?
        $stmt = $pdo->prepare("SELECT a.is_correct, q.text AS question_text, a.text AS answer_text 
                       FROM answer a 
                       JOIN question q ON q.id = a.question_id 
                       WHERE a.id = :answer_id AND a.question_id = :question_id");
        $stmt->execute(['answer_id' => $answer_id, 'question_id' => $question_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $is_correct = $result['is_correct'];
        $question_text = $result['question_text'];
        $answer_text = $result['answer_text'];

        /*$stmt = $pdo->prepare("INSERT INTO result (user_id, question_id, answer_id, is_correct)
                               VALUES (:user_id, :question_id, :answer_id, :is_correct)");
        $stmt->execute([
            'user_id'     => $user_id,
            'question_id' => $question_id,
            'answer_id'   => $answer_id,
            'is_correct'  => (bool)$is_correct,
        ]);*/

        if ($is_correct) $correct++;
        echo "<p class='" . ($is_correct ? "correct" : "incorrect") . "'>Frage: $question_text: Antwort $answer_text ist " . ($is_correct ? "richtig" : "falsch") . "</p>";
    }

    $percentage = $total > 0 ? $correct / $total * 100 : 0;

    $stmt = $pdo->prepare("
    INSERT INTO leaderboard (user_id, correct_answers, total_questions, percentage)
    VALUES (:user_id, :correct, :total, :percentage)
    ON DUPLICATE KEY UPDATE
        correct_answers = correct_answers + VALUES(correct_answers),
        total_questions = total_questions + VALUES(total_questions),
        percentage = (correct_answers + VALUES(correct_answers)) / (total_questions + VALUES(total_questions)) * 100,
        updated_at = CURRENT_TIMESTAMP
");

    $stmt->execute(['user_id' => $user_id, 'correct' => $correct, 'total' => $total, 'percentage' => $percentage]);


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