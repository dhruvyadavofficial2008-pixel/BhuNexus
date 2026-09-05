-- ==========================================================
-- Real-Time National Land Acquisition & Management System
-- Database Schema (MySQL / phpMyAdmin)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS land_acquisition_db;
USE land_acquisition_db;

-- ---------------- USERS ----------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('citizen','admin') NOT NULL DEFAULT 'citizen',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------- PROJECTS ----------------
-- e.g. "NH-44 Highway Expansion", "Metro Phase 2"
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(200) NOT NULL,
    description TEXT,
    department VARCHAR(120),
    status ENUM('planned','ongoing','completed') DEFAULT 'planned',
    start_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------- LAND PARCELS ----------------
CREATE TABLE parcels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_code VARCHAR(50) NOT NULL UNIQUE,
    owner_id INT,                              -- FK to users (citizen)
    project_id INT,                            -- FK to projects
    village VARCHAR(120),
    district VARCHAR(120),
    state VARCHAR(120),
    area_acres DECIMAL(10,2),
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6),
    boundary_geojson TEXT,                     -- drawn parcel boundary (GeoJSON), set via Add Parcel map picker
    -- acquisition stage per your workflow
    stage ENUM('identify','survey','notify','verify','acquire','compensate','monitor') DEFAULT 'identify',
    market_value DECIMAL(14,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- ---------------- TIMELINE / STAGE HISTORY ----------------
CREATE TABLE parcel_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    stage ENUM('identify','survey','notify','verify','acquire','compensate','monitor') NOT NULL,
    remarks VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------- DOCUMENTS ----------------
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    uploaded_by INT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    doc_type VARCHAR(100),                     -- e.g. "Title Deed", "Survey Report"
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------- COMPENSATION ----------------
CREATE TABLE compensation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    amount_due DECIMAL(14,2),
    amount_paid DECIMAL(14,2) DEFAULT 0,
    payment_status ENUM('pending','partial','paid') DEFAULT 'pending',
    delay_days INT DEFAULT 0,                  -- days beyond expected payment date
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE
);

-- ---------------- GRIEVANCES / OBJECTIONS ----------------
CREATE TABLE grievances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    raised_by INT,
    subject VARCHAR(200),
    description TEXT,
    status ENUM('open','under_review','resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    FOREIGN KEY (raised_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------- NOTIFICATIONS ----------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------- RISK ANALYSIS LOG (AI-assisted) ----------------
CREATE TABLE risk_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    disputes_count INT DEFAULT 0,
    pending_compensation_count INT DEFAULT 0,
    avg_delay_days DECIMAL(6,2) DEFAULT 0,
    pending_documents_count INT DEFAULT 0,
    risk_score DECIMAL(8,2),
    risk_label VARCHAR(20),
    recommendation VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ==========================================================
-- SAMPLE / DEMO DATA
-- ==========================================================

-- password for both demo accounts is: password123  (hashed with PHP password_hash)
INSERT INTO users (full_name, email, password, role, phone) VALUES
('State Admin', 'admin@land.gov', '$2b$10$R.TSSExM.2bvgnbXKftabuvGFSoqGZveyI4DZwuhuVWmAHHyRc3QK', 'admin', '9999999999'),
('Ramesh Kumar', 'ramesh@example.com', '$2b$10$R.TSSExM.2bvgnbXKftabuvGFSoqGZveyI4DZwuhuVWmAHHyRc3QK', 'citizen', '9876543210'),
('Sita Devi', 'sita@example.com', '$2b$10$R.TSSExM.2bvgnbXKftabuvGFSoqGZveyI4DZwuhuVWmAHHyRc3QK', 'citizen', '9876500000');

INSERT INTO projects (project_name, description, department, status, start_date) VALUES
('NH-44 Highway Expansion', 'Widening of national highway through district X', 'Roads & Highways', 'ongoing', '2025-01-10'),
('Metro Rail Phase 2', 'New metro corridor land acquisition', 'Urban Transport', 'ongoing', '2025-03-01');

INSERT INTO parcels (parcel_code, owner_id, project_id, village, district, state, area_acres, latitude, longitude, stage, market_value) VALUES
('PCL-001', 2, 1, 'Rampur', 'Central District', 'Demo State', 2.50, 28.6139, 77.2090, 'notify', 1500000),
('PCL-002', 3, 1, 'Rampur', 'Central District', 'Demo State', 1.20, 28.6200, 77.2150, 'verify', 900000),
('PCL-003', 2, 2, 'Shivpur', 'North District', 'Demo State', 3.10, 28.7041, 77.1025, 'compensate', 2100000);

INSERT INTO parcel_timeline (parcel_id, stage, remarks, updated_by) VALUES
(1, 'identify', 'Parcel identified for highway corridor', 1),
(1, 'survey', 'Survey completed', 1),
(1, 'notify', 'Notice served to owner', 1),
(2, 'identify', 'Parcel identified', 1),
(2, 'survey', 'Survey in progress', 1),
(2, 'notify', 'Notice served', 1),
(2, 'verify', 'Ownership verification pending', 1),
(3, 'identify', 'Parcel identified', 1),
(3, 'survey', 'Survey completed', 1),
(3, 'notify', 'Notice served', 1),
(3, 'verify', 'Ownership verified', 1),
(3, 'acquire', 'Acquisition order passed', 1),
(3, 'compensate', 'Compensation calculation in progress', 1);

INSERT INTO compensation (parcel_id, amount_due, amount_paid, payment_status, delay_days) VALUES
(1, 1500000, 0, 'pending', 12),
(2, 900000, 300000, 'partial', 25),
(3, 2100000, 2100000, 'paid', 0);

INSERT INTO grievances (parcel_id, raised_by, subject, description, status) VALUES
(2, 3, 'Incorrect area measurement', 'The surveyed area does not match my land records.', 'open'),
(1, 2, 'Compensation amount dispute', 'Market value used seems lower than nearby parcels.', 'under_review');

INSERT INTO documents (parcel_id, uploaded_by, file_name, file_path, doc_type, status) VALUES
(1, 2, 'title_deed_pcl001.pdf', 'uploads/title_deed_pcl001.pdf', 'Title Deed', 'verified'),
(2, 3, 'survey_report_pcl002.pdf', 'uploads/survey_report_pcl002.pdf', 'Survey Report', 'pending');
