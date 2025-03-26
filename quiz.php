<head>
    <title>SuperQuiz: Quiz</title>
    <link rel='stylesheet' href='style.css'>
    <script>

        function loadSubcategories(parentId, level) {
            fetch('get_subcategories.php?parent_id=' + parentId)
                .then(response => response.text())
                .then(data => {
                    let container = document.getElementById('subcategories');
                    let selects = container.querySelectorAll('.subcategory-select');

                    // Entferne alle tieferen Selects
                    selects.forEach(select => {
                        if (parseInt(select.dataset.level) >= level) {
                            select.remove();
                        }
                    });

                    // Falls es Unterkategorien gibt, ein neues Select-Menü hinzufügen
                    if (data.trim()) {
                        let select = document.createElement('select');
                        select.className = 'subcategory-select';
                        select.dataset.level = level;
                        select.name = 'category_id';
                        select.innerHTML = `<option value="${parentId}">Keine (Nur diese wählen)</option>` + data;
                        select.addEventListener('change', function () {
                            document.getElementById('finalCategory').value = this.value;
                            if (this.value !== `${parentId}`) loadSubcategories(this.value, level + 1);
                        });
                        container.appendChild(select);
                    } else {
                        // Wenn es keine Unterkategorie mehr gibt, speichere die letzte Auswahl
                        document.getElementById('finalCategory').value = parentId;
                    }
                });
        }

        function removeFirstOption(select) {
            if (select.options[0].value === "") {
                select.remove(0); // Entfernt die erste Option, wenn sie "-- Hauptkategorie wählen --" ist
                document.getElementById("categoryForm").querySelector("button").disabled = false;
            }
        }
    </script>
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


<?php
global $pdo;
session_start();
session_regenerate_id(true);
require 'db.php';
require_once 'error_handler.php';

// Nutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kategorie auswählen
if (!isset($_SESSION['category_id']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id = 1");
    $stmt->execute();
    $rootCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Kategorie auswählen</h2>";
    echo "<form method='POST' action='quiz.php' id='categoryForm'>";
    echo "<select id='categorySelect' name='category_id' onchange='removeFirstOption(this);loadSubcategories(this.value, 1)'>";
    echo "<option value=''>-- Hauptkategorie wählen --</option>";
    foreach ($rootCategories as $category):
        echo "<option value='" . htmlspecialchars($category['id']) . "'>" . htmlspecialchars($category['name']) . "</option>";
    endforeach;
    echo "</select>";
    echo "<div id='subcategories'></div>";
    echo "<input type='hidden' id='finalCategory' name='final_category' value=''>";
    echo "<button type='submit' disabled='disabled'>Quiz starten</button>";
    echo "</form>";
    exit;
}


// Kategorie setzen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_id'])) {
    $_SESSION['category_id'] = $_POST['category_id'];
    $_SESSION['question_index'] = 0;
}

// Fragen abrufen
$stmt = $pdo->prepare("SELECT id, text FROM question WHERE category_id = :category_id");
$stmt->execute(['category_id' => $_SESSION['category_id']]);
$questions = $stmt->fetchAll();
if (empty($questions)) {
    unset($_SESSION['category_id']);
    echo "<p>Keine Fragen in dieser Kategorie vorhanden!</p>";
    echo "<a href='quiz.php'>Zurück</a>";
    exit;
}

// Quiz beendet?
if ($_SESSION['question_index'] >= count($questions)) {
    header("Location: result.php");
    exit;
}

// Aktuelle Frage
$question = $questions[$_SESSION['question_index']];
$stmt = $pdo->prepare("SELECT id, text FROM answer WHERE question_id = :question_id");
$stmt->execute(['question_id' => $question['id']]);
$answers = $stmt->fetchAll();

// Antwort speichern
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer_id'])) {
    $answer_id = $_POST['answer_id'];
    $stmt = $pdo->prepare("SELECT is_correct FROM answer WHERE id = :answer_id");
    $stmt->execute(['answer_id' => $answer_id]);
    $is_correct = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO result (user_id, question_id, answer_id, is_correct) VALUES (:user_id, :question_id, :answer_id, :is_correct)");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'question_id' => $question['id'], 'answer_id' => $answer_id, 'is_correct' => $is_correct]);

    $_SESSION['question_index']++;
    header("Location: quiz.php");
    exit;
}

// Frage anzeigen
echo "<form method='post' class='q-form'>";
echo "<p class='c-question'>{$question['text']}</p>";
foreach ($answers as $answer) {
    echo "<label class='ans'><input type='radio' name='answer_id' value='{$answer['id']}' required> {$answer['text']}</label><br>";
}
echo "<button type='submit'>Weiter</button>";
echo "</form>";


// Fetch root categories
$rootCategories = $pdo->query("SELECT id, name FROM category WHERE parent_id IS NULL")->fetchAll();
?>

</body>