<?php
require_once __DIR__ . '/../db.php';
if (!$pdo) { echo "No DB connection.\n"; exit(1);} 
try {
    $row = $pdo->query("SHOW CREATE TABLE `semesters`")->fetch(PDO::FETCH_ASSOC);
    foreach ($row as $k=>$v) {
        if (!is_int($k)) echo "$k: \n$v\n";
    }
} catch (PDOException $e) {
    echo "Error: ".$e->getMessage()."\n";
}
