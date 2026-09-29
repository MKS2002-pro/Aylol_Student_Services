<?php
/**
 * كلية أيلول الجامعية - API استرجاع كشف الحضور والغياب بصيغة JSON
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$levelId = isset($_GET['level_id']) ? (int)$_GET['level_id'] : 3;
$semesterId = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 1;

if ($studentId <= 0) {
    json_response(['error' => 'معرف الطالب مطلوب'], 400);
}

$db = getDBConnection();
$coursesStmt = $db->prepare("
    SELECT c.id as course_id, c.course_name, c.course_code, co.id as offering_id
    FROM courses c
    JOIN course_offerings co ON co.course_id = c.id
    WHERE co.academic_level_id = ? AND co.semester_id = ?
    ORDER BY c.id ASC
");
$coursesStmt->execute([$levelId, $semesterId]);
$courses = $coursesStmt->fetchAll();

$result = [];
foreach ($courses as $c) {
    $attStmt = $db->prepare("
        SELECT ls.lecture_number, ls.session_date, ar.status 
        FROM lecture_sessions ls
        LEFT JOIN attendance_records ar ON ar.session_id = ls.id AND ar.student_id = ?
        WHERE ls.course_offering_id = ?
        ORDER BY ls.lecture_number ASC
    ");
    $attStmt->execute([$studentId, $c['offering_id']]);
    $sessions = $attStmt->fetchAll();

    $present = count(array_filter($sessions, fn($s) => $s['status'] === 'present'));
    $absent = count(array_filter($sessions, fn($s) => $s['status'] === 'absent'));
    $totalRecorded = $present + $absent;
    $percentage = $totalRecorded > 0 ? round(($present / $totalRecorded) * 100) : 100;
    $badge = get_attendance_status($absent);

    $result[] = [
        'course_id' => $c['course_id'],
        'course_name' => $c['course_name'],
        'course_code' => $c['course_code'],
        'attendance_percentage' => $percentage,
        'absent_count' => $absent,
        'status_badge' => $badge,
        'sessions' => $sessions
    ];
}

json_response([
    'status' => 'success',
    'student_id' => $studentId,
    'level_id' => $levelId,
    'semester_id' => $semesterId,
    'attendance_data' => $result
]);
