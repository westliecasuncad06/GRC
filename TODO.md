# TODO: Restrict Semester Selection in Professor Manage Subjects

## Tasks
- [ ] Update PHP code to fetch active school_year_semester combinations and pass to JavaScript
- [ ] Modify HTML to make semester dropdown dynamic
- [ ] Add JavaScript to populate semester options based on selected school year
- [ ] Add client-side error messages for invalid selections
- [ ] Update server-side validation to check for active status in school_year_semester table
- [ ] Test form submission with valid and invalid combinations

## Sidebar changes
- [x] Remove notification sidebar from admin, student and professor sidebars
  - Files updated:
    - c:\xampp\htdocs\GRC\admin\sidebar.php
    - c:\xampp\htdocs\GRC\student\sidebar.php
    - c:\xampp\htdocs\GRC\professor\sidebar.php
