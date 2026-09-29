<?php
/**
 * كلية أيلول الجامعية - تصدير وتحميل بيانات النظام بصيغة JSON
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$type = clean($_GET['type'] ?? 'student');
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$db = getDBConnection();

$filename = 'aylol_export_' . $type . '_' . date('Ymd_His') . '.json';
$data = [];

switch ($type) {
    case 'students_all':
        $stmt = $db->query("
            SELECT s.*, p.program_name, al.level_name, st.status_name 
            FROM students s
            LEFT JOIN programs p ON p.id = s.program_id
            LEFT JOIN academic_levels al ON al.id = s.current_level_id
            LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
        ");
        $data = [
            'institution' => 'Aylol University College',
            'exported_at' => date('c'),
            'type' => 'all_students',
            'count' => $stmt->rowCount(),
            'students' => $stmt->fetchAll()
        ];
        break;

    case 'finance_all':
    case 'finance':
        $q = "
            SELECT r.*, s.full_name as student_name, s.university_number 
            FROM receipts r 
            JOIN students s ON s.id = r.student_id
        ";
        if ($studentId > 0) $q .= " WHERE r.student_id = $studentId";
        $q .= " ORDER BY r.payment_date ASC";
        $stmt = $db->query($q);
        $receipts = $stmt->fetchAll();

        $tuition = array_filter($receipts, fn($r) => $r['category'] === 'tuition');
        $other = array_filter($receipts, fn($r) => $r['category'] === 'other');

        $data = [
            'institution' => 'Aylol University College',
            'exported_at' => date('c'),
            'exchange_rate' => 250.0,
            'tuition_receipts' => array_values($tuition),
            'other_receipts' => array_values($other)
        ];
        break;

    case 'grades_all':
    case 'grades':
        $q = "
            SELECT gr.*, s.full_name as student_name, s.university_number, c.course_name, c.course_code
            FROM grade_records gr
            JOIN students s ON s.id = gr.student_id
            JOIN courses c ON c.id = gr.course_id
        ";
        if ($studentId > 0) $q .= " WHERE gr.student_id = $studentId";
        $stmt = $db->query($q);
        $data = [
            'institution' => 'Aylol University College',
            'exported_at' => date('c'),
            'grades' => $stmt->fetchAll()
        ];
        break;

    case 'attendance_all':
    case 'attendance':
        $q = "
            SELECT ar.*, s.full_name as student_name, s.university_number, ls.lecture_number, ls.session_date, c.course_name
            FROM attendance_records ar
            JOIN students s ON s.id = ar.student_id
            JOIN lecture_sessions ls ON ls.id = ar.session_id
            JOIN course_offerings co ON co.id = ls.course_offering_id
            JOIN courses c ON c.id = co.course_id
        ";
        if ($studentId > 0) $q .= " WHERE ar.student_id = $studentId";
        $stmt = $db->query($q);
        $data = [
            'institution' => 'Aylol University College',
            'exported_at' => date('c'),
            'attendance_records' => $stmt->fetchAll()
        ];
        break;

    case 'student':
    default:
        if ($studentId <= 0 && isset($_SESSION['student_id'])) {
            $studentId = (int)$_SESSION['student_id'];
        }
        $sStmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $sStmt->execute([$studentId]);
        $student = $sStmt->fetch();

        $fStmt = $db->prepare("SELECT * FROM receipts WHERE student_id = ?");
        $fStmt->execute([$studentId]);
        $receipts = $fStmt->fetchAll();

        $gStmt = $db->prepare("
            SELECT gr.*, c.course_name, c.course_code 
            FROM grade_records gr 
            JOIN courses c ON c.id = gr.course_id 
            WHERE gr.student_id = ?
        ");
        $gStmt->execute([$studentId]);
        $grades = $gStmt->fetchAll();

        $data = [
            'institution' => 'Aylol University College',
            'student_profile' => $student,
            'receipts' => $receipts,
            'grades' => $grades,
            'exported_at' => date('c')
        ];
        break;
}

header('Content-Type: application/json; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
