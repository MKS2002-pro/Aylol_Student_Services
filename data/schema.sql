-- =========================================================
-- كلية أيلول الجامعية - Aylol University College
-- Database Schema & Complete Seed Data
-- =========================================================

CREATE DATABASE IF NOT EXISTS `aylol_university`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `aylol_university`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS `grade_records`;
DROP TABLE IF EXISTS `attendance_records`;
DROP TABLE IF EXISTS `lecture_sessions`;
DROP TABLE IF EXISTS `student_course_enrollments`;
DROP TABLE IF EXISTS `course_offerings`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `receipts`;
DROP TABLE IF EXISTS `student_fees`;
DROP TABLE IF EXISTS `fee_types`;
DROP TABLE IF EXISTS `student_accounts`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `academic_statuses`;
DROP TABLE IF EXISTS `identity_types`;
DROP TABLE IF EXISTS `semesters`;
DROP TABLE IF EXISTS `academic_levels`;
DROP TABLE IF EXISTS `academic_years`;
DROP TABLE IF EXISTS `programs`;
DROP TABLE IF EXISTS `institution`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. الكلية والبيانات العامة
-- =========================================================
CREATE TABLE `institution` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `college_name` VARCHAR(200) NOT NULL DEFAULT 'كلية أيلول الجامعية',
    `college_name_en` VARCHAR(200) NOT NULL DEFAULT 'Aylol University College',
    `logo` VARCHAR(255) DEFAULT 'assets/images/logo.png',
    `exchange_rate` DECIMAL(10,2) NOT NULL DEFAULT 250.00,
    `warning_absences` INT NOT NULL DEFAULT 3,
    `ban_absences` INT NOT NULL DEFAULT 5,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution` (`id`, `college_name`, `college_name_en`, `logo`, `exchange_rate`)
VALUES (1, 'كلية أيلول الجامعية', 'Aylol University College', 'assets/images/logo.png', 250.00);

-- =========================================================
-- 2. التخصصات والأقسام
-- =========================================================
CREATE TABLE `programs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `college_name` VARCHAR(150) NOT NULL DEFAULT 'كلية علوم الحاسوب',
    `program_name` VARCHAR(150) NOT NULL,
    `duration_years` TINYINT UNSIGNED NOT NULL DEFAULT 4,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_program` (`college_name`, `program_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `programs` (`id`, `college_name`, `program_name`, `duration_years`) VALUES
(1, 'كلية علوم الحاسوب', 'تقنية المعلومات', 4),
(2, 'كلية علوم الحاسوب', 'علوم الحاسوب', 4),
(3, 'كلية العلوم الإدارية', 'نظم المعلومات الإدارية', 4),
(4, 'كلية العلوم الطبية', 'صيدلة سريرية', 5),
(5, 'كلية العلوم الإدارية', 'محاسبة مالية', 4);

