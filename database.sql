-- LUMINAS HAIR & BEAUTY DATABASE SCHEMA
-- Drop database if exists (be careful with this in production)
-- DROP DATABASE IF EXISTS luminas_db;

-- Create database
CREATE DATABASE IF NOT EXISTS luminas_db;
USE luminas_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'images.png',
    user_type ENUM('customer', 'admin', 'staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Service categories
CREATE TABLE IF NOT EXISTS service_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id) ON DELETE SET NULL
);

-- Staff table
CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    specialization TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Staff services (which staff can perform which services)
CREATE TABLE IF NOT EXISTS staff_services (
    staff_service_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    UNIQUE KEY (staff_id, service_id)
);

-- Business hours
CREATE TABLE IF NOT EXISTS business_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    open_time TIME,
    close_time TIME,
    is_closed BOOLEAN DEFAULT FALSE,
    UNIQUE KEY (day_of_week)
);

-- Staff availability
CREATE TABLE IF NOT EXISTS staff_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week INT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- Special dates (holidays, special events)
CREATE TABLE IF NOT EXISTS special_dates (
    date_id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (date)
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    staff_id INT,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- Contact messages
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT,
    staff_id INT,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE SET NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
);

-- Promotions
CREATE TABLE IF NOT EXISTS promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5, 2),
    discount_amount DECIMAL(10, 2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    promo_code VARCHAR(50) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gallery
CREATE TABLE IF NOT EXISTS gallery (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial data

-- Insert service categories
INSERT INTO service_categories (name, description,image) VALUES
('Hair', 'All hair related services including cutting, styling, coloring, and treatments','selective-focus-at-liquid-hair-color-of-hair-stylish-while-hairdressing-and-put-care-treatment-while-styling-curly-hair-for-client-professional-occupation-beauty-and-fashion-service-during-covid-19-free-photo.jpg'),
('Facial', 'Facial treatments for all skin types','shutterstock_352907567-645x423.jpg'),
('Nails', 'Manicure and pedicure services','qwegrgre.jpg'),
('Makeup', 'Professional makeup services for all occasions','salon-service-makeup.jpg'),
('Massage', 'Relaxing and therapeutic massage services','massage.jpg');

-- Insert sample services
INSERT INTO services (category_id, name, description, price, duration, is_active, image) VALUES
(1, 'Haircut & Style', 'Professional haircut and styling service', 45.00, 45, TRUE,'AdobeStock_302850609-1-1024x683.jpg'),
(1, 'Hair Coloring', 'Full hair coloring service', 85.00, 120, TRUE,'service1.jpg'),
(1, 'Blowout', 'Professional blowout styling', 35.00, 30, TRUE,'jessica-dabrowski-W6cwaL7PMSw-unsplash-1-1-1100x1650.jpg'),
(2, 'Classic Facial', 'Deep cleansing facial treatment', 65.00, 60, TRUE,'engin-akyurt-g-m8EDc4X6Q-unsplash.jpg'),
(2, 'Anti-Aging Facial', 'Specialized facial to reduce signs of aging', 85.00, 75, TRUE,'Intense-Hydrating-Facial-scaled.jpg'),
(3, 'Classic Manicure', 'Nail shaping, cuticle care, and polish', 25.00, 30, TRUE,'20250301-female-hands-with-beautiful-natural-nails-how-to-do-a-home-manicure-1030x687.png'),
(3, 'Gel Manicure', 'Long-lasting gel polish manicure', 35.00, 45, TRUE,'60560439cf2a08c10856a85a414543ed.jpg'),
(4, 'Bridal Makeup', 'Full makeup application for the bride', 120.00, 90, TRUE,'k.jpg'),
(4, 'Special Occasion Makeup', 'Makeup application for special events', 75.00, 60, TRUE,'79991169.jpg'),
(5, 'Swedish Massage', 'Classic relaxation massage', 70.00, 60, TRUE,'masaz-sportowy-szczecin-treningowe.jpg'),
(5, 'Deep Tissue Massage', 'Therapeutic massage focusing on deep muscle tension', 85.00, 60, TRUE,'massage-therapy-guide-1440x810.jpg');

-- Set default business hours
INSERT INTO business_hours (day_of_week, open_time, close_time, is_closed) VALUES
(0, NULL, NULL, TRUE),          -- Sunday (closed)
(1, '09:00:00', '19:00:00', FALSE), -- Monday
(2, '09:00:00', '19:00:00', FALSE), -- Tuesday
(3, '09:00:00', '19:00:00', FALSE), -- Wednesday
(4, '09:00:00', '19:00:00', FALSE), -- Thursday
(5, '09:00:00', '20:00:00', FALSE), -- Friday
(6, '10:00:00', '17:00:00', FALSE); -- Saturday

-- First, insert user accounts for staff members with the updated names
INSERT INTO users (username, email, password, first_name, last_name, phone, user_type) VALUES
('kaveesha.geeganarachchi', 'kaveesha.g@luminasbeauty.lk', '$2y$10$hxK1ypXrSXWrt.vQJsUSReKuQwLW3IMP8DJHNvk0wxU0uiAF1q4Cm', 'Kaveesha', 'Geeganarachchi', '+94712345678', 'staff'),
('iuri.dinushani', 'iuri.d@luminasbeauty.lk', '$2y$10$ZTsWUvvKGvXMHKdIBQr3eOTLLnCIyQYeG8KpPQUJl8iWI7YeDUyOG', 'Iuri', 'Dinushani', '+94723456789', 'staff'),
('sawbhagya.keller', 'sawbhagya.k@luminasbeauty.lk', '$2y$10$9WXILqLDFBd8DuTsWZXJWeJPlJf8eO0C89AEXmDMDGO6X2.cQ3QQG', 'Sawbhagya', 'Keller', '+94734567890', 'staff'),
('nihal.jayasinghe', 'nihal.jayasinghe@luminasbeauty.lk', '$2y$10$rX3MeYcTG.Jk7qgBvYA1K.3dNMJVwW1Kfr8CwTSZz2qM9fvOIK2Ra', 'Nihal', 'Jayasinghe', '+94745678901', 'staff'),
('malik.gunawardana', 'malik.gunawardana@luminasbeauty.lk', '$2y$10$BKZcPXvGeAxNzSX7OmwbPuGnz5q1FSmqOPwn8j40sROWW5J0G/9Re', 'Malik', 'Gunawardana', '+94756789012', 'staff'),
('anjali.bandara', 'anjali.bandara@luminasbeauty.lk', '$2y$10$O5HnPXZf3yQwLJd/JxJxZOx1mfZqiPxJCLKgmpAIxZl1Kj5F3jDXe', 'Anjali', 'Bandara', '+94767890123', 'staff'),
('chaminda.fonseka', 'chaminda.fonseka@luminasbeauty.lk', '$2y$10$Vf5aHQs.AaKvQbTKiMbW0OubZ5XAQtqLEZ/k9f5RuPXOtRymS8ZHO', 'Chaminda', 'Fonseka', '+94778901234', 'staff'),
('nimali.wickrama', 'nimali.wickrama@luminasbeauty.lk', '$2y$10$f7DPYSgWDDm0.FtVTZMK1.ZXvw/pGV0XLh/wqUlKJ.CJoUFT86HcC', 'Nimali', 'Wickrama', '+94789012345', 'staff');

-- Now insert staff details with updated bios for the new names
INSERT INTO staff (user_id, bio, specialization, is_active) VALUES
(1, 'Kaveesha Geeganarachchi is a senior hair stylist with over 10 years of experience in cutting-edge hair designs and coloring techniques. She has trained in London and Paris, bringing international hair fashion trends to our salon.', 'Hair Cutting, Hair Coloring, Bridal Hair Styling', TRUE),

(2, 'Iuri Dinushani specializes in facial treatments with a focus on anti-aging and skin rejuvenation. She is certified in advanced skincare techniques and always stays updated with the latest beauty technology.', 'Anti-Aging Facials, Skin Rejuvenation, Chemical Peels', TRUE),

(3, 'Sawbhagya Keller is our nail art expert with a creative eye for design. Her detailed work and steady hands make her a favorite among clients looking for unique nail designs and perfect manicures.', 'Nail Art, Gel Extensions, Manicures, Pedicures', TRUE),

(4, 'Nihal is a master barber who combines traditional techniques with modern styles. He specializes in men\'s grooming and beard styling, providing a luxury experience for male clients.', 'Men\'s Haircuts, Beard Grooming, Hot Towel Shaves', TRUE),

(5, 'Malik is our resident massage therapist with expertise in various massage techniques. His therapeutic approach helps clients relieve stress and tension while promoting overall wellness.', 'Swedish Massage, Deep Tissue Massage, Hot Stone Therapy', TRUE),

(6, 'Anjali is a professional makeup artist who has worked with celebrities and at fashion events. Her expertise in creating flawless looks makes her our go-to specialist for bridal and special occasion makeup.', 'Bridal Makeup, Special Occasion Makeup, Editorial Makeup', TRUE),

(7, 'Chaminda specializes in hair treatments and hair restoration. His knowledge of hair health and innovative treatment methods helps clients with damaged hair or hair loss concerns.', 'Hair Treatments, Scalp Therapy, Hair Extensions', TRUE),

(8, 'Nimali is a versatile beautician with skills spanning multiple services. Her attention to detail and commitment to client satisfaction make her a popular choice for various beauty treatments.', 'Waxing, Threading, Lash Extensions, Brow Styling', TRUE);

-- Now link staff to the services they can perform
-- For staff member 1 (Kaveesha) - Hair services
INSERT INTO staff_services (staff_id, service_id) VALUES
(1, 1), -- Haircut & Style
(1, 2), -- Hair Coloring
(1, 3); -- Blowout

-- For staff member 2 (Iuri) - Facial services
INSERT INTO staff_services (staff_id, service_id) VALUES
(2, 4), -- Classic Facial
(2, 5); -- Anti-Aging Facial

-- For staff member 3 (Sawbhagya) - Nail services
INSERT INTO staff_services (staff_id, service_id) VALUES
(3, 6), -- Classic Manicure
(3, 7); -- Gel Manicure

-- For staff member 4 (Nihal) - Hair services (focusing on men's)
INSERT INTO staff_services (staff_id, service_id) VALUES
(4, 1); -- Haircut & Style

-- For staff member 5 (Malik) - Massage services
INSERT INTO staff_services (staff_id, service_id) VALUES
(5, 10), -- Swedish Massage
(5, 11); -- Deep Tissue Massage

-- For staff member 6 (Anjali) - Makeup services
INSERT INTO staff_services (staff_id, service_id) VALUES
(6, 8), -- Bridal Makeup
(6, 9); -- Special Occasion Makeup

-- For staff member 7 (Chaminda) - Hair services (focusing on treatments)
INSERT INTO staff_services (staff_id, service_id) VALUES
(7, 2); -- Hair Coloring (includes treatments)

-- For staff member 8 (Nimali) - Multiple services
INSERT INTO staff_services (staff_id, service_id) VALUES
(8, 4), -- Classic Facial
(8, 6); -- Classic Manicure

-- Insert staff availability
-- Kaveesha (staff_id 1)
INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time, is_available) VALUES
(1, 1, '09:00:00', '17:00:00', TRUE), -- Monday
(1, 2, '09:00:00', '17:00:00', TRUE), -- Tuesday
(1, 3, '09:00:00', '17:00:00', TRUE), -- Wednesday
(1, 4, '09:00:00', '17:00:00', TRUE), -- Thursday
(1, 5, '09:00:00', '17:00:00', TRUE); -- Friday

-- Iuri (staff_id 2)
INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time, is_available) VALUES
(2, 1, '10:00:00', '18:00:00', TRUE), -- Monday
(2, 2, '10:00:00', '18:00:00', TRUE), -- Tuesday
(2, 4, '10:00:00', '18:00:00', TRUE), -- Thursday
(2, 5, '10:00:00', '18:00:00', TRUE), -- Friday
(2, 6, '10:00:00', '17:00:00', TRUE); -- Saturday

-- Sawbhagya (staff_id 3)
INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time, is_available) VALUES
(3, 2, '09:00:00', '17:00:00', TRUE), -- Tuesday
(3, 3, '09:00:00', '17:00:00', TRUE), -- Wednesday
(3, 4, '09:00:00', '17:00:00', TRUE), -- Thursday
(3, 5, '09:00:00', '20:00:00', TRUE), -- Friday (extended hours)
(3, 6, '10:00:00', '17:00:00', TRUE); -- Saturday

