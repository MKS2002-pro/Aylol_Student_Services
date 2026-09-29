<?php
/**
 * كلية أيلول الجامعية - تعديل بيانات الطالب وترقية مستواه وحالته
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: students.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die("الطالب غير موجود.");
}

$adminPageTitle = 'تعديل بيانات الطالب: ' . $student['full_name'];
$activeNav = 'students';

$error = '';
$success = '';

$programs = $db->query("SELECT * FROM programs ORDER BY id ASC")->fetchAll();
$levels = $db->query("SELECT * FROM academic_levels ORDER BY level_number ASC")->fetchAll();
$statuses = $db->query("SELECT * FROM academic_statuses ORDER BY id ASC")->fetchAll();
$idTypes = $db->query("SELECT * FROM identity_types ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $univNum = clean($_POST['university_number'] ?? '');
    $fullName = clean($_POST['full_name'] ?? '');
    $gender = clean($_POST['gender'] ?? 'ذكر');
    $phone = clean($_POST['phone'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $birthDate = clean($_POST['birth_date'] ?? null);
    $birthPlace = clean($_POST['birth_place'] ?? '');
    $village = clean($_POST['village'] ?? '');
    $idTypeId = (int)($_POST['identity_type_id'] ?? 1);
    $idNumber = clean($_POST['identity_number'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $college = clean($_POST['college_name'] ?? 'كلية علوم الحاسوب');
    $programId = (int)($_POST['program_id'] ?? 1);
    $levelId = (int)($_POST['current_level_id'] ?? 1);
    $statusId = (int)($_POST['academic_status_id'] ?? 1);
    $tuitionCleared = isset($_POST['tuition_cleared']) ? 1 : 0;

    if (empty($univNum) || empty($fullName) || empty($idNumber)) {
        $error = 'يرجى إدخال الحقول الإلزامية: الرقم الجامعي، الاسم الرباعي، ورقم الهوية.';
    } else {
        $photoPath = $student['photo'];
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../../uploads/students/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $filename = 'student_' . $univNum . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $uploadDir . $filename)) {
                    $photoPath = 'uploads/students/' . $filename;
                }
            }
        }

        $updStmt = $db->prepare("
            UPDATE students SET
                university_number = ?, full_name = ?, gender = ?, phone = ?, email = ?,
                birth_date = ?, birth_place = ?, village = ?, identity_type_id = ?, identity_number = ?,
                address = ?, college_name = ?, program_id = ?, current_level_id = ?, academic_status_id = ?,
                tuition_cleared = ?, photo = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updStmt->execute([
            $univNum, $fullName, $gender, $phone, $email,
            $birthDate ?: null, $birthPlace, $village, $idTypeId, $idNumber,
            $address, $college, $programId, $levelId, $statusId,
            $tuitionCleared, $photoPath, $id
        ]);

        // تحديث كلمة المرور إن تم إدخالها
        $newPass = $_POST['reset_password'] ?? '';
        if (!empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $accChk = $db->prepare("SELECT id FROM student_accounts WHERE student_id = ?");
            $accChk->execute([$id]);
            if ($accChk->fetch()) {
                $db->prepare("UPDATE student_accounts SET password_hash = ? WHERE student_id = ?")->execute([$hash, $id]);
            } else {
                $db->prepare("INSERT INTO student_accounts (student_id, password_hash, is_active) VALUES (?, ?, 1)")->execute([$id, $hash]);
            }
        }

        $success = 'تم تحديث بيانات الطالب وحالته ومستواه الدراسي بنجاح!';
        
        // إعادة تحميل البيانات
        $stmt->execute([$id]);
        $student = $stmt->fetch();
    }
}

include_once __DIR__ . '/includes/admin_header.php';
$studentPhoto = !empty($student['photo']) && file_exists(__DIR__ . '/../../' . $student['photo']) 
    ? '../' . htmlspecialchars($student['photo']) 
    : '../assets/images/level1.jpg';
?>

<div style="max-width: 900px; margin: 0 auto;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">تعديل وتحديث ملف الطالب</h3>
      <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">تحديث المستوى الدراسي، نقل الحالة من طالب إلى خريج أو موقوف، وإدارة الرسوم</p>
    </div>
    <div style="display: flex; gap: 8px;">
      <a href="finance.php?student_id=<?= $student['id'] ?>" class="btn-primary-admin" style="font-size: 0.9rem; padding: 8px 16px;">كشف الحساب المالي</a>
      <a href="students.php" class="btn-term-outline" style="font-size: 0.9rem; padding: 8px 16px;">العودة للقائمة</a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="admin-form-card">
    <form method="POST" action="student_edit.php?id=<?= $student['id'] ?>" enctype="multipart/form-data">
      
      <!-- معاينة الصورة الحالية -->
      <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: var(--radius-md);">
        <img src="<?= $studentPhoto ?>" alt="صورة الطالب" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-blue);">
        <div>
          <h4 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin-bottom: 4px;"><?= htmlspecialchars($student['full_name']) ?></h4>
          <span style="font-weight: 800; color: var(--primary-blue); font-size: 0.95rem;">الرقم الجامعي: <?= htmlspecialchars($student['university_number']) ?></span>
        </div>
      </div>

      <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        1. تحديث المستوى والحالة الأكاديمية (شرط الكنترول)
      </h4>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>المستوى الدراسي الحالي *</label>
          <select name="current_level_id" class="form-control" required style="font-weight: 800; color: var(--primary-blue);">
            <?php foreach ($levels as $l): ?>
              <option value="<?= $l['id'] ?>" <?= $student['current_level_id'] == $l['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['level_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>الحالة الأكاديمية (تحويل من طالب إلى خريج أو موقوف قيد) *</label>
          <select name="academic_status_id" class="form-control" required style="font-weight: 800;">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $student['academic_status_id'] == $s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['status_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>الرقم الجامعي (الأكاديمي) *</label>
          <input type="text" name="university_number" class="form-control" required value="<?= htmlspecialchars($student['university_number']) ?>">
        </div>

        <div class="form-group">
          <label>الاسم الرباعي الكامل *</label>
          <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($student['full_name']) ?>">
        </div>

        <div class="form-group">
          <label>التخصص الأكاديمي *</label>
          <select name="program_id" class="form-control" required>
            <?php foreach ($programs as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $student['program_id'] == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['program_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>الكلية</label>
          <input type="text" name="college_name" class="form-control" value="<?= htmlspecialchars($student['college_name'] ?? 'كلية علوم الحاسوب') ?>">
        </div>
      </div>

      <!-- تحكم قفل الرسوم للدرجات -->
      <div class="form-group" style="background: #fef3c7; border: 1.5px solid #fcd34d; padding: 14px; border-radius: var(--radius-sm); margin: 16px 0;">
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 900; color: #92400e;">
          <input type="checkbox" name="tuition_cleared" value="1" <?= $student['tuition_cleared'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
          <span>تصفية الرسوم الدراسية (تحديد الخيار يفتح كشف درجات الطالب فوراً، وإلغاء التحديد يقفل درجاته برمز القفل 🔒)</span>
        </label>
      </div>

      <h4 style="color: var(--primary-blue); font-weight: 900; margin: 24px 0 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        2. البيانات الشخصية والهوية والصورة
      </h4>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>تحديث صورة الطالب الشخصية</label>
          <input type="file" name="student_photo" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
          <label>الجنس</label>
          <select name="gender" class="form-control">
            <option value="ذكر" <?= $student['gender'] === 'ذكر' ? 'selected' : '' ?>>ذكر</option>
            <option value="أنثى" <?= $student['gender'] === 'أنثى' ? 'selected' : '' ?>>أنثى</option>
          </select>
        </div>

        <div class="form-group">
          <label>نوع الهوية</label>
          <select name="identity_type_id" class="form-control">
            <?php foreach ($idTypes as $it): ?>
              <option value="<?= $it['id'] ?>" <?= $student['identity_type_id'] == $it['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($it['type_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>رقم الهوية الوطنية *</label>
          <input type="text" name="identity_number" class="form-control" required value="<?= htmlspecialchars($student['identity_number']) ?>">
        </div>

        <div class="form-group">
          <label>رقم الهاتف</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>تاريخ الميلاد</label>
          <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($student['birth_date'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>مكان الميلاد</label>
          <input type="text" name="birth_place" class="form-control" value="<?= htmlspecialchars($student['birth_place'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>القرية / العزلة</label>
          <input type="text" name="village" class="form-control" value="<?= htmlspecialchars($student['village'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>العنوان السكني</label>
          <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($student['address'] ?? '') ?>">
        </div>
      </div>

      <h4 style="color: var(--primary-blue); font-weight: 900; margin: 24px 0 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        3. إعادة تعيين كلمة المرور (اختياري)
      </h4>
      <div class="form-group" style="max-width: 400px;">
        <label>تعيين كلمة مرور جديدة للطالب</label>
        <input type="password" name="reset_password" class="form-control" placeholder="اتركها فارغة إن لم ترغب في التغيير">
      </div>

      <div style="margin-top: 28px; display: flex; gap: 14px;">
        <button type="submit" class="btn-primary-admin" style="font-size: 1.1rem; padding: 12px 36px;">
          حفظ التعديلات وتحديث الملف
        </button>
        <a href="students.php" class="btn-term-outline" style="padding: 12px 24px;">إلغاء</a>
      </div>

    </form>
  </div>

</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
