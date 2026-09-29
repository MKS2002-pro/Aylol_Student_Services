<?php
/**
 * كلية أيلول الجامعية - رصد وإدارة درجات المقررات للكنترول
 * مع التطبيق الصارم لقفل الـ 15 يوماً لمنع التعديل أو الحذف أو المغالطة
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'رصد وتعديل درجات الطلاب للمقررات';
$activeNav = 'grades';

$error = '';
$success = '';

// جلب التخصصات والمقررات والمستويات
$programs = $db->query("SELECT * FROM programs ORDER BY id ASC")->fetchAll();
$selectedProgram = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 1;

$coursesStmt = $db->prepare("
    SELECT c.id as course_id, c.course_name, c.course_code, co.id as offering_id, co.academic_level_id, co.semester_id
    FROM courses c
    JOIN course_offerings co ON co.course_id = c.id
    WHERE co.program_id = ?
    ORDER BY c.id ASC
");
$coursesStmt->execute([$selectedProgram]);
$courses = $coursesStmt->fetchAll();

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : ($courses[0]['course_id'] ?? 1);
$selectedLevel = isset($_GET['level_id']) ? (int)$_GET['level_id'] : 3;
$selectedSemester = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 1;

// معالجة حفظ أو تحديث الدرجات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $gradesData = $_POST['grades'] ?? [];
    $updatedCount = 0;
    $lockedViolations = 0;

    foreach ($gradesData as $stuId => $g) {
        $stuId = (int)$stuId;
        $att = (float)($g['attendance'] ?? 0);
        $part = (float)($g['participation'] ?? 0);
        $midT = (float)($g['midterm_theory'] ?? 0);
        $midP = (float)($g['midterm_practical'] ?? 0);
        $finP = (float)($g['final_practical'] ?? 0);
        $finT = (float)($g['final_theory'] ?? 0);
        $total = $att + $part + $midT + $midP + $finP + $finT;
        if ($total > 100) $total = 100;
        $letter = calculate_grade_letter($total);
        $isRepeat = isset($g['is_repeat']) ? 1 : 0;
        $notes = clean($g['notes'] ?? '');

        // فحص هل السجل موجود بالفعل ومقفل (مرور 15 يوماً)
        $chkStmt = $db->prepare("
            SELECT id, created_at 
            FROM grade_records 
            WHERE student_id = ? AND course_id = ? AND academic_level_id = ? AND semester_id = ?
        ");
        $chkStmt->execute([$stuId, $selectedCourseId, $selectedLevel, $selectedSemester]);
        $existing = $chkStmt->fetch();

        if ($existing) {
            // التحقق من شرط الـ 15 يوماً
            if (is_grade_locked($existing['created_at'])) {
                $lockedViolations++;
                continue; // حظر التعديل وفقاً للشرط الإجباري
            }

            // التحديث مسموح قبل انتهاء الـ 15 يوماً
            $upd = $db->prepare("
                UPDATE grade_records SET
                    attendance_grade = ?, participation_grade = ?, midterm_theory = ?,
                    midterm_practical = ?, final_practical = ?, final_theory = ?,
                    student_grade = ?, grade_letter = ?, is_repeat = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$att, $part, $midT, $midP, $finP, $finT, $total, $letter, $isRepeat, $notes, $existing['id']]);
            $updatedCount++;
        } else {
            // إدخال سجل رصد جديد لأول مرة
            $ins = $db->prepare("
                INSERT INTO grade_records (
                    student_id, course_id, academic_level_id, semester_id, academic_year_id,
                    attendance_grade, participation_grade, midterm_theory, midterm_practical,
                    final_practical, final_theory, student_grade, maximum_grade, passing_grade,
                    grade_letter, is_repeat, notes, created_at
                ) VALUES (?, ?, ?, ?, 3, ?, ?, ?, ?, ?, ?, ?, 100, 50, ?, ?, ?, NOW())
            ");
            $ins->execute([$stuId, $selectedCourseId, $selectedLevel, $selectedSemester, $att, $part, $midT, $midP, $finP, $finT, $total, $letter, $isRepeat, $notes]);
            $updatedCount++;
        }
    }

    if ($lockedViolations > 0) {
        $error = "تنبيه أمني: تم منع تعديل ($lockedViolations) درجات لمرور أكثر من 15 يوماً على رصدها وفقاً للائحة منع المغالطة.";
    }
    if ($updatedCount > 0) {
        $success = "تم حفظ وتحديث درجات ($updatedCount) طلاب بنجاح!";
    }
}

// جلب الطلاب وسجلات الدرجات للمقرر المختار
$studentsStmt = $db->prepare("
    SELECT s.id as student_id, s.university_number, s.full_name,
           gr.id as grade_id, gr.attendance_grade, gr.participation_grade, 
           gr.midterm_theory, gr.midterm_practical, gr.final_practical, gr.final_theory,
           gr.student_grade, gr.grade_letter, gr.is_repeat, gr.notes, gr.created_at
    FROM students s
    LEFT JOIN grade_records gr ON gr.student_id = s.id 
         AND gr.course_id = ? AND gr.academic_level_id = ? AND gr.semester_id = ?
    WHERE s.program_id = ?
    ORDER BY s.university_number ASC
");
$studentsStmt->execute([$selectedCourseId, $selectedLevel, $selectedSemester, $selectedProgram]);
$studentsGrades = $studentsStmt->fetchAll();

// جلب اسم المقرر المختار
$courseNameStmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$courseNameStmt->execute([$selectedCourseId]);
$currentCourseName = $courseNameStmt->fetchColumn() ?: 'المقرر';

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">رصد درجات المقررات للطلاب</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
      يتم قفل الدرجات ومنع الحذف أو التعديل نهائياً بعد مرور 15 يوماً على تاريخ الرصد منعاً لأي مغالطة
    </p>
  </div>
  
  <div style="background: #fee2e2; border: 1.5px solid #fca5a5; padding: 8px 16px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 8px;">
    <span style="font-size: 1.2rem;">🔒</span>
    <span style="font-size: 0.88rem; font-weight: 800; color: #991b1b;">
      القفل الأمني: 15 يوماً كحد أقصى للتعديل
    </span>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- شريط الفلاتر -->
<div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 24px; box-shadow: var(--shadow-sm);">
  <form method="GET" action="grades.php" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
    
    <div style="flex: 1; min-width: 200px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">1. التخصص:</label>
      <select name="program_id" class="form-control" onchange="this.form.submit()" style="font-weight: 800;">
        <?php foreach ($programs as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $selectedProgram == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['program_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="flex: 1; min-width: 240px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">2. المقرر الدراسي:</label>
      <select name="course_id" class="form-control" onchange="this.form.submit()" style="font-weight: 800; color: var(--primary-blue);">
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['course_id'] ?>" <?= $selectedCourseId == $c['course_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['course_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width: 140px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">3. المستوى:</label>
      <select name="level_id" class="form-control" onchange="this.form.submit()">
        <option value="1" <?= $selectedLevel == 1 ? 'selected' : '' ?>>المستوى 1</option>
        <option value="2" <?= $selectedLevel == 2 ? 'selected' : '' ?>>المستوى 2</option>
        <option value="3" <?= $selectedLevel == 3 ? 'selected' : '' ?>>المستوى 3</option>
        <option value="4" <?= $selectedLevel == 4 ? 'selected' : '' ?>>المستوى 4</option>
      </select>
    </div>

    <div style="width: 130px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">4. الفصل:</label>
      <select name="semester_id" class="form-control" onchange="this.form.submit()">
        <option value="1" <?= $selectedSemester == 1 ? 'selected' : '' ?>>ترم أول</option>
        <option value="2" <?= $selectedSemester == 2 ? 'selected' : '' ?>>ترم ثاني</option>
      </select>
    </div>

    <button type="submit" class="btn-primary-admin" style="padding: 0 20px; height: 44px;">تطبيق</button>
  </form>
</div>

<!-- نموذج رصد الدرجات لجميع الطلاب -->
<form method="POST" action="grades.php?program_id=<?= $selectedProgram ?>&course_id=<?= $selectedCourseId ?>&level_id=<?= $selectedLevel ?>&semester_id=<?= $selectedSemester ?>">
  
  <div class="statement-blue-frame" style="margin-bottom: 24px;">
    
    <div class="statement-box-header" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px;">
      <span>رصد درجات: <?= htmlspecialchars($currentCourseName) ?></span>
      <span style="font-size: 0.9rem; font-weight: 700; color: #0284c7;">
        المجموع الكلي: 100 درجة (درجة النجاح: 50)
      </span>
    </div>

    <div class="table-responsive-box">
      <table class="statement-data-table" style="font-size: 0.88rem;">
        <thead>
          <tr>
            <th style="width: 45px;">م</th>
            <th>الرقم الجامعي</th>
            <th style="text-align: right; min-width: 160px; padding-right: 12px;">اسم الطالب</th>
            <th style="width: 65px;">حضور<br><small>(5)</small></th>
            <th style="width: 65px;">مشاركة<br><small>(5)</small></th>
            <th style="width: 75px;">نصفي نظري<br><small>(10)</small></th>
            <th style="width: 75px;">نصفي عملي<br><small>(10)</small></th>
            <th style="width: 75px;">نهائي عملي<br><small>(10)</small></th>
            <th style="width: 75px;">نهائي نظري<br><small>(60)</small></th>
            <th style="width: 70px;">المجموع<br><small>(100)</small></th>
            <th style="width: 80px;">التقدير</th>
            <th style="width: 160px;">حالة التعديل والقفل</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $rowIdx = 1;
          foreach ($studentsGrades as $sg): 
              $isLocked = !empty($sg['created_at']) && is_grade_locked($sg['created_at']);
              $stuId = $sg['student_id'];
              
              // حساب الأيام المتبقية إن لم يقفل
              $daysRemaining = 15;
              if (!empty($sg['created_at'])) {
                  $cDate = new DateTime($sg['created_at']);
                  $nDate = new DateTime();
                  $diff = $cDate->diff($nDate);
                  $daysRemaining = max(0, 15 - $diff->days);
              }
          ?>
            <tr style="<?= $isLocked ? 'background-color: #f8fafc;' : '' ?>">
              <td><?= $rowIdx++ ?></td>
              <td style="font-weight: 800;"><?= htmlspecialchars($sg['university_number']) ?></td>
              <td style="text-align: right; padding-right: 12px; font-weight: 800; color: #0f172a;">
                <?= htmlspecialchars($sg['full_name']) ?>
              </td>

              <!-- الحضور (5) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="5" 
                  name="grades[<?= $stuId ?>][attendance]" 
                  value="<?= htmlspecialchars($sg['attendance_grade'] ?? '5') ?>" 
                  class="form-control" 
                  style="width: 58px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- المشاركة (5) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="5" 
                  name="grades[<?= $stuId ?>][participation]" 
                  value="<?= htmlspecialchars($sg['participation_grade'] ?? '5') ?>" 
                  class="form-control" 
                  style="width: 58px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- نصفي نظري (10) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="10" 
                  name="grades[<?= $stuId ?>][midterm_theory]" 
                  value="<?= htmlspecialchars($sg['midterm_theory'] ?? '8') ?>" 
                  class="form-control" 
                  style="width: 62px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- نصفي عملي (10) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="10" 
                  name="grades[<?= $stuId ?>][midterm_practical]" 
                  value="<?= htmlspecialchars($sg['midterm_practical'] ?? '9') ?>" 
                  class="form-control" 
                  style="width: 62px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- نهائي عملي (10) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="10" 
                  name="grades[<?= $stuId ?>][final_practical]" 
                  value="<?= htmlspecialchars($sg['final_practical'] ?? '9') ?>" 
                  class="form-control" 
                  style="width: 62px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- نهائي نظري (60) -->
              <td>
                <input 
                  type="number" step="0.5" min="0" max="60" 
                  name="grades[<?= $stuId ?>][final_theory]" 
                  value="<?= htmlspecialchars($sg['final_theory'] ?? '52') ?>" 
                  class="form-control" 
                  style="width: 65px; height: 34px; padding: 2px; text-align: center; font-weight: 800;"
                  <?= $isLocked ? 'readonly' : '' ?>
                >
              </td>

              <!-- المجموع النهائي (محسوب) -->
              <td style="font-weight: 900; font-size: 1.05rem; color: #0f172a;">
                <?= $sg['student_grade'] !== null ? number_format($sg['student_grade'], 1) : '-' ?>
              </td>

              <!-- التقدير -->
              <td style="font-weight: 800; color: #16a34a;">
                <?= htmlspecialchars($sg['grade_letter'] ?? '-') ?>
              </td>

              <!-- عمود حالة القفل الأمني للـ 15 يوماً -->
              <td>
                <?php if ($isLocked): ?>
                  <span class="lock-shield-badge" title="تم قفل الدرجة نهائياً لمرور أكثر من 15 يوماً">
                    🔒 مقفل نهائياً (15+ يوم)
                  </span>
                <?php elseif (!empty($sg['created_at'])): ?>
                  <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-weight: 800; font-size: 0.78rem;">
                    ✏️ قابل للتعديل (متبقي <?= $daysRemaining ?> يوم)
                  </span>
                <?php else: ?>
                  <span style="background: #f1f5f9; color: #64748b; padding: 3px 8px; border-radius: 12px; font-weight: 800; font-size: 0.78rem;">
                    لم ترصد بعد
                  </span>
                <?php endif; ?>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <button type="submit" name="save_grades" class="btn-primary-admin" style="font-size: 1.05rem; padding: 12px 36px;">
      💾 حفظ واعتماد درجات الطلاب
    </button>
    <a href="../api/export_json.php?type=grades&course_id=<?= $selectedCourseId ?>" class="pill-btn outline" style="font-size: 0.9rem;" target="_blank">
      تصدير الدرجات JSON ⤓
    </a>
  </div>

</form>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
