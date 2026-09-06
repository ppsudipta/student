-- Device tokens for FCM push (separate from notices content).
-- Run on the same database used by laravel-api (students/notices).

CREATE TABLE IF NOT EXISTS `student_device_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `token` varchar(512) NOT NULL,
  `platform` varchar(32) NOT NULL DEFAULT 'android',
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_device_tokens_token_unique` (`token`),
  KEY `student_device_tokens_student_id_index` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
