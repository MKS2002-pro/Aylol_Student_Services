<?php
/**
 * كلية أيلول الجامعية - نظام المصادقة وإدارة الجلسات
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function isStudentLoggedIn(): bool {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireStudent(): void {
    if (!isStudentLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header("Location: login.php?as=control");
        exit;
    }
}

function getCurrentStudent(): ?array {
    if (!isStudentLoggedIn()) {
        return null;
    }
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT s.*, p.program_name, al.level_name, st.status_name, it.type_name as identity_type_name,
               ay.year_name as admission_year_name, cay.year_name as current_academic_year_name
        FROM students s
        LEFT JOIN programs p ON p.id = s.program_id
        LEFT JOIN academic_levels al ON al.id = s.current_level_id
        LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
        LEFT JOIN identity_types it ON it.id = s.identity_type_id
        LEFT JOIN academic_years ay ON ay.id = s.admission_year_id
        LEFT JOIN academic_years cay ON cay.id = s.current_academic_year_id
        WHERE s.id = ?
    ");
    $stmt->execute([$_SESSION['student_id']]);
    return $stmt->fetch() ?: null;
}

function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}
