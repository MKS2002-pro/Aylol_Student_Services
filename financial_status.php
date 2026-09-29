<?php
/**
 * كلية أيلول الجامعية - بيان الحالة المالية للطالب
 * مطابقة تامة للصورة 5 و 6 في ملف PDF مع زري التنقل التفاعلي في الأعلى
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();
$db = getDBConnection();

$pageTitle = 'بيان الحالة المالية للطالب';
include_once __DIR__ . '/includes/header.php';

// جلب سندات الرسوم الدراسية
$tuitionStmt = $db->prepare("
    SELECT * FROM receipts 
    WHERE student_id = ? AND category = 'tuition' 
    ORDER BY payment_date ASC, id ASC
");
$tuitionStmt->execute([$student['id']]);
$tuitionReceipts = $tuitionStmt->fetchAll();

// جلب سندات الرسوم الأخرى
$otherStmt = $db->prepare("
    SELECT * FROM receipts 
    WHERE student_id = ? AND category = 'other' 
    ORDER BY payment_date ASC, id ASC
");
$otherStmt->execute([$student['id']]);
$otherReceipts = $otherStmt->fetchAll();

// حساب الإجماليات
$totalTuitionYer = 0;
$totalTuitionUsd = 0;
foreach ($tuitionReceipts as $r) {
    $totalTuitionYer += (float)$r['amount_yer'];
    $totalTuitionUsd += (float)$r['amount_usd'];
}

$totalOtherYer = 0;
foreach ($otherReceipts as $r) {
    $totalOtherYer += (float)$r['amount_yer'];
}

// المبلغ المتبقي (إذا كان الطالب مسدد الرسوم فالمتبقي 0، وإلا متبقي الرسوم)
$remainingUsd = $student['tuition_cleared'] ? 0.00 : 400.00;
?>

<main class="receipts-page-container">
  
  <!-- عنوان الصفحة في الأعلى (مطابق للصورة 5 و 6) -->
  <div class="receipts-top-title">
    <h2 class="college-subheading"><?= htmlspecialchars($student['college_name'] ?? 'كلية أيلول الجامعية') ?> _ <?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?> _ <?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?></h2>
  </div>

  <!-- زرا التصفية والتنقل بين الكشفين في الأعلى (مطابق للشرط وللصورة 5 و 6) -->
  <div class="fee-pills-row">
    <button type="button" id="tab-tuition" class="pill-btn solid">رسوم دراسية</button>
    <button type="button" id="tab-other" class="pill-btn outline">رسوم أخرى</button>
  </div>

  <!-- الكشف الأول: سندات الرسوم الدراسية (مطابق للصورة 5) -->
  <div id="view-tuition" class="statement-blue-frame">
    
    <div class="statement-box-header">
      كشف سندات الرسوم الدراسية
    </div>

    <div class="statement-student-strip">
      <div class="student-strip-bubble">
        <?= htmlspecialchars($student['full_name']) ?> _ <?= htmlspecialchars($student['program_name']) ?> _ <?= htmlspecialchars($student['level_name']) ?>
      </div>
    </div>

    <div class="table-responsive-box">
      <table class="statement-data-table">
        <thead>
          <tr>
            <th style="width: 14%;">رقم السند</th>
            <th style="width: 20%;">التاريخ</th>
            <th style="width: 22%;">المبلغ باليمني</th>
            <th style="width: 20%;">المبلغ بالدولار</th>
            <th style="width: 24%;">التفاصيل</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tuitionReceipts)): ?>
            <tr>
              <td colspan="5" style="padding: 20px; color: var(--text-muted);">لا توجد سندات رسوم دراسية مسجلة حالياً.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($tuitionReceipts as $rec): ?>
              <tr>
                <td><?= htmlspecialchars($rec['receipt_number']) ?></td>
                <td dir="ltr"><?= date('Y/m/d', strtotime($rec['payment_date'])) ?></td>
                <td><?= number_format($rec['amount_yer'], 0) ?></td>
                <td dir="ltr"><?= number_format($rec['amount_usd'], 0) ?> $</td>
                <td><?= htmlspecialchars($rec['details']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="footer-paid-cell">المبلغ المدفوع (بالدولار)</td>
            <td class="footer-paid-cell" dir="ltr"><?= number_format($totalTuitionUsd, 0) ?> $</td>
            <td class="footer-rem-cell">المبلغ المتبقي (بالدولار)</td>
            <td class="footer-rem-cell" dir="ltr"><?= number_format($remainingUsd, 1) ?> $</td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>

  <!-- الكشف الثاني: سندات الرسوم الأخرى (مطابق للصورة 6) -->
  <div id="view-other" class="statement-blue-frame" style="display: none;">
    
    <div class="statement-box-header">
      كشف سندات الرسوم الاخرى
    </div>

    <div class="statement-student-strip">
      <div class="student-strip-bubble">
        <?= htmlspecialchars($student['full_name']) ?> _ <?= htmlspecialchars($student['program_name']) ?> _ <?= htmlspecialchars($student['level_name']) ?>
      </div>
    </div>

    <div class="table-responsive-box">
      <table class="statement-data-table">
        <thead>
          <tr>
            <th style="width: 15%;">رقم السند</th>
            <th style="width: 25%;">التاريخ</th>
            <th style="width: 25%;">المبلغ باليمني</th>
            <th style="width: 35%;">التفاصيل</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($otherReceipts)): ?>
            <tr>
              <td colspan="4" style="padding: 20px; color: var(--text-muted);">لا توجد سندات رسوم أخرى مسجلة حالياً.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($otherReceipts as $rec): ?>
              <tr>
                <td><?= htmlspecialchars($rec['receipt_number']) ?></td>
                <td dir="ltr"><?= date('Y/m/d', strtotime($rec['payment_date'])) ?></td>
                <td><?= number_format($rec['amount_yer'], 0) ?></td>
                <td><?= htmlspecialchars($rec['details']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="footer-paid-cell" style="font-weight: 900; color: #0f172a;">الإجمالي</td>
            <td colspan="2" class="footer-paid-cell" style="font-weight: 900; color: #0f172a;" dir="ltr"><?= number_format($totalOtherYer, 0) ?> ريال يمني</td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>

  <div style="margin-top: 20px; text-align: left;">
    <a href="api/export_json.php?type=finance&student_id=<?= $student['id'] ?>" class="pill-btn outline" style="font-size:0.88rem; padding: 6px 14px;" target="_blank">
      تصدير الكشف بصيغة JSON ⤓
    </a>
  </div>

</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
