<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$body   = getRequestBody();
$action = $body['action'] ?? ($_GET['action'] ?? '');

// GET — fetch stories list
if ($method === 'GET' && !$action) {
    $stmt = $db->query("
        SELECT s.id, s.content, s.is_anonymous, s.created_at, s.user_id,
               CASE WHEN s.is_anonymous = 1 THEN 'Anonymous' ELSE COALESCE(u.name, 'Community Member') END AS author_name,
               CASE WHEN s.is_anonymous = 1 THEN '#8B85A0' ELSE COALESCE(u.avatar_color, '#B4A8E0') END AS author_color,
               COALESCE(u.gender_identity, '') AS gender_identity,
               COUNT(DISTINCT sl.id) AS like_count
        FROM stories s
        LEFT JOIN users u  ON u.id = s.user_id
        LEFT JOIN story_likes sl ON sl.story_id = s.id
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT 50
    ");
    $stories = $stmt->fetchAll();

    // Mark likes for current user
    $userId = $_SESSION['user_id'] ?? null;
    $likedIds = [];
    if ($userId) {
        $liked = $db->prepare('SELECT story_id FROM story_likes WHERE user_id = ?');
        $liked->execute([$userId]);
        $likedIds = array_column($liked->fetchAll(), 'story_id');
    }

    foreach ($stories as &$s) {
        $s['liked_by_me'] = in_array($s['id'], $likedIds);
        $s['time_ago']    = timeAgo($s['created_at']);
        $s['like_count']  = (int)$s['like_count'];
        $s['is_anonymous']= (int)$s['is_anonymous'];
        // Defensive: ensure author_name is never null
        if (empty($s['author_name'])) $s['author_name'] = 'Community Member';
        if (empty($s['author_color'])) $s['author_color'] = '#B4A8E0';
    }

    jsonSuccess(['stories' => $stories]);
}

// POST — create story
if ($method === 'POST' && $action === 'create') {
    requireLogin();
    $content    = trim($body['content'] ?? '');
    $isAnon     = !empty($body['is_anonymous']) ? 1 : 0;
    $userId     = $_SESSION['user_id'];

    if (strlen($content) < 10) jsonError('Story must be at least 10 characters.');
    if (strlen($content) > 2000) jsonError('Story cannot exceed 2000 characters.');

    $stmt = $db->prepare('INSERT INTO stories (user_id, content, is_anonymous) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $content, $isAnon]);
    jsonSuccess(['id' => $db->lastInsertId()], 'Story shared successfully!');
}

// POST — toggle like
if ($method === 'POST' && $action === 'like') {
    requireLogin();
    $storyId = (int)($body['story_id'] ?? 0);
    $userId  = $_SESSION['user_id'];

    if (!$storyId) jsonError('Invalid story.');

    // Check if already liked
    $check = $db->prepare('SELECT id FROM story_likes WHERE story_id = ? AND user_id = ?');
    $check->execute([$storyId, $userId]);
    if ($check->fetch()) {
        $db->prepare('DELETE FROM story_likes WHERE story_id = ? AND user_id = ?')->execute([$storyId, $userId]);
        $liked = false;
    } else {
        $db->prepare('INSERT INTO story_likes (story_id, user_id) VALUES (?, ?)')->execute([$storyId, $userId]);
        $liked = true;
    }

    $count = $db->prepare('SELECT COUNT(*) FROM story_likes WHERE story_id = ?');
    $count->execute([$storyId]);
    jsonSuccess(['liked' => $liked, 'count' => (int)$count->fetchColumn()]);
}

jsonError('Invalid request.', 400);
