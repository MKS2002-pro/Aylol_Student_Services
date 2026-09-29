<?php
/**
 * كلية أيلول الجامعية - دوال النظام المساعدة
 */

// معدل الصرف الرسمي لليمني مقابل الدولار (250 ريال = 1 دولار)
define('EXCHANGE_RATE', 250.00);

/**
 * تحويل المبلغ من الريال اليمني إلى الدولار
 * @param float $yer
 * @return float
 */
function yer_to_usd(float $yer): float {
    if ($yer <= 0) return 0.0;
    return round($yer / EXCHANGE_RATE, 2);
}

/**
 * تحويل المبلغ من الدولار إلى الريال اليمني
 * @param float $usd
 * @return float
 */
function usd_to_yer(float $usd): float {
    return round($usd * EXCHANGE_RATE, 2);
}

/**
 * التحقق من مرور 15 يوماً على رصد الدرجة لمنع التعديل أو الحذف
 * @param string|DateTime $createdAt
 * @return bool
 */
function is_grade_locked($createdAt): bool {
    if (empty($createdAt)) return false;
    try {
        $created = new DateTime($createdAt);
        $now = new DateTime();
        $diff = $created->diff($now);
        // إذا مرت 15 يوماً فأكثر يعتبر مغلقاً أمنياً
        return ($diff->days > 15 || ($diff->days === 15 && $diff->h > 0));
    } catch (Exception $e) {
        return false;
    }
}

/**
 * حساب حالة إنذار أو حرمان الغياب
 * 3 غياب = إنذار، 5 غياب = حرمان
 * @param int $absentCount
 * @return array
 */
function get_attendance_status(int $absentCount): array {
    if ($absentCount >= 5) {
        return [
            'status' => 'حرمان',
            'class'  => 'badge-danger',
            'code'   => 'ban'
        ];
    } elseif ($absentCount >= 3) {
        return [
            'status' => 'إنذار',
            'class'  => 'badge-warning',
            'code'   => 'warning'
        ];
    } else {
        return [
            'status' => 'طبيعي',
            'class'  => 'badge-normal',
            'code'   => 'normal'
        ];
    }
}

/**
 * حساب التقدير النصي بناءً على الدرجة
 * @param float $score
 * @return string
 */
function calculate_grade_letter(float $score): string {
    if ($score >= 90) return 'ممتاز';
    if ($score >= 80) return 'جيد جداً';
    if ($score >= 65) return 'جيد';
    if ($score >= 50) return 'مقبول';
    return 'راسب';
}

/**
 * تنظيف المدخلات النصية
 * @param mixed $data
 * @return string
 */
function clean($data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * قراءة إعدادات النظام من ملف JSON
 * @return array
 */
function get_system_settings(): array {
    $file = __DIR__ . '/../data/system_settings.json';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) return $data;
    }
    return [
        'college' => [
            'name_ar' => 'كلية أيلول الجامعية',
            'name_en' => 'Aylol University College',
            'code' => 'AUC'
        ],
        'financial' => [
            'exchange_rate_usd_yer' => 250.0
        ]
    ];
}

/**
 * إرجاع استجابة JSON وإنهاء الطلب
 * @param array $data
 * @param int $statusCode
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
