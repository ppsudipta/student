-- Unread admin replies for student app enquiry list.
-- Run on production DB (student_db / tea).

ALTER TABLE `enquiries`
  ADD COLUMN `student_seen` tinyint(1) NOT NULL DEFAULT 1 AFTER `replied_by`;

UPDATE `enquiries`
SET `student_seen` = 0
WHERE `reply_message` IS NOT NULL AND TRIM(`reply_message`) <> '';
