<head>
    <title>SuperQuiz: Leaderboard</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<header>
    <h2>Leaderboard</h2>
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
global $pdo;
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';

// Alle Ergebnisse abfragen, sortiert nach Anzahl der richtigen Antworten
$stmt = $pdo->query("SELECT u.username, r.correct_answers, r.percentage
                     FROM results r
                     JOIN user u ON r.user_id = u.id
                     ORDER BY r.correct_answers DESC, r.percentage DESC");

$results = $stmt->fetchAll();
?>

<h2>Leaderboard</h2>
<table>
    <thead>
    <tr>
        <th>Benutzername</th>
        <th>Richtige Antworten</th>
        <th>Prozent</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $result): ?>
        <tr>
            <td><?php echo htmlspecialchars($result['username']); ?></td>
            <td><?php echo $result['correct_answers']; ?></td>
            <td><?php echo number_format($result['percentage'], 2); ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="quiz.php">Zurück zum Quiz</a>
</body>