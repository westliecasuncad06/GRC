<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get enrolled classes with professor and subject info
$stmt = $pdo->prepare("SELECT c.class_id, c.class_name, c.class_code, c.subject_id, c.professor_id, c.schedule, c.room, c.section, c.semester_id, c.status, c.school_year_semester_id, s.subject_name, p.first_name, p.last_name
                     FROM student_classes sc
                     JOIN classes c ON sc.class_id = c.class_id
                     LEFT JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     WHERE sc.student_id = ? AND c.status != 'archived'");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();

// Get any unenrollment requests for the student
$stmt = $pdo->prepare("SELECT ur.request_id, ur.requested_at, ur.class_id, ur.status FROM unenrollment_requests ur WHERE ur.student_id = ? ORDER BY ur.requested_at DESC");
$stmt->execute([$student_id]);
$unenrollment_requests = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Classes - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        /* Merge styles from existing pages and keep consistent theme */
        :root { 
            --primary: #F75270; 
            --primary-dark:#DC143C; 
            --accent:#F7CAC9; 
            --dark:#343a40; 
            --gray:#6c757d; 
            --light:#FDEBD0; 
            --warning:#ffc107; 
            --danger:#dc3545;
            --success: #28a745;
        }
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .toast {
            background: white;
            color: var(--dark);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            border-left: 4px solid;
            width: 100%;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            border-left-color: var(--success);
            background: #FFFFFF; /* solid white background to avoid tint */
        }

        .toast.success i {
            color: var(--success);
        }

        .toast.error {
            border-left-color: var(--danger);
            background: #FFFFFF; /* solid white background to avoid tint */
        }

        .toast.error i {
            color: var(--danger);
        }

        @media (max-width: 768px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            .toast {
                width: auto;
            }
        }

        body { font-family: 'Poppins', sans-serif; background: var(--light); }
        .main-content { padding: 2rem; min-height: calc(100vh - 70px); }
        .page-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1.75rem; border-radius: 12px; box-shadow: 0 8px 25px rgba(247,82,112,0.18); margin-bottom: 1.5rem; }
        .page-title { font-size: 1.5rem; margin:0; display:flex; gap:0.75rem; align-items:center; }
        .page-subtitle { margin:0; opacity:0.95; }

        .controls { display:flex; gap:1rem; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; }
        .view-toggle { display:flex; gap:0.5rem; align-items:center; }
        .toggle-switch { position:relative; width:56px; height:30px; background:#fff; border-radius:999px; box-shadow: inset 0 0 0 2px rgba(0,0,0,0.05); }
        .toggle-knob { position:absolute; top:3px; left:3px; width:24px; height:24px; background:var(--primary); border-radius:50%; transition:all .25s ease; }
        .toggle-switch.active .toggle-knob { left:29px; background:var(--primary-dark); }

        /* Tile Grid */
        .tiles-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; }
        .class-card { background:white; border-radius:12px; padding:1.25rem; box-shadow:0 8px 25px rgba(0,0,0,0.08); border:1px solid rgba(247,82,112,0.06); }
        .class-card .header { display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem; }
        .class-code { background:linear-gradient(135deg,var(--primary) 0%, var(--primary-dark) 100%); color:white; padding:6px 10px; border-radius:8px; font-weight:600; }
        .class-title { font-weight:700; color:var(--dark); margin-top:0.5rem; }
        .class-sub { color:var(--gray); margin-bottom:0.5rem; }
        .class-meta { display:flex; gap:0.5rem; flex-direction:column; margin-bottom:0.75rem; }
        .class-actions { display:flex; gap:0.5rem; }

        /* List Table */
        .data-table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .data-table th { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:white; padding:0.75rem; text-align:left; font-weight:600; }
        .data-table td { padding:0.75rem; border-bottom:1px solid rgba(247,82,112,0.06); }

        .btn { padding:0.6rem 1rem; border-radius:10px; border:none; cursor:pointer; font-weight:600; display:inline-flex; gap:0.5rem; align-items:center; }
        .btn-primary { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:white; }
        .btn-danger { background:var(--danger); color:white; }
        .btn-warning { background:var(--warning); color:var(--dark); }
        .btn-secondary { background:var(--gray); color:white; }

        /* Responsive tweaks */
        @media (max-width:768px) { .main-content{ padding:1rem } .controls { flex-direction:column; align-items:flex-start; } .data-table thead { display:none; } .data-table, .data-table tbody, .data-table tr, .data-table td { display:block; width:100%; }
            .data-table td { box-sizing:border-box; padding:0.75rem 1rem; } .data-row { margin-bottom:1rem; background:white; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-book"></i> My Classes</h1>
                <p class="page-subtitle">View and manage your enrolled classes. Toggle between Tile and List views.</p>
            </div>
        </div>

        <div class="controls">
            <div class="view-toggle">
                <label style="font-weight:600; color:var(--dark);">Tile View</label>
                <div id="toggle" class="toggle-switch" role="switch" aria-checked="true" tabindex="0" onclick="toggleView()">
                    <div class="toggle-knob"></div>
                </div>
                <label style="font-weight:600; color:var(--dark);">List View</label>
            </div>
            <div>
                <button class="btn btn-primary" onclick="openEnrollModal()"><i class="fas fa-plus"></i> Enroll in Class</button>
            </div>
        </div>

        <div id="tileView" class="tiles-grid">
            <?php if (empty($enrolled_classes)): ?>
                <div class="empty-state" style="grid-column:1/-1; text-align:center; padding:2rem;">
                    <i class="fas fa-inbox" style="font-size:2.5rem; color:var(--gray);"></i>
                    <h3 style="margin:0.5rem 0; color:var(--dark);">No Classes Enrolled</h3>
                    <p style="color:var(--gray);">You haven't enrolled in any classes yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($enrolled_classes as $class): ?>
                    <?php
                        $professor_name = (!empty($class['first_name']) && !empty($class['last_name'])) ? 'Prof. ' . $class['first_name'] . ' ' . $class['last_name'] : 'N/A';
                        $has_pending_request = false;
                        foreach ($unenrollment_requests as $r) { if ($r['class_id'] == $class['class_id'] && $r['status'] == 'pending') { $has_pending_request = true; break; } }
                    ?>
                    <div class="class-card">
                        <div class="header">
                            <div class="class-code"><?php echo htmlspecialchars($class['class_code'] ?? ''); ?></div>
                            <div style="text-align:right; color:var(--gray); font-size:0.9rem;"><?php echo htmlspecialchars($class['schedule']); ?></div>
                        </div>
                        <div class="class-title"><?php echo htmlspecialchars($class['class_name']); ?></div>
                        <div class="class-sub"><?php echo htmlspecialchars($class['subject_name'] ?? ''); ?></div>
                        <div class="class-meta">
                            <div style="color:var(--gray); font-weight:600;"><?php echo htmlspecialchars($professor_name); ?></div>
                            <div style="color:var(--gray);">Room: <?php echo htmlspecialchars($class['room'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="class-actions">
                            <button class="btn btn-primary" onclick="viewAttendance('<?php echo $class['class_id']; ?>')"><i class="fas fa-eye"></i> View Attendance</button>
                                <?php if ($has_pending_request): ?>
                                    <button class="btn btn-warning btn-disabled pending-button" disabled data-class-id="<?php echo $class['class_id']; ?>"><i class="fas fa-clock"></i> Pending Approval</button>
                                <?php else: ?>
                                    <button class="btn btn-danger unenroll-button" onclick="unenrollFromClass('<?php echo $class['class_id']; ?>')" data-class-id="<?php echo $class['class_id']; ?>"><i class="fas fa-times"></i> Unenroll</button>
                                <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="listView" style="display:none;">
            <div class="table-container">
                <?php if (empty($enrolled_classes)): ?>
                    <div class="empty-state" style="text-align:center; padding:2rem;">
                        <i class="fas fa-inbox" style="font-size:2.5rem; color:var(--gray);"></i>
                        <h3 style="margin:0.5rem 0; color:var(--dark);">No Classes Enrolled</h3>
                        <p style="color:var(--gray);">You haven't enrolled in any classes yet.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Class Code</th>
                                <th>Subject Name</th>
                                <th>Class Name</th>
                                <th>Professor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_classes as $class): ?>
                                <?php
                                    $professor_name = (!empty($class['first_name']) && !empty($class['last_name'])) ? 'Prof. ' . $class['first_name'] . ' ' . $class['last_name'] : 'N/A';
                                    $has_pending_request = false;
                                    foreach ($unenrollment_requests as $r) { if ($r['class_id'] == $class['class_id'] && $r['status'] == 'pending') { $has_pending_request = true; break; } }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                    <td><?php echo htmlspecialchars($class['subject_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($professor_name); ?></td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="viewAttendance('<?php echo $class['class_id']; ?>')"><i class="fas fa-eye"></i> View Attendance</button>
                                        <?php if ($has_pending_request): ?>
                                            <button class="btn btn-warning btn-disabled" disabled><i class="fas fa-clock"></i> Pending Approval</button>
                                        <?php else: ?>
                                            <button class="btn btn-danger" onclick="unenrollFromClass('<?php echo $class['class_id']; ?>')"><i class="fas fa-times"></i> Unenroll</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- Enrollment Modal (reuse from student_manage_schedule) -->
    <div id="enrollModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Enroll in Class</h3>
                <button class="modal-close" onclick="closeEnrollModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:var(--gray);">Enter the class code provided by your professor to enroll in a class.</p>
                <form id="enrollForm">
                    <div class="form-group">
                        <label for="class_code">Class Code</label>
                        <input type="text" id="class_code" name="class_code" required style="width:100%; padding:0.6rem; border-radius:8px; border:1px solid #eee;">
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeEnrollModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="enrollBtn"><span id="enrollBtnText">Enroll</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Unenroll -->
    <div id="confirmationModal" class="modal" style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background: #FFFFFF; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.18); max-width: 520px; width: calc(100% - 2rem); margin: 1rem;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1.25rem 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Unenrollment Request
                </h3>
                <button class="modal-close" onclick="closeModal()" style="background: #FFFFFF; border: none; color: var(--dark); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; background: #FFFFFF; color: var(--dark);">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem; display: block;"></i>
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--dark);">Submit Unenrollment Request</h4>
                    <p style="margin: 0; color: var(--gray);">Are you sure you want to submit an unenrollment request for this class?</p>
                </div>
                <div style="background: #FFF8E6; border-left: 4px solid var(--warning); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: start;">
                        <i class="fas fa-info-circle" style="color: var(--warning); margin-top: 0.2rem;"></i>
                        <div>
                            <strong style="color: var(--dark); display: block; margin-bottom: 0.25rem;">Important Note:</strong>
                            <span style="color: var(--gray);">Your professor will need to approve this request before you are unenrolled from the class.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 0.95rem 1.25rem; background: #F8F9FA; border-top: 1px solid #E9ECEF; border-radius: 0 0 12px 12px; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button class="btn btn-secondary" onclick="closeModal()" style="min-width: 100px; background:#e9ecef; color:var(--dark);">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-warning" id="confirmUnenrollBtn" style="min-width: 140px; background: var(--warning); color: var(--dark);">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal" role="dialog" aria-hidden="true">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0;">
                <h3 class="modal-title" style="font-size: 1.25rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-calendar-check"></i>
                    Attendance Records
                </h3>
                <button class="modal-close" onclick="closeAttendanceModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; cursor: pointer; transition: all 0.2s ease;">&times;</button>
            </div>
            <div class="modal-body" id="attendanceModalBody" style="padding: 1.5rem;">
                <p style="color: var(--gray); text-align: center;">Loading attendance...</p>
            </div>
            <style>
                .attendance-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 0.5rem;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                }
                .attendance-table th {
                    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                    color: white;
                    padding: 1rem;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.9rem;
                }
                .attendance-table td {
                    padding: 1rem;
                    border-bottom: 1px solid rgba(0,0,0,0.05);
                    font-size: 0.9rem;
                }
                .attendance-table tr:last-child td {
                    border-bottom: none;
                }
                .attendance-table tr:hover {
                    background: rgba(247,82,112,0.02);
                }
                .attendance-status {
                    display: inline-block;
                    padding: 0.4rem 0.8rem;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 0.85rem;
                    text-align: center;
                    min-width: 100px;
                }
                .attendance-status.Present {
                    background: #d4edda;
                    color: #155724;
                }
                .attendance-status.Absent {
                    background: #f8d7da;
                    color: #721c24;
                }
                .attendance-status.Late {
                    background: #fff3cd;
                    color: #856404;
                }
                .attendance-status.Excused {
                    background: #d1ecf1;
                    color: #0c5460;
                }
                @media (max-width: 768px) {
                    .attendance-table th, 
                    .attendance-table td {
                        padding: 0.75rem;
                        font-size: 0.85rem;
                    }
                    .attendance-status {
                        padding: 0.3rem 0.6rem;
                        font-size: 0.8rem;
                        min-width: 90px;
                    }
                }
            </style>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script>
        let listView = false;
        let currentClassId = null;

        function toggleView() {
            const toggle = document.getElementById('toggle');
            listView = !listView;
            if (listView) {
                toggle.classList.add('active');
                document.getElementById('tileView').style.display = 'none';
                document.getElementById('listView').style.display = 'block';
            } else {
                toggle.classList.remove('active');
                document.getElementById('tileView').style.display = 'grid';
                document.getElementById('listView').style.display = 'none';
            }
        }

        function openEnrollModal() { document.getElementById('enrollModal').classList.add('show'); }
        function closeEnrollModal() { document.getElementById('enrollModal').classList.remove('show'); }

        document.getElementById('enrollForm').addEventListener('submit', function(e){
            e.preventDefault();
            const code = document.getElementById('class_code').value.trim();
            if (!code) return;
            const btn = document.getElementById('enrollBtn');
            btn.disabled = true; document.getElementById('enrollBtnText').textContent = 'Enrolling...';
            const form = new FormData(); form.append('class_code', code);
            fetch('../php/enroll_student.php', { method:'POST', body: form })
                .then(r=>r.json()).then(data=>{
                    if (data.success) { showToast('Enrolled successfully', 'success'); closeEnrollModal(); setTimeout(()=>location.reload(),1200); }
                    else { showToast(data.message || 'Error', 'error'); }
                }).catch(()=>showToast('Network error','error')).finally(()=>{ btn.disabled=false; document.getElementById('enrollBtnText').textContent='Enroll'; });
        });

        function unenrollFromClass(classId) { currentClassId = classId; document.getElementById('confirmationModal').classList.add('show'); }
        function closeModal(){ document.getElementById('confirmationModal').classList.remove('show'); currentClassId=null; }
    document.getElementById('confirmUnenrollBtn').addEventListener('click', function(){ if (!currentClassId) return; const id = currentClassId; closeModal(); fetch('../php/unenroll_student.php',{ method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ class_id: id }) }).then(r=>r.json()).then(d=>{ if (d.success){ showToast('Unenrollment request submitted','success'); // Wait 3 seconds so the UI shows Pending Approval before refresh
            setTimeout(()=>location.reload(),3000);
        } else showToast(d.message||'Error','error'); }).catch(()=>showToast('Network error','error')); });

        function viewAttendance(classId) {
            const modal = document.getElementById('attendanceModal');
            const modalBody = document.getElementById('attendanceModalBody');
            modal.classList.add('show');
            modalBody.innerHTML = '<p style="color: var(--gray); text-align: center;"><i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Loading attendance records...</p>';
            
            fetch(`../php/get_attendance_for_date.php?class_id=${classId}&student_id=<?php echo $student_id; ?>`)
                .then(r => r.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        modalBody.innerHTML = `
                            <div style="text-align: center; padding: 2rem;">
                                <i class="fas fa-calendar-times" style="font-size: 2.5rem; color: var(--gray); margin-bottom: 1rem; display: block;"></i>
                                <p style="color: var(--dark); font-weight: 600; margin-bottom: 0.5rem;">No Attendance Records Found</p>
                                <p style="color: var(--gray);">There are no attendance records available for this class yet.</p>
                            </div>`;
                        return;
                    }

                    let html = `
                        <div style="margin-bottom: 1rem;">
                            <h4 style="color: var(--dark); margin: 0 0 0.5rem 0;">
                                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                                Attendance Summary
                            </h4>
                            <p style="color: var(--gray); margin: 0;">Showing all attendance records for this class.</p>
                        </div>
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">
                                        <i class="fas fa-calendar-alt" style="margin-right: 0.5rem;"></i>Date
                                    </th>
                                    <th style="width: 30%;">
                                        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>Status
                                    </th>
                                    <th style="width: 40%;">
                                        <i class="fas fa-comment-alt" style="margin-right: 0.5rem;"></i>Remarks
                                    </th>
                                </tr>
                            </thead>
                            <tbody>`;

                    data.forEach(rec => {
                        const date = new Date(rec.date).toLocaleDateString('en-US', {
                            weekday: 'short',
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        
                        html += `
                            <tr>
                                <td>${date}</td>
                                <td>
                                    <span class="attendance-status ${rec.status || ''}">${rec.status || 'N/A'}</span>
                                </td>
                                <td style="color: var(--gray);">${rec.remarks || 'No remarks'}</td>
                            </tr>`;
                    });

                    html += `
                            </tbody>
                        </table>`;
                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-exclamation-circle" style="font-size: 2.5rem; color: var(--danger); margin-bottom: 1rem; display: block;"></i>
                            <p style="color: var(--dark); font-weight: 600; margin-bottom: 0.5rem;">Error Loading Attendance</p>
                            <p style="color: var(--gray);">Unable to load attendance records. Please try again later.</p>
                        </div>`;
                });
        }
        function closeAttendanceModal(){ document.getElementById('attendanceModal').classList.remove('show'); }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icon = document.createElement('i');
            icon.className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
            
            const messageSpan = document.createElement('span');
            messageSpan.textContent = message;
            
            toast.appendChild(icon);
            toast.appendChild(messageSpan);
            container.appendChild(toast);
            
            // Trigger reflow for animation
            toast.offsetHeight;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Accessibility: allow toggle via keyboard
        document.getElementById('toggle').addEventListener('keydown', function(e){ if (e.key==='Enter' || e.key===' ') { e.preventDefault(); toggleView(); } });

        // Polling: check for pending unenrollment request status every 2 seconds
        (function(){
            let lastPendingIds = null;
            // Helper to fetch current pending unenrollments for this student
            function fetchPending() {
                return fetch('../php/get_student_pending_unenrollments.php', { cache: 'no-store' })
                    .then(r => r.json())
                    .catch(() => ({ success: false }));
            }

            function arraysEqual(a, b) {
                if (a === null || b === null) return false;
                if (a.length !== b.length) return false;
                const sa = a.slice().sort();
                const sb = b.slice().sort();
                for (let i = 0; i < sa.length; i++) if (sa[i] !== sb[i]) return false;
                return true;
            }

            function updateUI(pendingIds) {
                // Disable unenroll buttons for pending classes, enable others
                document.querySelectorAll('.unenroll-button').forEach(btn => {
                    const cid = btn.getAttribute('data-class-id');
                    if (pendingIds.includes(cid)) {
                        // replace with disabled pending button visually
                        const pendingBtn = document.createElement('button');
                        pendingBtn.className = 'btn btn-warning btn-disabled pending-button';
                        pendingBtn.disabled = true;
                        pendingBtn.setAttribute('data-class-id', cid);
                        pendingBtn.innerHTML = '<i class="fas fa-clock"></i> Pending Approval';
                        btn.replaceWith(pendingBtn);
                    }
                });

                // Show enroll buttons back to actionable state when not pending
                document.querySelectorAll('.pending-button').forEach(btn => {
                    const cid = btn.getAttribute('data-class-id');
                    if (!pendingIds.includes(cid)) {
                        const unenrollBtn = document.createElement('button');
                        unenrollBtn.className = 'btn btn-danger unenroll-button';
                        unenrollBtn.setAttribute('data-class-id', cid);
                        unenrollBtn.setAttribute('onclick', `unenrollFromClass('${cid}')`);
                        unenrollBtn.innerHTML = '<i class="fas fa-times"></i> Unenroll';
                        btn.replaceWith(unenrollBtn);
                    }
                });
            }

            // Initial fetch
            fetchPending().then(data => {
                if (data && data.success) {
                    lastPendingIds = data.pending_class_ids || [];
                }
            });

            // Poll every 2 seconds
            setInterval(() => {
                fetchPending().then(data => {
                    if (!data || !data.success) return;
                    const pendingIds = data.pending_class_ids || [];
                    // If there's any change (professor accepted/rejected -> pending list changed), reload to sync UI
                    if (!arraysEqual(pendingIds, lastPendingIds)) {
                        // If difference is only that a new pending was added, update UI accordingly without full reload
                        // But to ensure full sync (and handle removals), reload after a short delay
                        lastPendingIds = pendingIds.slice();
                        // Update UI quickly
                        updateUI(pendingIds.map(String));
                        // If a pending was removed (professor acted), reload to reflect removal/unenrollment
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                });
            }, 2000);
        })();
    </script>
</body>
</html>
