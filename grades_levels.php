<?php
/**
 * كلية أيلول الجامعية - بيان درجات الطالب (المستويات)
 * مطابقة تامة للصورة 8 في ملف PDF
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

requireStudent();
$student = getCurrentStudent();
$db = getDBConnection();

$pageTitle = 'بيان درجات الطالب - المستويات';
include_once __DIR__ . '/includes/header.php';

$studentLevelNum = (int)$student['current_level_id'];
$isTuitionCleared = (bool)$student['tuition_cleared'];

$levels = [
    1 => ['name' => 'المستوى الأول', 'img' => 'assets/images/level1.jpg'],
    2 => ['name' => 'المستوى الثاني', 'img' => 'assets/images/level2.jpg'],
    3 => ['name' => 'المستوى الثالث', 'img' => 'assets/images/level3.png'],
    4 => ['name' => 'المستوى الرابع', 'img' => 'assets/images/level4.jpg'],
];
?>

<div class="page-title-block">
  <h2 class="college-subheading"><?= htmlspecialchars($student['college_name'] ?? 'كلية أيلول الجامعية') ?> _ <?= htmlspecialchars($student['program_name'] ?? 'تقنية المعلومات') ?> _ <?= htmlspecialchars($student['level_name'] ?? 'مستوى ثالث') ?></h2>
  <h1 class="red-statement-title">بيان درجات الطالب</h1>
</div>

<hr class="header-divider" />

<div class="levels-container-grid">
  
  <?php foreach ($levels as $lvlNum => $lvlData): 
      // إخفاء المستويات التي لم يصل إليها الطالب
      if ($lvlNum > $studentLevelNum) {
          continue;
      }
      $isLockedForFees = ($lvlNum === $studentLevelNum && !$isTuitionCleared);
  ?>

    <div class="level-card-item <?= $isLockedForFees ? 'level-locked' : '' ?>">
      
      <?php if ($isLockedForFees): ?>
        <a href="financial_status.php" class="padlock-overlay-link" title="الدرجات مقفلة لعدم تصفية الرسوم، انقر للانتقال للكشف المالي">
          <div class="padlock-icon-box">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <span class="padlock-lock-msg">مغلق لعدم تصفية الرسوم</span>
        </a>
      <?php endif; ?>

      <div class="level-illustration-wrap">
        <img src="<?= $lvlData['img'] ?>" alt="<?= $lvlData['name'] ?>" onerror="this.src='assets/images/level1.jpg';">
      </div>

      <h3 class="level-title-text"><?= $lvlData['name'] ?></h3>

      <div class="term-buttons-row">
        <?php if ($isLockedForFees): ?>
          <button type="button" class="btn-term-outline" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('تنبيه: لا يمكن فتح كشف الدرجات حتى يتم سداد وتصفية الرسوم الدراسية.');">ترم أول</button>
          <button type="button" class="btn-term-outline" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('تنبيه: لا يمكن فتح كشف الدرجات حتى يتم سداد وتصفية الرسوم الدراسية.');">ترم ثاني</button>
        <?php else: ?>
          <a href="grades_statement.php?level=<?= $lvlNum ?>&semester=1" class="btn-term-outline">ترم أول</a>
          <a href="grades_statement.php?level=<?= $lvlNum ?>&semester=2" class="btn-term-outline">ترم ثاني</a>
        <?php endif; ?>
      </div>

    </div>

  <?php endforeach; ?>

</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
