<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$body   = getRequestBody();
$action = $body['action'] ?? ($_GET['action'] ?? '');

/**
 * Keep Learning Hub aligned with the requested canonical 8 lessons.
 * This safely upserts by title so existing progress remains intact.
 */
function ensureCanonicalLessons(PDO $db): void {
    $canonical = [
        [
            'title'    => 'What is Gender Equality?',
            'content'  => 'Gender equality means equal rights, responsibilities, and opportunities for all genders regardless of identity.',
            'duration' => '5 min',
            'level'    => 'Beginner',
            'category' => 'Foundations',
        ],
        [
            'title'    => 'Why Leadership Diversity Matters',
            'content'  => 'Companies with diverse leadership teams outperform peers by 36%. Inclusive leadership drives innovation and better outcomes.',
            'duration' => '7 min',
            'level'    => 'Beginner',
            'category' => 'Leadership',
        ],
        [
            'title'    => 'Understanding Unconscious Bias',
            'content'  => 'Unconscious biases are hidden mental shortcuts that affect our decisions. In the workplace they impact hiring and promotions.',
            'duration' => '10 min',
            'level'    => 'Intermediate',
            'category' => 'Bias & Inclusion',
        ],
        [
            'title'    => 'The Gender Pay Gap Explained',
            'content'  => 'The gender pay gap refers to the difference in average earnings between men and women. Learn its causes and how to close it.',
            'duration' => '8 min',
            'level'    => 'Intermediate',
            'category' => 'Equal Pay',
        ],
        [
            'title'    => 'Allyship in the Workplace',
            'content'  => 'Allies actively support colleagues from marginalized groups by amplifying voices, challenging discrimination, and creating change.',
            'duration' => '6 min',
            'level'    => 'Beginner',
            'category' => 'Allyship',
        ],
        [
            'title'    => 'Navigating Workplace Discrimination',
            'content'  => 'Know your rights and the steps to take when facing gender discrimination — documentation, reporting, and legal protections.',
            'duration' => '12 min',
            'level'    => 'Advanced',
            'category' => 'Legal Rights',
        ],
        [
            'title'    => 'Inclusive Language Guide',
            'content'  => 'Words matter. This lesson explores gendered language, inclusive pronouns and titles, and building a welcoming environment.',
            'duration' => '5 min',
            'level'    => 'Beginner',
            'category' => 'Communication',
        ],
        [
            'title'    => 'Building Inclusive Teams',
            'content'  => 'Leaders who build inclusive teams outperform peers. Learn equitable hiring, retention, and psychological safety strategies.',
            'duration' => '15 min',
            'level'    => 'Advanced',
            'category' => 'Leadership',
        ],
    ];

    $sel = $db->prepare('SELECT id FROM lessons WHERE title = ? LIMIT 1');
    $ins = $db->prepare('INSERT INTO lessons (title, content, duration, level, category) VALUES (?, ?, ?, ?, ?)');
    $upd = $db->prepare('UPDATE lessons SET content = ?, duration = ?, level = ?, category = ? WHERE id = ?');

    foreach ($canonical as $l) {
        $sel->execute([$l['title']]);
        $row = $sel->fetch();
        if ($row) {
            $upd->execute([$l['content'], $l['duration'], $l['level'], $l['category'], (int)$row['id']]);
        } else {
            $ins->execute([$l['title'], $l['content'], $l['duration'], $l['level'], $l['category']]);
        }
    }
}

// GET — list lessons + progress
if ($method === 'GET') {
    ensureCanonicalLessons($db);

    $canonicalTitles = [
        'What is Gender Equality?',
        'Why Leadership Diversity Matters',
        'Understanding Unconscious Bias',
        'The Gender Pay Gap Explained',
        'Allyship in the Workplace',
        'Navigating Workplace Discrimination',
        'Inclusive Language Guide',
        'Building Inclusive Teams',
    ];
    $inList = implode(',', array_fill(0, count($canonicalTitles), '?'));
    $orderExpr = implode(',', array_fill(0, count($canonicalTitles), '?'));

    $stmt = $db->prepare("
        SELECT *
        FROM lessons
        WHERE title IN ($inList)
        ORDER BY FIELD(title, $orderExpr)
    ");
    $stmt->execute(array_merge($canonicalTitles, $canonicalTitles));
    $lessons = $stmt->fetchAll();

    $userId = $_SESSION['user_id'] ?? null;
    $completedIds = [];
    if ($userId) {
        $prog = $db->prepare('SELECT lesson_id FROM user_lesson_progress WHERE user_id = ?');
        $prog->execute([$userId]);
        $completedIds = array_column($prog->fetchAll(), 'lesson_id');
    }

    $displayedIds = [];
    foreach ($lessons as &$l) {
        $l['completed'] = in_array($l['id'], $completedIds);
        $displayedIds[] = (int)$l['id'];
    }

    $total     = count($lessons);
    $completed = 0;
    foreach ($completedIds as $cid) {
        if (in_array((int)$cid, $displayedIds, true)) {
            $completed++;
        }
    }

    jsonSuccess([
        'lessons'   => $lessons,
        'completed' => $completed,
        'total'     => $total,
        'progress_pct' => $total > 0 ? round($completed / $total * 100) : 0,
    ]);
}

// POST — mark lesson complete
if ($method === 'POST' && $action === 'complete') {
    requireLogin();
    $lessonId = (int)($body['lesson_id'] ?? 0);
    $userId   = $_SESSION['user_id'];

    if (!$lessonId) jsonError('Invalid lesson.');

    // Upsert — ignore if already exists
    $stmt = $db->prepare('INSERT IGNORE INTO user_lesson_progress (user_id, lesson_id) VALUES (?, ?)');
    $stmt->execute([$userId, $lessonId]);
    jsonSuccess([], 'Lesson marked as complete!');
}

jsonError('Invalid request.', 400);
