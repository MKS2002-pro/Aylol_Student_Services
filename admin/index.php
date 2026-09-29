<?php
/**
 * كلية أيلول الجامعية - الرئيسية / لوحة معلومات الكنترول
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'لوحة معلومات الكنترول العام';
$activeNav = 'dashboard';

// إحصائيات عامة
$studentsCount = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$coursesCount = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$receiptsCount = $db->query("SELECT COUNT(*) FROM receipts")->fetchColumn();

// إجمالي المبالغ المحصلة
$finStmt = $db->query("SELECT SUM(amount_yer) as yer_sum, SUM(amount_usd) as usd_sum FROM receipts");
$finTotals = $finStmt->fetch();
$totalYer = (float)($finTotals['yer_sum'] ?? 0);
$totalUsd = (float)($finTotals['usd_sum'] ?? 0);

// أحدث الطلاب المسجلين
$recentStudentsStmt = $db->query("
    SELECT s.*, p.program_name, al.level_name, st.status_name 
    FROM students s
    LEFT JOIN programs p ON p.id = s.program_id
    LEFT JOIN academic_levels al ON al.id = s.current_level_id
    LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
    ORDER BY s.id DESC LIMIT 5
");
$recentStudents = $recentStudentsStmt->fetchAll();

// أحدث السندات
$recentReceiptsStmt = $db->query("
    SELECT r.*, s.full_name as student_name, s.university_number 
    FROM receipts r
    JOIN students s ON s.id = r.student_id
    ORDER BY r.payment_date DESC, r.id DESC LIMIT 5
");
$recentReceipts = $recentReceiptsStmt->fetchAll();

include_once __DIR__ . '/includes/admin_header.php';
?>

<!-- بطاقات المؤشرات والإحصائيات -->
<div class="stats-cards-grid">
  <div class="stat-card">
    <div>
      <div class="stat-val"><?= number_format($studentsCount) ?></div>
      <div class="stat-lbl">إجمالي الطلاب المقيدين</div>
    </div>
    <div style="font-size: 2.2rem;">👨‍🎓</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val"><?= number_format($coursesCount) ?></div>
      <div class="stat-lbl">المقررات الدراسية</div>
    </div>
    <div style="font-size: 2.2rem;">📚</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val"><?= number_format($receiptsCount) ?></div>
      <div class="stat-lbl">سندات الرسوم الصادرة</div>
    </div>
    <div style="font-size: 2.2rem;">🧾</div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-val" style="color: #10b981;"><?= number_format($totalUsd) ?> $</div>
      <div class="stat-lbl"><?= number_format($totalYer) ?> ريال (صرف 250)</div>
    </div>
    <div style="font-size: 2.2rem;">💵</div>
  </div>
</div>

<!-- شريط الإجراءات السريعة للكنترول -->
<div style="background: #ffffff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 28px; box-shadow: var(--shadow-sm);">
  <h3 style="font-size: 1.1rem; font-weight: 900; margin-bottom: 14px; color: #0f172a;">إجراءات الكنترول السريعة:</h3>
  <div style="display: flex; gap: 12px; flex-wrap: wrap;">
    <a href="student_add.php" class="btn-primary-admin">+ إضافة طالب جديد</a>
    <a href="receipt_add.php" class="btn-success-admin">+ إصدار سند مالي</a>
    <a href="attendance.php" class="btn-term-outline" style="font-size: 0.95rem; padding: 8px 16px;">رصد الحضور والغياب</a>
    <a href="grades.php" class="btn-term-outline" style="font-size: 0.95rem; padding: 8px 16px;">رصد درجات المقررات</a>
    <a href="programs_levels.php" class="btn-term-outline" style="font-size: 0.95rem; padding: 8px 16px;">المستويات والمقررات</a>
    <a href="json_manager.php" class="btn-term-outline" style="font-size: 0.95rem; padding: 8px 16px;">تصدير واستيراد JSON</a>
  </div>
</div>

<!-- شبكة الجداول الحديثة -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
  
  <!-- أحدث الطلاب -->
  <div style="background: #ffffff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a;">أحدث الطلاب المقيدين</h3>
      <a href="students.php" style="color: var(--primary-blue); font-weight: 700; font-size: 0.9rem;">عرض الكل ←</a>
    </div>

    <table class="statement-data-table" style="font-size: 0.9rem;">
      <thead>
        <tr>
          <th>الرقم</th>
          <th>اسم الطالب</th>
          <th>المستوى</th>
          <th>الحالة</th>
          <th>إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentStudents as $st): ?>
          <tr>
            <td style="font-weight: 800;"><?= htmlspecialchars($st['university_number']) ?></td>
            <td style="text-align: right; font-weight: 800;"><?= htmlspecialchars($st['full_name']) ?></td>
            <td><?= htmlspecialchars($st['level_name'] ?? 'مستوى ثالث') ?></td>
            <td>
              <span class="badge-normal" style="font-size: 0.75rem;"><?= htmlspecialchars($st['status_name'] ?? 'طالب') ?></span>
            </td>
            <td>
              <a href="student_edit.php?id=<?= $st['id'] ?>" style="color: var(--primary-blue); font-weight: 800;">تعديل</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- أحدث السندات المالية -->
  <div style="background: #ffffff; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a;">أحدث السندات المالية الصادرة</h3>
      <a href="finance.php" style="color: var(--primary-blue); font-weight: 700; font-size: 0.9rem;">عرض الكشف ←</a>
    </div>

    <table class="statement-data-table" style="font-size: 0.9rem;">
      <thead>
        <tr>
          <th>رقم السند</th>
          <th>الطالب</th>
          <th>المبلغ (يمني)</th>
          <th>المبلغ ($)</th>
          <th>النوع</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentReceipts as $rc): ?>
          <tr>
            <td style="font-weight: 800;"><?= htmlspecialchars($rc['receipt_number']) ?></td>
            <td style="text-align: right; font-weight: 800;"><?= htmlspecialchars($rc['student_name']) ?></td>
            <td><?= number_format($rc['amount_yer'], 0) ?></td>
            <td dir="ltr" style="font-weight: 800; color: #0284c7;"><?= number_format($rc['amount_usd'], 0) ?> $</td>
            <td><?= $rc['category'] === 'tuition' ? 'رسوم دراسية' : 'رسوم أخرى' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- صندوق معلومات القوانين والسياسات الأكاديمية -->
<div style="margin-top: 24px; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: var(--radius-md); padding: 18px 24px;">
  <h4 style="color: #1e40af; font-weight: 900; margin-bottom: 8px;">قواعد وسياسات الكنترول الأكاديمي والمالي المفعلة:</h4>
  <ul style="padding-right: 20px; color: #1e3a8a; font-weight: 700; font-size: 0.92rem; line-height: 1.8;">
    <li><strong>سعر صرف الرسوم:</strong> تحويل تلقائي من الريال اليمني للدولار بقسمة المبلغ على 250 (1$ = 250 ريال يمني).</li>
    <li><strong>قفل تعديل الدرجات (15 يوماً):</strong> يتم حظر وتجميد أي إمكانية لتعديل أو حذف درجات الطلاب بعد مرور 15 يوماً على رصدها منعاً لأي مغالطة أو تغيير.</li>
    <li><strong>قفل الرسوم على الدرجات:</strong> يتم ربط كشف الدرجات للطلاب ببيان تسديد الرسوم، وتغلق درجات المستوى وتظهر شارة القفل في حال عدم تصفية الرسوم.</li>
    <li><strong>سياسة الإنذار والحرمان في الحضور:</strong> 3 غيابات = إنذار أكاديمي أول • 5 غيابات فأكثر = حرمان فصلي تلقائي من دخول اختبار المقرر.</li>
  </ul>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
