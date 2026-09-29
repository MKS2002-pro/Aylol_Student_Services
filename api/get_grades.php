<?php
/**
 * كلية أيلول الجامعية - API استرجاع درجات الطالب (نصفية ونهائية) بصيغة JSON
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
$stmt = $db->prepare("
    SELECT c.id as course_id, c.course_name, c.course_code, c.course_type,
           gr.attendance_grade, gr.participation_grade, gr.midterm_theory,
           gr.midterm_practical, gr.final_practical, gr.final_theory,
           gr.student_grade, gr.maximum_grade, gr.passing_grade,
           gr.grade_letter, gr.is_repeat, gr.notes, gr.created_at
    FROM courses c
    LEFT JOIN grade_records gr ON gr.course_id = c.id AND gr.student_id = ?
    WHERE gr.academic_level_id = ? AND gr.semester_id = ?
    ORDER BY c.id ASC
");
$stmt->execute([$studentId, $levelId, $semesterId]);
$grades = $stmt->fetchAll();

json_response([
    'status' => 'success',
    'student_id' => $studentId,
    'level_id' => $levelId,
    'semester_id' => $semesterId,
    'count' => count($grades),
    'grades' => $grades
]);
