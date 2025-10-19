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
        :root { --primary: #F75270; --primary-dark:#DC143C; --accent:#F7CAC9; --dark:#343a40; --gray:#6c757d; --light:#FDEBD0; --warning:#ffc107; --danger:#dc3545; --success: #28a745; }
        body { font-family: 'Poppins', sans-serif; background: var(--light); }
        .main-content { padding: 2rem; min-height: calc(100vh - 70px); }
        .page-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 8px 25px rgba(247,82,112,0.18); margin-bottom: 1rem; }
        .controls { display:flex; gap:1rem; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; }
        .search-row { display:flex; gap:0.75rem; align-items:center; }
        .search-input, .select-dept { padding:0.6rem 0.8rem; border-radius:8px; border:1px solid #eee; min-width:240px; }

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

        /* View toggle */
        .view-toggle { display:flex; gap:0.5rem; align-items:center; }
        .toggle-switch { position:relative; width:56px; height:30px; background:#fff; border-radius:999px; box-shadow: inset 0 0 0 2px rgba(0,0,0,0.05); cursor:pointer; }
        .toggle-knob { position:absolute; top:3px; left:3px; width:24px; height:24px; background:var(--primary); border-radius:50%; transition:all .25s ease; }
        .toggle-switch.active .toggle-knob { left:29px; background:var(--primary-dark); }

        /* List table */
        .list-table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .list-table th { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:white; padding:0.75rem; text-align:left; font-weight:600; }
        .list-table td { padding:0.75rem; border-bottom:1px solid rgba(0,0,0,0.05); }

        /* Responsive */
        @media (max-width:768px) { 
            .search-row { flex-direction:column; align-items:stretch; } 
            .search-input, .select-dept { width:100%; }
            
            .prof-list { grid-template-columns: 1fr; }
            .prof-card { 
                aspect-ratio: auto; 
                flex-direction:row; 
                align-items:center; 
                padding:1rem;
                transform: none !important;
            }
            .prof-card .meta { 
                padding: 0 1rem 0 0;
                text-align: left;
                background: none;
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
            }
            .prof-card .btn.btn-primary {
                padding: 0.5rem 1rem;
                width: auto;
            }
        }
    /* Tile grid for professors: make square tiles */
        /* Tile grid for professors */
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
    .prof-card .card-footer { width:100%; display:flex; justify-content:flex-end; }
        .subject-list { margin-top:1rem; display:grid; gap:0.75rem; }
        .subject-card { background:white; border-radius:12px; padding:0.8rem; box-shadow:0 6px 18px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:center; }
        .btn { padding:0.55rem 0.9rem; border-radius:10px; border:none; cursor:pointer; font-weight:600; display:inline-flex; gap:0.5rem; align-items:center; }
        .btn-primary { background:linear-gradient(135deg,var(--primary) 0%, var(--primary-dark) 100%); color:white; }
        .btn-secondary { background:var(--gray); color:white; }
        .no-results { color:var(--gray); padding:1rem; text-align:center; }

        /* View toggle (match My Classes) */
        .view-toggle { display:flex; gap:0.5rem; align-items:center; }
        .toggle-switch { position:relative; width:56px; height:30px; background:#fff; border-radius:999px; box-shadow: inset 0 0 0 2px rgba(0,0,0,0.05); cursor:pointer; }
        .toggle-knob { position:absolute; top:3px; left:3px; width:24px; height:24px; background:var(--primary); border-radius:50%; transition:all .25s ease; }
        .toggle-switch.active .toggle-knob { left:29px; background:var(--primary-dark); }

        /* List table for professors */
        .list-table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .list-table th { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:white; padding:0.75rem; text-align:left; font-weight:600; }
        .list-table td { padding:0.75rem; border-bottom:1px solid rgba(0,0,0,0.05); }

        @media (max-width:768px) { 
            .search-row { flex-direction:column; align-items:stretch; } 
            .search-input, .select-dept { width:100%; }
            /* On small screens show tiles as full-width horizontal cards instead of square */
            .prof-list { grid-template-columns: 1fr; }
            .prof-card { 
                aspect-ratio: auto; 
                flex-direction:row; 
                align-items:center; 
                padding:1rem;
                transform: none !important;
            }
            .prof-card .meta { 
                padding: 0 1rem 0 0;
                text-align: left;
                background: none;
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
            }
            .prof-card .btn.btn-primary {
                padding: 0.5rem 1rem;
                width: auto;
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
            <div style="display:flex; gap:1rem; align-items:center; flex:1;">
                <div class="search-row" style="flex:1;">
                    <input id="profSearch" class="search-input" type="search" placeholder="Search professors by name or ID..." autocomplete="off" />
                    <select id="deptFilter" class="select-dept">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="view-toggle" style="white-space:nowrap;">
                                <label style="font-weight:600; color:var(--dark);">Tile View</label>
                                <div id="toggleViewSwitch" class="toggle-switch" role="switch" aria-checked="false" tabindex="0" onclick="toggleProfView()">
                                    <div class="toggle-knob"></div>
                                </div>
                                <label style="font-weight:600; color:var(--dark);">List View</label>
                            </div>
            </div>
            <div>
                <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
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
</body>
</html>
