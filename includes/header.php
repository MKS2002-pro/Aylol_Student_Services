<?php
/**
 * كلية أيلول الجامعية - رأس الصفحة الرسمي المشترك
 */
if (!isset($pageTitle)) {
    $pageTitle = 'كلية أيلول الجامعية';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - كلية أيلول الجامعية</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- رأس الصفحة الرسمي مطابق تماماً للوثائق المرفقة -->
  <header class="site-header">
    <a href="#" class="menu-toggle-btn" title="القائمة الرئيسية">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" x2="20" y1="12" y2="12"/>
        <line x1="4" x2="20" y1="6" y2="6"/>
        <line x1="4" x2="20" y1="18" y2="18"/>
      </svg>
    </a>

    <div class="header-brand-wrap">
      <div class="brand-text-col">
        <span class="brand-title-ar">كلية أيلول الجامعية</span>
        <span class="brand-subtitle-en">Aylol University College</span>
      </div>
      <img src="assets/images/logo.png" alt="شعار كلية أيلول" class="header-logo-img" onerror="this.src='assets/images/3.png';">
    </div>
  </header>
