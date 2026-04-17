-- ============================================================
-- School ERP Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `school_erp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_erp`;

-- ----------------------------
-- Table: users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)  NOT NULL,
  `email`       VARCHAR(100)  NOT NULL UNIQUE,
  `password`    VARCHAR(255)  NOT NULL,
  `role`        ENUM('admin','student','teacher','parent') NOT NULL,
  `status`      ENUM('active','inactive') DEFAULT 'active',
  `last_login`  DATETIME      DEFAULT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: classes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: sections
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sections` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `class_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(10)  NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: subjects
-- ----------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `code`       VARCHAR(20)  DEFAULT NULL,
  `class_id`   INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: parents
-- ----------------------------
CREATE TABLE IF NOT EXISTS `parents` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) DEFAULT NULL,
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `relation`   ENUM('Father','Mother','Guardian') DEFAULT 'Father',
  `address`    TEXT         DEFAULT NULL,
  `photo`      VARCHAR(255) DEFAULT NULL,
  `status`     ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: students
-- ----------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED DEFAULT NULL,
  `student_id`     VARCHAR(20)  NOT NULL UNIQUE,
  `name`           VARCHAR(100) NOT NULL,
  `email`          VARCHAR(100) DEFAULT NULL,
  `phone`          VARCHAR(20)  DEFAULT NULL,
  `dob`            DATE         DEFAULT NULL,
  `gender`         ENUM('Male','Female','Other') DEFAULT NULL,
  `blood_group`    VARCHAR(5)   DEFAULT NULL,
  `address`        TEXT         DEFAULT NULL,
  `class_id`       INT UNSIGNED DEFAULT NULL,
  `section_id`     INT UNSIGNED DEFAULT NULL,
  `parent_id`      INT UNSIGNED DEFAULT NULL,
  `photo`          VARCHAR(255) DEFAULT NULL,
  `admission_date` DATE         DEFAULT NULL,
  `status`         ENUM('active','inactive') DEFAULT 'active',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_id`)  REFERENCES `parents`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: teachers
-- ----------------------------
CREATE TABLE IF NOT EXISTS `teachers` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED DEFAULT NULL,
  `teacher_id`    VARCHAR(20)  DEFAULT NULL UNIQUE,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) DEFAULT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `gender`        ENUM('Male','Female','Other') DEFAULT NULL,
  `qualification` VARCHAR(100) DEFAULT NULL,
  `subject_id`    INT UNSIGNED DEFAULT NULL,
  `class_id`      INT UNSIGNED DEFAULT NULL,
  `photo`         VARCHAR(255) DEFAULT NULL,
  `address`       TEXT         DEFAULT NULL,
  `status`        ENUM('active','inactive') DEFAULT 'active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: admissions
-- ----------------------------
CREATE TABLE IF NOT EXISTS `admissions` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `application_id` VARCHAR(20) NOT NULL UNIQUE,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) DEFAULT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `dob`           DATE         DEFAULT NULL,
  `gender`        ENUM('Male','Female','Other') DEFAULT NULL,
  `class_applying` INT UNSIGNED DEFAULT NULL,
  `previous_school` VARCHAR(200) DEFAULT NULL,
  `address`       TEXT         DEFAULT NULL,
  `parent_name`   VARCHAR(100) DEFAULT NULL,
  `parent_phone`  VARCHAR(20)  DEFAULT NULL,
  `parent_email`  VARCHAR(100) DEFAULT NULL,
  `document`      VARCHAR(255) DEFAULT NULL,
  `status`        ENUM('pending','approved','rejected') DEFAULT 'pending',
  `remarks`       TEXT         DEFAULT NULL,
  `reviewed_by`   INT UNSIGNED DEFAULT NULL,
  `reviewed_at`   DATETIME     DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_applying`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: exams
-- ----------------------------
CREATE TABLE IF NOT EXISTS `exams` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `type`       ENUM('Unit Test','Mid Term','Final','Other') DEFAULT 'Unit Test',
  `class_id`   INT UNSIGNED DEFAULT NULL,
  `start_date` DATE         DEFAULT NULL,
  `end_date`   DATE         DEFAULT NULL,
  `status`     ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: marks
-- ----------------------------
CREATE TABLE IF NOT EXISTS `marks` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `exam_id`        INT UNSIGNED NOT NULL,
  `student_id`     INT UNSIGNED NOT NULL,
  `subject_id`     INT UNSIGNED NOT NULL,
  `marks_obtained` DECIMAL(6,2) DEFAULT 0,
  `max_marks`      DECIMAL(6,2) DEFAULT 100,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_mark` (`exam_id`,`student_id`,`subject_id`),
  FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: results
-- ----------------------------
CREATE TABLE IF NOT EXISTS `results` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `exam_id`      INT UNSIGNED NOT NULL,
  `student_id`   INT UNSIGNED NOT NULL,
  `total_marks`  DECIMAL(8,2) DEFAULT 0,
  `max_marks`    DECIMAL(8,2) DEFAULT 0,
  `percentage`   DECIMAL(5,2) DEFAULT 0,
  `grade`        VARCHAR(5)   DEFAULT NULL,
  `result`       ENUM('Pass','Fail') DEFAULT 'Pass',
  `remarks`      TEXT         DEFAULT NULL,
  `published`    TINYINT(1)   DEFAULT 0,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_result` (`exam_id`,`student_id`),
  FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: attendance
