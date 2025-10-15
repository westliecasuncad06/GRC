<?php
require_once 'db.php';

try {
    // Check if constraints already exist and add them if not
    $constraints = [
        "fk_attendance_student" => "ALTER TABLE attendance ADD CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_attendance_class" => "ALTER TABLE attendance ADD CONSTRAINT fk_attendance_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        "fk_class_enrollments_student" => "ALTER TABLE class_enrollments ADD CONSTRAINT fk_class_enrollments_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_class_enrollments_class" => "ALTER TABLE class_enrollments ADD CONSTRAINT fk_class_enrollments_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        "fk_class_professors_class" => "ALTER TABLE class_professors ADD CONSTRAINT fk_class_professors_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        "fk_class_professors_professor" => "ALTER TABLE class_professors ADD CONSTRAINT fk_class_professors_professor FOREIGN KEY (professor_id) REFERENCES professors(professor_id) ON DELETE CASCADE;",
        "fk_enrollment_requests_student" => "ALTER TABLE enrollment_requests ADD CONSTRAINT fk_enrollment_requests_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_professor_subjects_professor" => "ALTER TABLE professor_subjects ADD CONSTRAINT fk_professor_subjects_professor FOREIGN KEY (professor_id) REFERENCES professors(professor_id) ON DELETE CASCADE;",
        "fk_professor_subjects_subject" => "ALTER TABLE professor_subjects ADD CONSTRAINT fk_professor_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE;",
        "fk_student_classes_student" => "ALTER TABLE student_classes ADD CONSTRAINT fk_student_classes_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_student_classes_class" => "ALTER TABLE student_classes ADD CONSTRAINT fk_student_classes_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;"
    ];

    foreach ($constraints as $name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Skip if constraint already exists
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Error adding constraint $name: " . $e->getMessage() . "\n";
            }
        }
    }

    // Add indexes
    $indexes = [
        "CREATE INDEX idx_student_id ON attendance(student_id);",
        "CREATE INDEX idx_class_id ON attendance(class_id);",
        "CREATE INDEX idx_enrollment_student ON class_enrollments(student_id);",
        "CREATE INDEX idx_enrollment_class ON class_enrollments(class_id);",
        "CREATE INDEX idx_professor_id ON classes(professor_id);"
    ];

    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Skip if index already exists
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Error adding index: " . $e->getMessage() . "\n";
            }
        }
    }

    // Fix unenrollment requests constraint issue
    try {
        $pdo->exec("ALTER TABLE unenrollment_requests DROP INDEX unique_unenroll_request;");
        echo "Removed unique constraint from unenrollment_requests.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'check that it exists') === false) {
            echo "Error removing constraint: " . $e->getMessage() . "\n";
        }
    }

    // Fix empty status fields
    try {
        $pdo->exec("UPDATE unenrollment_requests SET status = 'pending' WHERE status = '' OR status IS NULL;");
        echo "Fixed empty status fields in unenrollment_requests.\n";
    } catch (PDOException $e) {
        echo "Error fixing status fields: " . $e->getMessage() . "\n";
    }

    // Apply the updated database structure
    try {
        $sql = file_get_contents('updated_database_structure.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            }
        }

        echo "Updated database structure applied successfully.\n";
    } catch (PDOException $e) {
        echo "Error applying updated database structure: " . $e->getMessage() . "\n";
    }

    echo "Database updates applied successfully.";
} catch (PDOException $e) {
    echo "Error applying updates: " . $e->getMessage();
}
?>
