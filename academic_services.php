<?php
/**
 * كلية أيلول الجامعية - فهرس الخدمات الأكاديمية
 * مطابقة تامة للصورة 7 في ملف PDF مع البطاقات الثلاث التفاعلية
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();

$pageTitle = 'الخدمات الاكاديمية';
include_once __DIR__ . '/includes/header.php';
?>

<div class="academic-services-container">
  
  <div class="academic-title-block">
    <h2 class="academic-subheading"><?= htmlspecialchars($student['college_name'] ?? 'كلية أيلول الجامعية') ?> _ <?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?> _ <?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?></h2>
    <h1 class="academic-main-title">الخدمات الاكاديمية</h1>
  </div>

  <div class="academic-cards-stack">
    
    <!-- البطاقة 1: كشف الحضور و الغياب -->
    <a href="attendance.php" class="academic-service-card">
      <div class="academic-card-text">
        <h3>كشف الحضور و الغياب</h3>
        <p>سجل المحاضرات , نسبة الغياب , الإنذارات</p>
      </div>
      <div class="academic-icon-circle blue-circle">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
          <line x1="8" y1="14" x2="8.01" y2="14"/>
          <line x1="12" y1="14" x2="12.01" y2="14"/>
          <line x1="16" y1="14" x2="16.01" y2="14"/>
        </svg>
      </div>
    </a>

    <!-- البطاقة 2: كشف الدرجات النصفية (مطابقة للصورة 7) -->
    <a href="midterm_grades.php" class="academic-service-card">
      <div class="academic-card-text">
        <h3>كشف الدرجات</h3>
        <p>كشف الدرجات النصفية لكل ترم</p>
      </div>
      <div class="academic-icon-circle green-circle">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      </div>
    </a>

    <!-- البطاقة 3: بيان المحصلات (مطابقة للصورة 7) -->
    <a href="grades_statement.php" class="academic-service-card">
      <div class="academic-card-text">
        <h3>بيان المحصلات</h3>
        <p>نتائج الدرجات النصفية و النهائية لكل سنه دراسية</p>
      </div>
      <div class="academic-icon-circle red-circle">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
      </div>
    </a>

  </div>

</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
