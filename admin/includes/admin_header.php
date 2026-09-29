<?php
/**
 * كلية أيلول الجامعية - هيدر لوحة تحكم الكنترول
 */
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/functions.php';

requireAdmin();
$admin = getCurrentAdmin();

if (!isset($adminPageTitle)) {
    $adminPageTitle = 'لوحة تحكم الكنترول';
}
if (!isset($activeNav)) {
    $activeNav = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($adminPageTitle) ?> - كنترول كلية أيلول الجامعية</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .admin-badge-control {
      background: #e0f2fe;
      color: #0369a1;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 800;
    }
  </style>
</head>
<body style="background-color: #f1f5f9;">

<div class="admin-layout">
  
  <!-- القائمة الجانبية للكنترول -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-header">
      <img src="../assets/images/logo.png" alt="AUC" style="width: 44px; height: 44px; object-fit: contain;" onerror="this.src='../assets/images/3.png';">
      <div>
        <h3 style="font-size: 1.05rem; font-weight: 900; color: #ffffff;">كنترول أيلول</h3>
        <span style="font-size: 0.78rem; color: #94a3b8;">Aylol College Control</span>
      </div>
    </div>

    <ul class="admin-nav-list">
      <li class="admin-nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
        <a href="index.php">
          <span>📊</span>
          <span>لوحة المعلومات</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'students' ? 'active' : '' ?>">
        <a href="students.php">
          <span>👨‍🎓</span>
          <span>إدارة الطلاب (إضافة وتعديل)</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'finance' ? 'active' : '' ?>">
        <a href="finance.php">
          <span>💰</span>
          <span>كشف الحساب والرسوم (سندات)</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'attendance' ? 'active' : '' ?>">
        <a href="attendance.php">
          <span>📝</span>
          <span>كشف الحضور والغياب (المحاضرات)</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'grades' ? 'active' : '' ?>">
        <a href="grades.php">
          <span>🏆</span>
          <span>رصد الدرجات (قفل 15 يوماً)</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'curriculum' ? 'active' : '' ?>">
        <a href="programs_levels.php">
          <span>📚</span>
          <span>التخصصات والمستويات والمقررات</span>
        </a>
      </li>
      <li class="admin-nav-item <?= $activeNav === 'json' ? 'active' : '' ?>">
        <a href="json_manager.php">
          <span>📦</span>
          <span>إدارة ملفات JSON (تصدير/استيراد)</span>
        </a>
      </li>
      <li class="admin-nav-item" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 12px;">
        <a href="../login.php" target="_blank" style="color: #38bdf8;">
          <span>🌐</span>
          <span>معاينة بوابة الطالب</span>
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="../logout.php" style="color: #f87171;">
          <span>🚪</span>
          <span>تسجيل الخروج</span>
        </a>
      </li>
    </ul>
  </aside>

  <!-- مساحة المحتوى الرئيسية -->
  <div class="admin-content-wrap">
    
    <header class="admin-top-navbar">
      <div style="display: flex; align-items: center; gap: 12px;">
        <h2 style="font-size: 1.2rem; font-weight: 900; color: #0f172a;"><?= htmlspecialchars($adminPageTitle) ?></h2>
      </div>
      <div style="display: flex; align-items: center; gap: 16px;">
        <span class="admin-badge-control">صلاحية الكنترول الأكاديمي</span>
        <div style="font-weight: 800; font-size: 0.95rem; color: #334155;">
          👤 <?= htmlspecialchars($admin['full_name'] ?? 'مدير الكنترول') ?>
        </div>
      </div>
    </header>

    <main class="admin-main-body">
