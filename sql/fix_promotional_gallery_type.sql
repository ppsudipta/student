-- Fix promotional images that admin shows but API hides.
-- Cause: inserts used type "Promotional" while ENUM only allows "promotional"/"gallery".

UPDATE `gallery`
SET `type` = 'promotional'
WHERE (`image` LIKE 'promotional/%' OR `image` LIKE '%/promotional/%')
  AND LOWER(COALESCE(`type`, '')) <> 'promotional';
