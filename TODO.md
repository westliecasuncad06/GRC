# Fix Unenrollment Duplicate Request Error

## Issue
- Database error: Integrity constraint violation: 1062 Duplicate entry 'STU001-CLASS1759549022' for key 'unique_unenroll_request'
- Unique constraint prevents submitting new unenrollment requests even after previous ones are processed

## Root Cause
- Unique constraint `unique_unenroll_request` on (`student_id`, `class_id`) in `unenrollment_requests` table prevents any duplicate entries
- Application logic expects students to submit new requests after rejections/approvals

## Tasks
- [x] Remove the unique constraint `unique_unenroll_request` from `unenrollment_requests` table
- [x] Update frontend logic in `my_enrolled_classes.php` to check for any existing unenrollment request (not just pending)
- [x] Fix status inconsistency in `handle_unenrollment_request_with_notifications.php` (use 'approved' instead of 'accepted')
- [x] Fix records with empty status fields in database
- [x] Update frontend logic to only disable button for pending requests, allowing new requests after processing
- [ ] Test the unenrollment functionality to ensure no duplicate errors occur
- [ ] Verify that students can submit new requests after previous ones are processed
- [ ] Verify that professor can accept/reject requests and status updates correctly

## Files to Modify
- Database schema (remove constraint)
- Student/my_enrolled_classes.php (update button logic)
- php/handle_unenrollment_request_with_notifications.php (fix status values)

## Testing
- Submit unenrollment request
- Have professor reject it
- Try submitting another request for the same class
- Verify no database error occurs
- Verify professor can see and process requests
- Verify status updates correctly in database
