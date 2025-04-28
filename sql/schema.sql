-- Database Schema for Biotech Wales

-- Create database (uncomment for new installation)
-- CREATE DATABASE directory_db;
-- USE directory_db;

-- Table: admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Table: categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Table: entries
CREATE TABLE IF NOT EXISTS entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    location VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    website_url VARCHAR(255),
    image_url VARCHAR(255),
    logo_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    twitter_url VARCHAR(255),
    facebook_url VARCHAR(255),
    has_careers_page TINYINT(1) DEFAULT 0,
    careers_url VARCHAR(255),
    has_press_page TINYINT(1) DEFAULT 0,
    press_url VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table: contact_messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Insert some example categories
INSERT INTO categories (name) 
SELECT 'Biotechnology' 
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Biotechnology');

INSERT INTO categories (name) 
SELECT 'Agritech' 
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Agritech');

INSERT INTO categories (name) 
SELECT 'Pharmaceuticals' 
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Pharmaceuticals');

INSERT INTO categories (name) 
SELECT 'Medical Devices' 
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Medical Devices');

INSERT INTO categories (name) 
SELECT 'Research Institutes' 
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Research Institutes');

-- Insert some example biotech company entries
INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Cymru Biogen', 
    'Innovative biotech solutions for agriculture and healthcare. Cymru Biogen specializes in developing cutting-edge biological treatments that promote sustainability and improve health outcomes across Wales.', 
    (SELECT id FROM categories WHERE name = 'Biotechnology'), 
    'Cardiff', 
    'info@cymrubio.com', 
    '+44 29 1234 5678',
    'https://www.cymrubio.com',
    'published',
    'Cymru Biogen | Innovative Biotech Solutions in Cardiff',
    'Cymru Biogen is a leading Welsh biotechnology company based in Cardiff, developing innovative solutions for agriculture and healthcare.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Cymru Biogen');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'AberDNA Labs', 
    'Leading genetics research firm in Wales. Our state-of-the-art laboratories in Aberystwyth are dedicated to advancing genomic science and developing new diagnostic tools for genetic disorders.', 
    (SELECT id FROM categories WHERE name = 'Research Institutes'), 
    'Aberystwyth', 
    'contact@aberdnalabs.co.uk', 
    '+44 1970 123456',
    'https://www.aberdnalabs.co.uk',
    'published',
    'AberDNA Labs | Advanced Genetics Research in Aberystwyth',
    'AberDNA Labs is a premier genetics research firm based in Aberystwyth, Wales, specializing in genomic science and diagnostic tools.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'AberDNA Labs');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Snowdonia Biotech', 
    'Developing eco-friendly pharmaceuticals derived from native Welsh plant species. Our research team is focused on discovering natural compounds with therapeutic potential while preserving the unique biodiversity of Snowdonia.', 
    (SELECT id FROM categories WHERE name = 'Pharmaceuticals'), 
    'Bangor', 
    'info@snowdoniabio.com', 
    '+44 1248 789012',
    'https://www.snowdoniabio.com',
    'published',
    'Snowdonia Biotech | Eco-friendly Pharmaceuticals from Bangor',
    'Snowdonia Biotech specializes in developing sustainable, eco-friendly pharmaceuticals derived from native Welsh plant species in Bangor.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Snowdonia Biotech');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Brecon Cellworks', 
    'Focused on cellular therapies for rare diseases affecting children. Our team combines expertise in stem cell research and regenerative medicine to develop innovative treatments for previously untreatable conditions.', 
    (SELECT id FROM categories WHERE name = 'Biotechnology'), 
    'Brecon', 
    'support@breconcellworks.com', 
    '+44 1874 567890',
    'https://www.breconcellworks.com',
    'published',
    'Brecon Cellworks | Cellular Therapies for Rare Diseases',
    'Brecon Cellworks is dedicated to developing cellular therapies and regenerative medicine solutions for rare diseases affecting children.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Brecon Cellworks');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Swansea GeneTech', 
    'Pioneering genetic treatments for chronic illnesses prevalent in Wales. Our research focuses on personalized medicine approaches, using genetic markers to develop tailored therapies for conditions with high prevalence in the Welsh population.', 
    (SELECT id FROM categories WHERE name = 'Biotechnology'), 
    'Swansea', 
    'hello@swanseagenetech.uk', 
    '+44 1792 654321',
    'https://www.swanseagenetech.uk',
    'published',
    'Swansea GeneTech | Genetic Treatments for Chronic Illnesses',
    'Swansea GeneTech pioneers genetic treatments and personalized medicine approaches for chronic illnesses prevalent in the Welsh population.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Swansea GeneTech');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Newport AgriSolutions', 
    'Developing advanced crop protection technologies for Welsh farmers. Our sustainable solutions help improve crop yields while reducing environmental impact, supporting the agricultural sector across Wales.', 
    (SELECT id FROM categories WHERE name = 'Agritech'), 
    'Newport', 
    'info@newportagri.wales', 
    '+44 1633 112233',
    'https://www.newportagri.wales',
    'published',
    'Newport AgriSolutions | Advanced Crop Protection for Welsh Farmers',
    'Newport AgriSolutions develops sustainable crop protection technologies to improve yields and reduce environmental impact for Welsh farmers.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Newport AgriSolutions');

INSERT INTO entries (name, description, category_id, location, email, phone, website_url, status, meta_title, meta_description) 
SELECT 
    'Pembroke MedTech', 
    'Welsh innovators in medical device technology. We design and manufacture advanced diagnostic equipment and therapeutic devices, focusing on improving patient outcomes in rural healthcare settings.', 
    (SELECT id FROM categories WHERE name = 'Medical Devices'), 
    'Pembroke', 
    'contact@pembrokemedtech.com', 
    '+44 1646 445566',
    'https://www.pembrokemedtech.com',
    'published',
    'Pembroke MedTech | Innovative Medical Device Technology for Rural Healthcare',
    'Pembroke MedTech designs and manufactures advanced diagnostic equipment and therapeutic devices optimized for rural healthcare settings in Wales.'
WHERE NOT EXISTS (SELECT 1 FROM entries WHERE name = 'Pembroke MedTech');
