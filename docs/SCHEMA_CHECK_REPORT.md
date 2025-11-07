# Schema Check Report (Nov 7, 2025)

This report validates the latest backup `backups/grc_student_portal_for_attendance_monitoring (9).sql` against the PHP codebase, highlights mismatches, and provides run/import steps.

## Summary
- Backup imports cleanly on MariaDB 10.4+ and matches the app’s current expectations.
- All core tables present: administrators, students, professors, departments, subject_durations, subjects, school_years, semesters, school_year_semester, classes, student_classes, class_enrollments, class_professors, attendance, professor_attendance, professor_subjects, notifications, enrollment_requests, unenrollment_requests.
- FKs are defined; some are duplicated under different names (harmless but redundant). Index coverage is good for common joins.

## Issues found and fixed
- Professor Add Subject (INSERT into classes) used non-existent columns `semester`, `school_year`.
  - Fixed in `Professor/professor_manage_schedule.php` by removing those columns and using only `school_year_semester_id`.

No other code/schema mismatches were detected during scan for table/column usage.

## Known redundancies (safe to keep for now)
- Duplicate FKs on the same columns (e.g., in `attendance`, `class_enrollments`, `class_professors`, `student_classes`). They can be cleaned in a future migration for clarity and a small metadata win.

## Importing the backup (XAMPP / phpMyAdmin)
1. Start MySQL and Apache in XAMPP.
2. Open http://localhost/phpmyadmin
3. Create a DB named `grc_student_portal_for_attendance_monitoring` (if not exists).
4. Import `backups/grc_student_portal_for_attendance_monitoring (9).sql`.

Optional MySQL CLI (Git Bash):
```bash
/c/xampp/mysql/bin/mysql -u root -p grc_student_portal_for_attendance_monitoring < "backups/grc_student_portal_for_attendance_monitoring (9).sql"
```

## Running app updates/migrations
- Your last run failed with exit code 127 because `php` isn’t in PATH under Git Bash.
- Use XAMPP’s PHP explicitly:
```bash
/c/xampp/php/php.exe apply_updates.php
```

Environment toggle to remove legacy `school_year_semester` (disabled by default):
```bash
APPLY_REMOVE_SCHOOL_YEAR_SEMESTER=1 /c/xampp/php/php.exe apply_updates.php
```
Only enable if you’re ready to drop `school_year_semester`; code still references it in multiple places.

## Smoke test queries (optional)
After import, run in phpMyAdmin SQL tab:
- Foreign keys resolve:
```sql
SELECT COUNT(*) AS missing_students FROM attendance a LEFT JOIN students s ON a.student_id=s.student_id WHERE s.student_id IS NULL;
SELECT COUNT(*) AS missing_classes  FROM attendance a LEFT JOIN classes  c ON a.class_id=c.class_id   WHERE c.class_id   IS NULL;
```
Expect both 0.

- Basic joins used by UI:
```sql
SELECT c.class_id, c.class_code, s.subject_name, p.first_name, p.last_name
FROM classes c
LEFT JOIN subjects s ON c.subject_id=s.subject_id
LEFT JOIN professors p ON c.professor_id=p.professor_id
LIMIT 10;
```

## Recommendations (next steps)
- Consolidate on one academic period model. The codebase uses both `semesters` (normalized) and `school_year_semester` (legacy). When you’re ready:
  - Update remaining UI to rely on `semesters` and `school_years` exclusively.
  - Run guarded migration `db_migrations/002_remove_school_year_semester.sql` via `apply_updates.php`.
- Clean duplicate FK constraints in a future `004_cleanup_duplicate_fks.sql` (optional, cosmetic).

## Files touched
- Updated: `Professor/professor_manage_schedule.php` (fixed INSERT to match schema)
- Added: `docs/ERD.md` (enhanced Mermaid ERD)
- Added: `docs/SCHEMA_CHECK_REPORT.md` (this report)

All changes are non-breaking and align code with the current database structure.
