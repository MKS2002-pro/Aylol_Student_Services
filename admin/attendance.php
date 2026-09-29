<?php
/**
 * كلية أيلول الجامعية - رصد وكشف الحضور والغياب للكنترول
 * تقسيم: التخصص -> المقرر -> كشف بكافة الطلاب مع 12 محاضرة وتاريخ تلقائي ورموز ✓ و ✗
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'كشف ورصد الحضور والغياب للمقررات';
$activeNav = 'attendance';

// جلب التخصصات
$programs = $db->query("SELECT * FROM programs ORDER BY id ASC")->fetchAll();
$selectedProgram = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 1;

// جلب المقررات التابعة للتخصص المختار
$coursesStmt = $db->prepare("
    SELECT c.id as course_id, c.course_name, c.course_code, co.id as offering_id, co.academic_level_id, co.semester_id
    FROM courses c
    JOIN course_offerings co ON co.course_id = c.id
    WHERE co.program_id = ?
    ORDER BY c.id ASC
");
$coursesStmt->execute([$selectedProgram]);
$courses = $coursesStmt->fetchAll();

$selectedOfferingId = isset($_GET['offering_id']) ? (int)$_GET['offering_id'] : ($courses[0]['offering_id'] ?? 1);

// جلب تفاصيل المقرر الحالي
$curCourseStmt = $db->prepare("
    SELECT c.*, co.id as offering_id, co.academic_level_id, co.semester_id, p.program_name
    FROM course_offerings co
    JOIN courses c ON c.id = co.course_id
    JOIN programs p ON p.id = co.program_id
    WHERE co.id = ?
");
$curCourseStmt->execute([$selectedOfferingId]);
$currentCourse = $curCourseStmt->fetch();

// معالجة حفظ الحضور
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attData = $_POST['att'] ?? []; // att[student_id][session_id] = 'present' or 'absent'
    $sessionDatesPost = $_POST['session_date'] ?? [];

    // تحديث تواريخ المحاضرات
    foreach ($sessionDatesPost as $sId => $dVal) {
        $db->prepare("UPDATE lecture_sessions SET session_date = ? WHERE id = ?")->execute([$dVal, (int)$sId]);
    }

    // تحديث أو إدخال سجلات الحضور
    foreach ($attData as $sId => $sessions) {
        foreach ($sessions as $sessionId => $statusVal) {
            $st = ($statusVal == '1' || $statusVal === 'present') ? 'present' : (($statusVal == '0' || $statusVal === 'absent') ? 'absent' : 'upcoming');
            
            $chk = $db->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
            $chk->execute([$sessionId, $sId]);
            if ($chk->fetch()) {
                $db->prepare("UPDATE attendance_records SET status = ? WHERE session_id = ? AND student_id = ?")
                   ->execute([$st, $sessionId, $sId]);
            } else {
                $db->prepare("INSERT INTO attendance_records (session_id, student_id, status) VALUES (?, ?, ?)")
                   ->execute([$sessionId, $sId, $st]);
            }
        }
    }
    $message = 'تم حفظ كشف الحضور والغياب لجميع الطلاب بنجاح!';
}

// معالجة إضافة عمود محاضرة جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lecture_session'])) {
    $maxLec = $db->prepare("SELECT MAX(lecture_number) FROM lecture_sessions WHERE course_offering_id = ?");
    $maxLec->execute([$selectedOfferingId]);
    $nextNum = ((int)$maxLec->fetchColumn()) + 1;
    $today = date('Y-m-d');
    
    $insLec = $db->prepare("INSERT INTO lecture_sessions (course_offering_id, lecture_number, session_date) VALUES (?, ?, ?)");
    $insLec->execute([$selectedOfferingId, $nextNum, $today]);
    header("Location: attendance.php?program_id=$selectedProgram&offering_id=$selectedOfferingId&added=1");
    exit;
}

// جلب جلسات المحاضرات للمقرر
$sessionsStmt = $db->prepare("
    SELECT * FROM lecture_sessions 
    WHERE course_offering_id = ? 
    ORDER BY lecture_number ASC
");
$sessionsStmt->execute([$selectedOfferingId]);
$sessions = $sessionsStmt->fetchAll();

// إذا لم تكن هناك جلسات مسجلة للمقرر، ننشئ الـ 12 محاضرة تلقائياً!
if (empty($sessions)) {
    for ($i = 1; $i <= 12; $i++) {
        $dateVal = date('Y-m-d', strtotime("2026-09-10 + " . (($i - 1) * 4) . " days"));
        $db->prepare("INSERT INTO lecture_sessions (course_offering_id, lecture_number, session_date) VALUES (?, ?, ?)")
           ->execute([$selectedOfferingId, $i, $dateVal]);
    }
    $sessionsStmt->execute([$selectedOfferingId]);
    $sessions = $sessionsStmt->fetchAll();
}

// جلب الطلاب المسجلين بالتخصص
$studentsStmt = $db->prepare("
    SELECT s.*, al.level_name 
    FROM students s
    JOIN academic_levels al ON al.id = s.current_level_id
    WHERE s.program_id = ?
    ORDER BY s.university_number ASC
");
$studentsStmt->execute([$selectedProgram]);
$studentsList = $studentsStmt->fetchAll();

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">رصد كشف الحضور والغياب (المحاضرات)</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
      تقسيم: التخصص ← المقرر ← كشف بكافة الطلاب (12 محاضرة مع التواريخ ورموز 1 / 0 و ✓ / ✗)
    </p>
  </div>
  
  <form method="POST" action="attendance.php?program_id=<?= $selectedProgram ?>&offering_id=<?= $selectedOfferingId ?>">
    <button type="submit" name="add_lecture_session" class="btn-success-admin" style="font-size: 0.9rem;">
      + إضافة عمود محاضرة جديد
    </button>
  </form>
</div>

<?php if (!empty($message)): ?>
  <div class="alert-box alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- شريط اختيار التخصص والمقرر -->
<div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 24px; box-shadow: var(--shadow-sm);">
  <form method="GET" action="attendance.php" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
    
    <div style="flex: 1; min-width: 220px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">1. اختر التخصص الأكاديمي:</label>
      <select name="program_id" class="form-control" onchange="this.form.submit()" style="font-weight: 800;">
        <?php foreach ($programs as $pr): ?>
          <option value="<?= $pr['id'] ?>" <?= $selectedProgram == $pr['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($pr['program_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="flex: 1; min-width: 280px;">
      <label style="display: block; font-weight: 800; font-size: 0.9rem; margin-bottom: 6px;">2. اختر المقرر الدراسي:</label>
      <select name="offering_id" class="form-control" onchange="this.form.submit()" style="font-weight: 800; color: var(--primary-blue);">
        <?php foreach ($courses as $cs): ?>
          <option value="<?= $cs['offering_id'] ?>" <?= $selectedOfferingId == $cs['offering_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cs['course_name']) ?> (<?= htmlspecialchars($cs['course_code']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn-primary-admin" style="padding: 0 24px; height: 44px;">تطبيق الكشف</button>
  </form>
</div>

<!-- نموذج جدول رصد الحضور لجميع الطلاب -->
<form method="POST" action="attendance.php?program_id=<?= $selectedProgram ?>&offering_id=<?= $selectedOfferingId ?>">
  
  <div class="statement-blue-frame" style="margin-bottom: 24px;">
    
    <div class="statement-box-header" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px;">
      <span style="font-size: 1.15rem;">
        كشف حضور المقرر: <?= htmlspecialchars($currentCourse['course_name'] ?? '') ?>
      </span>
      <span style="font-size: 0.9rem; font-weight: 700; color: #0284c7;">
        عدد المحاضرات المرصودة: <?= count($sessions) ?> محاضرة
      </span>
    </div>

    <div class="table-responsive-box">
      <table class="statement-data-table" style="font-size: 0.88rem;">
        <thead>
          <tr class="header-row-green">
            <th style="width: 50px;">م</th>
            <th style="text-align: right; min-width: 170px; padding-right: 12px;">اسم الطالب</th>
            <th style="width: 90px;">الرقم الجامعي</th>
            <th style="width: 75px;">الغيابات</th>
            <th style="width: 80px;">الحالة</th>
            <?php foreach ($sessions as $ses): ?>
              <th style="min-width: 48px;">م <?= $ses['lecture_number'] ?></th>
            <?php endforeach; ?>
          </tr>
          <tr class="header-row-blue">
            <th colspan="3">تاريخ المحاضرة (معرف تلقائياً مع إمكانية التعديل):</th>
            <th>الإنذار</th>
            <th>3غ|5ح</th>
            <?php foreach ($sessions as $ses): ?>
              <th style="padding: 4px 2px;">
                <input 
                  type="date" 
                  name="session_date[<?= $ses['id'] ?>]" 
                  value="<?= htmlspecialchars($ses['session_date']) ?>"
                  style="font-size: 0.68rem; width: 68px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px;"
                  title="تعديل تاريخ المحاضرة"
                >
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sIndex = 1;
          foreach ($studentsList as $stu): 
              // جلب سجلات حضور هذا الطالب في جلسات هذا المقرر
              $recStmt = $db->prepare("
                  SELECT session_id, status 
                  FROM attendance_records 
                  WHERE student_id = ? AND session_id IN (" . implode(',', array_column($sessions, 'id')) . ")
              ");
              $recStmt->execute([$stu['id']]);
              $stRecs = [];
              $absentTotal = 0;
              while ($rRow = $recStmt->fetch()) {
                  $stRecs[$rRow['session_id']] = $rRow['status'];
                  if ($rRow['status'] === 'absent') $absentTotal++;
              }
              $stBadge = get_attendance_status($absentTotal);
          ?>
            <tr>
              <td><?= $sIndex++ ?></td>
              <td style="text-align: right; padding-right: 12px; font-weight: 800; color: #0f172a;">
                <?= htmlspecialchars($stu['full_name']) ?>
              </td>
              <td style="font-weight: 700;"><?= htmlspecialchars($stu['university_number']) ?></td>
              <td style="font-weight: 900; color: <?= $absentTotal >= 3 ? '#b91c1c' : '#0f172a' ?>;">
                <?= $absentTotal ?> غياب
              </td>
              <td>
                <span class="<?= $stBadge['class'] ?>" style="font-size: 0.75rem; padding: 2px 6px;">
                  <?= $stBadge['status'] ?>
                </span>
              </td>

              <!-- خلايا التحضير لكل محاضرة: 1/✓ أو 0/✗ أو - -->
              <?php foreach ($sessions as $ses): 
                  $curStatus = $stRecs[$ses['id']] ?? 'present';
              ?>
                <td style="padding: 4px;">
                  <select 
                    name="att[<?= $stu['id'] ?>][<?= $ses['id'] ?>]" 
                    style="font-size: 0.85rem; font-weight: 800; border-radius: 4px; border: 1px solid #cbd5e1; padding: 2px;"
                    class="att-select"
                  >
                    <option value="present" <?= $curStatus === 'present' ? 'selected' : '' ?> style="color:#16a34a;">✓ (1)</option>
                    <option value="absent" <?= $curStatus === 'absent' ? 'selected' : '' ?> style="color:#dc2626;">✗ (0)</option>
                    <option value="upcoming" <?= $curStatus === 'upcoming' ? 'selected' : '' ?> style="color:#64748b;">- (قادمة)</option>
                  </select>
                </td>
              <?php endforeach; ?>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; gap: 10px;">
      <button type="submit" name="save_attendance" class="btn-primary-admin" style="font-size: 1.05rem; padding: 12px 32px;">
        💾 حفظ واعتماد كشف الحضور والغياب
      </button>
      <button type="button" onclick="setAllPresent();" class="btn-term-outline" style="font-size: 0.95rem;">
        ✓ تحضير الجميع تلقائياً
      </button>
    </div>

    <a href="../api/export_json.php?type=attendance&program_id=<?= $selectedProgram ?>" class="pill-btn outline" style="font-size: 0.9rem;" target="_blank">
      تصدير الكشف بصيغة JSON ⤓
    </a>
  </div>

</form>

<script>
function setAllPresent() {
  document.querySelectorAll('.att-select').forEach(sel => {
    sel.value = 'present';
  });
}
</script>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
