<<<<<<< HEAD
# Enrollment Request System Implementation

## Tasks
- [ ] Create enrollment_requests table in database
- [ ] Modify enroll_student.php to create pending requests
- [ ] Update professor_dashboard.php to show real notifications
- [ ] Create handle_enrollment_request.php for accept/reject
- [ ] Test the complete flow

## Current Status
Starting implementation...
=======
# Task: Fix Student Views to Hide Archived Classes

## Overview
The issue was that student views were incorrectly filtering archived classes based on `school_year_semester.status` instead of `classes.status`. This caused archived classes to still appear in student dashboards, enrolled classes lists, and schedule views.

## Changes Made

### 1. Student/my_enrolled_classes.php ✅
- **File**: `Student/my_enrolled_classes.php`
- **Change**: Updated query filter from `sys.status != 'Archived'` to `c.status != 'archived'`
- **Impact**: Archived classes no longer appear in the "My Enrolled Classes" list

### 2. Student/student_dashboard.php ✅
- **File**: `Student/student_dashboard.php`
- **Change**: Added `AND c.status != 'archived'` to the query for enrolled subjects/classes
- **Impact**: Archived classes no longer appear in the dashboard stats grid

### 3. Student/student_manage_schedule.php ✅
- **File**: `Student/student_manage_schedule.php`
- **Change**: Updated query filter from `sys.status != 'Archived'` to `c.status != 'archived'`
- **Impact**: Archived classes no longer appear in the "Manage Schedule" view

## Verification
- `Student/student_archive.php` already correctly shows only archived classes with `c.status = 'archived'`
- All student views now properly exclude archived classes
- Archived classes remain accessible only through the archive view

## Status
✅ **COMPLETED** - All student views now correctly hide archived classes
>>>>>>> a47f00a5a1dadc7a66c21776537028970f051a96
