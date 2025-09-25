<?php
require_once 'php/db.php';

try {
    $stmt = $pdo->query("DESCRIBE professors");
    $columns = $stmt->fetchAll();
    
    echo "Columns in 'professors' table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check for 'id' or 'professor_id'
    $check_id = $pdo->query("SHOW COLUMNS FROM professors LIKE 'id'");
    $check_prof_id = $pdo->query("SHOW COLUMNS FROM professors LIKE 'professor_id'");
    
    if ($check_id->rowCount() > 0) {
        echo "\n'id' column exists.\n";
    } else {
        echo "\n'id' column does NOT exist.\n";
    }
    
    if ($check_prof_id->rowCount() > 0) {
        echo "'professor_id' column exists.\n";
    } else {
        echo "'professor_id' column does NOT exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
