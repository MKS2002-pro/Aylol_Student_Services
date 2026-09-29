<?php
/**
 * كلية أيلول الجامعية - نقطة الدخول الرئيسية
 */
require_once __DIR__ . '/config/auth.php';

if (isStudentLoggedIn()) {
    header("Location: profile.php");
    exit;
} elseif (isAdminLoggedIn()) {
    header("Location: admin/index.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
