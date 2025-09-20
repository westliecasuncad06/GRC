<?php
require_once 'db.php';

echo "=== Fixing Unenrollment Issues ===\n\n";

// 1. Create enrollment_requests table if it doesn't exist
echo "1. Creating enrollment_requests table...\n";
try {
    $sql = "CREATE TABLE IF NOT EXISTS enrollment_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        class_id VARCHAR(20) NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        handled_at DATETIME DEFAULT NULL,
        handled_by VARCHAR(20) DEFAULT NULL,
        UNIQUE KEY unique_request (student_id, class_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "✓ enrollment_requests table created or already exists.\n";
} catch (PDOException $e) {
    echo "✗ Error creating enrollment_requests table: " . $e->getMessage() . "\n";
}

// 2. Create unenrollment_requests table if it doesn't exist
echo "\n2. Creating unenrollment_requests table...\n";
try {
    $sql = "CREATE TABLE IF NOT EXISTS unenrollment_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        class_id VARCHAR(20) NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        handled_at DATETIME DEFAULT NULL,
        handled_by VARCHAR(20) DEFAULT NULL,
        UNIQUE KEY unique_unenroll_request (student_id, class_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "✓ unenrollment_requests table created or already exists.\n";
} catch (PDOException $e) {
    echo "✗ Error creating unenrollment_requests table: " . $e->getMessage() . "\n";
}

// 3. Check and add requested_at column to enrollment_requests if missing
echo "\n3. Checking enrollment_requests table structure...\n";
try {
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('requested_at', $columns)) {
        echo "Adding requested_at column to enrollment_requests...\n";
        $sql = "ALTER TABLE enrollment_requests ADD COLUMN requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER class_id";
        $pdo->exec($sql);
        echo "✓ requested_at column added to enrollment_requests.\n";
    } else {
        echo "✓ requested_at column already exists in enrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking enrollment_requests structure: " . $e->getMessage() . "\n";
}

// 4. Check and add requested_at column to unenrollment_requests if missing
echo "\n4. Checking unenrollment_requests table structure...\n";
try {
    $stmt = $pdo->query("DESCRIBE unenrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('requested_at', $columns)) {
        echo "Adding requested_at column to unenrollment_requests...\n";
        $sql = "ALTER TABLE unenrollment_requests ADD COLUMN requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER class_id";
        $pdo->exec($sql);
        echo "✓ requested_at column added to unenrollment_requests.\n";
    } else {
        echo "✓ requested_at column already exists in unenrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking unenrollment_requests structure: " . $e->getMessage() . "\n";
}

// 5. Check and add handled_at column to enrollment_requests if missing
echo "\n5. Checking for handled_at column in enrollment_requests...\n";
try {
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('handled_at', $columns)) {
        echo "Adding handled_at column to enrollment_requests...\n";
        $sql = "ALTER TABLE enrollment_requests ADD COLUMN handled_at DATETIME DEFAULT NULL AFTER requested_at";
        $pdo->exec($sql);
        echo "✓ handled_at column added to enrollment_requests.\n";
    } else {
        echo "✓ handled_at column already exists in enrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking handled_at column in enrollment_requests: " . $e->getMessage() . "\n";
}

// 6. Check and add handled_at column to unenrollment_requests if missing
echo "\n6. Checking for handled_at column in unenrollment_requests...\n";
try {
    $stmt = $pdo->query("DESCRIBE unenrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('handled_at', $columns)) {
        echo "Adding handled_at column to unenrollment_requests...\n";
        $sql = "ALTER TABLE unenrollment_requests ADD COLUMN handled_at DATETIME DEFAULT NULL AFTER requested_at";
        $pdo->exec($sql);
        echo "✓ handled_at column added to unenrollment_requests.\n";
    } else {
        echo "✓ handled_at column already exists in unenrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking handled_at column in unenrollment_requests: " . $e->getMessage() . "\n";
}

// 7. Check and add handled_by column to enrollment_requests if missing
echo "\n7. Checking for handled_by column in enrollment_requests...\n";
try {
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('handled_by', $columns)) {
        echo "Adding handled_by column to enrollment_requests...\n";
        $sql = "ALTER TABLE enrollment_requests ADD COLUMN handled_by VARCHAR(20) DEFAULT NULL AFTER handled_at";
        $pdo->exec($sql);
        echo "✓ handled_by column added to enrollment_requests.\n";
    } else {
        echo "✓ handled_by column already exists in enrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking handled_by column in enrollment_requests: " . $e->getMessage() . "\n";
}

// 8. Check and add handled_by column to unenrollment_requests if missing
echo "\n8. Checking for handled_by column in unenrollment_requests...\n";
try {
    $stmt = $pdo->query("DESCRIBE unenrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('handled_by', $columns)) {
        echo "Adding handled_by column to unenrollment_requests...\n";
        $sql = "ALTER TABLE unenrollment_requests ADD COLUMN handled_by VARCHAR(20) DEFAULT NULL AFTER handled_at";
        $pdo->exec($sql);
        echo "✓ handled_by column added to unenrollment_requests.\n";
    } else {
        echo "✓ handled_by column already exists in unenrollment_requests.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking handled_by column in unenrollment_requests: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
echo "Please refresh your professor dashboard to see if the issues are resolved.\n";
?>
