# User Manual Screenshot Guide
## Global Reciprocal Colleges - Student Portal for Attendance Monitoring

This guide will help you add screenshots to the user manuals.

---

## 📁 Files Created

Three comprehensive user manuals have been created:

1. **USER_MANUAL_ADMIN.md** - Administrator User Manual
2. **USER_MANUAL_PROFESSOR.md** - Professor User Manual  
3. **USER_MANUAL_STUDENT.md** - Student User Manual

---

## 📸 How to Add Screenshots

Each manual contains placeholder text like:
```
**[INSERT SCREENSHOT: Description of what to capture]**
```

### Step-by-Step Process:

#### 1. Run Your Application
```bash
# Start your XAMPP server
# Navigate to https://grc.gt.tc/
```

#### 2. Take Screenshots

**Recommended Tools:**
- **Windows:** Snipping Tool (Win + Shift + S) or Snip & Sketch
- **Windows:** Built-in Snipping Tool or use Screenshot (Print Screen)
- **Mac:** Cmd + Shift + 4
- **Browser Extensions:** Awesome Screenshot, Nimbus Screenshot

**Screenshot Tips:**
- Use high resolution (at least 1920x1080)
- Capture relevant portions only
- Ensure text is readable
- Hide sensitive information
- Use consistent browser zoom level (100%)

#### 3. Organize Screenshots

Create a folder structure:
```
GRC/
├── manuals/
│   ├── images/
│   │   ├── admin/
│   │   │   ├── 01-login-page.png
│   │   │   ├── 02-dashboard.png
│   │   │   ├── 03-manage-students.png
│   │   │   └── ...
│   │   ├── professor/
│   │   │   ├── 01-login-page.png
│   │   │   ├── 02-dashboard.png
│   │   │   ├── 03-take-attendance.png
│   │   │   └── ...
│   │   └── student/
│   │       ├── 01-login-page.png
│   │       ├── 02-dashboard.png
│   │       ├── 03-my-classes.png
│   │       └── ...
│   ├── USER_MANUAL_ADMIN.md
│   ├── USER_MANUAL_PROFESSOR.md
│   └── USER_MANUAL_STUDENT.md
```

#### 4. Name Your Screenshots

Use descriptive, sequential names:
```
01-login-page.png
02-login-form-highlighted.png
03-login-button.png
04-dashboard-full.png
05-dashboard-sidebar.png
06-statistics-cards.png
...
```

#### 5. Insert Screenshots into Manual

Replace placeholder text with markdown image syntax:

**Before:**
```markdown
**[INSERT SCREENSHOT: Login page overview]**
```

**After:**
```markdown
![Login Page Overview](images/admin/01-login-page.png)
```

---

## 📋 Screenshot Checklist by Section

### Administrator Manual Screenshots (60-80 screenshots recommended)

**Login & Dashboard (10-15):**
- [ ] Login page full view
- [ ] Login form with fields highlighted
- [ ] Login button
- [ ] Forgot password form
- [ ] Dashboard full view
- [ ] Navigation sidebar
- [ ] Top navigation bar
- [ ] Statistics cards
- [ ] Professor overview section
- [ ] Success login confirmation

**Managing Students (10-12):**
- [ ] Manage Students menu
- [ ] Student list view
- [ ] Add Student button
- [ ] Student registration form
- [ ] Edit student button
- [ ] Edit form
- [ ] Delete confirmation dialog
- [ ] Search functionality
- [ ] Student detail view
- [ ] Enrollment form

**Managing Professors (8-10):**
- [ ] Manage Professors menu
- [ ] Professor list
- [ ] Add Professor button
- [ ] Professor form
- [ ] Subject assignment
- [ ] Edit professor
- [ ] Delete confirmation

**Managing Subjects (8-10):**
- [ ] Manage Subjects menu
- [ ] Subject list
- [ ] Add Subject button
- [ ] Subject form
- [ ] Edit subject
- [ ] Archive confirmation
- [ ] Archived subjects view

**Managing Schedules (8-10):**
- [ ] Manage Schedule menu
- [ ] Schedule list
- [ ] Create Schedule button
- [ ] Schedule form
- [ ] Edit schedule
- [ ] Delete confirmation
- [ ] Filter by professor
- [ ] Filter by subject

**Academic Periods (6-8):**
- [ ] Academic Periods menu
- [ ] Period overview
- [ ] Create School Year
- [ ] Create Semester
- [ ] Set Active status
- [ ] Edit period

**Archive & Settings (6-8):**
- [ ] Archive menu
- [ ] Archived items
- [ ] Restore button
- [ ] Settings page
- [ ] Edit profile
- [ ] Change password form

### Professor Manual Screenshots (70-90 screenshots recommended)

