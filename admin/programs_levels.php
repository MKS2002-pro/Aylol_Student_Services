<?php
/**
 * كلية أيلول الجامعية - إدارة التخصصات والمستويات والمقررات للكنترول
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'إدارة التخصصات والمستويات والمقررات';
$activeNav = 'curriculum';

$success = '';
$error = '';

// 1. إضافة تخصص جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $progName = clean($_POST['program_name'] ?? '');
    $collegeName = clean($_POST['college_name'] ?? 'كلية علوم الحاسوب');
    $years = (int)($_POST['duration_years'] ?? 4);

    if (empty($progName)) {
        $error = 'يرجى إدخال اسم التخصص.';
    } else {
        $stmt = $db->prepare("INSERT INTO programs (college_name, program_name, duration_years) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$collegeName, $progName, $years]);
            $success = "تمت إضافة التخصص ($progName) بنجاح!";
        } catch (Exception $e) {
            $error = 'التخصص مسجل مسبقاً بنفس الكلية.';
        }
    }
}

// 2. إضافة مقرر دراسي جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $cCode = clean($_POST['course_code'] ?? '');
    $cName = clean($_POST['course_name'] ?? '');
    $hours = (int)($_POST['credit_hours'] ?? 3);
    $cType = clean($_POST['course_type'] ?? 'أساسي');
    $pId = (int)($_POST['program_id'] ?? 1);
    $lId = (int)($_POST['level_id'] ?? 1);
    $sId = (int)($_POST['semester_id'] ?? 1);

    if (empty($cCode) || empty($cName)) {
        $error = 'يرجى إدخال كود واسم المقرر.';
    } else {
        try {
            // إدخال المقرر
            $insCourse = $db->prepare("INSERT INTO courses (course_code, course_name, credit_hours, course_type) VALUES (?, ?, ?, ?)");
            $insCourse->execute([$cCode, $cName, $hours, $cType]);
            $newCourseId = $db->lastInsertId();

            // طرح المقرر للتخصص والمستوى والفصل
            $insOffer = $db->prepare("
                INSERT INTO course_offerings (course_id, program_id, academic_level_id, semester_id, academic_year_id)
                VALUES (?, ?, ?, ?, 3)
            ");
            $insOffer->execute([$newCourseId, $pId, $lId, $sId]);

            // إنشاء 12 محاضرة تلقائياً للمقرر الجديد
            $newOfferingId = $db->lastInsertId();
            for ($i = 1; $i <= 12; $i++) {
                $dt = date('Y-m-d', strtotime("2026-09-10 + " . (($i - 1) * 4) . " days"));
                $db->prepare("INSERT INTO lecture_sessions (course_offering_id, lecture_number, session_date) VALUES (?, ?, ?)")
                   ->execute([$newOfferingId, $i, $dt]);
            }

            $success = "تمت إضافة المقرر ($cName) وطرحه للمستوى والفصل وإنشاء 12 محاضرة بنجاح!";
        } catch (Exception $e) {
            $error = 'حدث خطأ: كود المقرر مكرر أو البيانات غير صحيحة.';
        }
    }
}

// جلب التخصصات والمقررات والمستويات
$programs = $db->query("SELECT * FROM programs ORDER BY id ASC")->fetchAll();
$levels = $db->query("SELECT * FROM academic_levels ORDER BY level_number ASC")->fetchAll();
$coursesList = $db->query("
    SELECT c.*, co.id as offering_id, p.program_name, al.level_name, sem.semester_name
    FROM courses c
    LEFT JOIN course_offerings co ON co.course_id = c.id
    LEFT JOIN programs p ON p.id = co.program_id
    LEFT JOIN academic_levels al ON al.id = co.academic_level_id
    LEFT JOIN semesters sem ON sem.id = co.semester_id
    ORDER BY p.id ASC, al.level_number ASC, c.id ASC
")->fetchAll();

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">إدارة التخصصات والمستويات والمقررات</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">تعريف البرامج الجامعية، خطط المستويات (3 أو 4 أو 5 سنوات)، وإضافة المقررات</p>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px;">
  
  <!-- نموذج إضافة تخصص جديد -->
  <div class="admin-form-card">
    <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
      + إضافة تخصص / قسم أكاديمي
    </h4>
    <form method="POST" action="programs_levels.php">
      <input type="hidden" name="add_program" value="1">

      <div class="form-group">
        <label>اسم الكلية</label>
        <input type="text" name="college_name" class="form-control" value="كلية علوم الحاسوب" required>
      </div>

      <div class="form-group">
        <label>اسم التخصص الأكاديمي *</label>
        <input type="text" name="program_name" class="form-control" placeholder="مثال: هندسة البرمجيات" required>
      </div>

      <div class="form-group">
        <label>مدة الدراسة (عدد السنوات / المستويات) *</label>
        <select name="duration_years" class="form-control" required>
          <option value="4">4 سنوات (4 مستويات - الافتراضي)</option>
          <option value="3">3 سنوات (3 مستويات)</option>
          <option value="5">5 سنوات (5 مستويات - للكليات الطبية والهندسية)</option>
        </select>
      </div>

      <button type="submit" class="btn-primary-admin" style="width: 100%; margin-top: 10px;">
        إضافة التخصص
      </button>
    </form>
  </div>

  <!-- نموذج إضافة مقرر دراسي جديد -->
  <div class="admin-form-card">
    <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
      + إضافة مقرر دراسي جديد
    </h4>
    <form method="POST" action="programs_levels.php">
      <input type="hidden" name="add_course" value="1">

      <div class="form-grid-2col">
        <div class="form-group">
          <label>رمز المقرر (الكود) *</label>
          <input type="text" name="course_code" class="form-control" placeholder="مثال: CS-201" required>
        </div>
        <div class="form-group">
          <label>اسم المقرر الدراسي *</label>
          <input type="text" name="course_name" class="form-control" placeholder="اسم المادة" required>
        </div>
      </div>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>التخصص التابع له *</label>
          <select name="program_id" class="form-control" required>
            <?php foreach ($programs as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['program_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>المستوى الدراسي *</label>
          <select name="level_id" class="form-control" required>
            <?php foreach ($levels as $lv): ?>
              <option value="<?= $lv['id'] ?>"><?= htmlspecialchars($lv['level_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>الفصل الدراسي (الترم) *</label>
          <select name="semester_id" class="form-control" required>
            <option value="1">ترم أول</option>
            <option value="2">ترم ثاني</option>
          </select>
        </div>
        <div class="form-group">
          <label>نوع المقرر والساعات</label>
          <select name="course_type" class="form-control">
            <option value="أساسي">أساسي</option>
            <option value="متطلب كلية">متطلب كلية</option>
            <option value="اختياري">اختياري</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn-success-admin" style="width: 100%; margin-top: 10px;">
        إضافة المقرر وربطه وإنشاء محاضراته
      </button>
    </form>
  </div>

</div>

<!-- جدول المقررات المطروحة -->
<div style="background: #ffffff; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1.5px solid #f1f5f9; font-weight: 900; font-size: 1.1rem; color: #0f172a;">
    دليل المقررات المطروحة في الكلية (<?= count($coursesList) ?> مقرر)
  </div>

  <div class="table-responsive-box">
    <table class="statement-data-table">
      <thead>
        <tr>
          <th style="width: 80px;">الرمز</th>
          <th style="text-align: right; padding-right: 14px;">اسم المقرر</th>
          <th>التخصص</th>
          <th>المستوى</th>
          <th>الفصل</th>
          <th>الساعات</th>
          <th>نوع المادة</th>
          <th>الدرجة العظمى</th>
          <th>النجاح</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($coursesList as $cr): ?>
          <tr>
            <td style="font-weight: 800; color: var(--primary-blue);"><?= htmlspecialchars($cr['course_code']) ?></td>
            <td style="text-align: right; padding-right: 14px; font-weight: 800; color: #0f172a;">
              <?= htmlspecialchars($cr['course_name']) ?>
            </td>
            <td><?= htmlspecialchars($cr['program_name'] ?? 'تقنية المعلومات') ?></td>
            <td style="font-weight: 700;"><?= htmlspecialchars($cr['level_name'] ?? 'المستوى الثالث') ?></td>
            <td><?= htmlspecialchars($cr['semester_name'] ?? 'ترم أول') ?></td>
            <td><?= $cr['credit_hours'] ?></td>
            <td><?= htmlspecialchars($cr['course_type']) ?></td>
            <td>100.00</td>
            <td>50.00</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
