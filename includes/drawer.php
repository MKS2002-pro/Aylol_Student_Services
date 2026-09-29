<?php
/**
 * كلية أيلول الجامعية - القائمة الجانبية (Drawer Sidebar)
 * مطابقة للصورة 3 في ملف PDF
 */
$student = getCurrentStudent();
$studentPhoto = !empty($student['photo']) && file_exists(__DIR__ . '/../' . $student['photo']) 
    ? htmlspecialchars($student['photo']) 
    : 'assets/images/mohammed_alokkad.jpg';
if (!file_exists(__DIR__ . '/../' . $studentPhoto)) {
    $studentPhoto = 'assets/images/2.jpg';
}
?>
<!-- طبقة القائمة الجانبية التفاعلية -->
<div class="sidebar-drawer-overlay">
  <aside class="sidebar-drawer-panel">
    
    <!-- زر إغلاق القائمة -->
    <div class="sidebar-top-bar">
      <span style="font-weight: 800; color: var(--primary-blue); font-size: 1.05rem;">فهرس الخدمات</span>
      <button type="button" class="sidebar-close-btn" title="إغلاق">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <!-- بطاقة الطالب المختصرة -->
    <div class="sidebar-student-card">
      <div class="sidebar-photo-ring">
        <img src="<?= $studentPhoto ?>" alt="صورة الطالب" onerror="this.src='assets/images/level1.jpg';">
      </div>
      <h3 class="sidebar-student-name"><?= htmlspecialchars($student['full_name'] ?? 'محمد منصور أحمد العكاد') ?></h3>
      <p class="sidebar-student-level"><?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?> _ <?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?></p>
    </div>

    <!-- أقسام الخدمات -->
    <div class="sidebar-sections-stack">
      
      <!-- الخدمات الأكاديمية -->
      <div class="sidebar-nav-group">
        <div class="group-header-strip">
          <span class="group-title-text">الخدمات الاكاديمية</span>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1b75bb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="21" x2="21" y2="21"/>
            <line x1="6" y1="21" x2="6" y2="10"/>
            <line x1="18" y1="21" x2="18" y2="10"/>
            <path d="M4 10h16"/>
            <polygon points="12 2 20 7 4 7"/>
          </svg>
        </div>
        <div class="group-items-list">
          <a href="attendance.php" class="sidebar-link-row">
            <span class="link-label">سجل الحضور و الغياب</span>
            <span class="blue-bullet-dot"></span>
          </a>
          <a href="grades_levels.php" class="sidebar-link-row">
            <span class="link-label">بيان الدرجات</span>
            <span class="blue-bullet-dot"></span>
          </a>
          <a href="academic_services.php" class="sidebar-link-row">
            <span class="link-label">فهرس الخدمات الأكاديمية</span>
            <span class="blue-bullet-dot"></span>
          </a>
        </div>
      </div>

      <!-- الخدمات المالية -->
      <div class="sidebar-nav-group">
        <div class="group-header-strip">
          <span class="group-title-text">الخدمات المالية</span>
          <span style="font-weight:900; color:#1b75bb; font-size:1.1rem;">$</span>
        </div>
        <div class="group-items-list">
          <a href="financial_status.php" class="sidebar-link-row">
            <span class="link-label">كشوفات مالية</span>
            <span class="blue-bullet-dot"></span>
          </a>
          <a href="levels.php" class="sidebar-link-row">
            <span class="link-label">حالة الرسوم وفتح المستويات</span>
            <span class="blue-bullet-dot"></span>
          </a>
        </div>
      </div>

      <!-- التنقل السريع -->
      <div class="sidebar-nav-group">
        <div class="group-header-strip">
          <span class="group-title-text">الحساب الشخصي</span>
        </div>
        <div class="group-items-list">
          <a href="profile.php" class="sidebar-link-row">
            <span class="link-label">الملف الشخصي للطالب</span>
            <span class="blue-bullet-dot"></span>
          </a>
        </div>
      </div>

    </div>

    <!-- أزرار الإجراءات السفلية -->
    <div class="sidebar-bottom-actions">
      <a href="change_password.php" class="btn-drawer-blue">تغيير كلمة المرور</a>
      <a href="logout.php" class="btn-drawer-red">تسجيل الخروج</a>
    </div>

  </aside>
</div>
