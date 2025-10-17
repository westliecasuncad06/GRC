<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Unauthorized';
    exit();
}

require_once __DIR__ . '/db.php';

// Check subject_id param
if (!isset($_GET['subject_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'subject_id is required';
    exit();
}

$subject_id = $_GET['subject_id'];
$professor_id = $_SESSION['user_id'];

// Verify subject belongs to this professor via classes table
$stmt = $pdo->prepare("SELECT s.subject_id, s.subject_name
                       FROM subjects s
                       JOIN classes c ON s.subject_id = c.subject_id
                       WHERE s.subject_id = ? AND c.professor_id = ?
                       LIMIT 1");
$stmt->execute([$subject_id, $professor_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$subject) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied to this subject';
    exit();
}

// Fetch attendance data for this subject
$stmt = $pdo->prepare("SELECT CONCAT(s.first_name, ' ', s.last_name) as studname, s.section, s.student_id as stud_id,
                       SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) as excused,
                       SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
                       CASE WHEN (SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) + SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) + SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END)) > 0
                            THEN ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / (SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) + SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) + SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END))) * 100, 2)
                            ELSE 0 END as percentage
                       FROM attendance a
                       JOIN classes c ON a.class_id = c.class_id
                       JOIN students s ON a.student_id = s.student_id
                       WHERE c.subject_id = ?
                       GROUP BY s.student_id
                       ORDER BY studname, s.section");
$stmt->execute([$subject_id]);
$attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals for the subject
$total_present = 0;
$total_absent = 0;
$total_excused = 0;
foreach ($attendanceRows as $row) {
    $total_present += $row['present'];
    $total_absent += $row['absent'];
    $total_excused += $row['excused'];
}
$total_records = $total_present + $total_absent + $total_excused;
$total_percentage = $total_records > 0 ? round(($total_present / $total_records) * 100, 2) : 0;

// Check for Composer autoload (PhpSpreadsheet)
$autoload = __DIR__ . '/../vendor/autoload.php';
$has_phpspreadsheet = file_exists($autoload);
if ($has_phpspreadsheet) {
    require_once $autoload;
}

