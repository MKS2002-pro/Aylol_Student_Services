/**
 * كلية أيلول الجامعية - مكتبة جافاسكربت التفاعلية
 */

document.addEventListener('DOMContentLoaded', function () {
  
  // 1. فتح وإغلاق القائمة الجانبية (Drawer Sidebar)
  const menuBtn = document.querySelector('.menu-toggle-btn');
  const drawerOverlay = document.querySelector('.sidebar-drawer-overlay');
  const closeBtn = document.querySelector('.sidebar-close-btn');

  if (menuBtn && drawerOverlay) {
    menuBtn.addEventListener('click', function (e) {
      e.preventDefault();
      drawerOverlay.classList.add('active');
    });
  }

  if (closeBtn && drawerOverlay) {
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      drawerOverlay.classList.remove('active');
    });
  }

  if (drawerOverlay) {
    drawerOverlay.addEventListener('click', function (e) {
      if (e.target === drawerOverlay) {
        drawerOverlay.classList.remove('active');
      }
    });
  }

  // 2. التحويل التلقائي للعملة (قسمة الريال اليمني على 250 لحساب الدولار)
  const yerInput = document.getElementById('amount_yer');
  const usdInput = document.getElementById('amount_usd');
  const exchangeRate = 250.0;

  if (yerInput && usdInput) {
    yerInput.addEventListener('input', function () {
      const yerVal = parseFloat(this.value) || 0;
      if (yerVal > 0) {
        usdInput.value = (yerVal / exchangeRate).toFixed(2);
      } else {
        usdInput.value = '0.00';
      }
    });
  }

  // 3. التبديل التفاعلي بين تبويبات الرسوم في صفحة الكشف المالي
  const tabTuition = document.getElementById('tab-tuition');
  const tabOther = document.getElementById('tab-other');
  const viewTuition = document.getElementById('view-tuition');
  const viewOther = document.getElementById('view-other');

  if (tabTuition && tabOther && viewTuition && viewOther) {
    tabTuition.addEventListener('click', function () {
      tabTuition.classList.remove('outline');
      tabTuition.classList.add('solid');
      tabOther.classList.remove('solid');
      tabOther.classList.add('outline');
      viewTuition.style.display = 'block';
      viewOther.style.display = 'none';
    });

    tabOther.addEventListener('click', function () {
      tabOther.classList.remove('outline');
      tabOther.classList.add('solid');
      tabTuition.classList.remove('solid');
      tabTuition.classList.add('outline');
      viewOther.style.display = 'block';
      viewTuition.style.display = 'none';
    });
  }

  // 4. التبديل في شاشة تسجيل الدخول بين طالب وكنترول
  const roleRadios = document.querySelectorAll('input[name="user_role"]');
  roleRadios.forEach(radio => {
    radio.addEventListener('change', function () {
      const idLabel = document.getElementById('login-id-label');
      const idInput = document.getElementById('login-id-input');
      const regLink = document.getElementById('register-link-wrap');

      if (this.value === 'control') {
        if (idInput) idInput.placeholder = 'اسم المستخدم (الكنترول)';
        if (regLink) regLink.style.display = 'none';
      } else {
        if (idInput) idInput.placeholder = 'الرقم الجامعي';
        if (regLink) regLink.style.display = 'block';
      }
    });
  });

});