**Login & Dashboard (10-15):**
- [ ] Login page
- [ ] Login form
- [ ] Professor Dashboard
- [ ] Sidebar navigation
- [ ] Statistics cards
- [ ] Subjects overview
- [ ] Notification bell
- [ ] Recent enrollments
- [ ] Pending requests

**Managing Subjects (8-10):**
- [ ] Manage Subjects menu
- [ ] Subject list with details
- [ ] Subject detail view
- [ ] Take Attendance button
- [ ] Download report button
- [ ] Report options

**Taking Attendance (15-20):**
- [ ] Attendance interface
- [ ] Date selection
- [ ] Student list for attendance
- [ ] Status options (Present/Absent/Excused/Late)
- [ ] Marking attendance
- [ ] Notes field
- [ ] Bulk action buttons
- [ ] Completed form
- [ ] Submit button
- [ ] Success confirmation
- [ ] Edit attendance option
- [ ] Select date to edit

**Viewing Reports (8-10):**
- [ ] Report options menu
- [ ] Class summary report
- [ ] Individual student report
- [ ] Date range selection
- [ ] Download format options
- [ ] Downloaded report example
- [ ] Print preview

**Managing Students (6-8):**
- [ ] Manage Students menu
- [ ] Student list by class
- [ ] Class filter
- [ ] Student details
- [ ] Attendance history
- [ ] Search bar

**Enrollment Requests (8-10):**
- [ ] Enrollment notification
- [ ] Notification panel
- [ ] Unenrollment request
- [ ] Approve button
- [ ] Reject button
- [ ] Confirmation dialogs
- [ ] Success messages

**Archive & Settings (6-8):**
- [ ] Archive menu
- [ ] Archived classes
- [ ] Academic period selection
- [ ] Settings page
- [ ] Change password
- [ ] Notification settings

### Student Manual Screenshots (60-80 screenshots recommended)

**Login & Dashboard (10-12):**
- [ ] Login page
- [ ] Login form
- [ ] Student Dashboard
- [ ] Sidebar navigation
- [ ] Statistics cards
- [ ] Enrolled classes section
- [ ] Quick actions

**Viewing Classes (10-12):**
- [ ] My Classes menu
- [ ] Classes list view
- [ ] Individual class card
- [ ] Class details page
- [ ] Filter options
- [ ] Search bar
- [ ] Class information columns

**Enrollment (10-12):**
- [ ] Enrollment menu
- [ ] Available classes list
- [ ] Search and filter
- [ ] Class details before enrollment
- [ ] Enroll button
- [ ] Confirmation dialog
- [ ] Success message
- [ ] Current enrollments section

**Unenrollment (8-10):**
- [ ] Unenroll button
- [ ] Reason form
- [ ] Confirmation dialog
- [ ] Pending status
- [ ] Approved notification
- [ ] Rejected notification
- [ ] Cancel request button

**Viewing Attendance (12-15):**
- [ ] View Attendance button
- [ ] Attendance overview
- [ ] Summary statistics
- [ ] Detailed records table
- [ ] Color-coded statuses
- [ ] Date range filter
- [ ] Calendar view
- [ ] Download button
- [ ] Download formats
- [ ] Downloaded file example

**Attendance Status (6-8):**
- [ ] Present indicator
- [ ] Absent indicator
- [ ] Excused indicator
- [ ] Late indicator
- [ ] Percentage calculation
- [ ] Low attendance warning

**Schedule & Archive (8-10):**
- [ ] My Schedule menu
- [ ] Weekly view
- [ ] List view
- [ ] Daily view
- [ ] Filter options
- [ ] Print schedule
- [ ] Archive menu
- [ ] Archived classes

**Notifications & Settings (8-10):**
- [ ] Notification bell
- [ ] Notification panel
- [ ] Different notification types
- [ ] Notification settings
- [ ] Settings page
- [ ] Edit profile
- [ ] Change password

---

## 🎨 Screenshot Enhancement Tips

### 1. Highlighting Important Elements
Use image editing tools to:
- Add red arrows pointing to buttons
- Draw red boxes around important sections
- Add text labels or callouts
- Circle form fields
- Highlight menu items

**Tools:**
- Paint (Windows)
- Preview (Mac)
- GIMP (Free, cross-platform)
- Snagit (Paid, professional)
- Photoshop (Paid)

### 2. Consistent Styling
- Use the same arrow style throughout
- Keep highlight colors consistent (red for buttons, yellow for text)
- Use same font for annotations
- Maintain consistent image dimensions

### 3. Image Optimization
- Save as PNG for better quality
- Compress images if manual becomes too large
- Aim for balance between quality and file size
- Typical size: 100-500 KB per image

---

## 📝 Example: Adding Screenshot with Highlights

### Before Adding to Manual:
1. Take screenshot of login page
2. Open in image editor
3. Add red box around email and password fields
4. Add arrow pointing to login button
5. Save as `01-login-form-highlighted.png`

