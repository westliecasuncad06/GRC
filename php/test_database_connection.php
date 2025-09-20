<?php
// Test database connection
require_once 'db.php';

echo "<h2>Database Connection Test</h2>";

if ($pdo === null) {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    echo "<p>Possible solutions:</p>";
    echo "<ol>";
    echo "<li>Make sure XAMPP is running (Apache and MySQL services)</li>";
    echo "<li>Check if MariaDB/MySQL is allowing connections from localhost</li>";
    echo "<li>Try running this command in XAMPP Shell: <code>mysql -u root -e \"GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;\"</code></li>";
    echo "<li>Or try accessing phpMyAdmin at http://localhost/phpmyadmin and run the above SQL command</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    try {
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Simple query test: " . ($result['test'] == 1 ? "✅ Passed" : "❌ Failed") . "</p>";

        // Check if required tables exist
        $tables = ['students', 'professors', 'administrators'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "<p>Table '$table' exists: " . ($exists ? "✅ Yes" : "❌ No") . "</p>";
        }

    } catch (PDOException $e) {
        echo "<p style='color: red;'>Query test failed: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='../index.php'>← Back to Login</a></p>";
?>
