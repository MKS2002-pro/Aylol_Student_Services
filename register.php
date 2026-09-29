<?php
/**
 * كلية أيلول الجامعية - تفعيل حساب الطالب / إنشاء كلمة المرور
 * تطبق آلية التحقق الأمني المحددة في المتطلبات
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = clean($_POST['full_name'] ?? '');
    $universityNumber = clean($_POST['university_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($fullName) || empty($universityNumber) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'كلمتا المرور غير متطابقتين. يرجى إعادة كتابتهما بدقة.';
    } elseif (strlen($password) < 4) {
        $error = 'كلمة المرور يجب أن لا تقل عن 4 خانات.';
    } else {
        $db = getDBConnection();

        // 1. البحث في قاعدة البيانات للتأكد هل هو طالب فعلي مطابق للاسم والرقم الأكاديمي
        $stmt = $db->prepare("
            SELECT s.*, st.status_name 
            FROM students s
            LEFT JOIN academic_statuses st ON st.id = s.academic_status_id
            WHERE s.university_number = ? 
              AND (
                s.full_name = ? 
                OR LOWER(REPLACE(s.full_name, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
              )
        ");
        $stmt->execute([$universityNumber, $fullName, $fullName]);
        $student = $stmt->fetch();

        // إذا لم يعثر عليه أو لم يكن طالباً فعلياً
        if (!$student) {
            $error = 'تأكد من إدارة الجامعة (لم يتم العثور على طالب مطابق للاسم الرباعي والرقم الجامعي المدخلين).';
        } else {
            // تشفير كلمة المرور بـ password_hash
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // التحقق هل له سجل حساب مسبق
            $chkStmt = $db->prepare("SELECT id FROM student_accounts WHERE student_id = ?");
            $chkStmt->execute([$student['id']]);
            $existingAccount = $chkStmt->fetch();

            if ($existingAccount) {
                // تحديث كلمة المرور
                $updStmt = $db->prepare("
                    UPDATE student_accounts 
                    SET password_hash = ?, is_active = 1, updated_at = NOW() 
                    WHERE student_id = ?
                ");
                $updStmt->execute([$passwordHash, $student['id']]);
            } else {
                // إنشاء سجل حساب جديد
                $insStmt = $db->prepare("
                    INSERT INTO student_accounts (student_id, password_hash, is_active) 
                    VALUES (?, ?, 1)
                ");
                $insStmt->execute([$student['id'], $passwordHash]);
            }

            // تسجيل دخوله مباشرة أو تحويله إلى صفحة تسجيل الدخول بنجاح
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            $_SESSION['student_num'] = $student['university_number'];
            header("Location: profile.php?welcome=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إنشاء وتفعيل حساب الطالب - كلية أيلول الجامعية</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page-body">
  
  <div class="login-card" style="max-width: 480px;">
    
    <div class="login-logo-container">
      <img src="assets/images/logo.png" alt="كلية أيلول الجامعية" class="login-logo-img" onerror="this.src='assets/images/3.png';">
    </div>

    <h1 class="login-heading">تفعيل حساب الطالب</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: -16px; margin-bottom: 20px; font-weight: 600;">
      أدخل بياناتك الجامعية المعتمدة لتعيين كلمة مرور خاصة بك
    </p>

    <?php if (!empty($error)): ?>
      <div class="alert-box alert-error">
        <strong>تنبيه: </strong> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="login-form">
      
      <!-- الاسم الرباعي -->
      <div class="login-input-wrap">
        <input 
          type="text" 
          name="full_name" 
          placeholder="الاسم الرباعي كما هو مقيد بالجامعة" 
          value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
          required 
        />
        <span class="login-input-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </span>
      </div>

      <!-- الرقم الجامعي -->
      <div class="login-input-wrap">
        <input 
          type="text" 
          name="university_number" 
          placeholder="الرقم الجامعي (الأكاديمي)" 
          value="<?= htmlspecialchars($_POST['university_number'] ?? '') ?>"
          required 
        />
        <span class="login-input-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 10h2"/>
            <path d="M16 14h2"/>
            <path d="M6.17 15a3 3 0 0 1 5.66 0"/>
            <circle cx="9" cy="11" r="2"/>
            <rect x="2" y="5" width="20" height="14" rx="2"/>
          </svg>
        </span>
      </div>

      <!-- كلمة المرور -->
      <div class="login-input-wrap">
        <input 
          type="password" 
          name="password" 
          placeholder="كلمة المرور الجديدة" 
          required 
        />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
      </div>

      <!-- تأكيد كلمة المرور -->
      <div class="login-input-wrap">
        <input 
          type="password" 
          name="password_confirm" 
          placeholder="تأكيد كلمة المرور" 
          required 
        />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </span>
      </div>

      <button type="submit" class="btn-login-submit">
        حفظ وتفعيل الحساب
      </button>

      <div class="login-footer-links">
        <a href="login.php">لديك حساب مفعل؟ تسجيل الدخول</a>
      </div>

    </form>

  </div>

  <script src="assets/js/main.js"></script>
</body>
</html>
