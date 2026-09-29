<?php
/**
 * كلية أيلول الجامعية - الملف الشخصي للطالب
 * مطابقة تامة للصورة 2 في ملف PDF
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();

$pageTitle = 'الملف الشخصي للطالب';
include_once __DIR__ . '/includes/header.php';

$studentPhoto = !empty($student['photo']) && file_exists(__DIR__ . '/' . $student['photo'])
    ? htmlspecialchars($student['photo'])
    : 'assets/images/level1.jpg';
?>

<main class="profile-layout-container">
  
  <!-- الجهة اليمنى في RTL: بطاقة الطالب وأزرار الخدمات (مطابقة للصورة 2) -->
  <aside class="student-card-side">
    <div class="student-profile-box">
      
      <!-- إطار صورة الطالب -->
      <div class="student-photo-frame">
        <img src="<?= $studentPhoto ?>" alt="<?= htmlspecialchars($student['full_name']) ?>" onerror="this.src='assets/images/2.jpg';">
      </div>

      <!-- اسم الطالب -->
      <h1 class="student-name-header"><?= htmlspecialchars($student['full_name']) ?></h1>

      <!-- جدول ملخص البيانات الأكاديمية -->
      <div class="student-summary-strip">
        <div class="summary-line">
          <span class="summary-val"><?= htmlspecialchars($student['university_number']) ?></span>
          <span class="summary-lbl">الرقم الاكاديمي</span>
        </div>
        <div class="summary-line">
          <span class="summary-val"><?= htmlspecialchars($student['college_name'] ?? 'كلية علوم الحاسوب') ?></span>
          <span class="summary-lbl">الكلية</span>
        </div>
        <div class="summary-line">
          <span class="summary-val"><?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?></span>
          <span class="summary-lbl">التخصص</span>
        </div>
      </div>

      <!-- أزرار الإجراءات الرئيسية -->
      <div class="profile-actions-wrap">
        <a href="financial_status.php" class="btn-cyan-action">
          خدمات مالية
        </a>
        <a href="academic_services.php" class="btn-blue-action">
          خدمات اكاديمية
        </a>
      </div>

    </div>
  </aside>

  <!-- الجهة اليسرى في RTL: بطاقات البيانات الشخصية والجامعية -->
  <div class="data-sections-side">
    
    <!-- البطاقة 1: البيانات الشخصية -->
    <section class="info-card">
      <div class="info-card-header">
        <h2>البيانات الشخصية</h2>
      </div>
      <div class="info-card-body">
        <div class="fields-2col-grid">
          
          <!-- العمود الفرعي الأيمن -->
          <div class="fields-col">
            <div class="field-item">
              <span class="field-value" dir="ltr"><?= htmlspecialchars($student['phone'] ?? '770612187 - 770017481') ?></span>
              <span class="field-title">رقم الهاتف</span>
            </div>
            <div class="field-item">
              <span class="field-value" dir="ltr"><?= htmlspecialchars($student['email'] ?? 'moh123.gmail.com') ?></span>
              <span class="field-title">البريد الالكتروني</span>
            </div>
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['identity_type_name'] ?? 'بطاقة شخصية') ?></span>
              <span class="field-title">نوع الهوية</span>
            </div>
            <div class="field-item no-border">
              <span class="field-value"><?= htmlspecialchars($student['address'] ?? 'يريم_المركزي') ?></span>
              <span class="field-title">العنوان</span>
            </div>
          </div>

          <!-- العمود الفرعي الأيسر -->
          <div class="fields-col">
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['birth_date'] ?? '2004-04-07') ?></span>
              <span class="field-title">تاريخ الميلاد</span>
            </div>
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['birth_place'] ?? 'يريم_بيت العكاد') ?></span>
              <span class="field-title">مكان الميلاد</span>
            </div>
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['village'] ?? 'بيت العكاد') ?></span>
              <span class="field-title">القرية</span>
            </div>
            <div class="field-item no-border">
              <span class="field-value" dir="ltr"><?= htmlspecialchars($student['identity_number'] ?? '145854698578546') ?></span>
              <span class="field-title">رقم الهوية</span>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- البطاقة 2: البيانات الجامعية -->
    <section class="info-card">
      <div class="info-card-header">
        <h2>البيانات الجامعية</h2>
      </div>
      <div class="info-card-body">
        <div class="fields-2col-grid">
          
          <!-- العمود الفرعي الأيمن -->
          <div class="fields-col">
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['college_name'] ?? 'كلية علوم الحاسوب') ?></span>
              <span class="field-title">الكلية</span>
            </div>
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?></span>
              <span class="field-title">التخصص</span>
            </div>
            <div class="field-item no-border">
              <span class="field-value"><?= htmlspecialchars($student['level_name'] ?? 'الثالث') ?></span>
              <span class="field-title">المستوى</span>
            </div>
          </div>

          <!-- العمود الفرعي الأيسر -->
          <div class="fields-col">
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['admission_year_name'] ?? '2024/2025') ?></span>
              <span class="field-title">عام الانظمام</span>
            </div>
            <div class="field-item">
              <span class="field-value"><?= htmlspecialchars($student['current_academic_year_name'] ?? '2026/2027') ?></span>
              <span class="field-title">العام الاكاديمي</span>
            </div>
            <div class="field-item no-border">
              <span class="field-value"><?= htmlspecialchars($student['status_name'] ?? 'طالب') ?></span>
              <span class="field-title">الحالة الاكاديمية</span>
            </div>
          </div>

        </div>
      </div>
    </section>

  </div>

</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
