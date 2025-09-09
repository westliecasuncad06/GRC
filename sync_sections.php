<?php
require_once 'db.php';

try {
    // Read and execute add_section_to_students.sql
    $sql = file_get_contents('add_section_to_students.sql');
    $pdo->exec($sql);
    echo "add_section_to_students.sql executed successfully.<br>";

    // Read and execute add_section_to_classes.sql
    $sql = file_get_contents('add_section_to_classes.sql');
    $pdo->exec($sql);
    echo "add_section_to_classes.sql executed successfully.<br>";

    echo "Sections synced successfully!";

} catch (PDOException $e) {
    echo "Error syncing sections: " . $e->getMessage();
}
?>
