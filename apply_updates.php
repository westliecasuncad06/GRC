<?php
require_once 'db.php';

try {
    // Ensure school_years has created_by/updated_by columns
    try {
        $pdo->exec("ALTER TABLE school_years ADD COLUMN created_by VARCHAR(20) NULL AFTER `status`");
        echo "Added school_years.created_by column\n";
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Duplicate column name') === false && stripos($e->getMessage(), 'already exists') === false) {
            // non-tolerable
            echo "Note on created_by column: " . $e->getMessage() . "\n";
        }
    }
    try {
        $pdo->exec("ALTER TABLE school_years ADD COLUMN updated_by VARCHAR(20) NULL AFTER `created_at`");
        echo "Added school_years.updated_by column\n";
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Duplicate column name') === false && stripos($e->getMessage(), 'already exists') === false) {
            echo "Note on updated_by column: " . $e->getMessage() . "\n";
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

    // Fix empty status fields BEFORE adding constraints
    try {
        $pdo->exec("UPDATE unenrollment_requests SET status = 'pending' WHERE status = '' OR status IS NULL;");
        echo "Fixed empty status fields in unenrollment_requests.\n";
    } catch (PDOException $e) {
        echo "Error fixing status fields: " . $e->getMessage() . "\n";
    }

    // Pre-clean: remove orphaned requests that would block FK creation
    try {
        $deletedUnenroll = $pdo->exec("DELETE ur FROM unenrollment_requests ur LEFT JOIN classes c ON ur.class_id = c.class_id WHERE c.class_id IS NULL");
        $deletedEnroll = $pdo->exec("DELETE er FROM enrollment_requests er LEFT JOIN classes c ON er.class_id = c.class_id WHERE c.class_id IS NULL");
        echo "Removed orphan rows -> unenrollment_requests: {$deletedUnenroll}, enrollment_requests: {$deletedEnroll}\n";
    } catch (PDOException $e) {
        echo "Error cleaning orphan requests: " . $e->getMessage() . "\n";
    }

    // Check if constraints already exist and add them if not
    $constraints = [
        // Attendance
        "fk_attendance_student" => "ALTER TABLE attendance ADD CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_attendance_class" => "ALTER TABLE attendance ADD CONSTRAINT fk_attendance_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        // Class enrollments
        "fk_class_enrollments_student" => "ALTER TABLE class_enrollments ADD CONSTRAINT fk_class_enrollments_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_class_enrollments_class" => "ALTER TABLE class_enrollments ADD CONSTRAINT fk_class_enrollments_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        // Class professors
        "fk_class_professors_class" => "ALTER TABLE class_professors ADD CONSTRAINT fk_class_professors_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        "fk_class_professors_professor" => "ALTER TABLE class_professors ADD CONSTRAINT fk_class_professors_professor FOREIGN KEY (professor_id) REFERENCES professors(professor_id) ON DELETE CASCADE;",
        // Enrollment & Unenrollment requests (NEW: link class_id to classes)
        "fk_enrollment_requests_student" => "ALTER TABLE enrollment_requests ADD CONSTRAINT fk_enrollment_requests_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_enrollment_requests_class" => "ALTER TABLE enrollment_requests ADD CONSTRAINT fk_enrollment_requests_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        "fk_unenrollment_requests_class" => "ALTER TABLE unenrollment_requests ADD CONSTRAINT fk_unenrollment_requests_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        // Professor subjects
        "fk_professor_subjects_professor" => "ALTER TABLE professor_subjects ADD CONSTRAINT fk_professor_subjects_professor FOREIGN KEY (professor_id) REFERENCES professors(professor_id) ON DELETE CASCADE;",
        "fk_professor_subjects_subject" => "ALTER TABLE professor_subjects ADD CONSTRAINT fk_professor_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE;",
        // Student classes
        "fk_student_classes_student" => "ALTER TABLE student_classes ADD CONSTRAINT fk_student_classes_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE;",
        "fk_student_classes_class" => "ALTER TABLE student_classes ADD CONSTRAINT fk_student_classes_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE;",
        // Link school_years to administrators (created_by, updated_by)
        "fk_school_years_created_by" => "ALTER TABLE school_years ADD CONSTRAINT fk_school_years_created_by FOREIGN KEY (created_by) REFERENCES administrators(admin_id) ON DELETE SET NULL;",
        "fk_school_years_updated_by" => "ALTER TABLE school_years ADD CONSTRAINT fk_school_years_updated_by FOREIGN KEY (updated_by) REFERENCES administrators(admin_id) ON DELETE SET NULL;"
    ];

    foreach ($constraints as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "Added constraint $name\n";
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            $tolerable = (
                stripos($msg, 'Duplicate') !== false ||
                stripos($msg, 'already exists') !== false ||
                stripos($msg, 'errno: 1826') !== false || // duplicate FK name
                stripos($msg, 'errno: 1022') !== false || // duplicate key / constraint
                stripos($msg, 'Cannot add foreign key constraint') !== false // may happen if already added in structure script
            );
            if ($tolerable) {
                echo "Skipped constraint (likely exists) $name\n";
            } else {
                echo "Error adding constraint $name: $msg\n";
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

    

    // Apply the updated database structure (opt-in to avoid accidental destructive changes)
    $applyUpdatedStructure = getenv('APPLY_UPDATED_STRUCTURE') === '1';
    if ($applyUpdatedStructure) {
        try {
            $sql = file_get_contents('updated_database_structure.sql');
            $rawStatements = explode(';', $sql);
            foreach ($rawStatements as $raw) {
                $statement = trim($raw);
                if ($statement === '' || strpos($statement, '--') === 0) {
                    continue;
                }
                // Safety: skip DROP TABLE statements unless explicitly allowed
                if (stripos($statement, 'DROP TABLE') === 0 && getenv('ALLOW_DROPS') !== '1') {
                    echo "Skipped DROP statement (ALLOW_DROPS not set): " . substr($statement, 0, 80) . "...\n";
                    continue;
                }
                try {
                    $pdo->exec($statement);
                    echo "Executed: " . substr($statement, 0, 80) . "...\n";
                } catch (PDOException $inner) {
                    $msg = $inner->getMessage();
                    // Tolerate duplicates/existence errors so migration remains idempotent
                    $tolerable = (
                        stripos($msg, 'Duplicate') !== false ||
                        stripos($msg, 'already exists') !== false ||
                        stripos($msg, 'errno: 1061') !== false || // duplicate key name
                        stripos($msg, 'errno: 1826') !== false    // duplicate foreign key name
                    );
                    if ($tolerable) {
                        echo "Skipped (already applied): " . substr($statement, 0, 80) . "...\n";
                        continue;
                    }
                    echo "Error executing statement: " . $msg . "\n";
                }
            }
            echo "Updated database structure applied with tolerant mode.\n";
        } catch (PDOException $e) {
            echo "Error applying updated database structure: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Skipped applying updated_database_structure.sql (set APPLY_UPDATED_STRUCTURE=1 to enable).\n";
    }

    // Optionally run migration 002 to remove school_year_semester (guarded)
    $applyRemoveSys = getenv('APPLY_REMOVE_SCHOOL_YEAR_SEMESTER') === '1';
    if ($applyRemoveSys) {
        $migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '002_remove_school_year_semester.sql';
        if (file_exists($migrationPath)) {
            $sql = file_get_contents($migrationPath);
            // Robust statement splitter: ignore comment lines and assemble statements until semicolon
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                // Strip BOM on first line if present
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue; // skip comment/blank lines
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    // Safety: Skip DROP TABLE if ALLOW_DROPS not set
                    if (stripos($stmt, 'DROP TABLE') === 0 && getenv('ALLOW_DROPS') !== '1') {
                        echo "Skipped DROP statement (ALLOW_DROPS not set): " . substr($stmt, 0, 80) . "...\n";
                        continue;
                    }
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false || // duplicate key name
                            stripos($msg, 'errno: 1826') !== false || // duplicate FK name
                            stripos($msg, 'Check that column/key exists') !== false ||
                            stripos($msg, 'Unknown table') !== false ||
                            stripos($msg, 'Unknown column') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 002 statement: $msg\n";
                        }
                    }
                }
            }
            // Post-check: attempt auto-fix unresolved classes without school_year_id by assigning to current active semester
            try {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE school_year_id IS NULL")->fetchColumn();
                if ($count > 0) {
                    echo "WARNING: classes.school_year_id unresolved for $count rows. Attempting fallback backfill...\n";
                    // pick an active semester if available, else most recent
                    $semId = null;
                    $stmt = $pdo->query("SELECT id FROM semesters WHERE status='Active' ORDER BY id DESC LIMIT 1");
                    $row = $stmt ? $stmt->fetchColumn() : false;
                    if ($row) { $semId = (int)$row; }
                    if (!$semId) {
                        $stmt = $pdo->query("SELECT id FROM semesters ORDER BY id DESC LIMIT 1");
                        $row = $stmt ? $stmt->fetchColumn() : false;
                        if ($row) { $semId = (int)$row; }
                    }
                    if ($semId) {
                        // assign missing semester_id to fallback semester
                        $upd1 = $pdo->prepare("UPDATE classes SET semester_id = ? WHERE school_year_id IS NULL");
                        $upd1->execute([$semId]);
                        // recompute school_year_id from bridge table if school_year_semester_id is set
                        $pdo->exec("UPDATE classes c
                                    JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                                    SET c.school_year_id = sys.school_year_id
                                    WHERE c.school_year_id IS NULL");
                        $count2 = (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE school_year_id IS NULL")->fetchColumn();
                        if ($count2 > 0) {
                            echo "WARNING: $count2 classes still unresolved. Manual backfill recommended before dropping legacy table.\n";
                        } else {
                            echo "Fallback backfill completed. All classes now have school_year_id.\n";
                        }
                    } else {
                        echo "WARNING: No semesters found to use for fallback. Manual backfill required.\n";
                    }
                }
            } catch (PDOException $e) {
                // ignore if classes not accessible for any reason
            }
        } else {
            echo "Migration file not found: $migrationPath\n";
        }
    } else {
        echo "Skipped migration 002 (set APPLY_REMOVE_SCHOOL_YEAR_SEMESTER=1 to enable).\n";
    }

    // Optionally run migration 003 to add professors.department_id -> departments FK (guarded)
    $applyProfDept = getenv('APPLY_PROFESSOR_DEPARTMENT_FK') === '1';
    if ($applyProfDept) {
        $migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '003_add_department_to_professors.sql';
        if (file_exists($migrationPath)) {
            $sql = file_get_contents($migrationPath);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 003 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath\n";
        }
    } else {
        echo "Skipped migration 003 (set APPLY_PROFESSOR_DEPARTMENT_FK=1 to enable).\n";
    }

    // Optionally run migration 004 to normalize school_year_semester (guarded)
    $applySysNormalize = getenv('APPLY_SYS_NORMALIZE') === '1';
    if ($applySysNormalize) {
        $migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '004_normalize_school_year_semester.sql';
        if (file_exists($migrationPath)) {
            $sql = file_get_contents($migrationPath);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    // Allow ALTER statements even if they drop columns; only skip DROP TABLE without ALLOW_DROPS
                    if (stripos($stmt, 'DROP TABLE') === 0 && getenv('ALLOW_DROPS') !== '1') {
                        echo "Skipped DROP TABLE (ALLOW_DROPS not set): " . substr($stmt, 0, 80) . "...\n";
                        continue;
                    }
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 004 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath\n";
        }
    } else {
        echo "Skipped migration 004 (set APPLY_SYS_NORMALIZE=1 to enable).\n";
    }

    // Optionally run migration 005 to adjust semesters and subjects FKs (guarded)
    $applySubjectsSyFk = getenv('APPLY_SUBJECTS_SCHOOL_YEAR_FK') === '1';
    if ($applySubjectsSyFk) {
        $migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '005_adjust_semesters_and_subjects_school_year_fk.sql';
        if (file_exists($migrationPath)) {
            $sql = file_get_contents($migrationPath);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 005 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath\n";
        }
    } else {
        echo "Skipped migration 005 (set APPLY_SUBJECTS_SCHOOL_YEAR_FK=1 to enable).\n";
    }

    // Optionally run migration 006 to drop semesters.school_year_id column (guarded)
    $applyDropSemSy = getenv('APPLY_DROP_SEMESTERS_SCHOOL_YEAR') === '1';
    if ($applyDropSemSy) {
        $migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '006_drop_semesters_school_year_id.sql';
        if (file_exists($migrationPath)) {
            $sql = file_get_contents($migrationPath);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 006 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath\n";
        }
    // Follow-up: also run 007 to drop indexes then column, to handle servers that require index removal first
        $migrationPath2 = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '007_drop_semesters_syid_indexes_then_column.sql';
        if (file_exists($migrationPath2)) {
            $sql = file_get_contents($migrationPath2);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 007 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath2\n";
        }
        // Follow-up: run 008 to drop cross-table FK then drop index and column (most robust path)
        $migrationPath3 = __DIR__ . DIRECTORY_SEPARATOR . 'db_migrations' . DIRECTORY_SEPARATOR . '008_drop_sys_consistency_fk_and_semesters_syid.sql';
        if (file_exists($migrationPath3)) {
            $sql = file_get_contents($migrationPath3);
            $lines = preg_split("/\r?\n/", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                if ($buffer === '' && strpos($line, "\xEF\xBB\xBF") === 0) {
                    $line = substr($line, 3);
                }
                $trim = ltrim($line);
                if ($trim === '' || strpos($trim, '--') === 0) {
                    continue;
                }
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $trim)) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt === '') continue;
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 80) . "...\n";
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        $tolerable = (
                            stripos($msg, 'Duplicate') !== false ||
                            stripos($msg, 'already exists') !== false ||
                            stripos($msg, 'errno: 1061') !== false ||
                            stripos($msg, 'errno: 1826') !== false ||
                            stripos($msg, 'Unknown column') !== false ||
                            stripos($msg, 'Check that column/key exists') !== false
                        );
                        if ($tolerable) {
                            echo "Skipped (likely applied or not applicable): " . substr($stmt, 0, 80) . "...\n";
                        } else {
                            echo "Error executing migration 008 statement: $msg\n";
                        }
                    }
                }
            }
        } else {
            echo "Migration file not found: $migrationPath3\n";
        }
    } else {
        echo "Skipped migration 006 (set APPLY_DROP_SEMESTERS_SCHOOL_YEAR=1 to enable).\n";
    }

    echo "Database updates applied successfully.";
} catch (PDOException $e) {
    echo "Error applying updates: " . $e->getMessage();
}
?>
