<?php
/**
 * كلية أيلول الجامعية - إدارة الطلاب للكنترول
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'إدارة الطلاب المقيدين';
$activeNav = 'students';

$search = clean($_GET['q'] ?? '');
$levelFilter = isset($_GET['level']) ? (int)$_GET['level'] : 0;

$query = "
    SELECT s.*, p.program_name, al.level_name, st.status_name, it.type_name as identity_type_name
    FROM students s
    LEFT JOIN programs p ON p.id = s.program_id
    LEFT JOIN academic_levels al ON al.id = s.current_level_id
    LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
    LEFT JOIN identity_types it ON it.id = s.identity_type_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.full_name LIKE ? OR s.university_number LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($levelFilter > 0) {
    $query .= " AND s.current_level_id = ?";
    $params[] = $levelFilter;
}

$query .= " ORDER BY s.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">سجل الطلاب الجامعيين</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">يمكن للكنترول إضافة طلاب جدد وتحديث مستوياتهم وحالاتهم الأكاديمية وصورهم</p>
  </div>
  <a href="student_add.php" class="btn-primary-admin">+ إضافة طالب جديد مع كافة بياناته</a>
</div>

<!-- شريط البحث والتصفية -->
<div style="background: #ffffff; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 24px; box-shadow: var(--shadow-sm);">
  <form method="GET" action="students.php" style="display: flex; gap: 14px; flex-wrap: wrap;">
    <input 
      type="text" 
      name="q" 
      placeholder="البحث بالاسم أو الرقم الجامعي أو الهاتف..." 
      value="<?= htmlspecialchars($search) ?>" 
      class="form-control" 
      style="max-width: 340px;"
    >
    <select name="level" class="form-control" style="max-width: 200px;">
      <option value="0">-- جميع المستويات --</option>
      <option value="1" <?= $levelFilter === 1 ? 'selected' : '' ?>>المستوى الأول</option>
      <option value="2" <?= $levelFilter === 2 ? 'selected' : '' ?>>المستوى الثاني</option>
      <option value="3" <?= $levelFilter === 3 ? 'selected' : '' ?>>المستوى الثالث</option>
      <option value="4" <?= $levelFilter === 4 ? 'selected' : '' ?>>المستوى الرابع</option>
    </select>
    <button type="submit" class="btn-primary-admin" style="padding: 0 20px;">بحث وتصفية</button>
    <?php if (!empty($search) || $levelFilter > 0): ?>
      <a href="students.php" class="btn-term-outline" style="padding: 8px 16px; font-size: 0.9rem;">إلغاء التصفية</a>
    <?php endif; ?>
  </form>
</div>

<!-- جدول الطلاب -->
<div style="background: #ffffff; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); overflow: hidden;">
  <div class="table-responsive-box">
    <table class="statement-data-table">
      <thead>
        <tr>
          <th style="width: 60px;">الصورة</th>
          <th>الرقم الجامعي</th>
          <th style="text-align: right; padding-right: 14px;">اسم الطالب الرباعي</th>
          <th>التخصص</th>
          <th>المستوى الدراسي</th>
          <th>الحالة الأكاديمية</th>
          <th>حالة الرسوم</th>
          <th>رقم الهاتف</th>
          <th style="width: 170px;">الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="9" style="padding: 30px; color: var(--text-muted); font-size: 1.05rem;">
              لا توجد سجلات مطابقة للبحث.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($students as $st): 
              $photo = !empty($st['photo']) && file_exists(__DIR__ . '/../../' . $st['photo']) 
                  ? '../' . htmlspecialchars($st['photo']) 
                  : '../assets/images/level1.jpg';
          ?>
            <tr>
              <td>
                <img src="<?= $photo ?>" alt="الصورة" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
              </td>
              <td style="font-weight: 800;"><?= htmlspecialchars($st['university_number']) ?></td>
              <td style="text-align: right; padding-right: 14px; font-weight: 800; color: #0f172a;">
                <?= htmlspecialchars($st['full_name']) ?>
              </td>
              <td><?= htmlspecialchars($st['program_name'] ?? 'تقنية المعلومات') ?></td>
              <td style="font-weight: 800; color: var(--primary-blue);"><?= htmlspecialchars($st['level_name'] ?? 'المستوى الأول') ?></td>
              <td>
                <?php if ($st['status_name'] === 'خريج'): ?>
                  <span style="background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.85rem;">خريج 🎓</span>
                <?php elseif ($st['status_name'] === 'موقوف قيد'): ?>
                  <span class="badge-danger">موقوف قيد ⛔</span>
                <?php else: ?>
                  <span class="badge-normal">طالب منتظم</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($st['tuition_cleared']): ?>
                  <span class="badge-normal" title="تفتح له الدرجات">مسدد للرسوم ✓</span>
                <?php else: ?>
                  <span class="badge-warning" title="الدرجات مقفولة برمز القفل">متبقي رسوم 🔒</span>
                <?php endif; ?>
              </td>
              <td dir="ltr"><?= htmlspecialchars($st['phone'] ?? '-') ?></td>
              <td>
                <div style="display: flex; gap: 6px; justify-content: center;">
                  <a href="student_edit.php?id=<?= $st['id'] ?>" class="pill-btn solid" style="font-size: 0.8rem; padding: 4px 10px;">تعديل وملف</a>
                  <a href="finance.php?student_id=<?= $st['id'] ?>" class="pill-btn outline" style="font-size: 0.8rem; padding: 4px 10px;">المالية</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
