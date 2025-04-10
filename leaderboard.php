<?php
require 'db.php';
session_start();
session_regenerate_id(true);

// Nutzername zu user_id mappen
$stmt = $pdo->query("
    SELECT u.username, r.correct_answers, r.total_questions, r.percentage, r.created_at
    FROM results r
    JOIN user u ON u.id = r.user_id
    INNER JOIN (
        SELECT user_id, MAX(percentage) AS best_percentage
        FROM results
        GROUP BY user_id
    ) AS best
    ON r.user_id = best.user_id AND r.percentage = best.best_percentage
    ORDER BY r.percentage DESC, r.correct_answers DESC, r.created_at
");

$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>SuperQuiz: Leaderboard</title>
    <link rel="stylesheet" href="style.css">
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

<main>
    <table>
        <thead>
        <tr>
            <th>Platz</th>
            <th>Benutzer</th>
            <th>Richtige</th>
            <th>Insgesamt</th>
            <th>Prozentsatz</th>
            <th>Datum</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($leaderboard): ?>
            <?php foreach ($leaderboard as $i => $entry): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($entry['username']) ?></td>
                    <td><?= $entry['correct_answers'] ?></td>
                    <td><?= $entry['total_questions'] ?></td>
                    <td><?= number_format($entry['percentage'], 2) ?>%</td>
                    <td><?= htmlspecialchars($entry['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Noch keine Einträge</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
