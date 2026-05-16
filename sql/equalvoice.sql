-- ================================================================
-- EqualVoice Database Schema for MySQL (XAMPP)
-- Import via phpMyAdmin: http://localhost/phpmyadmin
-- After import, visit: http://localhost/equalvoice/setup.php
-- ================================================================

CREATE DATABASE IF NOT EXISTS equalvoice_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE equalvoice_db;

-- ----------------------------------------------------------------
-- USERS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    gender_identity VARCHAR(50) DEFAULT NULL,
    avatar_color  VARCHAR(7)    NOT NULL DEFAULT '#667eea',
    bio           TEXT          DEFAULT NULL,
    department    VARCHAR(100)  DEFAULT NULL,
    interests     TEXT          DEFAULT NULL,
    goals         TEXT          DEFAULT NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP     NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- STORIES
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stories (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT  NULL,
    content      TEXT NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- STORY LIKES
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS story_likes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    story_id  INT NOT NULL,
    user_id   INT NOT NULL,
    UNIQUE KEY unique_like (story_id, user_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- LEADERSHIP DATA
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leadership_data (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    org_name        VARCHAR(150)   NOT NULL,
    women_pct       DECIMAL(5,2)   NOT NULL DEFAULT 0,
    men_pct         DECIMAL(5,2)   NOT NULL DEFAULT 0,
    nonbinary_pct   DECIMAL(5,2)   NOT NULL DEFAULT 0,
    transgender_pct DECIMAL(5,2)   NOT NULL DEFAULT 0,
    gay_pct         DECIMAL(5,2)   NOT NULL DEFAULT 0,
    lesbian_pct     DECIMAL(5,2)   NOT NULL DEFAULT 0,
    total_leaders   INT            NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by      INT            NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- MENTORS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mentors (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NULL,
    name         VARCHAR(100) NOT NULL,
    role_title   VARCHAR(100) NOT NULL,
    expertise    VARCHAR(200) NOT NULL,
    bio          TEXT         DEFAULT NULL,
    rating       DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    sessions     INT          NOT NULL DEFAULT 0,
    is_available TINYINT(1)   NOT NULL DEFAULT 1,
    icon         VARCHAR(50)  NOT NULL DEFAULT 'fa-user',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- MENTORSHIP REQUESTS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mentorship_requests (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    mentor_id INT NOT NULL,
    message   TEXT DEFAULT NULL,
    status    ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- REPORTS (Help Desk)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT  NULL,
    issue_type   VARCHAR(100) NOT NULL,
    description  TEXT         NOT NULL,
    is_anonymous TINYINT(1)   NOT NULL DEFAULT 0,
    status       ENUM('open','resolved') NOT NULL DEFAULT 'open',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- HELP RESOURCES
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS help_resources (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    type            VARCHAR(100) NOT NULL,
    description     TEXT         DEFAULT NULL,
    contact_phone   VARCHAR(50)  DEFAULT NULL,
    contact_address TEXT         DEFAULT NULL,
    hours           VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- LESSONS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lessons (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    title    VARCHAR(200) NOT NULL,
    content  TEXT         NOT NULL,
    duration VARCHAR(20)  NOT NULL DEFAULT '5 min',
    level    ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
    category VARCHAR(100) NOT NULL DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- USER LESSON PROGRESS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_lesson_progress (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    lesson_id    INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- COMPANIES (Job Fairness Tracker)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(150) NOT NULL,
    gender_pay_gap        DECIMAL(5,2) NOT NULL DEFAULT 0,
    women_in_leadership   DECIMAL(5,2) NOT NULL DEFAULT 0,
    rating                DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    fairness_score        INT          NOT NULL DEFAULT 0,
    icon                  VARCHAR(50)  NOT NULL DEFAULT 'fa-building'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SEED DATA
-- ================================================================

-- Users (demo accounts)
INSERT IGNORE INTO users (name, email, password_hash, role, gender_identity, avatar_color, bio, department, interests, goals) VALUES
('Admin User',       'admin@equalvoice.com',  '$2y$10$DPlggoX.J5i8CxpEXhfxruHxiz.znU1A8uEmr5Ta47/VSF1Ai3ITW', 'admin', 'Prefer not to say', '#9C8FCC', 'Platform Administrator passionate about gender equality and inclusive leadership.', 'Platform Management', NULL, NULL),
('Jane Leader',      'user@example.com',      '$2y$10$KDatURxYzn0Fcs2OcKdJ5.4zsRmfR6tMH0FGLSInEOUHiEFCp/3J6', 'user',  'Woman',             '#ED8E89', 'Aspiring leader in tech, looking for mentorship and growth opportunities.', 'Technology', 'Technology, Leadership, DEI', 'Become a team lead within 2 years'),
('Elena Vasquez',    'mentor@equalvoice.com', '$2y$10$vpPm2p02db1ryozxL3zq..sjEksAsDSD4IWDMGeBYS3ASVOykPuzS', 'user',  'Woman',             '#B4A8E0', 'Senior mentor account for leadership and startup scaling.', 'Mentorship', 'Leadership Coaching, Inclusion, Growth', 'Support at least 100 mentees this year'),
('Dr. Amina Diallo', 'amina@equalvoice.com',  '$2y$10$vpPm2p02db1ryozxL3zq..sjEksAsDSD4IWDMGeBYS3ASVOykPuzS', 'user',  'Woman',             '#ED8E89', 'Board-level mentor focused on DEI strategy and executive impact.', 'Mentorship', 'DEI Strategy, Leadership, Board Governance', 'Mentor future diverse board leaders'),
('Jordan Taylor',    'jordan@equalvoice.com', '$2y$10$vpPm2p02db1ryozxL3zq..sjEksAsDSD4IWDMGeBYS3ASVOykPuzS', 'user',  'Prefer not to say', '#9BD6D9', 'Executive director helping leaders build inclusive communities.', 'Mentorship', 'Community Leadership, Nonprofit Governance', 'Scale equitable leadership pipelines'),
('Marcus Chen',      'marcus@equalvoice.com', '$2y$10$vpPm2p02db1ryozxL3zq..sjEksAsDSD4IWDMGeBYS3ASVOykPuzS', 'user',  'Man',               '#94C691', 'Engineering leadership mentor focused on bias-aware team growth.', 'Mentorship', 'Engineering Leadership, Unconscious Bias', 'Help mentees become inclusive tech leaders'),
('Priya Nair',       'priya@equalvoice.com',  '$2y$10$vpPm2p02db1ryozxL3zq..sjEksAsDSD4IWDMGeBYS3ASVOykPuzS', 'user',  'Woman',             '#F7B685', 'People operations mentor specializing in inclusive hiring systems.', 'Mentorship', 'HR Strategy, Inclusive Hiring', 'Coach future inclusive people leaders');

-- Mentors
INSERT INTO mentors (name, role_title, expertise, bio, rating, sessions, is_available, icon) VALUES
('Dr. Amina Diallo',  'Board Member',       'Corporate leadership & DEI strategy',    'Over 20 years driving diversity initiatives at Fortune 500 companies.',          4.9, 45, 1, 'fa-user-tie'),
('Jordan Taylor',     'Executive Director', 'Community leadership & nonprofit governance', 'Passionate advocate for gender-balanced leadership in underserved communities.', 4.8, 32, 1, 'fa-users'),
('Elena Vasquez',     'Former CEO',         'Startup scaling & executive coaching',   'Scaled three startups from seed to Series B, championing inclusive cultures.',    5.0, 67, 1, 'fa-rocket'),
('Marcus Chen',       'VP Engineering',     'Tech leadership & unconscious bias',     'Building equitable engineering teams at top Silicon Valley firms.',               4.7, 28, 1, 'fa-laptop-code'),
('Priya Nair',        'Chief People Officer','HR transformation & inclusive hiring',  'Redesigned hiring pipelines for 12 global companies to eliminate gender bias.',   4.8, 41, 1, 'fa-heart');

-- Link mentor accounts to mentor profiles
UPDATE mentors m JOIN users u ON u.name = m.name SET m.user_id = u.id;

-- Help Resources
INSERT INTO help_resources (title, type, description, contact_phone, contact_address, hours) VALUES
("Women's Support Center",    'Counselor',    'Free counseling and advocacy for gender discrimination cases.',     '1-800-123-4567', '123 Main St, City Center',   '24/7'),
('Gender Rights Legal Aid',   'Lawyer',       'Pro-bono legal representation for workplace gender discrimination.','1-800-234-5678', '456 Oak Ave, Suite 200',     'Mon-Fri 9am-5pm'),
('National Crisis Support',   'Crisis Line',  'Immediate emotional support for gender-based harassment.',         '988',            'Available nationwide',        '24/7'),
('LGBTQ+ Resource Center',    'Support Group','Safe space and resources for LGBTQ+ professionals.',               '1-800-345-6789', '789 Rainbow Blvd',           'Mon-Sun 8am-10pm'),
('Equal Pay Advocacy Network','Advocacy',     'Resources and guidance on equal pay rights and negotiations.',     '1-800-456-7890', 'Online & in-person sessions', 'Tue-Thu 10am-4pm');

-- Lessons
INSERT INTO lessons (title, content, duration, level, category) VALUES
('What is Gender Equality?',         'Gender equality means equal rights, responsibilities, and opportunities for all genders regardless of identity. It encompasses equal pay, equal access to leadership, and freedom from discrimination.',  '5 min',  'Beginner',     'Foundations'),
('Why Leadership Diversity Matters', 'Research shows that companies with diverse leadership teams outperform peers by 36%. Inclusive leadership drives innovation, improves decision-making, and creates better outcomes for all employees.',     '7 min',  'Beginner',     'Leadership'),
('Understanding Unconscious Bias',   'Unconscious biases are hidden mental shortcuts that affect our decisions without our awareness. In the workplace, they impact hiring, promotion, and daily interactions. Learn to identify and interrupt them.', '10 min', 'Intermediate', 'Bias & Inclusion'),
('The Gender Pay Gap Explained',     'The gender pay gap refers to the difference in average earnings between men and women. Understanding its causes — from occupational segregation to negotiation bias — is the first step to closing it.',   '8 min',  'Intermediate', 'Equal Pay'),
('Allyship in the Workplace',        'Allies actively support colleagues from marginalized gender groups. Effective allyship includes amplifying voices, challenging discrimination, and using your privilege to create systemic change.',         '6 min',  'Beginner',     'Allyship'),
('Navigating Workplace Discrimination','Know your rights and the steps to take when facing gender discrimination at work. This lesson covers documentation, reporting, legal protections, and self-care strategies.',                          '12 min', 'Advanced',     'Legal Rights'),
('Inclusive Language Guide',         'Words matter. This lesson explores gendered language in the workplace, how to use inclusive pronouns and titles, and how thoughtful communication builds a more welcoming environment for all genders.', '5 min',  'Beginner',     'Communication'),
('Building Inclusive Teams',         'Leaders who build inclusive teams outperform peers on every metric. Learn practical strategies for equitable hiring, retention, psychological safety, and measurable inclusion goals.',                  '15 min', 'Advanced',     'Leadership');

-- Companies
INSERT INTO companies (name, gender_pay_gap, women_in_leadership, rating, fairness_score, icon) VALUES
('TechCorp',      5.2,  45.0, 4.8, 92, 'fa-microchip'),
('Global Finance',12.1, 32.0, 3.9, 78, 'fa-chart-line'),
('HealthPlus',     2.8,  58.0, 4.9, 95, 'fa-heartbeat'),
('EcoEnergy',      1.5,  52.0, 4.7, 94, 'fa-leaf'),
('InnovateLab',    4.1,  48.0, 4.6, 91, 'fa-flask'),
('RetailGiant',   14.3,  29.0, 3.5, 72, 'fa-shopping-bag'),
('MediaWorks',     6.7,  43.0, 4.2, 85, 'fa-film'),
('EduTech',        3.2,  55.0, 4.8, 93, 'fa-graduation-cap');

-- Leadership Data (Overall Platform)
INSERT INTO leadership_data (org_name, women_pct, men_pct, nonbinary_pct, transgender_pct, gay_pct, lesbian_pct, total_leaders) VALUES
('EqualVoice Platform Overall', 28.0, 42.0, 8.0, 10.0, 7.0, 5.0, 1500),
('Tech Industry Average',       23.5, 58.0, 6.5,  5.5, 4.5, 2.0,  850),
('Finance Sector Average',      19.0, 65.0, 5.0,  4.0, 4.5, 2.5, 1200),
('Healthcare Average',          55.0, 32.0, 5.0,  4.0, 2.5, 1.5,  960);

-- Stories (sample community stories)
INSERT INTO stories (user_id, content, is_anonymous, created_at) VALUES
(NULL, 'After 5 years in tech, I finally negotiated a 20% raise by using market data and asking a mentor to help me prepare. Do not be afraid to advocate for yourself!', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(NULL, 'Our company just announced a new parental leave policy that applies equally to all genders. Small victories like this give me hope for real change in the workplace.', 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(NULL, 'As a non-binary engineer, I used to dread performance reviews. Since joining a company with an explicit inclusion policy, I feel seen and valued. Representation matters.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));
