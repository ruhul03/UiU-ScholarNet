-- ==========================================================
-- UiU-ScholarNet Complete Database Schema & Seed Data
-- Database: uiu_scholarnet
-- Target Environment: MySQL
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `uiu_scholarnet` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uiu_scholarnet`;

-- --------------------------------------------------------
-- 1. Users Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'faculty', 'admin') DEFAULT 'student',
    `is_verified` TINYINT(1) DEFAULT 1,
    `account_status` ENUM('active', 'banned') DEFAULT 'active',
    `department` VARCHAR(100),
    `interests` TEXT,
    `skills` TEXT,
    `points` INT DEFAULT 0,
    `reputation` INT DEFAULT 0,
    `last_active_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 1.1 User Profiles (Extensions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `user_id` INT PRIMARY KEY,
    `institution` VARCHAR(150),
    `biography` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 1.2 Password Reset Codes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_reset_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `code_hash` CHAR(64) NOT NULL,
    `attempts` TINYINT UNSIGNED DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_prc_email` (`email`),
    INDEX `idx_prc_expires` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Projects Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `department` VARCHAR(100),
    `visibility` ENUM('public', 'institution', 'private') DEFAULT 'public',
    `status` ENUM('planning', 'active', 'review', 'completed') DEFAULT 'active',
    `progress` INT DEFAULT 0,
    `creator_id` INT,
    `supervisor_id` INT,
    `supervisor_approved` TINYINT(1) DEFAULT 0,
    `research_phase` VARCHAR(50) DEFAULT 'literature_review',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2.1 Project Members Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('owner', 'editor', 'viewer') DEFAULT 'viewer',
    `status` ENUM('pending', 'active') DEFAULT 'active',
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_project_user` (`project_id`, `user_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Documents Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `visibility` ENUM('public', 'institution', 'private') DEFAULT 'private',
    `created_by` INT,
    `last_edited_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`last_edited_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3.1 Document Versions Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_versions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT NOT NULL,
    `version_name` VARCHAR(100),
    `content` LONGTEXT,
    `created_by` INT,
    `commit_message` VARCHAR(255) NULL,
    `status` ENUM('approved', 'pending', 'rejected') DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tasks Table (KanBan Board)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `pipeline_stage` VARCHAR(50) NULL,
    `document_id` INT NULL,
    `assigned_to` INT,
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `status` ENUM('todo', 'inprogress', 'done') DEFAULT 'todo',
    `due_date` DATE,
    `is_milestone` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Collaboration Posts (Collaboration Finder)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `collaboration_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `project_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `department` VARCHAR(100),
    `description` TEXT,
    `skills_required` TEXT,
    `opportunity_type` VARCHAR(50) DEFAULT 'Research',
    `status` VARCHAR(20) DEFAULT 'open',
    `slots_total` INT DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5.1 Collaboration Applications Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `collaboration_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NULL,
    `status` ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_post_user` (`post_id`, `user_id`),
    FOREIGN KEY (`post_id`) REFERENCES `collaboration_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Resources Table (Resource Hub / File Repository)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `resource_type` ENUM('PDF', 'Dataset', 'Report', 'Paper', 'CSV', 'Image', 'Archive', 'Other') DEFAULT 'Paper',
    `file_path` VARCHAR(255),
    `file_size` VARCHAR(50),
    `category` VARCHAR(100) DEFAULT 'General',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Messages Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT,
    `receiver_id` INT,
    `channel` VARCHAR(100) DEFAULT 'general',
    `message` TEXT NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `file_name` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Preprints Table (Open Access Repository)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `preprints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `abstract` TEXT,
    `keywords` VARCHAR(255),
    `file_path` VARCHAR(255) NOT NULL,
    `author_id` INT,
    `version` INT DEFAULT 1,
    `visibility` ENUM('public', 'private') DEFAULT 'public',
    `accepted_copyright` TINYINT(1) DEFAULT 1,
    `license_type` VARCHAR(50) DEFAULT 'All Rights Reserved',
    `views_count` INT DEFAULT 0,
    `downloads_count` INT DEFAULT 0,
    `project_id` INT NULL,
    `moderation_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    `moderated_by` INT NULL,
    `moderated_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8.1 Preprint Comments Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `preprint_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `preprint_id` INT,
    `user_id` INT,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`preprint_id`) REFERENCES `preprints`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8.2 Reports Table (Copyright / Moderation)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `item_type` ENUM('preprint', 'resource') NOT NULL,
    `reported_by` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Discussion Threads Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discussion_threads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) DEFAULT 'General',
    `content` TEXT NOT NULL,
    `views` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9.1 Discussion Replies Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discussion_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `thread_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`thread_id`) REFERENCES `discussion_threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Notifications Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) DEFAULT 'general',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Lookup & System Tables
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `opportunity_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reputation_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action_key` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `points` INT NOT NULL DEFAULT 0,
    `icon` VARCHAR(100) DEFAULT 'fa-solid fa-star'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA
-- Default account passwords are: 'password'
-- Generated via PHP: password_hash('password', PASSWORD_DEFAULT)
-- ==========================================================

-- 1. Users
INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `is_verified`, `account_status`, `department`, `interests`, `skills`, `points`, `reputation`)
VALUES
(1, 'Sabbir Ahmed', 'sabbir@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 'active', 'CSE', 'Machine Learning, NLP, Distributed Systems', 'Python, PyTorch, PHP, MySQL', 1245, 25),
(2, 'Admin User', 'admin', '$2y$10$l/4O6miJLHF6T0sBfkdZSOFyIdhzopaqTDglGkpWsjbAr24PXgHf6', 'admin', 1, 'active', 'Administration', 'System Security, Platform Governance', 'System Administration, SQL, PHP', 500, 100),
(3, 'Dr. Tariqul Islam', 'faculty@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 1, 'active', 'CSE', 'Artificial Intelligence, Bioinformatics, IoT', 'Python, R, Deep Learning, Cloud Computing', 890, 85),
(4, 'Nafis Imtiaz', 'nafis@uiu.ac.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 'active', 'CSE', 'Web Engineering, Database Systems', 'PHP, MySQL, JavaScript, React', 420, 15),
(5, 'Ruhul Amin', 'aamin2410003@bscse.uiu.ac.bd', '$2y$10$G.VZrUH/8fCVwJdsXfNkouNDpcOxCUW3HKO81T5bci3eGkxO4L5dW', 'admin', 1, 'active', 'CSE', 'Artificial Intelligence, Machine Learning', '', 0, 0);

-- 2. User Profiles
INSERT IGNORE INTO `user_profiles` (`user_id`, `institution`, `biography`)
VALUES
(1, 'United International University', 'Undergraduate student in Computer Science & Engineering. Passionate about natural language processing and academic collaboration.'),
(2, 'United International University', 'ScholarNet Platform Administrator ensuring data integrity and system health.'),
(3, 'United International University - Dept of CSE', 'Associate Professor conducting research in machine learning architectures and supervised collaborative research.'),
(4, 'United International University', 'Junior software researcher focusing on database optimization and full-stack web applications.'),
(5, 'United International University', 'Platform Administrator & Researcher.');

-- 3. Lookup Tables Seed Data
INSERT IGNORE INTO `departments` (`name`)
VALUES ('CSE'), ('EEE'), ('BBA'), ('Economics'), ('English'), ('Civil Engineering');

INSERT IGNORE INTO `skills` (`name`)
VALUES ('Python'), ('Machine Learning'), ('Data Mining'), ('LaTeX'), ('SPSS'), ('NLP'), ('Statistics'), ('Deep Learning'), ('PHP'), ('MySQL'), ('JavaScript'), ('React');

INSERT IGNORE INTO `opportunity_types` (`name`)
VALUES ('Research'), ('Project'), ('Thesis'), ('Publication'), ('Dataset');

INSERT IGNORE INTO `reputation_rules` (`action_key`, `title`, `description`, `points`, `icon`)
VALUES
('task_completed', 'Complete a Task', 'Earn points when an assigned project task is completed.', 50, 'fa-solid fa-list-check'),
('preprint_published', 'Publish a Preprint', 'Earn points for sharing academic work.', 100, 'fa-solid fa-file-lines'),
('collaboration_posted', 'Post Collaboration', 'Earn points for opening a collaboration opportunity.', 20, 'fa-solid fa-users'),
('discussion_started', 'Start Discussion', 'Earn points for starting a research discussion.', 2, 'fa-solid fa-comments');

-- 4. Projects
INSERT IGNORE INTO `projects` (`id`, `title`, `description`, `department`, `visibility`, `status`, `progress`, `creator_id`, `supervisor_id`, `supervisor_approved`, `research_phase`)
VALUES 
(1, 'AI Research Platform', 'Deep learning for academic collaboration and semantic search over scholarly papers.', 'CSE', 'public', 'active', 68, 1, 3, 1, 'literature_review'),
(2, 'Smart Campus IoT', 'Internet of Things for campus infrastructure monitoring, air quality, and occupancy sensors.', 'EEE', 'institution', 'review', 35, 1, 3, 0, 'methodology'),
(3, 'Bangla NLP Dataset', 'Building a large-scale Bangla natural language processing corpus and benchmark suites.', 'CSE', 'public', 'active', 82, 1, 3, 1, 'data_collection'),
(4, 'TechFest Hackathon', 'Logistics, team coordination, and project showcases for the annual university hackathon.', 'CSE', 'public', 'planning', 15, 1, NULL, 0, 'planning');

-- 5. Project Members
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`, `status`)
VALUES
(1, 1, 'owner', 'active'),
(1, 4, 'editor', 'active'),
(2, 1, 'owner', 'active'),
(3, 1, 'owner', 'active'),
(3, 4, 'viewer', 'active'),
(4, 1, 'owner', 'active');

-- 6. Documents
INSERT IGNORE INTO `documents` (`id`, `project_id`, `title`, `content`, `visibility`, `created_by`, `last_edited_by`)
VALUES
(1, 1, 'System Architecture Specification', 'This document details the high-level architecture, module decomposition, and API contracts for the AI Research Platform.', 'public', 1, 1),
(2, 3, 'Bangla NLP Dataset Annotation Guidelines', 'Guidelines for POS tagging, named entity recognition, and sentiment classification on Bengali news corpus.', 'institution', 1, 1);

-- 7. Document Versions
INSERT IGNORE INTO `document_versions` (`document_id`, `version_name`, `content`, `created_by`, `commit_message`, `status`)
VALUES
(1, 'v1.0-draft', 'Initial specification draft outlining database schema and authentication flow.', 1, 'Initial draft commit', 'approved'),
(1, 'v1.1-final', 'Updated specification with microservices communication and Redis caching layer.', 1, 'Added caching and load balancing specs', 'approved'),
(2, 'v1.0', 'First revision of POS tagging and NER guidelines for student annotators.', 1, 'Initial guidelines release', 'approved');

-- 8. Tasks (Kanban)
INSERT IGNORE INTO `tasks` (`id`, `project_id`, `title`, `description`, `pipeline_stage`, `document_id`, `assigned_to`, `priority`, `status`, `due_date`, `is_milestone`)
VALUES
(1, 1, 'Analyze Historical Archive Data for Degradation', 'Cross-reference current humidity levels with archival storage logs from 1920-1950.', 'Analysis', 1, 1, 'high', 'todo', '2026-10-24', 0),
(2, 1, 'Refactor Collaboration Search Algorithm', 'Improve semantic matching for inter-departmental research requests.', 'Development', 1, 4, 'medium', 'inprogress', '2026-11-15', 1),
(3, 1, 'Update BibTeX Export Templates', 'Ensure export templates match latest IEEE and ACM citation formats.', 'Testing', NULL, 1, 'low', 'todo', '2026-12-01', 0),
(4, 1, 'Finalize Library Floorplan API', 'Complete the REST API for library space allocation and booking.', 'Deployment', NULL, 1, 'medium', 'done', '2026-09-12', 0),
(5, 1, 'Manuscript Digitization Protocol', 'Updated documentation for high-resolution scanners.', 'Documentation', NULL, 4, 'low', 'done', '2026-09-10', 0),
(6, 1, 'Database Index Migration', 'Migrate legacy database indexes to optimized B-tree structure.', 'Maintenance', NULL, 1, 'high', 'done', '2026-09-05', 1),
(7, 3, 'Preprocess 50,000 Raw Bengali Articles', 'Tokenize, clean whitespace, and deduplicate articles from daily newspapers.', 'Data Pipeline', 2, 4, 'high', 'inprogress', '2026-09-30', 1);

-- 9. Collaboration Posts & Applications
INSERT IGNORE INTO `collaboration_posts` (`id`, `user_id`, `project_id`, `title`, `department`, `description`, `skills_required`, `opportunity_type`, `status`, `slots_total`)
VALUES
(1, 1, 1, 'AI Ethics Research Partner', 'CSE', 'Looking for a research partner to explore ethical implications of LLMs in academic settings. Focus on bias detection and mitigation strategies.', 'Python, NLP, Ethics', 'Research', 'open', 5),
(2, 1, 2, 'Cross-Campus Data Visualization Expert', 'CSE', 'Need a skilled data visualization developer for campus sensor dashboards. Experience with chart libraries preferred.', 'JavaScript, D3.js, Chart.js', 'Project', 'open', 3);

INSERT IGNORE INTO `collaboration_applications` (`id`, `post_id`, `user_id`, `message`, `status`)
VALUES
(1, 1, 4, 'I have completed coursework in NLP and would love to collaborate on the ethics benchmarking sub-module.', 'pending');

-- 10. Resources
INSERT IGNORE INTO `resources` (`id`, `user_id`, `title`, `resource_type`, `file_path`, `file_size`, `category`)
VALUES
(1, 1, 'Research Methodology & Academic Writing Handbook', 'PDF', 'uploads/resources/research_methodology_guide.pdf', '2.4 MB', 'Guide'),
(2, 1, 'Bengali Stopwords & Sentiment Lexicon v1.0', 'Dataset', 'uploads/resources/bengali_stopwords.csv', '850 KB', 'Dataset'),
(3, 3, 'Sample IEEE Conference Paper LaTeX Template', 'Other', 'uploads/resources/ieee_latex_template.zip', '1.1 MB', 'Template');

-- 11. Preprints & Comments
INSERT IGNORE INTO `preprints` (`id`, `title`, `abstract`, `keywords`, `file_path`, `author_id`, `version`, `visibility`, `accepted_copyright`, `license_type`, `views_count`, `downloads_count`, `project_id`, `moderation_status`, `moderated_by`, `moderated_at`)
VALUES
(1, 'Benchmarking Transformer Models on Low-Resource Bengali Dialects', 'Transformer architectures have revolutionized NLP, yet their performance on low-resource regional dialects remains under-studied. We evaluate multiple multilingual architectures on Bengali dialects and propose a lightweight domain adaptation method.', 'Bengali NLP, Transformers, Dialect Identification, Deep Learning', 'uploads/preprints/bengali_dialect_benchmark.pdf', 1, 1, 'public', 1, 'CC-BY-4.0', 142, 38, 3, 'approved', 2, '2026-09-01 10:00:00'),
(2, 'Edge-Assisted Sensor Node Clustering for Smart Campus Microclimates', 'This paper presents an energy-efficient clustering algorithm for battery-operated wireless sensor nodes deployed across large academic campuses.', 'IoT, Sensor Networks, Microclimate, Edge Computing', 'uploads/preprints/iot_smart_campus.pdf', 1, 1, 'public', 1, 'CC-BY-4.0', 76, 19, 2, 'approved', 2, '2026-09-05 14:30:00');

INSERT IGNORE INTO `preprint_comments` (`id`, `preprint_id`, `user_id`, `comment`)
VALUES
(1, 1, 3, 'Promising results! Have you considered evaluating against quantized models for mobile inferencing?');

-- 12. Discussion Threads & Replies
INSERT IGNORE INTO `discussion_threads` (`id`, `user_id`, `title`, `category`, `content`, `views`)
VALUES
(1, 1, 'Undergraduate Thesis Topic Selection Guide & Tips', 'Academic Advice', 'Choosing the right undergraduate thesis topic can shape your post-grad career. Here are the core factors to balance: supervisor expertise, data accessibility, and timeline realism.', 185),
(2, 4, 'Handling Large CSV Datasets in PHP & MySQL Efficiently', 'Technical', 'When importing 500k+ rows via PHP, standard PDO/mysqli loops can hit memory limits. What chunking strategies or LOAD DATA LOCAL INFILE setups work best?', 94);

INSERT IGNORE INTO `discussion_replies` (`id`, `thread_id`, `user_id`, `content`)
VALUES
(1, 1, 3, 'Great advice Sabbir. I always recommend students first read at least 10 recent papers from top venues before proposing their thesis methodology.'),
(2, 2, 1, 'You can use MySQL LOAD DATA INFILE or fgetcsv with transaction batches of 1,000 rows. That keeps memory usage constant under 8MB.');

-- 13. Messages
INSERT IGNORE INTO `messages` (`id`, `sender_id`, `receiver_id`, `channel`, `message`, `is_read`)
VALUES
(1, 3, 1, 'general', 'Welcome to UIU ScholarNet! Please review the project guidelines.', 1),
(2, 1, 3, 'direct', 'Thank you Dr. Tariqul, I have uploaded the updated system architecture draft for your review.', 1);

-- 14. Notifications
INSERT IGNORE INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`)
VALUES
(1, 1, 'welcome', 'Welcome to UIU ScholarNet!', 'Your researcher account is set up. Explore projects, share preprints, or find research collaborators.', 'dashboard/index.php', 1),
(2, 1, 'task', 'New Task Assigned', 'You have been assigned to task: Analyze Historical Archive Data for Degradation.', 'dashboard/tasks.php', 0),
(3, 1, 'collaboration', 'New Collaboration Application', 'Nafis Imtiaz applied to your post: AI Ethics Research Partner.', 'dashboard/manage_collaboration.php', 0);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