-- ----------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `class_id`   INT UNSIGNED DEFAULT NULL,
  `section_id` INT UNSIGNED DEFAULT NULL,
  `date`       DATE         NOT NULL,
  `status`     ENUM('Present','Absent','Late','Holiday') DEFAULT 'Present',
  `marked_by`  INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: fees
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fees` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT UNSIGNED NOT NULL,
  `fee_type`    VARCHAR(100) NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL,
  `due_date`    DATE          DEFAULT NULL,
  `status`      ENUM('pending','paid','overdue','partial') DEFAULT 'pending',
  `invoice_no`  VARCHAR(20)   NOT NULL UNIQUE,
  `created_by`  INT UNSIGNED  DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: transactions
-- ----------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fee_id`         INT UNSIGNED  DEFAULT NULL,
  `student_id`     INT UNSIGNED  DEFAULT NULL,
  `amount`         DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50)   DEFAULT 'PayU',
  `txn_id`         VARCHAR(100)  DEFAULT NULL,
  `payu_txn_id`    VARCHAR(100)  DEFAULT NULL,
  `payu_response`  TEXT          DEFAULT NULL,
  `status`         ENUM('success','failed','pending') DEFAULT 'pending',
  `payment_date`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`fee_id`)     REFERENCES `fees`(`id`)     ON DELETE SET NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: notifications
-- ----------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED  DEFAULT NULL,
  `role`       ENUM('admin','student','teacher','parent','all') DEFAULT NULL,
  `title`      VARCHAR(255)  NOT NULL,
  `message`    TEXT          NOT NULL,
  `type`       VARCHAR(50)   DEFAULT 'info',
  `is_read`    TINYINT(1)    DEFAULT 0,
  `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: otps
-- ----------------------------
CREATE TABLE IF NOT EXISTS `otps` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(100) NOT NULL,
  `otp`        VARCHAR(10)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `is_used`    TINYINT(1)   DEFAULT 0,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: settings
-- ----------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key`  VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT        DEFAULT NULL,
  `updated_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: gallery
-- ----------------------------
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(200)  DEFAULT NULL,
  `image`       VARCHAR(255)  NOT NULL,
  `sort_order`  INT UNSIGNED  DEFAULT 0,
  `is_active`   TINYINT(1)    DEFAULT 1,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: notices
-- ----------------------------
CREATE TABLE IF NOT EXISTS `notices` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255)  NOT NULL,
  `body`        TEXT          DEFAULT NULL,
  `category`    ENUM('general','exam','event','holiday','admission') DEFAULT 'general',
  `is_active`   TINYINT(1)    DEFAULT 1,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: testimonials
-- ----------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)  NOT NULL,
  `role`        VARCHAR(100)  DEFAULT 'Student',
  `message`     TEXT          NOT NULL,
  `photo`       VARCHAR(255)  DEFAULT NULL,
  `rating`      TINYINT(1)    DEFAULT 5,
  `is_active`   TINYINT(1)    DEFAULT 1,
  `sort_order`  INT UNSIGNED  DEFAULT 0,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: fee_categories
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fee_categories` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`                VARCHAR(100)   NOT NULL,
  `description`         TEXT           DEFAULT NULL,
  `amount`              DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `class_id`            INT UNSIGNED   DEFAULT NULL COMMENT 'NULL = all classes',
  `apply_on_admission`  TINYINT(1)     DEFAULT 0 COMMENT '1 = auto-apply when admission approved',
  `is_active`           TINYINT(1)     DEFAULT 1,
  `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: teacher_subjects (many-to-many)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `class_id`   INT UNSIGNED DEFAULT NULL,
  UNIQUE KEY `unique_ts` (`teacher_id`,`subject_id`,`class_id`),
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default admin user (password: Admin@123)
INSERT INTO `users` (`name`,`email`,`password`,`role`,`status`) VALUES
('Super Admin','admin@school.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','active');

-- Classes 1-12
INSERT INTO `classes` (`name`) VALUES
('Class 1'),('Class 2'),('Class 3'),('Class 4'),('Class 5'),
('Class 6'),('Class 7'),('Class 8'),('Class 9'),('Class 10'),
('Class 11'),('Class 12');

-- Sections for each class
INSERT INTO `sections` (`class_id`,`name`) VALUES
(1,'A'),(1,'B'),(2,'A'),(2,'B'),(3,'A'),(3,'B'),
(4,'A'),(4,'B'),(5,'A'),(5,'B'),(6,'A'),(6,'B'),
(7,'A'),(7,'B'),(8,'A'),(8,'B'),(9,'A'),(9,'B'),
(10,'A'),(10,'B'),(11,'A'),(11,'B'),(12,'A'),(12,'B');

