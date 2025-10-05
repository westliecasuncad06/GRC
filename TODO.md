# Task: Transfer Request History to Notifications Modal and Remove Separate Page

## Steps to Complete:
- [x] Modify includes/navbar_student.php:
  - Change modal title to "Notifications"
  - Remove tab buttons (Notifications and Request History)
  - Remove the notification-list div (notifications content)
  - Move history-list content into modal-body as notification-list
  - Remove history-list div outside modal
  - Update script to remove tab switching
  - Update notification badge to count pending requests
- [x] Update includes/sidebar_student.php to remove "Request History" navigation item
- [x] Delete Student/student_request_history.php
- [x] Test the modal functionality
- [x] Verify request history data loads correctly
- [x] Confirm no broken links after removal
