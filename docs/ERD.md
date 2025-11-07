# GRC Student Portal – Enhanced ERD

This ERD reflects the latest schema from `backups/grc_student_portal_for_attendance_monitoring (9).sql` and consolidates relationships for clarity. Duplicate FK aliases in the dump (e.g., `attendance_ibfk_1` and `fk_attendance_student`) are shown as a single relationship.

## Mermaid ERD

```mermaid
erDiagram
  administrators {
    varchar(20) admin_id PK
    varchar(50) first_name
    varchar(50) last_name
    varchar(100) email UK
    varchar(255) password
    datetime created_at
    datetime updated_at
  }

  students {
    varchar(20) student_id PK
    varchar(50) first_name
    varchar(50) last_name
    varchar(50) middle_name
    varchar(100) email UK
    varchar(255) password
    varchar(15) mobile
    text address
    datetime created_at
    datetime updated_at
    varchar(10) section
  }

  departments {
    int department_id PK
    varchar(100) department_name UK
    datetime created_at
    datetime updated_at
  }

  professors {
    varchar(20) professor_id PK
    varchar(20) employee_id UK
    varchar(50) first_name
    varchar(50) last_name
    varchar(100) email UK
    varchar(255) password
    varchar(100) department
    int department_id FK
    varchar(15) mobile
    datetime created_at
    datetime updated_at
  }

  subject_durations {
    int duration_id PK
    varchar(50) subject_duration
  }

  subjects {
    varchar(20) subject_id PK
    varchar(100) subject_name
    varchar(20) subject_code UK
    text description
    int credits
    int duration_id FK
    datetime created_at
    datetime updated_at
    int semester_id FK
  }

  school_years {
    int id PK
    varchar(20) year_label UK
    enum status
    timestamp created_at
    timestamp updated_at
  }

  semesters {
    int id PK
    int school_year_id FK
    enum semester_name
    enum status
    timestamp created_at
    timestamp updated_at
  }

  school_year_semester {
    int id PK
    varchar(20) school_year
    enum semester
    enum status
    timestamp created_at
    timestamp updated_at
  }

  classes {
    varchar(20) class_id PK
    varchar(100) class_name
    varchar(20) class_code UK
    varchar(20) subject_id FK
    varchar(20) professor_id FK
    varchar(100) schedule
    varchar(50) room
    datetime created_at
    datetime updated_at
    varchar(10) section
    int semester_id FK
    enum status
    int school_year_semester_id FK
  }

  student_classes {
    int enrollment_id PK
    varchar(20) student_id FK
    varchar(20) class_id FK
    datetime enrolled_at
  }

  class_enrollments {
    int id PK
    varchar(20) class_id FK
    varchar(20) student_id FK
    enum enrollment_status
    varchar(10) grade
    timestamp created_at
    timestamp updated_at
  }

  class_professors {
    int id PK
    varchar(20) class_id FK
    varchar(20) professor_id FK
    enum role
    timestamp created_at
    timestamp updated_at
  }

  attendance {
    int attendance_id PK
    varchar(20) student_id FK
    varchar(20) class_id FK
    date date
    enum status
    text remarks
    datetime created_at
  }

  professor_attendance {
    int attendance_id PK
    varchar(20) professor_id FK
    varchar(20) subject_id FK
    date date
    datetime time_in
    datetime time_out
    timestamp created_at
    timestamp updated_at
  }

  professor_subjects {
    int assignment_id PK
    varchar(20) professor_id FK
    varchar(20) subject_id FK
    datetime assigned_at
  }

  enrollment_requests {
    int request_id PK
    varchar(20) student_id FK
    varchar(20) class_id FK
    enum status
    datetime requested_at
    datetime handled_at
    varchar(20) handled_by
    datetime processed_at
    varchar(20) processed_by FK
  }

  unenrollment_requests {
    int request_id PK
    varchar(20) student_id FK
    varchar(20) class_id FK
    enum status
    datetime requested_at
    datetime handled_at
    varchar(20) handled_by
    datetime processed_at
    varchar(20) processed_by FK
  }

  notifications {
    int notification_id PK
    varchar(20) user_id
    enum user_type
    varchar(255) title
    text message
    enum type
    tinyint is_read
    datetime created_at
    int related_request_id
    varchar(20) related_class_id FK
  }

  students ||--o{ attendance : "has"
  classes ||--o{ attendance : "records"

  subjects ||--o{ classes : "taught"
  professors ||--o{ classes : "handles"
  semesters ||--o{ classes : "groups"
  school_year_semester ||--o{ classes : "belongs"

  students ||--o{ student_classes : "enrolls"
  classes ||--o{ student_classes : "has"

  students ||--o{ class_enrollments : "enrolled"
  classes ||--o{ class_enrollments : "contains"

  classes ||--o{ class_professors : "assigned"
  professors ||--o{ class_professors : "teaches"

  professors ||--o{ professor_attendance : "logs"
  subjects ||--o{ professor_attendance : "for"

  professors ||--o{ professor_subjects : "qualified"
  subjects ||--o{ professor_subjects : "has"

  school_years ||--o{ semesters : "has"

  subject_durations ||--o{ subjects : "duration"

  students ||--o{ enrollment_requests : "requests"
  classes ||--o{ enrollment_requests : "requested"
  professors ||--o{ enrollment_requests : "processed_by"

  students ||--o{ unenrollment_requests : "requests"
  classes ||--o{ unenrollment_requests : "requested"
  professors ||--o{ unenrollment_requests : "processed_by"

  classes ||--o{ notifications : "related_class"
```

## Notes
- Classes use both normalized `semesters` and legacy `school_year_semester` via `classes.semester_id` and `classes.school_year_semester_id`. The app appears to be transitioning; keep both until UI fully aligns.
- Several tables have duplicate FK definitions in the dump (different names, same columns). Functionally harmless but redundant. Consider pruning duplicates in a future migration for clarity.