-- Common Subjects
INSERT INTO `subjects` (`name`,`code`,`class_id`) VALUES
('Mathematics','MATH',NULL),('English','ENG',NULL),('Science','SCI',NULL),
('Social Studies','SS',NULL),('Hindi','HIN',NULL),('Computer Science','CS',NULL),
('Physics','PHY',NULL),('Chemistry','CHEM',NULL),('Biology','BIO',NULL),
('History','HIST',NULL),('Geography','GEO',NULL),('Economics','ECO',NULL);

-- Default settings
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','School ERP System'),
('site_tagline','Empowering Education'),
('site_url','http://localhost/school-erp'),
('site_logo',''),
('site_favicon',''),
('footer_text','© 2026 School ERP. All rights reserved.'),
('contact_email','info@school.com'),
('contact_phone','+91 9000000000'),
('contact_address','123 School Street, City, Country'),
('smtp_host','smtp.gmail.com'),
('smtp_port','587'),
('smtp_user',''),
('smtp_pass',''),
('smtp_from','noreply@school.com'),
('smtp_from_name','School ERP'),
('smtp_encryption','tls'),
('payu_merchant_key',''),
('payu_merchant_salt',''),
('payu_mode','test'),
('payu_surl',''),
('payu_furl',''),
('meta_title','School ERP - Complete School Management System'),
('meta_description','A complete school management system for students, teachers, and parents.'),
('meta_keywords','school erp, school management, student portal'),
('pass_percentage','33'),
('academic_year','2025-2026'),
('currency','INR'),
('currency_symbol','₹'),
-- Landing page settings
('lp_hero_title','Welcome to Our School'),
('lp_hero_subtitle','Empowering students with quality education, values, and excellence since 2000.'),
('lp_hero_image',''),
('lp_hero_btn1_text','Apply for Admission'),
('lp_hero_btn1_url','/public/admission.php'),
('lp_hero_btn2_text','Check Status'),
('lp_hero_btn2_url','/public/admission-status.php'),
('lp_about_title','About Our School'),
('lp_about_text','We are committed to providing a nurturing, inclusive learning environment where every student can discover their potential and grow into confident, capable individuals. Our experienced faculty, modern facilities, and holistic curriculum set us apart.'),
('lp_about_image',''),
('lp_stat_students','1200+'),
('lp_stat_teachers','80+'),
('lp_stat_years','25+'),
('lp_stat_success','98%'),
('lp_stat_label1','Students Enrolled'),
('lp_stat_label2','Expert Teachers'),
('lp_stat_label3','Years of Excellence'),
('lp_stat_label4','Pass Rate'),
('lp_show_gallery','1'),
('lp_show_notices','1'),
('lp_show_about','1'),
('lp_show_stats','1'),
('lp_primary_color','#0d6efd'),
('lp_cta_title','Start Your Journey With Us'),
('lp_cta_text','Applications for the new academic session are now open. Secure your child''s future with us.'),
-- Extra public pages settings
('lp_show_testimonials','1'),
('lp_show_courses','1'),
('about_mission','To provide every child with an exceptional education that fosters curiosity, creativity, critical thinking, and strong values — preparing them for a rapidly changing world.'),
('about_vision','To be a leading institution that nurtures future-ready, responsible global citizens through academic excellence and character development.'),
('contact_map_embed',''),
('social_facebook',''),
('social_twitter',''),
('social_instagram',''),
('social_youtube',''),
('fee_apply_on_admission','1');

-- Default fee categories
INSERT INTO `fee_categories` (`name`,`description`,`amount`,`class_id`,`apply_on_admission`,`is_active`) VALUES
('Admission Fee','One-time fee charged at the time of admission',5000.00,NULL,1,1),
('Tuition Fee','Monthly tuition fee',2000.00,NULL,0,1),
('Annual Charges','Annual maintenance and development fee',3000.00,NULL,1,1),
('Library Fee','Annual library subscription fee',500.00,NULL,0,1);

-- Default sample notices
INSERT INTO `notices` (`title`,`body`,`category`,`is_active`) VALUES
('Admission Open for 2025-26','Applications for all classes are now open. Apply online today.','admission',1),
('Annual Sports Day','Annual Sports Day will be held on 15th March. All students are requested to participate.','event',1),
('Mid-Term Examination Schedule','Mid-term examinations begin from 10th March. Time table is available in the school office.','exam',1);

-- Default testimonials
INSERT INTO `testimonials` (`name`,`role`,`message`,`rating`,`is_active`,`sort_order`) VALUES
('Priya Sharma','Parent of Class 10 Student','This school has transformed my child completely. The teachers are dedicated and the environment is very supportive. Highly recommended!',5,1,1),
('Rahul Verma','Class 12 Graduate','I scored 95% in boards thanks to the excellent faculty here. The smart classrooms and lab facilities are world-class.',5,1,2),
('Sunita Patel','Parent of Class 6 Student','Very happy with the holistic development approach. My daughter loves going to school every day. The staff is always approachable.',5,1,3),
('Amit Singh','Class 11 Student','The teachers here genuinely care about each student. Extra classes, doubt sessions, and personal attention helped me improve a lot.',5,1,4);

SET FOREIGN_KEY_CHECKS = 1;
