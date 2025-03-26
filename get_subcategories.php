<?php
global $pdo;
require 'db.php';

if (isset($_GET['parent_id']) && is_numeric($_GET['parent_id'])) {
    $parent_id = (int) $_GET['parent_id'];

    $stmt = $pdo->prepare("SELECT id, name FROM category WHERE parent_id = :parent_id ORDER BY name");
    $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
    $stmt->execute();
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subcategories as $subcategory) {
        echo '<option value="' . $subcategory['id'] . '">' . htmlspecialchars($subcategory['name']) . '</option>';
    }
}
?>