if ($has_phpspreadsheet) {
    try {
        // Use fully-qualified class names to avoid "use" inside conditional
        $templatePath = __DIR__ . '/../Attendance.xlsx';
        if (!file_exists($templatePath)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Template Attendance.xlsx not found in project root.';
            exit();
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Helper to find a cell by exact text
        $findCellByText = function($sheet, $text) {
            $highestRow = $sheet->getHighestRow();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
            for ($r = 1; $r <= $highestRow; $r++) {
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $val = (string)$sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                    if ($val === $text) {
                        return ['col' => $c, 'row' => $r];
                    }
                }
            }
            return null;
        };

        // Place SUBJECT NAME and SUBJECT ID next to labels if found, otherwise write fallback
        $cellSubjectName = $findCellByText($sheet, 'SUBJECT NAME');
        if ($cellSubjectName) {
            $sheet->setCellValueByColumnAndRow($cellSubjectName['col'] + 1, $cellSubjectName['row'], $subject['subject_name']);
        } else {
            $sheet->setCellValue('A1', 'SUBJECT NAME');
            $sheet->setCellValue('B1', $subject['subject_name']);
        }

        $cellSubjectId = $findCellByText($sheet, 'SUBJECT ID');
        if ($cellSubjectId) {
            $sheet->setCellValueByColumnAndRow($cellSubjectId['col'] + 1, $cellSubjectId['row'], $subject['subject_id']);
        } else {
            $sheet->setCellValue('C1', 'SUBJECT ID');
            $sheet->setCellValue('D1', $subject['subject_id']);
        }

        // Locate header row containing STUDNAME, SECTION, STUD ID, ABSENT, EXCUSED, PRESENT, PERCENTAGE
        $headers = ['STUDNAME', 'SECTION', 'STUD ID', 'ABSENT', 'EXCUSED', 'PRESENT', 'PERCENTAGE'];
        $headerColumns = [];
        $headerRow = null;
        $highestRow = $sheet->getHighestRow();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($r = 1; $r <= $highestRow; $r++) {
            $foundCount = 0;
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $val = (string)$sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                if (in_array($val, $headers, true)) {
                    $headerColumns[$val] = $c;
                    $foundCount++;
                }
            }
            if ($foundCount === count($headers)) {
                $headerRow = $r;
                break;
            }
        }

        // If header row not found, use defaults at row 3 columns A..G and write headers
        if (!$headerRow) {
            $headerRow = 3;
            $headerColumns = [
                'STUDNAME' => 1,
                'SECTION' => 2,
                'STUD ID' => 3,
                'ABSENT' => 4,
                'EXCUSED' => 5,
                'PRESENT' => 6,
                'PERCENTAGE' => 7
            ];
            foreach ($headerColumns as $label => $col) {
                $sheet->setCellValueByColumnAndRow($col, $headerRow, $label);
            }
        }

        // Capture header styles per column to reuse for data rows
        $headerStyles = [];
        foreach ($headerColumns as $label => $colIndex) {
            $headerStyles[$colIndex] = $sheet->getStyleByColumnAndRow($colIndex, $headerRow);
        }

        // Write attendance rows starting after header
        $currentRow = $headerRow + 1;
        foreach ($attendanceRows as $row) {
            $sheet->setCellValueByColumnAndRow($headerColumns['STUDNAME'], $currentRow, $row['studname']);
            $sheet->setCellValueByColumnAndRow($headerColumns['SECTION'], $currentRow, $row['section']);
            $sheet->setCellValueByColumnAndRow($headerColumns['STUD ID'], $currentRow, $row['stud_id']);
            $sheet->setCellValueByColumnAndRow($headerColumns['ABSENT'], $currentRow, $row['absent']);
            $sheet->setCellValueByColumnAndRow($headerColumns['EXCUSED'], $currentRow, $row['excused']);
            $sheet->setCellValueByColumnAndRow($headerColumns['PRESENT'], $currentRow, $row['present']);
            $sheet->setCellValueByColumnAndRow($headerColumns['PERCENTAGE'], $currentRow, $row['percentage'] . '%');

            // duplicate style per column from header row to preserve borders/font/etc.
            foreach ($headerColumns as $label => $colIndex) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $currentRow;
                $sheet->duplicateStyle($headerStyles[$colIndex], $coord);
            }

            $currentRow++;
        }

        // Add total row
        $sheet->setCellValueByColumnAndRow($headerColumns['STUDNAME'], $currentRow, 'TOTAL');
        $sheet->setCellValueByColumnAndRow($headerColumns['SECTION'], $currentRow, '');
        $sheet->setCellValueByColumnAndRow($headerColumns['STUD ID'], $currentRow, '');
        $sheet->setCellValueByColumnAndRow($headerColumns['ABSENT'], $currentRow, $total_absent);
        $sheet->setCellValueByColumnAndRow($headerColumns['EXCUSED'], $currentRow, $total_excused);
        $sheet->setCellValueByColumnAndRow($headerColumns['PRESENT'], $currentRow, $total_present);
        $sheet->setCellValueByColumnAndRow($headerColumns['PERCENTAGE'], $currentRow, $total_percentage . '%');

        // Apply header style to total row
        foreach ($headerColumns as $label => $colIndex) {
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $currentRow;
            $sheet->duplicateStyle($headerStyles[$colIndex], $coord);
        }

        // Filename safe
        $subjectSafe = preg_replace('/[^A-Za-z0-9_\- ]/', '', $subject['subject_name']);
        $subjectSafe = trim(preg_replace('/\s+/', '_', $subjectSafe));
        $filename = 'Attendance_' . ($subjectSafe ?: $subject['subject_id']) . '.xlsx';

        // Output as download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit();

    } catch (\Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error generating spreadsheet: ' . $e->getMessage();
        exit();
    }
} else {
    // Fallback: generate an Excel-compatible HTML table (.xls) with inline styles
    $subjectName = $subject['subject_name'];
    $subjectId = $subject['subject_id'];

    $style = "
        <style>
            body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
            .meta { font-weight: bold; font-size: 14px; padding: 6px 0; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
            th { background: #f2f2f2; font-weight: bold; }
            .col-studname { width: 35%; }
            .col-section { width: 15%; }
            .col-studid { width: 20%; }
            .col-absent, .col-excused, .col-present { width: 10%; text-align: center; }
        </style>
    ";

    $html  = '<html><head><meta charset="UTF-8"/>' . $style . '</head><body>';
    $html .= '<table>';
    $html .= '<tr><td class="meta">SUBJECT NAME</td><td colspan="6">' . htmlspecialchars($subjectName) . '</td></tr>';
    $html .= '<tr><td class="meta">SUBJECT ID</td><td colspan="6">' . htmlspecialchars($subjectId) . '</td></tr>';
    $html .= '<tr><td colspan="7" style="height:8px;border:none;"></td></tr>';
    $html .= '<tr><td class="meta">ATTENDANCE</td><td colspan="6">ATTENDANCE STATUS …......</td></tr>';
    $html .= '<tr>';
    $html .= '<th class="col-studname">STUDNAME</th>';
    $html .= '<th class="col-section">SECTION</th>';
    $html .= '<th class="col-studid">STUD ID</th>';
    $html .= '<th class="col-absent">ABSENT</th>';
    $html .= '<th class="col-excused">EXCUSED</th>';
    $html .= '<th class="col-present">PRESENT</th>';
    $html .= '<th class="col-percentage">PERCENTAGE</th>';
    $html .= '</tr>';

    foreach ($attendanceRows as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['studname']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['section']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['stud_id']) . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($row['absent']) . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($row['excused']) . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($row['present']) . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($row['percentage']) . '%</td>';
        $html .= '</tr>';
    }

    // Add total row
    $html .= '<tr>';
    $html .= '<td><strong>TOTAL</strong></td>';
    $html .= '<td></td>';
    $html .= '<td></td>';
    $html .= '<td style="text-align:center;"><strong>' . htmlspecialchars($total_absent) . '</strong></td>';
    $html .= '<td style="text-align:center;"><strong>' . htmlspecialchars($total_excused) . '</strong></td>';
    $html .= '<td style="text-align:center;"><strong>' . htmlspecialchars($total_present) . '</strong></td>';
    $html .= '<td style="text-align:center;"><strong>' . htmlspecialchars($total_percentage) . '%</strong></td>';
    $html .= '</tr>';

    $html .= '</table></body></html>';

    $subjectSafe = preg_replace('/[^A-Za-z0-9_\- ]/', '', $subjectName);
    $subjectSafe = trim(preg_replace('/\s+/', '_', $subjectSafe));
    $filename = 'Attendance_' . ($subjectSafe ?: $subjectId) . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: public');
    header('Cache-Control: max-age=0');

    echo $html;
    exit();
}
?>
