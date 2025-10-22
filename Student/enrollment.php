<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Handle AJAX actions in this file for simplicity: action=search_professors | get_subjects | get_departments
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    if ($action === 'search_professors') {
        $q = trim($_GET['q'] ?? '');
        $dept = trim($_GET['department'] ?? '');
        $params = [];
        $sql = "SELECT professor_id, first_name, last_name, department FROM professors WHERE 1=1";
        if ($q !== '') {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ? OR professor_id LIKE ? )";
            $like = "%$q%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($dept !== '') {
            $sql .= " AND department = ?";
            $params[] = $dept;
        }
        $sql .= " ORDER BY last_name, first_name LIMIT 25";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'results' => $rows]);
        exit();
    }

    if ($action === 'get_departments') {
        $stmt = $pdo->query("SELECT DISTINCT department FROM professors WHERE department IS NOT NULL AND department <> '' ORDER BY department");
        $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'departments' => $depts]);
        exit();
    }

    if ($action === 'get_subjects' && isset($_GET['professor_id'])) {
        $professor_id = $_GET['professor_id'];
        // Return only subjects (classes) from this professor that the student is NOT yet enrolled in (by subject, not just exact class)
        $sql = "
            SELECT c.class_id, s.subject_code, s.subject_name, c.section, c.class_code
            FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.professor_id = ?
              AND c.status != 'archived'
              AND NOT EXISTS (
                  SELECT 1
                  FROM student_classes sc
                  JOIN classes c2 ON sc.class_id = c2.class_id
                  WHERE sc.student_id = ?
                    AND c2.subject_id = c.subject_id
              )
            ORDER BY s.subject_code, c.section
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$professor_id, $student_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'subjects' => $rows]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Enrollment - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
    :root { 
        /* Core colors */
        --primary: #F75270; 
        --primary-dark: #DC143C; 
        --primary-light: #FF8DA3;
        --accent: #F7CAC9; 
        --dark: #343a40; 
        --gray: #6c757d;
        --light: #FDEBD0; 
        --warning: #ffc107; 
        --danger: #dc3545; 
        --success: #28a745;
        
        /* Shadows */
        --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
        --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        --shadow-primary: 0 8px 24px rgba(247,82,112,0.15);
        
        /* Transitions */
        --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-normal: 250ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
        
        /* Spacing */
        --space-xs: 0.25rem;
        --space-sm: 0.5rem;
        --space-md: 1rem;
        --space-lg: 1.5rem;
        --space-xl: 2rem;
        
        /* Border Radius */
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-full: 9999px;
    }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content { 
            padding: 2rem; 
            min-height: calc(100vh - 70px);
            flex: 1;
            margin-left: 250px; /* Match sidebar width */
            width: calc(100% - 250px);
            transition: margin-left 0.3s, width 0.3s;
        }
        .page-header { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
            color: white; 
            padding: 1.5rem; 
            border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(247,82,112,0.18); 
            margin-bottom: 2rem;
        }
        .page-header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .controls { 
            display: flex; 
            flex-direction: column;
            gap: 1rem; 
            margin-bottom: 1.5rem; 
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        /* First Row: Search + View Toggle */
        .search-toggle-row { 
            display: flex; 
            gap: 1rem; 
            align-items: center;
            width: 100%;
        }
        
        /* Second Row: Dropdown */
        .dropdown-row {
            display: flex;
            width: 100%;
        }
        
        /* Third Row: Button */
        .button-row {
            display: flex;
            width: 100%;
        }
        /* Common input/select styling */
        .search-input, .select-dept { 
            padding: 0.625rem 0.875rem; 
            border-radius: 10px; 
            border: 2px solid #e8e8e8; 
            font-size: 0.95rem;
            transition: all 0.25s ease;
            box-sizing: border-box;
            background: #FFFFFF;
            height: 44px;
            outline: none;
            color: #343a40;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }
        
        /* Search input specific */
        .search-input {
            flex: 1;
            min-width: 250px;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%236c757d"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 0.875rem center;
            background-size: 18px;
            padding-right: 2.5rem;
            padding-left: 0.875rem;
        }
        /* Dropdown specific - full width in its row */
        .select-dept {
            width: 100%;
            background-color: #FFFFFF;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23F75270"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 20px;
            padding-right: 2.75rem;
            padding-left: 0.875rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
            color: #343a40;
            font-weight: 600;
        }
        .select-dept option {
            color: #343a40;
            background: white;
            padding: 0.625rem;
            font-weight: 500;
        }
        .search-input:hover, .select-dept:hover {
            border-color: #d0d0d0;
            background-color: #fafafa;
            transform: translateY(-1px);
        }
        .search-input:focus, .select-dept:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(247,82,112,0.12);
            background-color: white;
            transform: translateY(-1px);
        }
        
        /* View toggle - compact for top right */
        .view-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(247,82,112,0.06);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.25s ease;
            border: 2px solid rgba(247,82,112,0.12);
            flex-shrink: 0;
            white-space: nowrap;
        }
        .view-toggle:hover {
            background: rgba(247,82,112,0.08);
            border-color: rgba(247,82,112,0.2);
        }
        .view-toggle label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
            user-select: none;
            transition: color 0.2s ease;
        }
        .view-toggle label:hover {
            color: var(--primary);
        }
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 26px;
            background: white;
            border-radius: 999px;
            box-shadow: inset 0 0 0 2px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .toggle-knob {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(247,82,112,0.25);
        }
        .toggle-switch.active {
            background: rgba(247,82,112,0.12);
            box-shadow: inset 0 0 0 2px var(--primary);
        }
        .toggle-switch.active .toggle-knob {
            left: 25px;
            background: var(--primary-dark);
            box-shadow: 0 2px 8px rgba(220,20,60,0.3);
        }
        
        /* Clear button - full width in its row */
        .btn-clear {
            width: 100%;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            border: 2px solid transparent;
            background: linear-gradient(135deg, var(--gray) 0%, #5a6268 100%);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            height: 44px;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-clear:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-clear:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .btn-clear i {
            font-size: 1rem;
        }

        /* Responsive adjustments */
        /* Tablet breakpoint */
        @media (max-width: 992px) {
            .main-content {
                padding: 1.25rem;
                margin-left: 0;
                width: 100%;
            }
            .controls {
                padding: 1rem;
            }
            .search-toggle-row {
                gap: 0.75rem;
            }
        }

        /* Small tablet / Large mobile */
        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1rem;
            }
            .page-header h1 {
                font-size: 1.25rem;
            }
            .controls {
                padding: 1rem;
                gap: 0.875rem;
                border-radius: 10px;
            }
            .search-toggle-row {
                flex-direction: column;
                gap: 0.75rem;
            }
            .search-input {
                width: 100%;
                min-width: 100%;
                font-size: 0.95rem;
                height: 44px;
                padding: 0.625rem 2.5rem 0.625rem 0.875rem;
                background-position: right 0.875rem center;
                background-size: 18px;
            }
            .select-dept {
                width: 100%;
                font-size: 0.95rem;
                height: 44px;
                padding: 0.625rem 2.75rem 0.625rem 0.875rem;
                background-position: right 0.875rem center;
                background-size: 18px;
            }
            .view-toggle {
                width: 100%;
                justify-content: center;
                padding: 0.625rem 1rem;
            }
            .btn-clear {
                width: 100%;
                height: 44px;
                font-size: 0.95rem;
            }
        }

        /* Mobile breakpoint */
        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
            }
            .page-header {
                padding: 0.875rem;
                border-radius: 8px;
                margin-bottom: 0.875rem;
            }
            .page-header h1 {
                font-size: 1.15rem;
                font-weight: 600;
            }
            .controls {
                padding: 0.875rem;
                gap: 0.75rem;
            }
            .search-input, .select-dept, .btn-clear {
                height: 42px;
                font-size: 0.9rem;
            }

        }
        
        /* Small mobile optimization */
        @media (max-width: 360px) {
            .main-content {
                padding: 0.5rem;
            }
            .controls {
                padding: 0.625rem;
                gap: 0.625rem;
            }
            .search-input, .select-dept, .btn-clear {
                height: 40px;
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
            }
            .search-input {
                padding-right: 2rem;
                background-position: right 0.625rem center;
                background-size: 16px;
            }
            .select-dept {
                padding-right: 2rem;
                background-position: right 0.625rem center;
                background-size: 16px;
            }
        }
            .toggle-switch {
                width: 44px;
                height: 24px;
            }
            .toggle-knob {
                width: 18px;
                height: 18px;
                top: 3px;
                left: 3px;
            }
            .toggle-switch.active .toggle-knob {
                left: 21px;
            }
    
        /* Dropdown responsiveness */
        .select-dept {
            max-width: 220px;
        }
        @media (max-width: 480px) {
            .select-dept {
                max-width: 100%;
            }
        }
        /* Responsive modal for mobile */
        @media (max-width: 480px) {
            .modal .modal-content {
                padding: 0.5rem !important;
                border-radius: 8px !important;
            }
            .modal-header, .modal-footer {
                padding: 0.5rem 0.5rem !important;
                border-radius: 8px 8px 0 0 !important;
            }
            .modal-body {
                padding: 0.5rem !important;
            }
        }


        /* Professor Tile Grid */
        .prof-list { 
            margin-top:1rem; 
            display:grid; 
            gap:1rem; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
        }
        .prof-card { 
            background:white; 
            border-radius:16px; 
            padding:1.25rem; 
            box-shadow: 0 8px 24px rgba(247,82,112,0.08), 0 2px 8px rgba(0,0,0,0.03);
            display:flex; 
            flex-direction:column; 
            justify-content:space-between; 
            align-items:center; 
            aspect-ratio: 1 / 1; 
            overflow:hidden;
            position: relative;
            transition: all 0.2s ease;
        }
        .prof-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(247,82,112,0.12), 0 4px 12px rgba(0,0,0,0.05);
        }
        .prof-card .meta { 
            width:100%; 
            text-align:center; 
            position: relative;
            padding: 0.5rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.95) 100%);
            border-radius: 12px;
        }
        .prof-card .meta strong { 
            display:block; 
            margin-bottom:0.5rem; 
            font-size: 1.1rem;
            color: var(--dark);
        }
        .prof-card .meta .dept { 
            color:var(--gray); 
            font-size:0.9rem;
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: rgba(247,82,112,0.06);
            border-radius: 999px;
            margin-top: 0.25rem;
        }
        .prof-card .card-footer { 
            width:100%; 
            display:flex; 
            justify-content:center;
            margin-top: 0.5rem;
        }
        .prof-card .btn.btn-primary {
            padding: 0.7rem 1.2rem;
            font-size: 0.95rem;
            width: 100%;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(247,82,112,0.2);
            transition: all 0.2s ease;
        }
        .prof-card .btn.btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(247,82,112,0.25);
        }

        /* Subject Cards */
        .subject-list { margin-top:1rem; display:grid; gap:0.75rem; }
        .subject-card { background:white; border-radius:12px; padding:0.8rem; box-shadow:0 6px 18px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:center; }
        
        /* Buttons */
        .btn { padding:0.55rem 0.9rem; border-radius:10px; border:none; cursor:pointer; font-weight:600; display:inline-flex; gap:0.5rem; align-items:center; }
        .btn-primary { background:linear-gradient(135deg,var(--primary) 0%, var(--primary-dark) 100%); color:white; }
        .btn-secondary { background:var(--gray); color:white; }
        .no-results { color:var(--gray); padding:1rem; text-align:center; }


        .toggle-switch { 
            position: relative; 
            width: 40px; 
            height: 22px; 
            background: #fff; 
            border-radius: 999px; 
            box-shadow: inset 0 0 0 1px #e0e0e0; 
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .toggle-knob { 
            position: absolute; 
            top: 2px; 
            left: 2px; 
            width: 18px; 
            height: 18px; 
            background: var(--primary); 
            border-radius: 50%; 
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .toggle-switch.active { 
            background: rgba(247,82,112,0.1);
            box-shadow: inset 0 0 0 1px var(--primary);
        }
        .toggle-switch.active .toggle-knob { 
            left: 20px; 
            background: var(--primary);
        }

        /* List table */
        .list-table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .list-table th { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:white; padding:0.75rem; text-align:left; font-weight:600; }
        .list-table td { padding:0.75rem; border-bottom:1px solid rgba(0,0,0,0.05); }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .prof-list {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) { 
            .prof-list { 
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .prof-card { 
                aspect-ratio: auto; 
                flex-direction: row; 
                align-items: center; 
                padding: 1rem;
                transform: none !important;
            }
            .prof-card .meta { 
                padding: 0 1rem 0 0;
                text-align: left;
                background: none;
                flex: 1;
            }
            .prof-card .meta strong {
                font-size: 1rem;
                margin-bottom: 0.35rem;
            }
            .prof-card .meta .dept {
                font-size: 0.85rem;
                padding: 0.25rem 0.6rem;
            }
            .prof-card .card-footer { 
                justify-content: flex-end;
                margin: 0;
                width: auto;
                min-width: 120px;
            }
            .prof-card .btn.btn-primary {
                padding: 0.5rem 1rem;
                width: auto;
                font-size: 0.9rem;
            }

            /* Subject list responsiveness */
            .subject-list {
                gap: 0.5rem;
            }
            .subject-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem;
            }
            .subject-card > div {
                width: 100%;
            }
            .subject-card .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 1rem;
            }
            .page-header h1 {
                font-size: 1.5rem;
            }
            .controls {
                gap: 0.75rem;
            }
            .controls-inner { gap: 0.5rem; }
            .view-toggle {
                flex-wrap: wrap;
                justify-content: center;
            }
            .prof-card {
                flex-direction: column;
                text-align: center;
                padding: 1.25rem;
            }
            .prof-card .meta {
                text-align: center;
                padding: 0;
                margin-bottom: 1rem;
            }
            .prof-card .card-footer {
                width: 100%;
                justify-content: center;
            }
            .prof-card .btn.btn-primary {
                width: 100%;
            }
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            /* Keep horizontal cards on mobile for better alignment */
            .prof-list {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .prof-card {
                aspect-ratio: auto;
                flex-direction: row;
                align-items: center;
                padding: 1rem;
                transform: none !important;
                min-height: 100px; /* Ensure consistent height */
                justify-content: space-between;
            }
            .prof-card .meta {
                flex: 1;
                padding: 0 1rem 0 0;
                text-align: left;
                background: none;
                margin: 0;
            }
            .prof-card .meta strong {
                font-size: 1rem;
                margin-bottom: 0.35rem;
                display: block;
            }
            .prof-card .meta .dept {
                font-size: 0.85rem;
                padding: 0.25rem 0.6rem;
                display: inline-block;
            }
            .prof-card .card-footer {
                justify-content: flex-end;
                margin: 0;
                width: auto;
                flex-shrink: 0;
            }
            .prof-card .btn.btn-primary {
                padding: 0.5rem 1rem;
                width: auto;
                min-width: 100px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1 style="margin:0; font-size:1.25rem;"><i class="fas fa-graduation-cap"></i> Enrollment</h1>
            <p style="margin:0.25rem 0 0 0; opacity:0.95;">Search for professors and enroll in subjects they offer.</p>
        </div>

        <div class="controls">
            <!-- First Row: Search Bar + View Toggle -->
            <div class="search-toggle-row">
                <input id="profSearch" class="search-input" type="search" placeholder="Search professors by name or ID..." autocomplete="off" />
                <div class="view-toggle">
                    <label>Tile View</label>
                    <div id="toggleViewSwitch" class="toggle-switch" role="switch" aria-checked="false" tabindex="0" onclick="toggleProfView()">
                        <div class="toggle-knob"></div>
                    </div>
                    <label>List View</label>
                </div>
            </div>
            
            <!-- Second Row: Department Dropdown (Full Width) -->
            <div class="dropdown-row">
                <select id="deptFilter" class="select-dept">
                    <option value="">All Departments</option>
                </select>
            </div>
            
            <!-- Third Row: Clear Filters Button -->
            <div class="button-row">
                <button class="btn-clear" onclick="clearFilters()">
                    <i class="fas fa-times"></i>
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Containers for both views -->
        <div id="tileProfessors" class="prof-list"></div>
        <div id="listProfessors" style="display:none; margin-top:0.75rem;">
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th style="width:220px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="listProfBody"></tbody>
            </table>
        </div>

        <div id="subjects" class="subject-list"></div>
    </main>

    <div id="toast" style="position:fixed; top:20px; right:20px; z-index:9999;"></div>

    <script>
    const profSearch = document.getElementById('profSearch');
    const deptFilter = document.getElementById('deptFilter');
    const subjectsContainer = document.getElementById('subjects');
        let debounceTimer = null;

        function showToast(msg, type='success'){
            const el = document.createElement('div'); el.textContent = msg; el.style.padding='0.7rem 1rem'; el.style.borderRadius='8px'; el.style.background= type==='success'?'#d4edda':'#f8d7da'; el.style.color=type==='success'?'#155724':'#721c24'; document.getElementById('toast').appendChild(el);
            setTimeout(()=>el.remove(),3000);
        }

        function fetchDepartments(){
            fetch('?action=get_departments').then(r=>r.json()).then(d=>{
                if (d.success){
                    d.departments.forEach(dep=>{
                        const opt = document.createElement('option'); opt.value=dep; opt.textContent = dep; deptFilter.appendChild(opt);
                    });
                }
            });
        }

        function searchProfessors(){
            const q = profSearch.value.trim();
            const dept = deptFilter.value;
            fetch(`?action=search_professors&q=${encodeURIComponent(q)}&department=${encodeURIComponent(dept)}`)
                .then(r=>r.json()).then(d=>{
                    // Clear both views and subjects
                    document.getElementById('tileProfessors').innerHTML = '';
                    document.getElementById('listProfBody').innerHTML = '';
                    subjectsContainer.innerHTML = '';
                    if (!d.success || !d.results || d.results.length===0){
                        document.getElementById('tileProfessors').innerHTML = '<div class="no-results">No professors found.</div>';
                        document.getElementById('listProfBody').innerHTML = '<tr><td colspan="3" class="no-results">No professors found.</td></tr>';
                        return;
                    }

                    d.results.forEach(p=>{
                        // Tile/card view
                        const el = document.createElement('div'); el.className='prof-card';
                        const meta = document.createElement('div'); meta.className = 'meta';
                        meta.innerHTML = `<strong>${escapeHTML(p.first_name)} ${escapeHTML(p.last_name)}</strong><div class="dept">${escapeHTML(p.department||'')}</div>`;
                        const footer = document.createElement('div'); footer.className = 'card-footer';
                        const btn = document.createElement('button');
                        btn.setAttribute('type', 'button');
                        btn.className = 'btn btn-primary view-subjects-btn';
                        btn.textContent = 'View Subjects';
                        btn.setAttribute('data-professor-id', p.professor_id);
                        btn.setAttribute('data-professor-name', (p.first_name || '') + ' ' + (p.last_name || ''));
                        btn.addEventListener('click', function(){ openSubjectsModal(p.professor_id, ((p.first_name || '') + ' ' + (p.last_name || '')).trim()); });
                        footer.appendChild(btn);
                        el.appendChild(meta);
                        el.appendChild(footer);
                        document.getElementById('tileProfessors').appendChild(el);

                        // List/table view row
                        const tr = document.createElement('tr');
                        const nameTd = document.createElement('td'); nameTd.textContent = `${p.first_name} ${p.last_name}`;
                        const deptTd = document.createElement('td'); deptTd.textContent = p.department || '';
                        const actionsTd = document.createElement('td'); actionsTd.style.textAlign = 'right';
                        const btnList = document.createElement('button'); btnList.className = 'btn btn-primary'; btnList.textContent = 'View Subjects'; btnList.setAttribute('type','button');
                        btnList.addEventListener('click', function(){ openSubjectsModal(p.professor_id, ((p.first_name || '') + ' ' + (p.last_name || '')).trim()); });
                        actionsTd.appendChild(btnList);
                        tr.appendChild(nameTd); tr.appendChild(deptTd); tr.appendChild(actionsTd);
                        document.getElementById('listProfBody').appendChild(tr);
                    });
                }).catch(()=>{
                    document.getElementById('tileProfessors').innerHTML = '<div class="no-results">Error fetching results.</div>';
                    document.getElementById('listProfBody').innerHTML = '<tr><td colspan="3" class="no-results">Error fetching results.</td></tr>';
                });
        }

        // Open modal and load subjects for the professor
        function openSubjectsModal(profId, profName){
            const modal = document.getElementById('subjectsModal');
            const title = document.getElementById('subjectsModalTitle');
            const body = document.getElementById('subjectsModalBody');
            setModalAlert('', '');
            title.textContent = `Subjects taught by ${profName}`;
            body.innerHTML = '<div class="no-results">Loading subjects...</div>';
            modal.classList.add('show');

            fetch(`?action=get_subjects&professor_id=${encodeURIComponent(profId)}`).then(r=>r.json()).then(d=>{
                body.innerHTML = '';
                if (!d.success || !d.subjects || d.subjects.length===0){ body.innerHTML = '<div class="no-results">No available subjects to enroll for this professor.</div>'; return; }
                d.subjects.forEach(s=>{
                    const card = document.createElement('div'); card.className='subject-card';
                    card.innerHTML = `<div style="flex:1"><div style="font-weight:700">${escapeHTML(s.subject_code)} - ${escapeHTML(s.subject_name)}</div><div style="color:var(--gray);">Prof. ${escapeHTML(profName)} • Section: ${escapeHTML(s.section||'')}</div></div><div><button class="btn btn-primary" onclick="enroll('${escapeJS(s.class_code)}')">Enroll</button></div>`;
                    body.appendChild(card);
                });
            }).catch(()=>{ body.innerHTML = '<div class="no-results">Error loading subjects.</div>'; });
        }

        function setModalAlert(type, message){
            const alertBox = document.getElementById('subjectsModalAlert');
            if (!alertBox) return;
            alertBox.className = 'modal-alert ' + (type === 'success' ? 'success' : 'error');
            alertBox.textContent = message || '';
            alertBox.style.display = message ? 'block' : 'none';
        }

        function enroll(classCode){
            const data = new URLSearchParams(); data.append('class_code', classCode);
            setModalAlert('', '');
            fetch('../php/enroll_student.php', { method:'POST', body: data })
                .then(r=>r.json())
                .then(d=>{
                    if (d.success){
                        setModalAlert('success', 'Enrolled successfully. Redirecting to My Classes...');
                        // Optional toast; comment out if not desired
                        // showToast('Enrolled successfully','success');
                        setTimeout(()=>location.href='my_classes.php', 1200);
                    } else {
                        setModalAlert('error', d.message || 'Enrollment failed');
                        // Optional toast; comment out if not desired
                        // showToast(d.message||'Enrollment failed','error');
                    }
                })
                .catch(()=>{
                    setModalAlert('error', 'Network error');
                    // showToast('Network error','error');
                });
        }

        function clearFilters(){ profSearch.value=''; deptFilter.value=''; searchProfessors(); }

        function escapeHTML(s){ if(!s) return ''; return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
        function escapeJS(s){ return (s||'').replace(/'/g,"\\'").replace(/\n/g,' '); }

        profSearch.addEventListener('input', ()=>{ clearTimeout(debounceTimer); debounceTimer = setTimeout(searchProfessors, 250); });
        deptFilter.addEventListener('change', searchProfessors);

        // View toggle logic for professors list
        let profListView = false; // false = tile (default), true = list
        function toggleProfView(){
            const toggle = document.getElementById('toggleViewSwitch');
            profListView = !profListView;
            if (profListView) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-checked','true');
                document.getElementById('tileProfessors').style.display = 'none';
                document.getElementById('listProfessors').style.display = 'block';
            } else {
                toggle.classList.remove('active');
                toggle.setAttribute('aria-checked','false');
                document.getElementById('tileProfessors').style.display = 'grid';
                document.getElementById('listProfessors').style.display = 'none';
            }
        }

        // Accessibility: allow toggle via keyboard
        document.getElementById('toggleViewSwitch').addEventListener('keydown', function(e){ if (e.key==='Enter' || e.key===' ') { e.preventDefault(); toggleProfView(); } });

        // Init
        fetchDepartments();
        searchProfessors();

        // Delegated click handler for View Subjects buttons
        // Delegated click handler for View Subjects buttons (robust to text-node clicks)
        document.addEventListener('click', function(e){
            if (!(e.target instanceof Element)) return;
            const btn = e.target.closest('.view-subjects-btn');
            if (!btn) return;
            const profId = btn.getAttribute('data-professor-id');
            const profName = btn.getAttribute('data-professor-name') || '';
            if (profId) openSubjectsModal(profId, profName);
        });
    </script>

    <!-- Subjects Modal -->
    <div id="subjectsModal" class="modal">
        <div class="modal-content" style="max-width:720px; width:calc(100% - 2rem);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1rem; border-radius: 12px 12px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="subjectsModalTitle" style="margin:0; font-size:1.05rem;">Subjects</h3>
                <button class="modal-close" onclick="closeSubjectsModal()" style="background: rgba(255,255,255,0.2); border:none; color:white; width:34px; height:34px; border-radius:50%;">&times;</button>
            </div>
            <div class="modal-body" style="padding:1rem; background:white; max-height:60vh; overflow:auto;">
                <div id="subjectsModalAlert" class="modal-alert" style="display:none;"></div>
                <div id="subjectsModalBody"><!-- Subject cards populate here --></div>
            </div>
            <div class="modal-footer" style="padding:0.8rem 1rem; background:#F8F9FA; border-top:1px solid #E9ECEF; border-radius:0 0 12px 12px; text-align:right;">
                <button class="btn btn-secondary" onclick="closeSubjectsModal()">Close</button>
            </div>
        </div>
    </div>

    <style>
        /* Modal base styles matching portal theme */
    .modal { position:fixed; left:0; top:0; right:0; bottom:0; display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.45); z-index:99999; }
    .modal .modal-content { background: transparent; }
    .modal.show { display:flex; }
    .modal:not(.show) { display:none; }
    @media (max-width:768px) { .modal .modal-content { width: calc(100% - 1rem); } }
    /* Inline modal alert styles */
    .modal-alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem; font-weight: 500; }
    .modal-alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .modal-alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>

    <script>
        function closeSubjectsModal(){
            const modal = document.getElementById('subjectsModal');
            if (!modal) return;
            modal.classList.remove('show');
            // clear content after closing
            const body = document.getElementById('subjectsModalBody');
            if (body) body.innerHTML = '';
            setModalAlert('', '');
        }
        // Close modal when clicking on the overlay (not anywhere in the document)
        (function(){
            const modal = document.getElementById('subjectsModal');
            if (!modal) return;
            modal.addEventListener('click', function(e){
                // Only close if clicking directly on the backdrop overlay
                if (e.target === modal) closeSubjectsModal();
            });
        })();
        // Close modal on Esc
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeSubjectsModal(); });
    </script>
    <?php include '../includes/footbar.php'; ?>
</body>
</html>
