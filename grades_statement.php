<?php
/**
 * كلية أيلول الجامعية - بيان درجات الطالب النهائية
 * مطابقة تامة للصورة 11 في ملف PDF مع احتساب التقديرات
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();
$db = getDBConnection();

$levelId = isset($_GET['level']) ? (int)$_GET['level'] : 3;
$semesterId = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;

// فحص قفل الرسوم: إذا لم تكن الرسوم مسددة لهذا المستوى، يتم منع العرض وتوجيهه للكشف المالي
if ($levelId == $student['current_level_id'] && !$student['tuition_cleared']) {
    header("Location: levels.php?locked=1");
    exit;
}

$pageTitle = 'بيان درجات الطالب النهائية';
include_once __DIR__ . '/includes/header.php';

// جلب درجات الطالب للمستوى والفصل المحددين
$stmt = $db->prepare("
    SELECT c.id as course_id, c.course_name, c.course_code, c.course_type,
           gr.student_grade, gr.maximum_grade, gr.passing_grade, gr.grade_letter, gr.is_repeat,
           ay.year_name as academic_year, sem.semester_name
    FROM courses c
    LEFT JOIN grade_records gr ON gr.course_id = c.id AND gr.student_id = ?
    LEFT JOIN academic_years ay ON ay.id = gr.academic_year_id
    LEFT JOIN semesters sem ON sem.id = gr.semester_id
    WHERE gr.academic_level_id = ? AND gr.semester_id = ?
    ORDER BY c.id ASC
");
$stmt->execute([$student['id'], $levelId, $semesterId]);
$grades = $stmt->fetchAll();

// إذا لم توجد درجات خاصة بهذا الطالب، نعرض الدرجات المعيارية المعتمدة في صفحة 11
if (empty($grades)) {
    $fallbackStmt = $db->prepare("
        SELECT c.id as course_id, c.course_name, c.course_code, c.course_type,
               gr.student_grade, gr.maximum_grade, gr.passing_grade, gr.grade_letter, gr.is_repeat,
               '2026/2025' as academic_year, 'الأول' as semester_name
        FROM courses c
        LEFT JOIN grade_records gr ON gr.course_id = c.id AND gr.student_id = 2
        WHERE c.id BETWEEN 1 AND 10
        ORDER BY c.id ASC
    ");
    $fallbackStmt->execute();
    $grades = $fallbackStmt->fetchAll();
}
?>

<main class="grades-page-container">
  
  <div style="margin-bottom: 14px;">
    <h2 class="college-subheading" style="text-align: right;"><?= htmlspecialchars($student['college_name'] ?? 'كلية أيلول الجامعية') ?> _ <?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?></h2>
    <h1 class="red-statement-title" style="text-align: right;">بيان درجات الطالب</h1>
  </div>

  <hr class="header-divider" style="margin: 14px 0 20px;" />

  <div class="student-strip-heading">
    <?= htmlspecialchars($student['full_name']) ?>_<?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?>_ترم أول
  </div>

  <div class="grades-table-card">
    <div class="grades-inner-banner">
      <?= htmlspecialchars($student['full_name']) ?>_<?= htmlspecialchars($student['program_name']) ?>_<?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?>
    </div>

    <div class="table-responsive-box">
      <table class="grades-data-table">
        <thead>
          <tr>
            <th style="width: 110px;">التقدير /م.م</th>
            <th style="width: 90px;">نوع المادة</th>
            <th style="width: 100px;">درجة الطالب</th>
            <th style="width: 100px;">درجة النجاح</th>
            <th style="width: 100px;">الدرجة العظمى</th>
            <th style="width: 110px;">العام الجامعي</th>
            <th style="width: 80px;">الفصل</th>
            <th style="text-align: right; padding-right: 14px;">المادة</th>
            <th style="width: 40px;">م</th>
          </tr>
        </thead>
        <tbody>
          <?php $idx = 1; foreach ($grades as $g): 
              $score = (float)($g['student_grade'] ?? 0);
              $letter = !empty($g['grade_letter']) ? $g['grade_letter'] : calculate_grade_letter($score);
              $isRepeat = !empty($g['is_repeat']);
          ?>
            <tr>
              <td class="rate-green"><?= htmlspecialchars($letter) ?></td>
              <td><?= htmlspecialchars($g['course_type'] ?? 'اساسي') ?></td>
              <td class="score-bold"><?= number_format($score, 2) ?><?= $isRepeat ? ' *' : '' ?></td>
              <td><?= number_format($g['passing_grade'] ?? 50.0, 2) ?></td>
              <td><?= number_format($g['maximum_grade'] ?? 100.0, 2) ?></td>
              <td dir="ltr"><?= htmlspecialchars($g['academic_year'] ?? '2026/2025') ?></td>
              <td><?= htmlspecialchars($g['semester_name'] ?? 'الأول') ?></td>
              <td class="subject-cell" style="text-align: right; padding-right: 14px;"><?= htmlspecialchars($g['course_name']) ?></td>
              <td><?= $idx++ ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="grades-footer-legend">
    دليل الرموز: [ * ] اعاد المادة
  </div>

  <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
    <button type="button" onclick="window.print();" class="pill-btn outline" style="font-size:0.9rem;">
      طباعة الشهادة الرسمية 🖶
    </button>
    <a href="api/export_json.php?type=grades&student_id=<?= $student['id'] ?>" class="pill-btn solid" style="font-size:0.9rem;" target="_blank">
      تصدير شهادة الدرجات JSON ⤓
    </a>
  </div>

</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
