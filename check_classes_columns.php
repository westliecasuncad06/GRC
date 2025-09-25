<?php
require_once 'php/db.php';

try {
    $stmt = $pdo->query("DESCRIBE classes");
    $columns = $stmt->fetchAll();
    
    echo "Columns in 'classes' table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Specifically check for school_year
    $check = $pdo->query("SHOW COLUMNS FROM classes LIKE 'school_year'");
    if ($check->rowCount() > 0) {
        echo "\n'school_year' column exists.\n";
    } else {
        echo "\n'school_year' column does NOT exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
