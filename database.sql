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

-- 2. Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Resources Table (Resource Hub)
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    resource_type ENUM('PDF', 'Dataset', 'Report', 'Paper') DEFAULT 'Paper',
    file_path VARCHAR(255),
    file_size VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Dummy Data for Testing
INSERT INTO users (full_name, email, password, role, department, points) 
VALUES ('Sabbir Ahmed', 'sabbir@uiu.ac.bd', 'password123', 'student', 'CSE', 1245);

INSERT INTO projects (title, description, status, progress, creator_id) 
VALUES ('AI Research Platform', 'Deep learning for academic collab', 'active', 74, 1);
