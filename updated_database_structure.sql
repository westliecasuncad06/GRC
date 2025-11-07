-- Server baseline: MariaDB 10.4.x

-- 1) Professors -> Departments (add FK while keeping existing string column for compatibility)
ALTER TABLE professors
  ADD COLUMN IF NOT EXISTS department_id INT NULL AFTER `department`;

-- Populate department_id based on matching names
UPDATE professors p
JOIN departments d ON p.department = d.department_name
SET p.department_id = d.department_id
WHERE p.department_id IS NULL;

-- Add FK constraint (ignore if it already exists)
ALTER TABLE professors
  ADD CONSTRAINT fk_professors_department
  FOREIGN KEY (department_id) REFERENCES departments(department_id)
  ON DELETE SET NULL;

-- 2) Classes -> school_year_semester (add FK)
ALTER TABLE classes
  ADD CONSTRAINT fk_classes_school_year_semester
  FOREIGN KEY (school_year_semester_id) REFERENCES school_year_semester(id)
  ON DELETE SET NULL;

-- 3) Add created_by/updated_by on school years, semesters, and school_year_semester (link to administrators)
ALTER TABLE school_years
  ADD COLUMN IF NOT EXISTS created_by VARCHAR(20) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS updated_by VARCHAR(20) NULL AFTER `created_at`;

ALTER TABLE semesters
  ADD COLUMN IF NOT EXISTS created_by VARCHAR(20) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS updated_by VARCHAR(20) NULL AFTER `created_at`;

ALTER TABLE school_year_semester
  ADD COLUMN IF NOT EXISTS created_by VARCHAR(20) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS updated_by VARCHAR(20) NULL AFTER `created_at`;

-- Add FKs to administrators
ALTER TABLE school_years
  ADD CONSTRAINT fk_school_years_created_by
  FOREIGN KEY (created_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL,
  ADD CONSTRAINT fk_school_years_updated_by
  FOREIGN KEY (updated_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL;

ALTER TABLE semesters
  ADD CONSTRAINT fk_semesters_created_by
  FOREIGN KEY (created_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL,
  ADD CONSTRAINT fk_semesters_updated_by
  FOREIGN KEY (updated_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL;

ALTER TABLE school_year_semester
  ADD CONSTRAINT fk_sys_created_by
  FOREIGN KEY (created_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL,
  ADD CONSTRAINT fk_sys_updated_by
  FOREIGN KEY (updated_by) REFERENCES administrators(admin_id)
  ON DELETE SET NULL;

-- 4) Remove unused tables not referenced by the application code
DROP TABLE IF EXISTS class_enrollments;
DROP TABLE IF EXISTS class_professors;

-- End of migration

-- 7) Link enrollment_requests.class_id and unenrollment_requests.class_id to classes
ALTER TABLE enrollment_requests
  ADD INDEX idx_enrollment_requests_class (class_id),
  ADD CONSTRAINT fk_enrollment_requests_class
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
    ON DELETE CASCADE;

ALTER TABLE unenrollment_requests
  ADD INDEX idx_unenrollment_requests_class (class_id),
  ADD CONSTRAINT fk_unenrollment_requests_class
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
    ON DELETE CASCADE;

