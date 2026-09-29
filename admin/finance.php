<?php
/**
 * كلية أيلول الجامعية - إدارة كشف الحساب والسندات المالية للكنترول
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'كشف الحساب والسندات المالية';
$activeNav = 'finance';

$studentFilter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$activeTab = $_GET['tab'] ?? 'tuition';

// معالجة حذف سند
if (isset($_GET['delete_receipt'])) {
    $delId = (int)$_GET['delete_receipt'];
    $delStmt = $db->prepare("DELETE FROM receipts WHERE id = ?");
    $delStmt->execute([$delId]);
    header("Location: finance.php?tab=" . urlencode($activeTab) . ($studentFilter ? "&student_id=$studentFilter" : ""));
    exit;
}

// استعلام سندات الرسوم الدراسية
$tuitionQuery = "
    SELECT r.*, s.full_name as student_name, s.university_number, p.program_name
    FROM receipts r
    JOIN students s ON s.id = r.student_id
    LEFT JOIN programs p ON p.id = s.program_id
    WHERE r.category = 'tuition'
";
if ($studentFilter > 0) {
    $tuitionQuery .= " AND r.student_id = " . $studentFilter;
}
$tuitionQuery .= " ORDER BY r.payment_date DESC, r.receipt_number DESC";
$tuitionReceipts = $db->query($tuitionQuery)->fetchAll();

// استعلام سندات الرسوم الأخرى
$otherQuery = "
    SELECT r.*, s.full_name as student_name, s.university_number, p.program_name
    FROM receipts r
    JOIN students s ON s.id = r.student_id
    LEFT JOIN programs p ON p.id = s.program_id
    WHERE r.category = 'other'
";
if ($studentFilter > 0) {
    $otherQuery .= " AND r.student_id = " . $studentFilter;
}
$otherQuery .= " ORDER BY r.payment_date DESC, r.receipt_number DESC";
$otherReceipts = $db->query($otherQuery)->fetchAll();

// الطلاب للقائمة المنسدلة
$allStudents = $db->query("SELECT id, full_name, university_number FROM students ORDER BY full_name ASC")->fetchAll();

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

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">إدارة كشوفات الحساب وسندات الرسوم</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">سندات الرسوم الدراسية مع التحويل التلقائي للدولار (صرف 250) وسندات الرسوم الأخرى</p>
  </div>
  <a href="receipt_add.php<?= $studentFilter ? '?student_id='.$studentFilter : '' ?>" class="btn-success-admin">
    + إصدار سند دفع جديد
  </a>
</div>

<!-- تصفية حسب الطالب -->
<div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 20px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
  <span style="font-weight: 800; color: #334155;">تصفية الكشف لطالب محدد:</span>
  <form method="GET" action="finance.php" style="display: flex; gap: 10px; align-items: center;">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
    <select name="student_id" class="form-control" style="min-width: 280px;" onchange="this.form.submit()">
      <option value="0">-- جميع الطلاب (كشف عام) --</option>
      <?php foreach ($allStudents as $st): ?>
        <option value="<?= $st['id'] ?>" <?= $studentFilter == $st['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['university_number']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($studentFilter > 0): ?>
      <a href="finance.php?tab=<?= htmlspecialchars($activeTab) ?>" class="btn-term-outline" style="padding: 6px 14px; font-size: 0.85rem;">إلغاء التصفية</a>
    <?php endif; ?>
  </form>
</div>

<!-- تبويبات الكشف المالي المطابقة للمتطلبات -->
<div class="fee-pills-row" style="justify-content: flex-start; margin-bottom: 16px;">
  <button type="button" id="tab-tuition" class="pill-btn <?= $activeTab === 'tuition' ? 'solid' : 'outline' ?>">
    كشف سندات الرسوم الدراسية (<?= count($tuitionReceipts) ?>)
  </button>
  <button type="button" id="tab-other" class="pill-btn <?= $activeTab === 'other' ? 'solid' : 'outline' ?>">
    كشف سندات الرسوم الأخرى (<?= count($otherReceipts) ?>)
  </button>
</div>

<!-- 1. جدول سندات الرسوم الدراسية -->
<div id="view-tuition" class="statement-blue-frame" style="<?= $activeTab === 'other' ? 'display:none;' : '' ?>">
  <div class="statement-box-header" style="background: #f8fafc; border-bottom: 1.5px solid var(--primary-blue);">
    كشف سندات الرسوم الدراسية (تحويل الدولار بقسمة اليمني على 250)
  </div>

  <div class="table-responsive-box">
    <table class="statement-data-table">
      <thead>
        <tr>
          <th style="width: 10%;">رقم السند</th>
          <th style="width: 14%;">التاريخ</th>
          <th style="text-align: right; padding-right: 14px;">اسم الطالب</th>
          <th style="width: 15%;">المبلغ باليمني</th>
          <th style="width: 15%;">المبلغ بالدولار ($)</th>
          <th style="width: 20%;">التفاصيل</th>
          <th style="width: 80px;">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tuitionReceipts)): ?>
          <tr><td colspan="7" style="padding: 24px; color: var(--text-muted);">لا توجد سندات رسوم دراسية مسجلة.</td></tr>
        <?php else: ?>
          <?php foreach ($tuitionReceipts as $tr): ?>
            <tr>
              <td style="font-weight: 900; color: var(--primary-blue);"><?= htmlspecialchars($tr['receipt_number']) ?></td>
              <td dir="ltr"><?= date('Y/m/d', strtotime($tr['payment_date'])) ?></td>
              <td style="text-align: right; padding-right: 14px; font-weight: 800;">
                <a href="finance.php?student_id=<?= $tr['student_id'] ?>&tab=tuition" style="color: inherit; text-decoration: underline;">
                  <?= htmlspecialchars($tr['student_name']) ?>
                </a>
              </td>
              <td style="font-weight: 800;"><?= number_format($tr['amount_yer'], 0) ?></td>
              <td dir="ltr" style="font-weight: 900; color: #0284c7;"><?= number_format($tr['amount_usd'], 0) ?> $</td>
              <td><?= htmlspecialchars($tr['details']) ?></td>
              <td>
                <a href="finance.php?delete_receipt=<?= $tr['id'] ?>&tab=tuition<?= $studentFilter ? '&student_id='.$studentFilter : '' ?>" 
                   onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السند المالي؟');"
                   style="color: var(--danger-red); font-weight: 800; font-size: 0.85rem;">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="footer-paid-cell" style="font-weight: 900;">إجمالي مبالغ الرسوم الدراسية</td>
          <td class="footer-paid-cell" style="font-weight: 900;"><?= number_format($totalTuitionYer, 0) ?> ريال</td>
          <td class="footer-paid-cell" dir="ltr" style="font-weight: 900; font-size: 1.25rem;"><?= number_format($totalTuitionUsd, 0) ?> $</td>
          <td colspan="2" class="footer-rem-cell">سعر الصرف المعتمد: 250</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- 2. جدول سندات الرسوم الأخرى -->
<div id="view-other" class="statement-blue-frame" style="<?= $activeTab === 'other' ? 'display:block;' : 'display:none;' ?>">
  <div class="statement-box-header" style="background: #f8fafc; border-bottom: 1.5px solid var(--primary-blue);">
    كشف سندات الرسوم الأخرى (رسوم بطاقة، إعادة اختبار، مخالفات، تأكيد وتوقيف قيد)
  </div>

  <div class="table-responsive-box">
    <table class="statement-data-table">
      <thead>
        <tr>
          <th style="width: 12%;">رقم السند</th>
          <th style="width: 16%;">التاريخ</th>
          <th style="text-align: right; padding-right: 14px;">اسم الطالب</th>
          <th style="width: 18%;">المبلغ باليمني</th>
          <th style="width: 32%;">التفاصيل</th>
          <th style="width: 80px;">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($otherReceipts)): ?>
          <tr><td colspan="6" style="padding: 24px; color: var(--text-muted);">لا توجد سندات رسوم أخرى مسجلة.</td></tr>
        <?php else: ?>
          <?php foreach ($otherReceipts as $or): ?>
            <tr>
              <td style="font-weight: 900; color: var(--primary-blue);"><?= htmlspecialchars($or['receipt_number']) ?></td>
              <td dir="ltr"><?= date('Y/m/d', strtotime($or['payment_date'])) ?></td>
              <td style="text-align: right; padding-right: 14px; font-weight: 800;">
                <a href="finance.php?student_id=<?= $or['student_id'] ?>&tab=other" style="color: inherit; text-decoration: underline;">
                  <?= htmlspecialchars($or['student_name']) ?>
                </a>
              </td>
              <td style="font-weight: 900;"><?= number_format($or['amount_yer'], 0) ?></td>
              <td><?= htmlspecialchars($or['details']) ?></td>
              <td>
                <a href="finance.php?delete_receipt=<?= $or['id'] ?>&tab=other<?= $studentFilter ? '&student_id='.$studentFilter : '' ?>" 
                   onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السند؟');"
                   style="color: var(--danger-red); font-weight: 800; font-size: 0.85rem;">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="footer-paid-cell" style="font-weight: 900; color: #0f172a;">إجمالي الرسوم الأخرى</td>
          <td colspan="3" class="footer-paid-cell" style="font-weight: 900; color: #0f172a; font-size: 1.2rem;" dir="ltr">
            <?= number_format($totalOtherYer, 0) ?> ريال يمني
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