### Add to Manual:
```markdown
**Step 1: Enter Your Credentials**
1. Locate the login form on the right side of the screen
2. In the **Email** field, enter your email address
3. In the **Password** field, enter your password

![Login Form with Highlighted Fields](images/admin/01-login-form-highlighted.png)
*The email and password fields are highlighted in red. Enter your credentials here.*
```

---

## 📤 Converting to PDF (Optional)

To create PDF versions of the manuals:

### Method 1: Using Markdown to PDF Converter
```bash
# Install markdown-pdf (requires Node.js)
npm install -g markdown-pdf

# Convert to PDF
markdown-pdf USER_MANUAL_ADMIN.md
markdown-pdf USER_MANUAL_PROFESSOR.md
markdown-pdf USER_MANUAL_STUDENT.md
```

### Method 2: Using Pandoc
```bash
# Install Pandoc: https://pandoc.org/installing.html

# Convert to PDF
pandoc USER_MANUAL_ADMIN.md -o USER_MANUAL_ADMIN.pdf
pandoc USER_MANUAL_PROFESSOR.md -o USER_MANUAL_PROFESSOR.pdf
pandoc USER_MANUAL_STUDENT.md -o USER_MANUAL_STUDENT.pdf
```

### Method 3: Using Online Converters
- https://www.markdowntopdf.com/
- https://md2pdf.netlify.app/
- Upload .md file and download PDF

### Method 4: Using VS Code Extension
1. Install "Markdown PDF" extension in VS Code
2. Open manual file
3. Right-click > "Markdown PDF: Export (pdf)"

---

## 🔍 Quality Checklist

Before finalizing your manuals:

**Content:**
- [ ] All placeholder texts replaced with screenshots
- [ ] Screenshots are clear and readable
- [ ] Important elements are highlighted
- [ ] Consistent image quality throughout
- [ ] All links work correctly
- [ ] Table of contents links work

**Screenshots:**
- [ ] All screenshots taken at consistent resolution
- [ ] No sensitive data visible (real emails, passwords, etc.)
- [ ] Browser interface is minimal (no unnecessary bookmarks, extensions)
- [ ] Images are properly named and organized
- [ ] All images are referenced in the manual

**Formatting:**
- [ ] Consistent heading styles
- [ ] Proper indentation
- [ ] Tables formatted correctly
- [ ] Lists properly structured
- [ ] Code blocks formatted correctly

**Testing:**
- [ ] Test all procedures using the manual
- [ ] Verify all screenshots match current UI
- [ ] Check for outdated information
- [ ] Ensure step-by-step instructions are accurate

---

## 📞 Support

If you need assistance with the manuals:

1. **For Content Updates:**
   - Edit the .md files directly
   - Use any text editor or Markdown editor

2. **For Screenshot Issues:**
   - Ensure you have screen capture permissions
   - Use recommended screenshot tools
   - Check image paths are correct

3. **For PDF Conversion:**
   - Follow one of the methods above
   - Ensure images are embedded correctly
   - Test PDF on different devices

---

## 📊 Manual Statistics

| Manual | Sections | Approximate Pages | Est. Screenshots Needed |
|--------|----------|-------------------|------------------------|
| Administrator | 13 | 35-45 | 60-80 |
| Professor | 16 | 40-50 | 70-90 |
| Student | 15 | 45-55 | 60-80 |
| **Total** | **44** | **120-150** | **190-250** |

---

## 🎯 Priority Screenshot List

If you're short on time, focus on these critical screenshots first:

### HIGH PRIORITY (Must Have):
1. Login page
2. Dashboard overview (each role)
3. Main navigation sidebar
4. Key function pages (Manage Students, Take Attendance, My Classes)
5. Forms (Add/Edit screens)
6. Confirmation dialogs
7. Success/Error messages

### MEDIUM PRIORITY (Should Have):
8. Detailed list views
9. Filter and search functions
10. Report downloads
11. Settings pages
12. Notification panels

### LOW PRIORITY (Nice to Have):
13. Multiple angles of same screen
14. Calendar views
15. Print previews
16. Archive sections

---

## ✅ Final Steps

Once all screenshots are added:

1. **Review Each Manual:**
   - Read through entirely
   - Verify all images display correctly
   - Check that instructions match screenshots

2. **Test with Real Users:**
   - Have admin, professor, and student test the manuals
   - Get feedback on clarity
   - Update based on feedback

3. **Distribute:**
   - Convert to PDF for easy distribution
   - Upload to school website/portal
   - Print copies for offices
   - Share via email with staff and students

4. **Maintain:**
   - Update when system changes
   - Add new features as developed
   - Keep version history updated
   - Collect ongoing feedback

---

**Good luck with your user manuals!** 📚✨

If you need any clarification or have questions, feel free to ask!
