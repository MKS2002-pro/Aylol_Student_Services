<?php
/**
 * كلية أيلول الجامعية - API استرجاع كشوفات الحساب المالية بصيغة JSON
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId <= 0) {
    json_response(['error' => 'معرف الطالب مطلوب'], 400);
}

$db = getDBConnection();
$tuitionStmt = $db->prepare("SELECT * FROM receipts WHERE student_id = ? AND category = 'tuition' ORDER BY payment_date ASC");
$tuitionStmt->execute([$studentId]);
$tuitionReceipts = $tuitionStmt->fetchAll();

$otherStmt = $db->prepare("SELECT * FROM receipts WHERE student_id = ? AND category = 'other' ORDER BY payment_date ASC");
$otherStmt->execute([$studentId]);
$otherReceipts = $otherStmt->fetchAll();

$totalTuitionYer = array_sum(array_column($tuitionReceipts, 'amount_yer'));
$totalTuitionUsd = array_sum(array_column($tuitionReceipts, 'amount_usd'));
$totalOtherYer = array_sum(array_column($otherReceipts, 'amount_yer'));

json_response([
    'status' => 'success',
    'student_id' => $studentId,
    'exchange_rate' => EXCHANGE_RATE,
    'tuition' => [
        'count' => count($tuitionReceipts),
        'total_yer' => $totalTuitionYer,
        'total_usd' => $totalTuitionUsd,
        'receipts' => $tuitionReceipts
    ],
    'other_fees' => [
        'count' => count($otherReceipts),
        'total_yer' => $totalOtherYer,
        'receipts' => $otherReceipts
    ]
]);
