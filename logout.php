<?php
/**
 * كلية أيلول الجامعية - تسجيل الخروج
 */
session_start();
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;
