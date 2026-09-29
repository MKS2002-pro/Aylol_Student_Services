<?php
/**
 * كلية أيلول الجامعية - إضافة طالب جديد مع كافة بياناته وصورته
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();
$db = getDBConnection();

$adminPageTitle = 'إضافة طالب جديد';
$activeNav = 'students';

$error = '';
$success = '';

// جلب البرامج والمستويات وحالات القيد وأنواع الهوية
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
        // التحقق من عدم تكرار الرقم الجامعي
        $chkStmt = $db->prepare("SELECT id FROM students WHERE university_number = ?");
        $chkStmt->execute([$univNum]);
        if ($chkStmt->fetch()) {
            $error = 'الرقم الجامعي مسجل مسبقاً لطالب آخر!';
        } else {
            // معالجة رفع صورة الطالب
            $photoPath = null;
            if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
                if (in_array($ext, $allowed)) {
                    $uploadDir = __DIR__ . '/../../uploads/students/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filename = 'student_' . $univNum . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $targetPath)) {
                        $photoPath = 'uploads/students/' . $filename;
                    }
                }
            }

            // إدخال الطالب في قاعدة البيانات
            $insStmt = $db->prepare("
                INSERT INTO students (
                    university_number, full_name, gender, phone, email,
                    birth_date, birth_place, village, identity_type_id, identity_number,
                    address, college_name, program_id, current_level_id, academic_status_id,
                    tuition_cleared, photo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insStmt->execute([
                $univNum, $fullName, $gender, $phone, $email,
                $birthDate ?: null, $birthPlace, $village, $idTypeId, $idNumber,
                $address, $college, $programId, $levelId, $statusId,
                $tuitionCleared, $photoPath
            ]);
            $newStudentId = $db->lastInsertId();

            // إنشاء كلمة مرور أولية اختيارية (123456) أو تركها لتفعيل الطالب
            $initPass = $_POST['initial_password'] ?? '';
            if (!empty($initPass)) {
                $hash = password_hash($initPass, PASSWORD_DEFAULT);
                $accStmt = $db->prepare("INSERT INTO student_accounts (student_id, password_hash, is_active) VALUES (?, ?, 1)");
                $accStmt->execute([$newStudentId, $hash]);
            }

            $success = "تمت إضافة الطالب ($fullName) بنجاح!";
        }
    }
}

include_once __DIR__ . '/includes/admin_header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">إضافة طالب جامعي جديد</h3>
      <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">إدخال جميع البيانات الشخصية والأكاديمية والصورة الرسمية</p>
    </div>
    <a href="students.php" class="btn-term-outline" style="font-size: 0.9rem; padding: 8px 16px;">العودة لقائمة الطلاب</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="admin-form-card">
    <form method="POST" action="student_add.php" enctype="multipart/form-data">
      
      <h4 style="color: var(--primary-blue); font-weight: 900; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        1. البيانات الأكاديمية والجامعية
      </h4>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>الرقم الجامعي (الأكاديمي) *</label>
          <input type="text" name="university_number" class="form-control" required placeholder="مثال: 20241089" value="<?= htmlspecialchars($_POST['university_number'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>الاسم الرباعي الكامل *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="الاسم الرباعي الكامل للطالب" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>الكلية</label>
          <input type="text" name="college_name" class="form-control" value="كلية علوم الحاسوب">
        </div>

        <div class="form-group">
          <label>التخصص الأكاديمي *</label>
          <select name="program_id" class="form-control" required>
            <?php foreach ($programs as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['program_name']) ?> (<?= $p['duration_years'] ?> سنوات)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>المستوى الدراسي الحالي *</label>
          <select name="current_level_id" class="form-control" required>
            <?php foreach ($levels as $l): ?>
              <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['level_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>الحالة الأكاديمية للطالب *</label>
          <select name="academic_status_id" class="form-control" required>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group" style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1.5px dashed var(--border-light); margin-top: 10px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 800;">
          <input type="checkbox" name="tuition_cleared" value="1" checked style="width: 18px; height: 18px;">
          <span>تصفية الرسوم الدراسية (عند التحديد تفتح درجات الطالب، وعند إلغاء التحديد تظهر شارة القفل 🔒 وتغلق درجاته)</span>
        </label>
      </div>

      <h4 style="color: var(--primary-blue); font-weight: 900; margin: 24px 0 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        2. البيانات الشخصية والهوية والصورة
      </h4>

      <div class="form-grid-2col">
        <div class="form-group">
          <label>صورة الطالب الشخصية</label>
          <input type="file" name="student_photo" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
          <label>الجنس</label>
          <select name="gender" class="form-control">
            <option value="ذكر">ذكر</option>
            <option value="أنثى">أنثى</option>
          </select>
        </div>

        <div class="form-group">
          <label>نوع الهوية</label>
          <select name="identity_type_id" class="form-control">
            <?php foreach ($idTypes as $it): ?>
              <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['type_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>رقم الهوية (البطاقة/الجواز) *</label>
          <input type="text" name="identity_number" class="form-control" required placeholder="رقم الهوية الوطنية" value="<?= htmlspecialchars($_POST['identity_number'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>رقم الهاتف</label>
          <input type="text" name="phone" class="form-control" placeholder="770000000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" placeholder="student@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>تاريخ الميلاد</label>
          <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($_POST['birth_date'] ?? '2004-01-01') ?>">
        </div>

        <div class="form-group">
          <label>مكان الميلاد</label>
          <input type="text" name="birth_place" class="form-control" placeholder="مثال: يريم" value="<?= htmlspecialchars($_POST['birth_place'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>القرية / العزلة</label>
          <input type="text" name="village" class="form-control" placeholder="مثال: بيت العكاد" value="<?= htmlspecialchars($_POST['village'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>العنوان السكني</label>
          <input type="text" name="address" class="form-control" placeholder="مثال: يريم - الدائري" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>
      </div>

      <h4 style="color: var(--primary-blue); font-weight: 900; margin: 24px 0 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
        3. كلمة المرور الأولية (اختياري)
      </h4>
      <div class="form-group" style="max-width: 400px;">
        <label>كلمة المرور (اتركها فارغة ليدخل الطالب عبر شاشة تفعيل الحساب بنفسه)</label>
        <input type="password" name="initial_password" class="form-control" placeholder="كلمة مرور أولية إن رغبت">
      </div>

      <div style="margin-top: 28px; display: flex; gap: 14px;">
        <button type="submit" class="btn-primary-admin" style="font-size: 1.1rem; padding: 12px 36px;">
          حفظ وإضافة الطالب
        </button>
        <a href="students.php" class="btn-term-outline" style="padding: 12px 24px;">إلغاء</a>
      </div>

    </form>
  </div>

</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
