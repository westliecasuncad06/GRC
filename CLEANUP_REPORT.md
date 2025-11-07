# Cleanup Report (2025-11-07)

This project cleanup removed documentation, assets, and orphaned scripts that are not referenced by the running system. All deletions were validated via repository-wide search for include/require/href/src/form actions and fetch/AJAX calls.

Safe deletions (no runtime references):

Docs and manuals
- MANUAL_PDF_ENHANCEMENT.md
- MANUAL_PDF_QUICK_REFERENCE.md
- MANUAL_VISUAL_LAYOUT_GUIDE.md
- MOBILE_FIX_DOCUMENTATION.md
- USER_MANUAL_ADMIN.md
- USER_MANUAL_INSTRUCTIONS.md
- USER_MANUAL_PROFESSOR.md
- USER_MANUAL_STUDENT.md
- docs/ERD.md
- docs/SCHEMA_CHECK_REPORT.md
- manuals/index.html
- manuals/script.js
- manuals/style.css
- manuals/USER MANUAL.pdf
- manuals/img/logo.jpg (could not be removed by the editor; folder remains but is unused)

Orphaned or one-off scripts (php/)
- php/delete_summer_from_school_year_semester.php
- php/create_school_year.php
- php/create_semester.php
- php/create_subject.php
- php/manage_students.php
- php/error_display.php
- php/maintenance_cleanup_and_backfill.php
- php/update_user.php

Other
- grc_student_portal_for_attendance_monitoring.sql (old dump; backups/ folder retains a copy)

Notes
- All CSS, includes, and runtime PHP endpoints used by Admin/Professor/Student pages remain intact.
- Apply/update scripts remain (apply_updates.php, db_migrations/, updated_database_structure.sql) as they are referenced.
- If you want me to also remove the remaining unused image `manuals/img/logo.jpg` or the empty `manuals/` directory, let me know and I will retry with file-system permissions adjusted.

Validation
- Verified no references to the deleted files exist in the codebase.
- The app entry points (index.php, Admin/*, Professor/*, Student/*) and API endpoints in php/ continue to resolve to existing files.
