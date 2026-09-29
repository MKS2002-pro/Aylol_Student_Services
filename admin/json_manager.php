<?php
/**
 * كلية أيلول الجامعية - إدارة ملفات وبيانات JSON للكنترول
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'إدارة وتصدير ملفات JSON';
$activeNav = 'json';

$message = '';
$error = '';

// تحديث ملف system_settings.json
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settingsData = [
        'college' => [
            'name_ar' => clean($_POST['name_ar'] ?? 'كلية أيلول الجامعية'),
            'name_en' => clean($_POST['name_en'] ?? 'Aylol University College'),
            'code' => 'AUC',
            'phone' => clean($_POST['phone'] ?? '770612187 - 770017481'),
            'email' => clean($_POST['email'] ?? 'info@aylol.edu.ye')
        ],
        'financial' => [
            'exchange_rate_usd_yer' => (float)($_POST['exchange_rate'] ?? 250.0),
            'currency_local' => 'ريال يمني',
            'currency_foreign' => 'دولار أمريكي',
            'tuition_lock_enabled' => true
        ],
        'attendance_policy' => [
            'warning_threshold' => (int)($_POST['warning_threshold'] ?? 3),
            'ban_threshold' => (int)($_POST['ban_threshold'] ?? 5)
        ]
    ];

    $filePath = __DIR__ . '/../../data/system_settings.json';
    if (file_put_contents($filePath, json_encode($settingsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        // تحديث سعر الصرف وسياسة الغياب في جدول institution أيضاً
        $db->prepare("UPDATE institution SET college_name = ?, college_name_en = ?, exchange_rate = ?, warning_absences = ?, ban_absences = ? WHERE id = 1")
           ->execute([
               $settingsData['college']['name_ar'],
               $settingsData['college']['name_en'],
               $settingsData['financial']['exchange_rate_usd_yer'],
               $settingsData['attendance_policy']['warning_threshold'],
               $settingsData['attendance_policy']['ban_threshold']
           ]);
        $message = 'تم تحديث ملف إعدادات النظام JSON وحفظ التعديلات بنجاح!';
    } else {
        $error = 'تعذر الكتابة إلى ملف system_settings.json';
    }
}

// قراءة الإعدادات الحالية
$settings = get_system_settings();

// قراءة محتوى curriculum.json
$curriculumJson = file_exists(__DIR__ . '/../../data/curriculum.json') 
    ? file_get_contents(__DIR__ . '/../../data/curriculum.json') 
    : '';

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">إدارة ملفات وتصدير بيانات JSON</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">تصدير واستيراد الكشوفات والدرجات والسندات وإعدادات النظام كملفات JSON معيارية</p>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="alert-box alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- بطاقات تصدير بيانات النظام بصيغة JSON -->
<div style="background: #ffffff; padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 28px; box-shadow: var(--shadow-sm);">
  <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px;">
    📥 تحميل وتصدير كشوفات الكلية بصيغة JSON:
  </h4>
  <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">
    يمكنك تحميل البيانات الحية بصيغة JSON لأغراض الأرشفة أو الربط مع أنظمة أخرى:
  </p>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
    
    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
      <div style="font-size: 2rem; margin-bottom: 8px;">👨‍🎓</div>
      <h5 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 6px;">كشف الطلاب</h5>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">تصدير سجلات الطلاب وبياناتهم الأكاديمية</p>
      <a href="../api/export_json.php?type=students_all" class="pill-btn solid" style="font-size: 0.85rem; display: inline-block;" target="_blank">
        تحميل الطلاب JSON ⤓
      </a>
    </div>

    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
      <div style="font-size: 2rem; margin-bottom: 8px;">💰</div>
      <h5 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 6px;">السندات المالية</h5>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">سندات الرسوم الدراسية وسندات الرسوم الأخرى</p>
      <a href="../api/export_json.php?type=finance_all" class="pill-btn solid" style="font-size: 0.85rem; display: inline-block;" target="_blank">
        تحميل السندات JSON ⤓
      </a>
    </div>

    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
      <div style="font-size: 2rem; margin-bottom: 8px;">🏆</div>
      <h5 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 6px;">كشف الدرجات</h5>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">نتائج الدرجات النصفية والنهائية والتقديرات</p>
      <a href="../api/export_json.php?type=grades_all" class="pill-btn solid" style="font-size: 0.85rem; display: inline-block;" target="_blank">
        تحميل الدرجات JSON ⤓
      </a>
    </div>

    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
      <div style="font-size: 2rem; margin-bottom: 8px;">📝</div>
      <h5 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 6px;">سجل الحضور والغياب</h5>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">المحاضرات الـ 12 ونسب الحضور والإنذارات</p>
      <a href="../api/export_json.php?type=attendance_all" class="pill-btn solid" style="font-size: 0.85rem; display: inline-block;" target="_blank">
        تحميل الحضور JSON ⤓
      </a>
    </div>

  </div>
</div>

<!-- تعديل إعدادات النظام المحفوظة في system_settings.json -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
  
  <div class="admin-form-card">
    <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
      ⚙️ إعدادات النظام (system_settings.json)
    </h4>
    <form method="POST" action="json_manager.php">
      <input type="hidden" name="save_settings" value="1">

      <div class="form-group">
        <label>اسم الكلية (عربي)</label>
        <input type="text" name="name_ar" class="form-control" value="<?= htmlspecialchars($settings['college']['name_ar'] ?? 'كلية أيلول الجامعية') ?>" required>
      </div>

      <div class="form-group">
        <label>اسم الكلية (إنجليزي)</label>
        <input type="text" name="name_en" class="form-control" value="<?= htmlspecialchars($settings['college']['name_en'] ?? 'Aylol University College') ?>" required>
      </div>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>سعر صرف الدولار مقابل الريال اليمني *</label>
          <input type="number" step="0.5" name="exchange_rate" class="form-control" value="<?= htmlspecialchars($settings['financial']['exchange_rate_usd_yer'] ?? '250') ?>" required style="font-weight: 900; color: #0284c7;">
        </div>

        <div class="form-group">
          <label>أرقام هواتف الكلية</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['college']['phone'] ?? '770612187 - 770017481') ?>">
        </div>
      </div>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>حد الإنذار الأكاديمي (عدد الغيابات)</label>
          <input type="number" name="warning_threshold" class="form-control" value="<?= htmlspecialchars($settings['attendance_policy']['warning_threshold'] ?? '3') ?>" required>
        </div>

        <div class="form-group">
          <label>حد الحرمان الفصلي (عدد الغيابات)</label>
          <input type="number" name="ban_threshold" class="form-control" value="<?= htmlspecialchars($settings['attendance_policy']['ban_threshold'] ?? '5') ?>" required>
        </div>
      </div>

      <button type="submit" class="btn-primary-admin" style="width: 100%; margin-top: 10px;">
        💾 حفظ الإعدادات في ملف JSON
      </button>
    </form>
  </div>

  <!-- عرض ملف curriculum.json -->
  <div class="admin-form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
      <h4 style="color: var(--primary-blue); font-weight: 900;">
        📄 معاينة هيكل الكلية والمقررات (curriculum.json)
      </h4>
      <a href="../data/curriculum.json" target="_blank" class="btn-term-outline" style="font-size: 0.8rem; padding: 4px 10px;">فتح الملف الخام</a>
    </div>

    <pre style="background: #0f172a; color: #38bdf8; padding: 16px; border-radius: var(--radius-sm); font-size: 0.82rem; height: 350px; overflow-y: auto; direction: ltr; text-align: left; font-family: monospace; line-height: 1.5;"><?= htmlspecialchars($curriculumJson) ?></pre>
  </div>

</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
