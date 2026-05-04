CREATE DATABASE IF NOT EXISTS uiu_scholarnet;
USE uiu_scholarnet;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty') DEFAULT 'student',
    department VARCHAR(100),
    interests TEXT,
    skills TEXT,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1.1 User Profile Extensions
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    institution VARCHAR(150),
    biography TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    department VARCHAR(100),
    visibility ENUM('public', 'institution', 'private') DEFAULT 'public',
    status ENUM('planning', 'active', 'review', 'completed') DEFAULT 'active',
    progress INT DEFAULT 0,
    creator_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Tasks Table (KanBan)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('todo', 'inprogress', 'done') DEFAULT 'todo',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. Collaboration Posts (Collaboration Finder)
CREATE TABLE IF NOT EXISTS collaboration_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    skills_required TEXT,
    opportunity_type VARCHAR(50) DEFAULT 'Research',
    status VARCHAR(20) DEFAULT 'open',
    slots_total INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS collaboration_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_post_user (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES collaboration_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Resources Table (File Upload / Resource Hub)
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    resource_type ENUM('PDF', 'Dataset', 'Report', 'Paper', 'CSV', 'Image', 'Archive', 'Other') DEFAULT 'Paper',
    file_path VARCHAR(255),
    file_size VARCHAR(50),
    category VARCHAR(100) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    channel VARCHAR(100) DEFAULT 'general',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    visibility ENUM('public', 'institution', 'private') DEFAULT 'private',
    created_by INT,
    last_edited_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (last_edited_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ===========================
-- Seed Data (password is hashed version of 'password')
-- Generated via: password_hash('password', PASSWORD_DEFAULT)
-- ===========================
INSERT INTO users (full_name, email, password, role, department, points)
VALUES ('Sabbir Ahmed', 'sabbir@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'CSE', 1245);

INSERT INTO projects (title, description, status, progress, creator_id)
VALUES 
('AI Research Platform', 'Deep learning for academic collaboration tools', 'active', 68, 1),
('Smart Campus IoT', 'Internet of Things for campus infrastructure monitoring', 'review', 35, 1),
('Bangla NLP Dataset', 'Building a large-scale Bangla natural language processing corpus', 'active', 82, 1),
('TechFest Hackathon', 'Logistics and team coordination for the annual tech festival', 'planning', 15, 1);

INSERT INTO tasks (project_id, title, description, assigned_to, priority, status, due_date)
VALUES
(1, 'Analyze Historical Archive Data for Vellum Degradation', 'Cross-reference current humidity levels with archival storage logs from 1920-1950.', 1, 'high', 'todo', '2026-10-24'),
(1, 'Refactor Collaboration Search Algorithm', 'Improve semantic matching for inter-departmental research requests.', 1, 'medium', 'todo', '2026-11-15'),
(1, 'Update BibTeX Export Templates', 'Ensure export templates match latest IEEE and ACM citation formats.', 1, 'low', 'todo', '2026-12-01'),
(1, 'Finalize Library Floorplan API', 'Complete the REST API for library space allocation and booking.', 1, 'medium', 'done', '2026-09-12'),
(1, 'Manuscript Digitization Protocol', 'Updated documentation for high-res scanners.', 1, 'low', 'done', '2026-09-10'),
(1, 'Database Index Migration', 'Migrate legacy database indexes to optimized B-tree structure.', 1, 'high', 'done', '2026-09-05');

INSERT INTO collaboration_posts (user_id, title, department, description, skills_required)
VALUES
(1, 'AI Ethics Research Partner', 'Computer Science', 'Looking for a research partner to explore ethical implications of LLMs in academic settings. Focus on bias detection and mitigation strategies.', 'Python, NLP, Ethics'),
(1, 'Cross-Campus Data Visualization', 'CSE', 'Need a skilled data visualization expert for our urban development research project. D3.js and Tableau experience preferred.', 'D3.js, Tableau, Statistics');

-- 8. Preprints Table
CREATE TABLE IF NOT EXISTS preprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    abstract TEXT,
    keywords VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    author_id INT,
    version INT DEFAULT 1,
    visibility ENUM('public', 'private') DEFAULT 'public',
    accepted_copyright TINYINT(1) DEFAULT 1,
    license_type VARCHAR(50) DEFAULT 'All Rights Reserved',
    views_count INT DEFAULT 0,
    downloads_count INT DEFAULT 0,
    project_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- 9. Preprint Comments Table
CREATE TABLE IF NOT EXISTS preprint_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preprint_id INT,
    user_id INT,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (preprint_id) REFERENCES preprints(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 10. Reports Table (Copyright/Moderation)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('preprint', 'resource') NOT NULL,
    reported_by INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE
);
