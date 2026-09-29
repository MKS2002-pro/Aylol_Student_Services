<?php
/**
 * كلية أيلول الجامعية - إصدار سند مالي جديد للكنترول
 * يحقق كافة شروط الترقيم التلقائي، تاريخ اليوم، وتحويل الصرف لـ 250
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'إصدار سند مالي جديد';
$activeNav = 'finance';

$error = '';
$success = '';

// حساب رقم السند التلقائي
$maxNum = $db->query("SELECT MAX(receipt_number) FROM receipts")->fetchColumn();
$nextReceiptNumber = $maxNum ? ((int)$maxNum + 1) : 101;

$preselectedStudent = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$allStudents = $db->query("SELECT id, full_name, university_number FROM students ORDER BY full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiptNum = (int)($_POST['receipt_number'] ?? $nextReceiptNumber);
    $studentId = (int)($_POST['student_id'] ?? 0);
    $category = clean($_POST['category'] ?? 'tuition');
    $paymentDate = clean($_POST['payment_date'] ?? date('Y-m-d'));
    $amountYer = (float)($_POST['amount_yer'] ?? 0);
    $details = clean($_POST['details'] ?? ($category === 'tuition' ? 'رسوم دراسية' : 'رسوم بطاقة'));

    // التحويل التلقائي للدولار بقسمة المبلغ على 250
    $amountUsd = yer_to_usd($amountYer);

    if ($studentId <= 0 || $amountYer <= 0 || empty($receiptNum)) {
        $error = 'يرجى اختيار الطالب وإدخال مبلغ صحيح ورقم السند.';
    } else {
        $ins = $db->prepare("
            INSERT INTO receipts (
                receipt_number, student_id, category, payment_date, 
                amount_yer, amount_usd, details, created_by_admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $receiptNum, $studentId, $category, $paymentDate,
            $amountYer, $amountUsd, $details, $_SESSION['admin_id']
        ]);

        $success = "تم إصدار السند رقم ($receiptNum) بنجاح بمبلغ " . number_format($amountYer) . " ريال ($amountUsd $)!";
        $nextReceiptNumber = $receiptNum + 1;
    }
}

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="max-width: 760px; margin: 0 auto;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">إصدار سند قبض مالي</h3>
      <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">ترقيم تلقائي، تاريخ تلقائي لنفس اليوم، وتحويل فوري للدولار (حساب الصرف 250)</p>
    </div>
    <a href="finance.php" class="btn-term-outline" style="font-size: 0.9rem; padding: 8px 16px;">العودة لكشف الحساب</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="admin-form-card">
    <form method="POST" action="receipt_add.php">
      
      <!-- اختيار الطالب -->
      <div class="form-group">
        <label>اختيار الطالب المستفيد *</label>
        <select name="student_id" class="form-control" required style="font-weight: 800;">
          <option value="">-- حدد الطالب من القائمة --</option>
          <?php foreach ($allStudents as $st): ?>
            <option value="<?= $st['id'] ?>" <?= ($preselectedStudent == $st['id'] || (isset($_POST['student_id']) && $_POST['student_id'] == $st['id'])) ? 'selected' : '' ?>>
              <?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['university_number']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-grid-2col">
        
        <!-- رقم السند: ترقيم تلقائي مع إمكانية التعديل -->
        <div class="form-group">
          <label>رقم السند (ترقيم تلقائي لجميع السندات) *</label>
          <input 
            type="number" 
            name="receipt_number" 
            class="form-control" 
            required 
            value="<?= htmlspecialchars($_POST['receipt_number'] ?? $nextReceiptNumber) ?>"
            style="font-weight: 900; color: var(--primary-blue);"
          >
        </div>

        <!-- التاريخ: إدخال تلقائي حسب نفس اليوم مع إمكانية التعديل -->
        <div class="form-group">
          <label>تاريخ السند (إدخال تلقائي لتاريخ اليوم مع إمكانية التعديل) *</label>
          <input 
            type="date" 
            name="payment_date" 
            class="form-control" 
            required 
            value="<?= htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')) ?>"
          >
        </div>

      </div>

      <!-- نوع السند: رسوم دراسية أو رسوم أخرى -->
      <div class="form-group">
        <label>نوع السند المالي *</label>
        <div style="display: flex; gap: 20px; padding: 10px 0;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 800; cursor: pointer;">
            <input type="radio" name="category" value="tuition" checked onchange="handleCategoryChange(this.value)">
            <span>سند رسوم دراسية (يحول للدولار تلقائياً بقسمة 250)</span>
          </label>
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 800; cursor: pointer;">
            <input type="radio" name="category" value="other" onchange="handleCategoryChange(this.value)">
            <span>سند رسوم أخرى (بطاقة، توقيف قيد، إعادة اختبار، مخالفات...)</span>
          </label>
        </div>
      </div>

      <div class="form-grid-2col">
        
        <!-- المبلغ بالريال اليمني -->
        <div class="form-group">
          <label>المبلغ بالعملة اليمنية (ريال يمني) *</label>
          <input 
            type="number" 
            step="1" 
            name="amount_yer" 
            id="amount_yer" 
            class="form-control" 
            required 
            placeholder="مثال: 10000"
            style="font-size: 1.15rem; font-weight: 800;"
            value="<?= htmlspecialchars($_POST['amount_yer'] ?? '') ?>"
          >
        </div>

        <!-- المبلغ بالدولار: يحسب تلقائياً بقسمة اليمني على 250 -->
        <div class="form-group" id="usd-group">
          <label>المبلغ بالدولار ($) [محول تلقائي = يمني ÷ 250]</label>
          <input 
            type="text" 
            name="amount_usd" 
            id="amount_usd" 
            class="form-control" 
            readonly 
            style="background: #f1f5f9; font-size: 1.15rem; font-weight: 900; color: #0284c7;" 
            placeholder="0.00 $"
          >
        </div>

      </div>

      <!-- التفاصيل: الافتراضي رسوم دراسية، أو خيارات رسوم أخرى -->
      <div class="form-group">
        <label>التفاصيل / البيان *</label>
        <div style="display: flex; gap: 10px; margin-bottom: 8px;">
          <select id="quick-details" class="form-control" onchange="document.getElementById('details-input').value = this.value;">
            <option value="رسوم دراسية">رسوم دراسية (الافتراضي)</option>
            <option value="رسوم بطاقة">رسوم بطاقة جامعية</option>
            <option value="رسوم إعادة أختبار">رسوم إعادة أختبار</option>
            <option value="رسوم مخالفات">رسوم مخالفات</option>
            <option value="رسوم تأكيد قيد">رسوم تأكيد قيد</option>
            <option value="رسوم توقيف قيد">رسوم توقيف قيد</option>
            <option value="رسوم تسجيل و تنسيق">رسوم تسجيل و تنسيق</option>
            <option value="رسوم عقوبة">رسوم عقوبة</option>
          </select>
        </div>
        <input 
          type="text" 
          name="details" 
          id="details-input" 
          class="form-control" 
          required 
          value="<?= htmlspecialchars($_POST['details'] ?? 'رسوم دراسية') ?>"
          placeholder="اكتب التفاصيل هنا أو اختر من القائمة"
        >
      </div>

      <div style="margin-top: 28px; display: flex; gap: 14px;">
        <button type="submit" class="btn-success-admin" style="font-size: 1.1rem; padding: 12px 36px;">
          حفظ وإصدار السند المالي
        </button>
        <a href="finance.php" class="btn-term-outline" style="padding: 12px 24px;">إلغاء</a>
      </div>

    </form>
  </div>

</div>

<script>
function handleCategoryChange(cat) {
  const detailsInput = document.getElementById('details-input');
  const quickSelect = document.getElementById('quick-details');
  if (cat === 'tuition') {
    detailsInput.value = 'رسوم دراسية';
    quickSelect.value = 'رسوم دراسية';
  } else {
    detailsInput.value = 'رسوم بطاقة';
    quickSelect.value = 'رسوم بطاقة';
  }
}
</script>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
