-- Run once on the database (phpMyAdmin or mysql CLI).
-- Adds threaded enquiry messages and backfills from existing enquiries rows.

CREATE TABLE IF NOT EXISTS `enquiry_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `sender_type` enum('student','admin') NOT NULL,
  `sender_name` varchar(110) DEFAULT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `enquiry_id` (`enquiry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Backfill initial student messages
INSERT INTO `enquiry_messages` (`enquiry_id`, `sender_type`, `sender_name`, `message`, `attachment`, `created_at`)
SELECT e.id, 'student', e.name, e.message, e.attachment, e.created_at
FROM `enquiries` e
WHERE NOT EXISTS (
  SELECT 1 FROM `enquiry_messages` m WHERE m.enquiry_id = e.id AND m.sender_type = 'student'
);

-- Backfill legacy admin replies as thread messages
INSERT INTO `enquiry_messages` (`enquiry_id`, `sender_type`, `sender_name`, `message`, `created_at`)
SELECT e.id, 'admin', COALESCE(e.replied_by, 'Admin'), e.reply_message, COALESCE(e.replied_at, e.created_at)
FROM `enquiries` e
WHERE e.reply_message IS NOT NULL AND e.reply_message != ''
  AND NOT EXISTS (
    SELECT 1 FROM `enquiry_messages` m
    WHERE m.enquiry_id = e.id AND m.sender_type = 'admin' AND m.message = e.reply_message
  );
