<?php
/**
 * كلية أيلول الجامعية - واجهة تحديد المستويات وربط الرسوم بالدرجات
 * مطابقة للصورة 4 في ملف PDF مع تطبيق القفل المالي
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();
$db = getDBConnection();

$pageTitle = 'بيان الحالة المالية للطالب - تحديد المستويات';
include_once __DIR__ . '/includes/header.php';

// جلب تخصص الطالب وعدد سنوات الدراسة
$progStmt = $db->prepare("SELECT * FROM programs WHERE id = ?");
$progStmt->execute([$student['program_id']]);
$program = $progStmt->fetch() ?: ['duration_years' => 4];
$durationYears = (int)$program['duration_years'];

// جلب المستويات الدراسية المتاحة للتخصص
$levelsStmt = $db->prepare("SELECT * FROM academic_levels WHERE level_number <= ? ORDER BY level_number ASC");
$levelsStmt->execute([$durationYears]);
$allLevels = $levelsStmt->fetchAll();

$studentLevelNum = (int)$student['current_level_id'];
$isTuitionCleared = (bool)$student['tuition_cleared'];
?>

<div class="page-title-block">
  <h2 class="college-subheading"><?= htmlspecialchars($student['college_name'] ?? 'كلية أيلول الجامعية') ?> _ <?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?> _ <?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?></h2>
  <h1 class="red-statement-title">تحديد المستويات الدراسية</h1>
</div>

<hr class="header-divider" />

<!-- شبكة المستويات الدراسية -->
<div class="levels-container-grid">
  
  <?php foreach ($allLevels as $lvl): 
      $lvlNum = (int)$lvl['level_number'];
      
      // شرط 3: إخفاء المستويات التي لم يصل إليها الطالب
      if ($lvlNum > $studentLevelNum) {
          continue; 
      }

      // صور توضيحية مطابقة لكل مستوى
      $imgMap = [
          1 => 'assets/images/level1.jpg',
          2 => 'assets/images/level2.jpg',
          3 => 'assets/images/level3.png',
          4 => 'assets/images/level4.jpg',
      ];
      $lvlImg = $imgMap[$lvlNum] ?? 'assets/images/level1.jpg';

      // شرط 4: ربط رسوم الطالب بدرجاته: في حالة لم يصفِّ الرسوم لا تفتح له درجاته ويظهر القفل
      $isLockedForFees = ($lvlNum === $studentLevelNum && !$isTuitionCleared);
  ?>

    <div class="level-card-item <?= $isLockedForFees ? 'level-locked' : '' ?>">
      
      <?php if ($isLockedForFees): ?>
        <!-- طبقة القفل الأزرق الكبير للرسوم غير المسددة (مطابقة للصورة 4) -->
        <a href="financial_status.php" class="padlock-overlay-link" title="الدرجات مقفلة لعدم تصفية الرسوم، انقر للانتقال للكشف المالي">
          <div class="padlock-icon-box">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <span class="padlock-lock-msg">مغلق لعدم تصفية الرسوم الدراسية</span>
        </a>
      <?php endif; ?>

      <div class="level-illustration-wrap">
        <img src="<?= $lvlImg ?>" alt="<?= htmlspecialchars($lvl['level_name']) ?>" onerror="this.src='assets/images/level1.jpg';">
      </div>

      <h3 class="level-title-text"><?= htmlspecialchars($lvl['level_name']) ?></h3>

      <!-- زرا الترم الأول والثاني -->
      <div class="term-buttons-row">
        <?php if ($isLockedForFees): ?>
          <button type="button" class="btn-term-outline" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('تنبيه: لا يمكن فتح كشف الدرجات حتى يتم تصفية الرسوم الدراسية لهذا المستوى من قبل الإدارة.');">ترم أول</button>
          <button type="button" class="btn-term-outline" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('تنبيه: لا يمكن فتح كشف الدرجات حتى يتم تصفية الرسوم الدراسية لهذا المستوى من قبل الإدارة.');">ترم ثاني</button>
        <?php else: ?>
          <a href="grades_statement.php?level=<?= $lvlNum ?>&semester=1" class="btn-term-outline">ترم أول</a>
          <a href="grades_statement.php?level=<?= $lvlNum ?>&semester=2" class="btn-term-outline">ترم ثاني</a>
        <?php endif; ?>
      </div>

    </div>

  <?php endforeach; ?>

</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
