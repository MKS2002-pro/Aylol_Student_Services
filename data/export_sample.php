<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$data = [
    'export_time' => date('c'),
    'institution' => $db->query('SELECT * FROM institution')->fetchAll(),
    'programs' => $db->query('SELECT * FROM programs')->fetchAll(),
    'academic_levels' => $db->query('SELECT * FROM academic_levels')->fetchAll(),
    'students' => $db->query('SELECT * FROM students')->fetchAll(),
    'courses' => $db->query('SELECT * FROM courses')->fetchAll(),
    'receipts' => $db->query('SELECT * FROM receipts')->fetchAll(),
    'grades' => $db->query('SELECT * FROM grade_records')->fetchAll(),
    'attendance_records' => $db->query('SELECT * FROM attendance_records')->fetchAll()
];

file_put_contents(__DIR__ . '/sample_data.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "SUCCESS: Created data/sample_data.json\n";
