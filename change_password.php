<?php
/**
 * كلية أيلول الجامعية - تغيير كلمة المرور للطالب
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();
$db = getDBConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass)) {
        $error = 'يرجى إدخال جميع الحقول.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'كلمتا المرور الجديدتان غير متطابقتين.';
    } elseif (strlen($newPass) < 4) {
        $error = 'كلمة المرور يجب أن لا تقل عن 4 أحرف أو أرقام.';
    } else {
        $stmt = $db->prepare("SELECT password_hash FROM student_accounts WHERE student_id = ?");
        $stmt->execute([$student['id']]);
        $acc = $stmt->fetch();

        if (!$acc || !password_verify($currentPass, $acc['password_hash'])) {
            $error = 'كلمة المرور الحالية غير صحيحة.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE student_accounts SET password_hash = ?, updated_at = NOW() WHERE student_id = ?");
            $upd->execute([$newHash, $student['id']]);
            $success = 'تم تحديث كلمة المرور الخاصة بك بنجاح!';
        }
    }
}

$pageTitle = 'تغيير كلمة المرور';
include_once __DIR__ . '/includes/header.php';
?>

<div class="login-page-body" style="min-height: calc(100vh - 120px);">
  <div class="login-card" style="max-width: 440px;">
    
    <h1 class="login-heading">تغيير كلمة المرور</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: -16px; margin-bottom: 20px; font-weight: 600;">
      <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['university_number']) ?>)
    </p>

    <?php if (!empty($error)): ?>
      <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="change_password.php" method="POST" class="login-form">
      
      <div class="login-input-wrap">
        <input type="password" name="current_password" placeholder="كلمة المرور الحالية" required />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
      </div>

      <div class="login-input-wrap">
        <input type="password" name="new_password" placeholder="كلمة المرور الجديدة" required />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
      </div>

      <div class="login-input-wrap">
        <input type="password" name="confirm_password" placeholder="تأكيد كلمة المرور الجديدة" required />
        <span class="login-input-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
      </div>

      <button type="submit" class="btn-login-submit">
        حفظ كلمة المرور الجديدة
      </button>

      <div class="login-footer-links">
        <a href="profile.php">العودة إلى الملف الشخصي</a>
      </div>

    </form>

  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