-- =========================================================
-- 3. الأعوام الأكاديمية
-- =========================================================
CREATE TABLE `academic_years` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `year_name` VARCHAR(20) NOT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_year_name` (`year_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_years` (`id`, `year_name`, `is_current`) VALUES
(1, '2024/2025', 0),
(2, '2025/2026', 0),
(3, '2026/2027', 1);

-- =========================================================
-- 4. المستويات الأكاديمية
-- =========================================================
CREATE TABLE `academic_levels` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level_number` TINYINT UNSIGNED NOT NULL,
    `level_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_level_num` (`level_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_levels` (`id`, `level_number`, `level_name`) VALUES
(1, 1, 'المستوى الأول'),
(2, 2, 'المستوى الثاني'),
(3, 3, 'المستوى الثالث'),
(4, 4, 'المستوى الرابع'),
(5, 5, 'المستوى الخامس');

-- =========================================================
-- 5. الفصول الدراسية (الترم)
-- =========================================================
CREATE TABLE `semesters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `semester_number` TINYINT UNSIGNED NOT NULL,
    `semester_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sem_num` (`semester_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `semesters` (`id`, `semester_number`, `semester_name`) VALUES
(1, 1, 'ترم أول'),
(2, 2, 'ترم ثاني');

-- =========================================================
-- 6. أنواع الهوية والحالات الأكاديمية
-- =========================================================
CREATE TABLE `identity_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `identity_types` (`id`, `type_name`) VALUES
(1, 'بطاقة شخصية'),
(2, 'جواز سفر'),
(3, 'بطاقة عائلية');

CREATE TABLE `academic_statuses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `status_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_statuses` (`id`, `status_name`) VALUES
(1, 'طالب'),
(2, 'خريج'),
(3, 'موقوف قيد'),
(4, 'منسحب');

-- =========================================================
-- 7. مستخدمو الكنترول والإدارة
-- =========================================================
CREATE TABLE `admin_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `role` VARCHAR(30) NOT NULL DEFAULT 'control',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الكنترول الافتراضي: admin / admin123  أو  control / control123
INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$j.04CNpsfwAzVHZ5P1CesOXggsJjLahDbAm2tOs9ddDUiSEVFRggK', 'مدير الكنترول العام', 'control'),
(2, 'control', '$2y$10$j.04CNpsfwAzVHZ5P1CesOXggsJjLahDbAm2tOs9ddDUiSEVFRggK', 'أخصائي الكنترول الأكاديمي', 'control');

-- =========================================================
-- 8. الطلاب
-- =========================================================
CREATE TABLE `students` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `university_number` VARCHAR(50) NOT NULL,
    `full_name` VARCHAR(200) NOT NULL,
    `gender` ENUM('ذكر', 'أنثى') NOT NULL DEFAULT 'ذكر',
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(150) NULL,
    `birth_date` DATE NULL,
    `birth_place` VARCHAR(150) NULL,
    `village` VARCHAR(150) NULL,
    `identity_type_id` INT UNSIGNED NULL,
    `identity_number` VARCHAR(80) NOT NULL,
    `address` VARCHAR(255) NULL,
    `college_name` VARCHAR(150) NOT NULL DEFAULT 'كلية علوم الحاسوب',
    `program_id` INT UNSIGNED NOT NULL,
    `admission_year_id` INT UNSIGNED NULL,
    `current_academic_year_id` INT UNSIGNED NULL,
    `current_level_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `academic_status_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `photo` VARCHAR(255) NULL,
    `tuition_cleared` TINYINT(1) NOT NULL DEFAULT 1, -- 1: مسدد للرسوم تفتح درجاته، 0: غير مسدد تظهر أيقونة القفل
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_univ_num` (`university_number`),
    KEY `idx_student_name` (`full_name`),
    CONSTRAINT `fk_students_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_students_level` FOREIGN KEY (`current_level_id`) REFERENCES `academic_levels` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_students_status` FOREIGN KEY (`academic_status_id`) REFERENCES `academic_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 9. حسابات تسجيل الدخول للطلاب
-- =========================================================
CREATE TABLE `student_accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_account_student` (`student_id`),
    CONSTRAINT `fk_account_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 10. المقررات الدراسية
-- =========================================================
CREATE TABLE `courses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_code` VARCHAR(30) NOT NULL,
    `course_name` VARCHAR(150) NOT NULL,
    `credit_hours` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `course_type` VARCHAR(50) NOT NULL DEFAULT 'أساسي',
    `max_grade` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `pass_grade` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- المقررات العشرة الظاهرة في وثائق الكلية الرسمية (صفحات 9 و 10 و 11)
INSERT INTO `courses` (`id`, `course_code`, `course_name`, `credit_hours`, `course_type`, `max_grade`, `pass_grade`) VALUES
(1, 'MOB-311', 'تطوير تطبيقات الموبايل _عملي', 2, 'أساسي', 100.00, 50.00),
(2, 'MOB-312', 'تطوير تطبيقات الموبايل _نظري', 3, 'أساسي', 100.00, 50.00),
(3, 'WEB-311', 'تطوير الويب _عملي', 2, 'أساسي', 100.00, 50.00),
(4, 'WEB-312', 'تطوير الويب _نظري', 3, 'أساسي', 100.00, 50.00),
(5, 'SE-311', 'هندسة البرمجيات _عملي', 2, 'أساسي', 100.00, 50.00),
(6, 'SE-312', 'هندسة البرمجيات _نظري', 3, 'أساسي', 100.00, 50.00),
(7, 'AI-311', 'الذكاء الاصطناعي _عملي', 2, 'أساسي', 100.00, 50.00),
(8, 'AI-312', 'الذكاء الاصطناعي _نظري', 3, 'أساسي', 100.00, 50.00),
(9, 'SEC-311', 'أمن المعلومات _نظري', 3, 'أساسي', 100.00, 50.00),
(10, 'SEC-312', 'أمن المعلومات _عملي', 2, 'أساسي', 100.00, 50.00);

-- =========================================================
-- 11. ربط المقررات بالتخصص والمستوى والفصل (Course Offerings)
-- =========================================================
CREATE TABLE `course_offerings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_id` INT UNSIGNED NOT NULL,
    `program_id` INT UNSIGNED NOT NULL,
    `academic_level_id` INT UNSIGNED NOT NULL,
    `semester_id` INT UNSIGNED NOT NULL,
    `academic_year_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_offering` (`course_id`, `program_id`, `academic_level_id`, `semester_id`, `academic_year_id`),
    CONSTRAINT `fk_offering_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_offering_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- طرح المقررات العشرة للمستوى الثالث - ترم أول - تقنية المعلومات - العام 2026/2027
INSERT INTO `course_offerings` (`course_id`, `program_id`, `academic_level_id`, `semester_id`, `academic_year_id`)
SELECT id, 1, 3, 1, 3 FROM `courses` WHERE id BETWEEN 1 AND 10;

-- =========================================================
-- 12. تسجيل الطلاب في المقررات (Enrollments)
-- =========================================================
CREATE TABLE `student_course_enrollments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `course_offering_id` INT UNSIGNED NOT NULL,
    `enrollment_status` ENUM('enrolled', 'completed', 'dropped') NOT NULL DEFAULT 'enrolled',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_course` (`student_id`, `course_offering_id`),
    CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enroll_offering` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 13. جلسات المحاضرات (12 محاضرة مع التواريخ)
-- =========================================================
CREATE TABLE `lecture_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_offering_id` INT UNSIGNED NOT NULL,
    `lecture_number` TINYINT UNSIGNED NOT NULL,
    `session_date` DATE NOT NULL,
    `title` VARCHAR(150) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_course_lecture` (`course_offering_id`, `lecture_number`),
    CONSTRAINT `fk_session_offering` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 14. سجل الحضور والغياب للطلاب
-- =========================================================
CREATE TABLE `attendance_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `status` ENUM('present', 'absent', 'upcoming') NOT NULL DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_student` (`session_id`, `student_id`),
    CONSTRAINT `fk_att_session` FOREIGN KEY (`session_id`) REFERENCES `lecture_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 15. سندات الدفع والرسوم المالية (الرسوم الدراسية ورسوم أخرى)
-- =========================================================
CREATE TABLE `receipts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `receipt_number` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `category` ENUM('tuition', 'other') NOT NULL DEFAULT 'tuition',
    `payment_date` DATE NOT NULL,
    `amount_yer` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount_usd` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- محول بقسمة اليمني على 250
    `details` VARCHAR(255) NOT NULL DEFAULT 'رسوم دراسية',
    `notes` VARCHAR(255) NULL,
    `created_by_admin_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_receipt_num` (`receipt_number`),
    KEY `idx_receipt_student` (`student_id`),
    CONSTRAINT `fk_receipt_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 16. سجلات الدرجات (نصفية ونهائية) مع قفل الـ 15 يوماً
-- =========================================================
CREATE TABLE `grade_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `academic_level_id` INT UNSIGNED NOT NULL,
    `semester_id` INT UNSIGNED NOT NULL,
    `academic_year_id` INT UNSIGNED NOT NULL,
    -- عناصر الدرجات النصفية (المجموع 40)
    `attendance_grade` DECIMAL(5,2) NOT NULL DEFAULT 5.00,    -- الحضور (5)
    `participation_grade` DECIMAL(5,2) NOT NULL DEFAULT 5.00, -- المشاركة (5)
    `midterm_theory` DECIMAL(5,2) NOT NULL DEFAULT 0.00,      -- نصفي نظري (10)
    `midterm_practical` DECIMAL(5,2) NOT NULL DEFAULT 0.00,   -- نصفي عملي (10)
    `final_practical` DECIMAL(5,2) NOT NULL DEFAULT 0.00,     -- نهائي عملي (10)
    -- الامتحان النهائي (60)
    `final_theory` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    -- المحصلة النهائية
    `student_grade` DECIMAL(5,2) NOT NULL DEFAULT 0.00,       -- إجمالي الدرجة من 100
    `maximum_grade` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `passing_grade` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    `grade_letter` VARCHAR(30) NOT NULL DEFAULT 'ممتاز',
    `is_repeat` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_course_year` (`student_id`, `course_id`, `academic_level_id`, `semester_id`, `academic_year_id`),
    CONSTRAINT `fk_grade_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grade_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- البيانات الأولية: إدخال الطلاب المطابقين لوثائق الكلية
-- =========================================================

-- الطالب 1: محمد منصور أحمد العكاد (الظاهر في بطاقة البروفايل الرسمية - صفحة 2)
INSERT INTO `students` (
    `id`, `university_number`, `full_name`, `gender`, `phone`, `email`,
    `birth_date`, `birth_place`, `village`, `identity_type_id`, `identity_number`,
    `address`, `college_name`, `program_id`, `admission_year_id`, `current_academic_year_id`,
    `current_level_id`, `academic_status_id`, `photo`, `tuition_cleared`
) VALUES (
    1,
    '202412056542',
    'محمد منصور أحمد العكاد',
    'ذكر',
    '770612187 - 770017481',
    'moh123.gmail.com',
    '2004-04-07',
    'يريم_بيت العكاد',
    'بيت العكاد',
    1,
    '145854698578546',
    'يريم_المركزي',
    'كلية علوم الحاسوب',
    1, -- تقنية المعلومات
    1, -- 2024/2025
    3, -- 2026/2027
    3, -- المستوى الثالث
    1, -- طالب
    'uploads/students/mohammed_alokkad.jpg',
    0  -- غير مسدد لرسوم المستوى الثالث لإظهار القفل 🔒 كما في صفحة 4!
);

-- الطالب 2: محمد خالد السنفاني (الظاهر في كشوفات السندات والدرجات والغياب - صفحات 5، 6، 9، 10، 11)
INSERT INTO `students` (
    `id`, `university_number`, `full_name`, `gender`, `phone`, `email`,
    `birth_date`, `birth_place`, `village`, `identity_type_id`, `identity_number`,
    `address`, `college_name`, `program_id`, `admission_year_id`, `current_academic_year_id`,
    `current_level_id`, `academic_status_id`, `photo`, `tuition_cleared`
) VALUES (
    2,
    '20241088',
    'محمد خالد السنفاني',
    'ذكر',
    '771234567',
    'sanfani@aylol.edu.ye',
    '2003-08-15',
    'يريم',
    'يريم',
    1,
    '102938475612345',
    'يريم - الشارع العام',
    'كلية علوم الحاسوب',
    1, -- تقنية المعلومات
    1, -- 2024/2025
    3, -- 2026/2027
    3, -- المستوى الثالث
    1, -- طالب
    'uploads/students/mohammed_alokkad.jpg',
    1  -- مسدد كامل الرسوم (تفتح له كشوف الدرجات والغياب كاملة)
);

-- الطالب 3: طالب جديد غير مفعل حسابه لتجربة شاشة "إنشاء حساب" (الخدعة)
INSERT INTO `students` (
    `id`, `university_number`, `full_name`, `gender`, `phone`, `email`,
    `birth_date`, `birth_place`, `village`, `identity_type_id`, `identity_number`,
    `address`, `college_name`, `program_id`, `admission_year_id`, `current_academic_year_id`,
    `current_level_id`, `academic_status_id`, `photo`, `tuition_cleared`
) VALUES (
    3,
    '20241099',
    'أحمد علي عبدالله الحسام',
    'ذكر',
    '775555555',
    'ahmed@aylol.edu.ye',
    '2004-01-01',
    'إب',
    'العدين',
    1,
    '998877665544332',
    'إب - شارع العدين',
    'كلية علوم الحاسوب',
    1,
    1,
    3,
    2, -- المستوى الثاني
    1,
    NULL,
    1
);

-- كلمات المرور الافتراضية للطلاب المفعلين: 123456
-- $2y$10$wE9q.h7H7t0P.f8f04cbe.l4z9f0vQ4aZ6qYp2c3V1K0wI6hZ4XqK  =>  123456
INSERT INTO `student_accounts` (`student_id`, `password_hash`, `is_active`) VALUES
(1, '$2y$10$SI8tZAcFGzwI8IGogt3TnOwgGdFFCFlTMqA85sXHL1BDGv69z/ZFe', 1),
(2, '$2y$10$SI8tZAcFGzwI8IGogt3TnOwgGdFFCFlTMqA85sXHL1BDGv69z/ZFe', 1);
-- لاحظ أن الطالب رقم 3 ليس لديه حساب بعد! سيقوم بإنشائه عبر واجهة إنشاء حساب!

-- =========================================================
-- إدخال السندات المالية للطالب 2 (محمد خالد السنفاني) - مطابقة لصفحة 5 و 6
-- =========================================================
-- سندات الرسوم الدراسية (صفحة 5)
INSERT INTO `receipts` (`receipt_number`, `student_id`, `category`, `payment_date`, `amount_yer`, `amount_usd`, `details`) VALUES
(86,  2, 'tuition', '2024-08-01', 9000.00,  36.00,  'رسوم دراسية'),
(115, 2, 'tuition', '2024-08-02', 10000.00, 40.00,  'رسوم دراسية'),
(145, 2, 'tuition', '2024-08-05', 37500.00, 150.00, 'رسوم دراسية'),
(200, 2, 'tuition', '2024-09-30', 15000.00, 60.00,  'رسوم دراسية'),
(215, 2, 'tuition', '2024-09-25', 10000.00, 40.00,  'رسوم دراسية'),
(318, 2, 'tuition', '2024-09-19', 7000.00,  28.00,  'رسوم دراسية'),
(450, 2, 'tuition', '2024-09-18', 15000.00, 60.00,  'رسوم دراسية');

-- سندات الرسوم الأخرى (صفحة 6)
INSERT INTO `receipts` (`receipt_number`, `student_id`, `category`, `payment_date`, `amount_yer`, `amount_usd`, `details`) VALUES
(86,  2, 'other', '2024-08-01', 9000.00,  36.00,  'رسوم تسجيل و تنسيق'),
(115, 2, 'other', '2024-08-02', 10000.00, 40.00,  'رسوم بطاقة'),
(145, 2, 'other', '2024-08-05', 37500.00, 150.00, 'رسوم تأكيد قيد'),
(200, 2, 'other', '2024-09-30', 15000.00, 60.00,  'رسوم إعادة أختبار'),
(215, 2, 'other', '2024-09-25', 10000.00, 40.00,  'رسوم مخالفات'),
(318, 2, 'other', '2024-09-19', 7000.00,  28.00,  'رسوم عقوبة'),
(450, 2, 'other', '2024-09-18', 15000.00, 60.00,  'رسوم بطاقة');

-- سندات للطالب 1 (محمد منصور أحمد العكاد)
INSERT INTO `receipts` (`receipt_number`, `student_id`, `category`, `payment_date`, `amount_yer`, `amount_usd`, `details`) VALUES
(501, 1, 'tuition', '2024-08-10', 25000.00, 100.00, 'رسوم دراسية - قسط أول'),
(502, 1, 'other',   '2024-08-10', 10000.00, 40.00,  'رسوم بطاقة جامعية');

-- =========================================================
-- إدخال درجات الطالب 2 (محمد خالد السنفاني) - مطابقة لصفحة 10 و 11
-- =========================================================
INSERT INTO `grade_records` (
    `student_id`, `course_id`, `academic_level_id`, `semester_id`, `academic_year_id`,
    `attendance_grade`, `participation_grade`, `midterm_theory`, `midterm_practical`, `final_practical`, `final_theory`,
    `student_grade`, `maximum_grade`, `passing_grade`, `grade_letter`, `is_repeat`, `notes`
) VALUES
(2, 1,  3, 1, 3, 5.00, 5.00, 8.00,  9.00,  9.00,  52.00, 88.00, 100.00, 50.00, 'جيد جداً', 0, NULL),
(2, 2,  3, 1, 3, 4.00, 4.00, 8.00,  8.00,  8.00,  50.00, 82.00, 100.00, 50.00, 'جيد جداً', 0, NULL),
(2, 3,  3, 1, 3, 5.00, 5.00, 10.00, 10.00, 10.00, 54.00, 94.00, 100.00, 50.00, 'ممتاز',   0, NULL),
(2, 4,  3, 1, 3, 5.00, 5.00, 8.00,  9.00,  8.00,  50.00, 85.00, 100.00, 50.00, 'جيد جداً', 0, NULL),
(2, 5,  3, 1, 3, 5.00, 5.00, 9.00,  9.00,  9.00,  53.00, 90.00, 100.00, 50.00, 'ممتاز',   0, NULL),
(2, 6,  3, 1, 3, 4.00, 4.00, 7.00,  8.00,  7.00,  48.00, 78.00, 100.00, 50.00, 'جيد',     0, NULL),
(2, 7,  3, 1, 3, 5.00, 5.00, 9.00,  10.00, 9.00,  53.00, 91.00, 100.00, 50.00, 'ممتاز',   0, NULL),
(2, 8,  3, 1, 3, 4.00, 4.00, 7.00,  7.00,  7.00,  46.00, 75.00, 100.00, 50.00, 'جيد',     0, NULL),
(2, 9,  3, 1, 3, 5.00, 4.00, 8.00,  9.00,  8.00,  50.00, 84.00, 100.00, 50.00, 'جيد جداً', 0, NULL),
(2, 10, 3, 1, 3, 5.00, 5.00, 9.00,  9.00,  9.00,  52.00, 89.00, 100.00, 50.00, 'جيد جداً', 0, NULL);

-- درجات تجريبية للطالب 1
INSERT INTO `grade_records` (
    `student_id`, `course_id`, `academic_level_id`, `semester_id`, `academic_year_id`,
    `attendance_grade`, `participation_grade`, `midterm_theory`, `midterm_practical`, `final_practical`, `final_theory`,
    `student_grade`, `maximum_grade`, `passing_grade`, `grade_letter`, `is_repeat`, `notes`
) VALUES
(1, 1, 3, 1, 3, 5.00, 5.00, 10.00, 10.00, 9.00, 53.00, 92.00, 100.00, 50.00, 'ممتاز', 0, NULL),
(1, 2, 3, 1, 3, 4.00, 4.00, 8.00,  8.00,  9.00, 51.00, 84.00, 100.00, 50.00, 'جيد جداً', 0, NULL);

-- =========================================================
-- إدخال المحاضرات الـ 12 لكل مقرر
-- =========================================================
-- إنشاء 12 محاضرة لكل مقرر مطروح
INSERT INTO `lecture_sessions` (`course_offering_id`, `lecture_number`, `session_date`)
SELECT co.id, num.n, DATE_ADD('2026-09-10', INTERVAL (num.n - 1) * 4 DAY)
FROM `course_offerings` co
CROSS JOIN (
    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
    UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
) num;

-- تسجيل الطلاب في المقررات
INSERT INTO `student_course_enrollments` (`student_id`, `course_offering_id`)
SELECT 2, co.id FROM `course_offerings` co;

INSERT INTO `student_course_enrollments` (`student_id`, `course_offering_id`)
SELECT 1, co.id FROM `course_offerings` co;

-- =========================================================
-- إدخال سجل حضور وغياب الطالب 2 (مطابق لصفحة 9)
-- م1 إلى م12
-- =========================================================
-- تحضير تلقائي للـ 12 محاضرة مع مطابقات نسب الحضور في صفحة 9
-- مقرر 1: 100% حضور (طبيعي)
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, 'present'
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 1;

-- مقرر 2: 71% حضور (غياب م6 وم7) -> طبيعي
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 2;

-- مقرر 3: 43% حضور (غياب 4 محاضرات: م4، م5، م6، م7) -> إنذار
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (4,5,6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 3;

-- مقرر 4: 57% حضور (غياب 3 محاضرات: م5، م6، م7) -> إنذار
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (5,6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 4;

-- مقرر 5: 100% حضور (طبيعي)
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, 'present'
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 5;

-- مقرر 6: 71% حضور (غياب 2) -> طبيعي
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 6;

-- مقرر 7: 38% حضور (غياب 5 محاضرات: م4, م5, م6, م7, م8) -> حرمان فصلي
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (4,5,6,7,8) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 7;

-- مقرر 8: 38% حضور (غياب 5 محاضرات) -> حرمان فصلي
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (4,5,6,7,8) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 8;

-- مقرر 9: 57% حضور (غياب 3) -> إنذار
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (5,6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 9;

-- مقرر 10: 57% حضور (غياب 3) -> إنذار
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`)
SELECT ls.id, 2, CASE WHEN ls.lecture_number IN (5,6,7) THEN 'absent' ELSE 'present' END
FROM `lecture_sessions` ls
JOIN `course_offerings` co ON co.id = ls.course_offering_id
WHERE co.course_id = 10;
