-- ============================================================
-- FUD Sports Facility Reservation and Approval Management System
-- Import this file after selecting the target database.
-- ============================================================

-- ------------------------------------------------------------
-- TABLE: users
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'security') NOT NULL,
    is_privileged TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: facilities
-- ------------------------------------------------------------
CREATE TABLE facilities (
    facility_id INT PRIMARY KEY AUTO_INCREMENT,
    facility_name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    status ENUM('available', 'unavailable') DEFAULT 'available'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: reservations
-- ------------------------------------------------------------
CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    session ENUM('morning', 'evening') NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_facility FOREIGN KEY (facility_id)
        REFERENCES facilities(facility_id) ON DELETE CASCADE,
    INDEX idx_facility_date_session_status (facility_id, reservation_date, session, status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: approvals
-- ------------------------------------------------------------
CREATE TABLE approvals (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    admin_id INT NOT NULL,
    decision ENUM('approved', 'rejected') NOT NULL,
    comments TEXT,
    decided_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_approvals_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_admin FOREIGN KEY (admin_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: notifications
-- ------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reservation_id INT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations(reservation_id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: login_attempts
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    successful TINYINT(1) DEFAULT 0,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SEED DATA
-- ------------------------------------------------------------

-- Facilities
INSERT INTO facilities (facility_name, location, status) VALUES
('Football Field', 'Main Campus Sports Complex', 'available'),
('Basketball Court', 'Main Campus Sports Complex', 'available'),
('Tennis Court', 'Main Campus Sports Complex', 'available');

-- Admin and Security accounts.
-- NOTE: the password hashes below are PLACEHOLDERS and will NOT work.
-- bcrypt hashes cannot be reliably hand-written into a .sql file (each
-- run of password_hash() produces a different salted hash). After
-- creating the database, run generate_seed_hashes.php in a browser or
-- via `php generate_seed_hashes.php` on the command line — it will
-- print two ready-to-run UPDATE statements with real hashes for these
-- two accounts. Run those UPDATE statements immediately after this
-- script to set working passwords:
--   admin@fud.edu.ng    / Admin@1234
--   security@fud.edu.ng / Security@1234

INSERT INTO users (full_name, email, password, role, is_privileged) VALUES
('Sport Unit Admin', 'admin@fud.edu.ng', 'REPLACE_WITH_GENERATED_HASH', 'admin', 0),
('Security Unit', 'security@fud.edu.ng', 'REPLACE_WITH_GENERATED_HASH', 'security', 0);