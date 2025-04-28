-- Add new columns to the entries table
ALTER TABLE entries 
ADD COLUMN logo_url VARCHAR(255) DEFAULT NULL COMMENT 'Path to company logo file' AFTER image_url,
ADD COLUMN linkedin_url VARCHAR(255) DEFAULT NULL COMMENT 'LinkedIn profile URL' AFTER website_url,
ADD COLUMN twitter_url VARCHAR(255) DEFAULT NULL COMMENT 'Twitter/X profile URL' AFTER linkedin_url,
ADD COLUMN facebook_url VARCHAR(255) DEFAULT NULL COMMENT 'Facebook page URL' AFTER twitter_url,
ADD COLUMN has_careers_page TINYINT(1) DEFAULT 0 COMMENT 'Whether the company has a careers page' AFTER facebook_url,
ADD COLUMN careers_url VARCHAR(255) DEFAULT NULL COMMENT 'URL to careers page' AFTER has_careers_page,
ADD COLUMN has_press_page TINYINT(1) DEFAULT 0 COMMENT 'Whether the company has a press release page' AFTER careers_url,
ADD COLUMN press_url VARCHAR(255) DEFAULT NULL COMMENT 'URL to press release page' AFTER has_press_page;

-- Create directory for logo uploads if it doesn't exist
-- Note: This needs to be executed from the command line, not MySQL:
-- mkdir -p /Applications/MAMP/htdocs/biotechwales/uploads/logos 