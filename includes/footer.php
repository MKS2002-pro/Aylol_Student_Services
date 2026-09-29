<?php
/**
 * كلية أيلول الجامعية - تذييل الصفحة الرسمي المشترك
 */
?>
  <!-- تضمين القائمة الجانبية في كافة صفحات الطالب -->
  <?php if (isStudentLoggedIn()): ?>
    <?php include_once __DIR__ . '/drawer.php'; ?>
  <?php endif; ?>

  <script src="assets/js/main.js"></script>
</body>
</html>
