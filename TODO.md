# Notification System Bug Fix

## Plan Overview
Fix the notification system where notifications are stored in DB but not displayed in UI for professors and students. The issue is that students don't receive notifications about request status changes, and API endpoints are restricted to students only.

## Steps to Complete

### 1. Update Request Handling Scripts
- [x] Modify `php/handle_enrollment_request_with_notifications.php` to create notifications for students when requests are accepted/rejected
- [x] Modify `php/handle_unenrollment_request_with_notifications.php` to create notifications for students when requests are accepted/rejected

### 2. Modify API Endpoints for Both Roles
- [x] Update `php/get_notifications.php` to work for both professors and students (remove role restrictions)
- [x] Update `php/get_unread_notification_count.php` to work for both professors and students
- [x] Update `php/mark_all_notifications_read.php` to work for both professors and students

### 3. Update Student Navbar
- [x] Modify `includes/navbar_student.php` to fetch actual notifications via API instead of showing request history
- [x] Ensure notification modal displays real notifications from DB

### 4. Testing and Verification
- [ ] Test notification creation for both professors and students
- [ ] Verify API endpoints work for both roles
- [ ] Check UI displays notifications correctly for both roles
