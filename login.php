<?php
/**
 * كلية أيلول الجامعية - واجهة تسجيل الدخول
 * مطابقة للصورة 1 في ملف PDF مع خيار التبديل كطالب أو ككنترول
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

// توجيه المستخدم إذا كان مسجل دخول بالفعل
if (isStudentLoggedIn()) {
    header("Location: profile.php");
    exit;
} elseif (isAdminLoggedIn()) {
    header("Location: admin/index.php");
    exit;
}

$error = '';
$success = '';
$selectedRole = $_GET['as'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['user_role'] ?? 'student';
    $identifier = clean($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'يرجى إدخال جميع الحقول المطلوبة.';
    } else {
        $db = getDBConnection();

        if ($role === 'control') {
            // تسجيل دخول الكنترول
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                header("Location: admin/index.php");
                exit;
            } else {
                $error = 'اسم مستخدم الكنترول أو كلمة المرور غير صحيحة.';
            }
        } else {
            // تسجيل دخول الطالب
            $stmt = $db->prepare("
                SELECT s.*, sa.password_hash, sa.is_active 
                FROM students s 
                LEFT JOIN student_accounts sa ON sa.student_id = s.id 
                WHERE s.university_number = ?
            ");
            $stmt->execute([$identifier]);
            $student = $stmt->fetch();

            if (!$student) {
                $error = 'الرقم الجامعي غير مسجل بالكلية. يرجى مراجعة إدارة القبول والتسجيل.';
            } elseif (empty($student['password_hash'])) {
                $error = 'حسابك غير مفعل بعد. يرجى الضغط على "إنشاء حساب" لتعيين كلمة المرور لأول مرة.';
            } elseif (!password_verify($password, $student['password_hash'])) {
                $error = 'كلمة المرور غير صحيحة. يرجى إعادة المحاولة.';
            } else {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_num'] = $student['university_number'];
                header("Location: profile.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول - كلية أيلول الجامعية</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page-body">
  
  <div class="login-card">
    
    <!-- شعار كلية أيلول الجامعية الرسمي -->
    <div class="login-logo-container">
      <img src="assets/images/logo.png" alt="كلية أيلول الجامعية" class="login-logo-img" onerror="this.src='assets/images/3.png';">
    </div>

    <!-- عنوان تسجيل الدخول -->
    <h1 class="login-heading">تسجيل الدخول</h1>

    <?php if (!empty($error)): ?>
      <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert-box alert-success">تم تفعيل حسابك وإنشاء كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول.</div>
    <?php endif; ?>

    <!-- نموذج تسجيل الدخول -->
    <form action="login.php" method="POST" class="login-form">
      
      <!-- محدد الدور: طالب أو كنترول (Radio / Tabs مطابق للشرط في المتطلبات) -->
      <div class="login-role-switch">
        <label>
          <input type="radio" name="user_role" value="student" <?= $selectedRole !== 'control' ? 'checked' : '' ?>>
          <span>دخول الطالب</span>
        </label>
        <label>
          <input type="radio" name="user_role" value="control" <?= $selectedRole === 'control' ? 'checked' : '' ?>>
          <span>دخول الكنترول</span>
        </label>
      </div>

      <!-- حقل الرقم الجامعي / اسم مستخدم الكنترول -->
      <div class="login-input-wrap">
        <input 
          type="text" 
          id="login-id-input"
          name="identifier" 
          placeholder="<?= $selectedRole === 'control' ? 'اسم المستخدم (الكنترول)' : 'الرقم الجامعي' ?>" 
          value="<?= htmlspecialchars($_POST['identifier'] ?? ($selectedRole === 'control' ? 'admin' : '202412056542')) ?>"
          required 
          autocomplete="username"
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

      <!-- حقل كلمة المرور -->
      <div class="login-input-wrap">
        <input 
          type="password" 
          name="password" 
          placeholder="كلمة المرور" 
          value="<?= $selectedRole === 'control' ? 'admin123' : '123456' ?>"
          required 
          autocomplete="current-password"
        />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
      </div>

      <!-- زر تسجيل الدخول الأزرق -->
      <button type="submit" class="btn-login-submit">
        تسجيل الدخول
      </button>

      <!-- رابط إنشاء حساب (الخدعة للطلاب) -->
      <div class="login-footer-links" id="register-link-wrap" style="<?= $selectedRole === 'control' ? 'display:none;' : '' ?>">
        <a href="register.php">ليس لديك حساب؟ إنشاء حساب</a>
      </div>

    </form>

  </div>

  <script src="assets/js/main.js"></script>
</body>
</html>
