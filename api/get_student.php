<?php
/**
 * كلية أيلول الجامعية - API استرجاع بيانات الطالب بصيغة JSON
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($studentId <= 0 && isset($_GET['univ_no'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id FROM students WHERE university_number = ?");
    $stmt->execute([clean($_GET['univ_no'])]);
    $studentId = (int)$stmt->fetchColumn();
}

if ($studentId <= 0) {
    json_response(['error' => 'يرجى تزويد معرف الطالب أو الرقم الجامعي'], 400);
}

$db = getDBConnection();
$stmt = $db->prepare("
    SELECT s.*, p.program_name, al.level_name, st.status_name, it.type_name as identity_type_name
    FROM students s
    LEFT JOIN programs p ON p.id = s.program_id
    LEFT JOIN academic_levels al ON al.id = s.current_level_id
    LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
    LEFT JOIN identity_types it ON it.id = s.identity_type_id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    json_response(['error' => 'الطالب غير موجود'], 404);
}

json_response([
    'status' => 'success',
    'data' => $student
]);
