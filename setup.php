<?php
/**
 * EqualVoice — One-time Setup Script
 * Visit: http://localhost/equalvoice/setup.php
 * This script creates the database, all tables, and seeds default data.
 * DELETE this file after setup is complete!
 */

$host = 'localhost';
$user = 'root';
$pass = '';

$messages = [];
$errors   = [];

// ----------------------------------------------------------------
// 1. Connect WITHOUT specifying a database (to create it)
// ----------------------------------------------------------------
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die('<p style="color:red;font-family:monospace;padding:20px;">
        Cannot connect to MySQL: ' . htmlspecialchars($e->getMessage()) . '<br>
        Make sure XAMPP MySQL is running (green light in XAMPP Control Panel).
    </p>');
}

// ----------------------------------------------------------------
// 2. Create database
// ----------------------------------------------------------------
$pdo->exec("CREATE DATABASE IF NOT EXISTS equalvoice_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE equalvoice_db");
$messages[] = "✅ Database <strong>equalvoice_db</strong> ready.";

// ----------------------------------------------------------------
// 3. Create all tables
// ----------------------------------------------------------------
$tables = [
"CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    gender_identity VARCHAR(50) DEFAULT NULL,
    avatar_color  VARCHAR(7)    NOT NULL DEFAULT '#B4A8E0',
    bio           TEXT          DEFAULT NULL,
    department    VARCHAR(100)  DEFAULT NULL,
    interests     TEXT          DEFAULT NULL,
    goals         TEXT          DEFAULT NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP     NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS stories (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT  NULL,
    content      TEXT NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS story_likes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    story_id  INT NOT NULL,
    user_id   INT NOT NULL,
    UNIQUE KEY unique_like (story_id, user_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS leadership_data (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS mentors (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS mentorship_requests (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    mentor_id INT NOT NULL,
    message   TEXT DEFAULT NULL,
    status    ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS reports (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT  NULL,
    issue_type   VARCHAR(100) NOT NULL,
    description  TEXT         NOT NULL,
    is_anonymous TINYINT(1)   NOT NULL DEFAULT 0,
    status       ENUM('open','resolved') NOT NULL DEFAULT 'open',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS help_resources (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    type            VARCHAR(100) NOT NULL,
    description     TEXT         DEFAULT NULL,
    contact_phone   VARCHAR(50)  DEFAULT NULL,
    contact_address TEXT         DEFAULT NULL,
    hours           VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS lessons (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    title    VARCHAR(200) NOT NULL,
    content  TEXT         NOT NULL,
    duration VARCHAR(20)  NOT NULL DEFAULT '5 min',
    level    ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
    category VARCHAR(100) NOT NULL DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS user_lesson_progress (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    lesson_id    INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS companies (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(150) NOT NULL,
    gender_pay_gap       DECIMAL(5,2) NOT NULL DEFAULT 0,
    women_in_leadership  DECIMAL(5,2) NOT NULL DEFAULT 0,
    rating               DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    fairness_score       INT          NOT NULL DEFAULT 0,
    icon                 VARCHAR(50)  NOT NULL DEFAULT 'fa-building'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
}
$messages[] = "✅ All <strong>11 tables</strong> created (or already exist).";

// ----------------------------------------------------------------
// 4. Seed users (with proper password_hash)
// ----------------------------------------------------------------
$defaultUsers = [
    [
        'name'     => 'Admin User',
        'email'    => 'admin@equalvoice.com',
        'password' => 'admin123',
        'role'     => 'admin',
        'gender'   => 'Prefer not to say',
        'color'    => '#9C8FCC',
        'bio'      => 'Platform Administrator passionate about gender equality and inclusive leadership.',
        'dept'     => 'Platform Management',
    ],
    [
        'name'      => 'Jane Leader',
        'email'     => 'user@example.com',
        'password'  => 'user123',
        'role'      => 'user',
        'gender'    => 'Woman',
        'color'     => '#ED8E89',
        'bio'       => 'Aspiring leader in tech, looking for mentorship and growth opportunities.',
        'dept'      => 'Technology',
        'interests' => 'Technology, Leadership, DEI',
        'goals'     => 'Become a team lead within 2 years',
    ],
    [
        'name'      => 'Elena Vasquez',
        'email'     => 'mentor@equalvoice.com',
        'password'  => 'mentor123',
        'role'      => 'user',
        'gender'    => 'Woman',
        'color'     => '#B4A8E0',
        'bio'       => 'Senior mentor account for leadership and startup scaling.',
        'dept'      => 'Mentorship',
        'interests' => 'Leadership Coaching, Inclusion, Growth',
        'goals'     => 'Support at least 100 mentees this year',
    ],
    [
        'name'      => 'Dr. Amina Diallo',
        'email'     => 'amina@equalvoice.com',
        'password'  => 'mentor123',
        'role'      => 'user',
        'gender'    => 'Woman',
        'color'     => '#ED8E89',
        'bio'       => 'Board-level mentor focused on DEI strategy and executive impact.',
        'dept'      => 'Mentorship',
        'interests' => 'DEI Strategy, Leadership, Board Governance',
        'goals'     => 'Mentor future diverse board leaders',
    ],
    [
        'name'      => 'Jordan Taylor',
        'email'     => 'jordan@equalvoice.com',
        'password'  => 'mentor123',
        'role'      => 'user',
        'gender'    => 'Prefer not to say',
        'color'     => '#9BD6D9',
        'bio'       => 'Executive director helping leaders build inclusive communities.',
        'dept'      => 'Mentorship',
        'interests' => 'Community Leadership, Nonprofit Governance',
        'goals'     => 'Scale equitable leadership pipelines',
    ],
    [
        'name'      => 'Marcus Chen',
        'email'     => 'marcus@equalvoice.com',
        'password'  => 'mentor123',
        'role'      => 'user',
        'gender'    => 'Man',
        'color'     => '#94C691',
        'bio'       => 'Engineering leadership mentor focused on bias-aware team growth.',
        'dept'      => 'Mentorship',
        'interests' => 'Engineering Leadership, Unconscious Bias',
        'goals'     => 'Help mentees become inclusive tech leaders',
    ],
    [
        'name'      => 'Priya Nair',
        'email'     => 'priya@equalvoice.com',
        'password'  => 'mentor123',
        'role'      => 'user',
        'gender'    => 'Woman',
        'color'     => '#F7B685',
        'bio'       => 'People operations mentor specializing in inclusive hiring systems.',
        'dept'      => 'Mentorship',
        'interests' => 'HR Strategy, Inclusive Hiring',
        'goals'     => 'Coach future inclusive people leaders',
    ],
];

foreach ($defaultUsers as $u) {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$u['email']]);
    if ($check->fetch()) {
        $messages[] = "⚠️  User <strong>{$u['email']}</strong> already exists — skipped.";
        continue;
    }
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $ins  = $pdo->prepare('
        INSERT INTO users (name, email, password_hash, role, gender_identity, avatar_color, bio, department, interests, goals)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([
        $u['name'], $u['email'], $hash, $u['role'],
        $u['gender'] ?? null, $u['color'],
        $u['bio'] ?? null, $u['dept'] ?? null,
        $u['interests'] ?? null, $u['goals'] ?? null,
    ]);
    $messages[] = "✅ Created user <strong>{$u['email']}</strong> (password: <code>{$u['password']}</code>)";
}

// ----------------------------------------------------------------
// 5. Seed mentors (skip if already seeded)
// ----------------------------------------------------------------
$mentorCount = (int)$pdo->query('SELECT COUNT(*) FROM mentors')->fetchColumn();
if ($mentorCount === 0) {
    $mentorData = [
        ['Dr. Amina Diallo',  'Board Member',       'Corporate leadership & DEI strategy',       4.9, 45, 'fa-user-tie'],
        ['Jordan Taylor',     'Executive Director', 'Community leadership & nonprofit governance', 4.8, 32, 'fa-users'],
        ['Elena Vasquez',     'Former CEO',         'Startup scaling & executive coaching',       5.0, 67, 'fa-rocket'],
        ['Marcus Chen',       'VP Engineering',     'Tech leadership & unconscious bias',         4.7, 28, 'fa-laptop-code'],
        ['Priya Nair',        'Chief People Officer','HR transformation & inclusive hiring',      4.8, 41, 'fa-heart'],
    ];
    $ins = $pdo->prepare('INSERT INTO mentors (name, role_title, expertise, rating, sessions, is_available, icon) VALUES (?,?,?,?,?,1,?)');
    foreach ($mentorData as $m) $ins->execute($m);
    $messages[] = "✅ Seeded <strong>" . count($mentorData) . " mentors</strong>.";
}

// Ensure canonical mentor list exists (upsert by name)
$canonicalMentors = [
    ['Dr. Amina Diallo',  'Board Member',        'Corporate leadership & DEI strategy',        'Over 20 years driving diversity initiatives at Fortune 500 companies.',          4.9, 45, 1, 'fa-user-tie'],
    ['Jordan Taylor',     'Executive Director',  'Community leadership & nonprofit governance', 'Passionate advocate for gender-balanced leadership in underserved communities.',  4.8, 32, 1, 'fa-users'],
    ['Elena Vasquez',     'Former CEO',          'Startup scaling & executive coaching',        'Scaled three startups from seed to Series B, championing inclusive cultures.',    5.0, 67, 1, 'fa-rocket'],
    ['Marcus Chen',       'VP Engineering',      'Tech leadership & unconscious bias',          'Building equitable engineering teams at top Silicon Valley firms.',               4.7, 28, 1, 'fa-laptop-code'],
    ['Priya Nair',        'Chief People Officer','HR transformation & inclusive hiring',        'Redesigned hiring pipelines for 12 global companies to eliminate gender bias.',   4.8, 41, 1, 'fa-heart'],
];
$selMentor = $pdo->prepare("SELECT id FROM mentors WHERE name = ? ORDER BY id ASC LIMIT 1");
$updMentor = $pdo->prepare("UPDATE mentors SET role_title = ?, expertise = ?, bio = ?, rating = ?, sessions = ?, is_available = ?, icon = ? WHERE id = ?");
$insMentor = $pdo->prepare("INSERT INTO mentors (name, role_title, expertise, bio, rating, sessions, is_available, icon) VALUES (?,?,?,?,?,?,?,?)");
foreach ($canonicalMentors as $cm) {
    $selMentor->execute([$cm[0]]);
    $existingId = (int)$selMentor->fetchColumn();
    if ($existingId > 0) {
        $updMentor->execute([$cm[1], $cm[2], $cm[3], $cm[4], $cm[5], $cm[6], $cm[7], $existingId]);
    } else {
        $insMentor->execute($cm);
    }
}

// Link mentor account to mentor profile (best effort for existing/new DBs)
try {
    $pdo->exec("ALTER TABLE mentors ADD COLUMN user_id INT NULL AFTER id");
} catch (Throwable $e) { /* column exists */ }
try {
    $pdo->exec("ALTER TABLE mentors ADD INDEX idx_mentors_user_id (user_id)");
} catch (Throwable $e) { /* index exists */ }
try {
    $pdo->exec("ALTER TABLE mentors ADD CONSTRAINT fk_mentors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
} catch (Throwable $e) { /* constraint exists */ }

$mentorAccountMap = [
    'Elena Vasquez'    => 'mentor@equalvoice.com',
    'Dr. Amina Diallo' => 'amina@equalvoice.com',
    'Jordan Taylor'    => 'jordan@equalvoice.com',
    'Marcus Chen'      => 'marcus@equalvoice.com',
    'Priya Nair'       => 'priya@equalvoice.com',
];
$linkStmt = $pdo->prepare("UPDATE mentors SET user_id = ? WHERE name = ? LIMIT 1");
$findUser = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
foreach ($mentorAccountMap as $mentorName => $email) {
    $findUser->execute([$email]);
    $uid = (int)$findUser->fetchColumn();
    if ($uid > 0) {
        $linkStmt->execute([$uid, $mentorName]);
    }
}

// Remove duplicate mentor rows and remap requests to the earliest mentor id.
$dupRows = $pdo->query("
    SELECT name, role_title, expertise, MIN(id) AS keep_id
    FROM mentors
    GROUP BY name, role_title, expertise
    HAVING COUNT(*) > 1
")->fetchAll();
$selDup = $pdo->prepare("SELECT id FROM mentors WHERE name = ? AND role_title = ? AND expertise = ? ORDER BY id ASC");
$repReq = $pdo->prepare("UPDATE mentorship_requests SET mentor_id = ? WHERE mentor_id = ?");
$delDup = $pdo->prepare("DELETE FROM mentors WHERE id = ?");
foreach ($dupRows as $d) {
    $keepId = (int)$d['keep_id'];
    $selDup->execute([$d['name'], $d['role_title'], $d['expertise']]);
    $ids = array_map(fn($r) => (int)$r['id'], $selDup->fetchAll());
    foreach ($ids as $mid) {
        if ($mid === $keepId) continue;
        $repReq->execute([$keepId, $mid]);
        $delDup->execute([$mid]);
    }
}

// ----------------------------------------------------------------
// 6. Seed help resources
// ----------------------------------------------------------------
$resCount = (int)$pdo->query('SELECT COUNT(*) FROM help_resources')->fetchColumn();
if ($resCount === 0) {
    $resources = [
        ["Women's Support Center",    'Counselor',    'Free counseling and advocacy.',                '1-800-123-4567', '123 Main St',      '24/7'],
        ['Gender Rights Legal Aid',   'Lawyer',       'Pro-bono legal representation.',              '1-800-234-5678', '456 Oak Ave',      'Mon-Fri 9am-5pm'],
        ['National Crisis Support',   'Crisis Line',  'Immediate emotional support.',                 '988',            'Nationwide',       '24/7'],
        ['LGBTQ+ Resource Center',    'Support Group','Safe space and resources for LGBTQ+ pros.',  '1-800-345-6789', '789 Rainbow Blvd', 'Mon-Sun 8am-10pm'],
        ['Equal Pay Advocacy Network','Advocacy',     'Guidance on equal pay rights.',               '1-800-456-7890', 'Online & in-person','Tue-Thu 10am-4pm'],
    ];
    $ins = $pdo->prepare('INSERT INTO help_resources (title, type, description, contact_phone, contact_address, hours) VALUES (?,?,?,?,?,?)');
    foreach ($resources as $r) $ins->execute($r);
    $messages[] = "✅ Seeded <strong>" . count($resources) . " help resources</strong>.";
}

// ----------------------------------------------------------------
// 7. Seed lessons
// ----------------------------------------------------------------
$lessonCount = (int)$pdo->query('SELECT COUNT(*) FROM lessons')->fetchColumn();
if ($lessonCount === 0) {
    $lessons = [
        ['What is Gender Equality?',         'Gender equality means equal rights, responsibilities, and opportunities for all genders regardless of identity.',                 '5 min',  'Beginner',     'Foundations'],
        ['Why Leadership Diversity Matters',  'Companies with diverse leadership teams outperform peers by 36%. Inclusive leadership drives innovation and better outcomes.',    '7 min',  'Beginner',     'Leadership'],
        ['Understanding Unconscious Bias',    'Unconscious biases are hidden mental shortcuts that affect our decisions. In the workplace they impact hiring and promotions.',   '10 min', 'Intermediate', 'Bias & Inclusion'],
        ['The Gender Pay Gap Explained',      'The gender pay gap refers to the difference in average earnings between men and women. Learn its causes and how to close it.',  '8 min',  'Intermediate', 'Equal Pay'],
        ['Allyship in the Workplace',         'Allies actively support colleagues from marginalized groups by amplifying voices, challenging discrimination, and creating change.','6 min',  'Beginner',     'Allyship'],
        ['Navigating Workplace Discrimination','Know your rights and the steps to take when facing gender discrimination — documentation, reporting, and legal protections.',    '12 min', 'Advanced',     'Legal Rights'],
        ['Inclusive Language Guide',          'Words matter. This lesson explores gendered language, inclusive pronouns and titles, and building a welcoming environment.',    '5 min',  'Beginner',     'Communication'],
        ['Building Inclusive Teams',          'Leaders who build inclusive teams outperform peers. Learn equitable hiring, retention, and psychological safety strategies.',   '15 min', 'Advanced',     'Leadership'],
    ];
    $ins = $pdo->prepare('INSERT INTO lessons (title, content, duration, level, category) VALUES (?,?,?,?,?)');
    foreach ($lessons as $l) $ins->execute($l);
    $messages[] = "✅ Seeded <strong>" . count($lessons) . " lessons</strong>.";
}

// ----------------------------------------------------------------
// 8. Seed companies
// ----------------------------------------------------------------
$companyCount = (int)$pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
if ($companyCount === 0) {
    $companies = [
        ['TechCorp',      5.2,  45.0, 4.8, 92, 'fa-microchip'],
        ['Global Finance',12.1, 32.0, 3.9, 78, 'fa-chart-line'],
        ['HealthPlus',    2.8,  58.0, 4.9, 95, 'fa-heartbeat'],
        ['EcoEnergy',     1.5,  52.0, 4.7, 94, 'fa-leaf'],
        ['InnovateLab',   4.1,  48.0, 4.6, 91, 'fa-flask'],
        ['RetailGiant',   14.3, 29.0, 3.5, 72, 'fa-shopping-bag'],
        ['MediaWorks',    6.7,  43.0, 4.2, 85, 'fa-film'],
        ['EduTech',       3.2,  55.0, 4.8, 93, 'fa-graduation-cap'],
    ];
    $ins = $pdo->prepare('INSERT INTO companies (name, gender_pay_gap, women_in_leadership, rating, fairness_score, icon) VALUES (?,?,?,?,?,?)');
    foreach ($companies as $c) $ins->execute($c);
    $messages[] = "✅ Seeded <strong>" . count($companies) . " companies</strong>.";
}

// ----------------------------------------------------------------
// 9. Seed leadership data
// ----------------------------------------------------------------
$ldCount = (int)$pdo->query('SELECT COUNT(*) FROM leadership_data')->fetchColumn();
if ($ldCount === 0) {
    $ld = [
        ['EqualVoice Platform Overall', 28.0, 42.0, 8.0, 10.0, 7.0, 5.0, 1500],
        ['Tech Industry Average',       23.5, 58.0, 6.5,  5.5, 4.5, 2.0,  850],
        ['Finance Sector Average',      19.0, 65.0, 5.0,  4.0, 4.5, 2.5, 1200],
        ['Healthcare Average',          55.0, 32.0, 5.0,  4.0, 2.5, 1.5,  960],
    ];
    $ins = $pdo->prepare('INSERT INTO leadership_data (org_name, women_pct, men_pct, nonbinary_pct, transgender_pct, gay_pct, lesbian_pct, total_leaders) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($ld as $r) $ins->execute($r);
    $messages[] = "✅ Seeded <strong>" . count($ld) . " leadership records</strong>.";
}

// ----------------------------------------------------------------
// 10. Seed sample stories
// ----------------------------------------------------------------
$storyCount = (int)$pdo->query('SELECT COUNT(*) FROM stories')->fetchColumn();
if ($storyCount === 0) {
    $stories = [
        [null, 'After 5 years in tech, I finally negotiated a 20% raise using market data and mentor prep. Do not be afraid to advocate for yourself!', 1],
        [null, 'Our company just announced equal parental leave for all genders. Small victories like this give me hope for real change.', 0],
        [null, 'As a non-binary engineer, I now work at a company with explicit inclusion policies. I finally feel seen and valued. Representation matters.', 1],
    ];
    $ins = $pdo->prepare('INSERT INTO stories (user_id, content, is_anonymous) VALUES (?,?,?)');
    foreach ($stories as $s) $ins->execute($s);
    $messages[] = "✅ Seeded <strong>" . count($stories) . " sample stories</strong>.";
}

$messages[] = "<br><strong style='color:#94C691;'>🎉 Setup complete! EqualVoice is ready to use.</strong>";

// ----------------------------------------------------------------
// Handle self-deletion
// ----------------------------------------------------------------
if (isset($_GET['delete'])) {
    @unlink(__FILE__);
    header('Location: /equalvoice/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EqualVoice Setup</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Inter',system-ui,sans-serif; background:linear-gradient(135deg,#B4A8E0,#ED8E89); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .card { background:#fff; border-radius:20px; padding:2.5rem; max-width:600px; width:100%; box-shadow:0 30px 80px rgba(0,0,0,.25); }
    h1 { font-size:1.75rem; font-weight:800; margin-bottom:.5rem; background:linear-gradient(135deg,#B4A8E0,#ED8E89); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .subtitle { color:#7a7a9a; font-size:.875rem; margin-bottom:1.5rem; }
    .msg { padding:.75rem 1rem; border-radius:10px; margin-bottom:.5rem; font-size:.875rem; background:#FAF6F2; border-left:4px solid #B4A8E0; line-height:1.6; }
    .msg.warn { background:#fff8e1; border-left-color:#f6b93b; }
    .actions { margin-top:1.5rem; display:flex; gap:.75rem; flex-wrap:wrap; }
    a.btn { display:inline-flex; align-items:center; gap:.5rem; background:linear-gradient(135deg,#B4A8E0,#ED8E89); color:#fff; padding:.75rem 1.5rem; border-radius:10px; text-decoration:none; font-weight:700; font-size:.875rem; }
    a.btn-del { background:linear-gradient(135deg,#ED8E89,#D67670); }
    a.btn:hover { opacity:.9; }
    .note { background:#fff3cd; border-left:4px solid #ffc107; padding:.75rem 1rem; border-radius:10px; font-size:.8rem; margin-top:1rem; color:#856404; }
    code { background:#e8ebff; padding:1px 6px; border-radius:4px; font-size:.85em; font-family:monospace; }
    .creds { background:#f8f9ff; border:1px solid #d0d4e8; border-radius:10px; padding:1rem; margin-top:1rem; font-size:.875rem; }
    .creds h4 { margin-bottom:.5rem; color:#4a4a6a; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; }
    .cred-row { display:flex; justify-content:space-between; padding:.375rem 0; border-bottom:1px solid #eef0ff; }
    .cred-row:last-child { border-bottom:none; }
  </style>
</head>
<body>
<div class="card">
  <h1>EqualVoice Setup</h1>
  <p class="subtitle">Database initialization complete</p>

  <?php foreach ($messages as $m): ?>
    <div class="msg <?= str_contains($m, '⚠️') ? 'warn' : '' ?>"><?= $m ?></div>
  <?php endforeach; ?>

  <div class="creds">
    <h4>Demo Login Credentials</h4>
    <div class="cred-row"><span>👑 Admin</span> <span><code>admin@equalvoice.com</code> / <code>admin123</code></span></div>
    <div class="cred-row"><span>👤 User</span>  <span><code>user@example.com</code> / <code>user123</code></span></div>
    <div class="cred-row"><span>🎓 Mentor (Elena)</span>  <span><code>mentor@equalvoice.com</code> / <code>mentor123</code></span></div>
    <div class="cred-row"><span>🎓 Mentor (Amina)</span>  <span><code>amina@equalvoice.com</code> / <code>mentor123</code></span></div>
    <div class="cred-row"><span>🎓 Mentor (Jordan)</span> <span><code>jordan@equalvoice.com</code> / <code>mentor123</code></span></div>
    <div class="cred-row"><span>🎓 Mentor (Marcus)</span> <span><code>marcus@equalvoice.com</code> / <code>mentor123</code></span></div>
    <div class="cred-row"><span>🎓 Mentor (Priya)</span>  <span><code>priya@equalvoice.com</code> / <code>mentor123</code></span></div>
  </div>

  <p class="note">⚠️ <strong>Security:</strong> Delete <code>setup.php</code> after confirming login works. Use the button below.</p>

  <div class="actions">
    <a class="btn" href="/equalvoice/login.php">Go to Login →</a>
    <a class="btn" href="/equalvoice/index.php">View Landing Page</a>
    <a class="btn btn-del" href="/equalvoice/setup.php?delete=1" onclick="return confirm('Delete setup.php? Make sure login works first!')">🗑 Delete setup.php</a>
  </div>
</div>
</body>
</html>
