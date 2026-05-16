<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$db = getPDO();

// --- Quick stats --------------------------------------------
$stats = [
    'total_users'        => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'admin_users'        => (int)$db->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn(),
    'total_stories'      => (int)$db->query('SELECT COUNT(*) FROM stories')->fetchColumn(),
    'anon_stories'       => (int)$db->query('SELECT COUNT(*) FROM stories WHERE is_anonymous = 1')->fetchColumn(),
    'open_reports'       => (int)$db->query('SELECT COUNT(*) FROM reports WHERE status = "open"')->fetchColumn(),
    'resolved_reports'   => (int)$db->query('SELECT COUNT(*) FROM reports WHERE status = "resolved"')->fetchColumn(),
    'total_mentors'      => (int)$db->query('SELECT COUNT(*) FROM mentors WHERE is_available = 1')->fetchColumn(),
    'mentorship_requests'=> (int)$db->query('SELECT COUNT(*) FROM mentorship_requests')->fetchColumn(),
    'total_lessons'      => (int)$db->query('SELECT COUNT(*) FROM lessons')->fetchColumn(),
    'lesson_completions' => (int)$db->query('SELECT COUNT(*) FROM user_lesson_progress')->fetchColumn(),
    'total_likes'        => (int)$db->query('SELECT COUNT(*) FROM story_likes')->fetchColumn(),
    'companies_tracked'  => (int)$db->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
];

// --- User growth (last 30 days) -----------------------------
$growthRaw = $db->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

$growthMap = [];
foreach ($growthRaw as $row) $growthMap[$row['day']] = (int)$row['count'];

$growth = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $growth[] = [
        'date'  => $date,
        'label' => date('M j', strtotime($date)),
        'count' => $growthMap[$date] ?? 0,
    ];
}

// --- Users by role ------------------------------------------
$roleStats = [
    'user'  => (int)$db->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
    'admin' => $stats['admin_users'],
];

// --- Users by gender identity -------------------------------
$genderRows = $db->query("
    SELECT gender_identity, COUNT(*) AS count
    FROM users
    WHERE gender_identity IS NOT NULL AND gender_identity != ''
    GROUP BY gender_identity
    ORDER BY count DESC
")->fetchAll();

$genderBreakdown = [];
foreach ($genderRows as $row) {
    $key = strtolower($row['gender_identity']);
    $color = '#B4A8E0';
    if (str_contains($key, 'lesbian'))               $color = '#94C691';
    elseif (str_contains($key, 'gay'))               $color = '#B4A8E0';
    elseif (str_contains($key, 'transgender') || str_contains($key, 'trans')) $color = '#F7B685';
    elseif (str_contains($key, 'non-binary') || str_contains($key, 'nonbinary')) $color = '#F3EBA5';
    elseif (str_contains($key, 'woman'))             $color = '#ED8E89';
    elseif (str_contains($key, 'man'))               $color = '#9BD6D9';

    $genderBreakdown[] = [
        'label' => $row['gender_identity'],
        'count' => (int)$row['count'],
        'color' => $color,
    ];
}

// --- Reports by type ----------------------------------------
$reportTypes = $db->query("
    SELECT issue_type, COUNT(*) AS count
    FROM reports
    GROUP BY issue_type
    ORDER BY count DESC
    LIMIT 8
")->fetchAll();

// --- Recent activity ----------------------------------------
$recentUsers = $db->query("
    SELECT id, name, email, role, gender_identity, avatar_color, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

$recentReports = $db->query("
    SELECT r.id, r.issue_type, r.status, r.is_anonymous, r.created_at, u.name AS reporter
    FROM reports r
    LEFT JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

foreach ($recentUsers as &$u)   $u['time_ago'] = timeAgo($u['created_at']);
foreach ($recentReports as &$r) $r['time_ago'] = timeAgo($r['created_at']);

jsonSuccess([
    'stats'           => $stats,
    'user_growth'     => $growth,
    'role_breakdown'  => $roleStats,
    'gender_breakdown'=> $genderBreakdown,
    'report_types'    => $reportTypes,
    'recent_users'    => $recentUsers,
    'recent_reports'  => $recentReports,
]);
