-- Add status field to entries table
ALTER TABLE `entries` 
ADD COLUMN `status` ENUM('draft', 'published', 'archived') DEFAULT 'published';

-- Update all existing entries to published status
UPDATE `entries` SET `status` = 'published' WHERE `status` IS NULL;

-- Make sure all entries have a location value
UPDATE `entries` SET `location` = 'Wales' WHERE `location` IS NULL OR `location` = '';

-- Verify that all appropriate fields are not null
UPDATE `entries` SET `name` = CONCAT('Biotech Entry ', `id`) WHERE `name` IS NULL OR `name` = '';
UPDATE `entries` SET `description` = 'No description available.' WHERE `description` IS NULL OR `description` = ''; 