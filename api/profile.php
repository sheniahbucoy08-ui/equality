<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$userId = $_SESSION['user_id'];

// GET — fetch profile + stats
if ($method === 'GET') {
    $stmt = $db->prepare('SELECT id, name, email, role, gender_identity, avatar_color, bio, department, interests, goals, created_at, last_login FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) jsonError('User not found.', 404);

    // Count stories
    $stories = $db->prepare('SELECT COUNT(*) FROM stories WHERE user_id = ?');
    $stories->execute([$userId]);
    $user['stories_shared'] = (int)$stories->fetchColumn();

    // Count lessons
    $lessons = $db->prepare('SELECT COUNT(*) FROM user_lesson_progress WHERE user_id = ?');
    $lessons->execute([$userId]);
    $user['lessons_completed'] = (int)$lessons->fetchColumn();

    // Count mentorship requests
    $mentorship = $db->prepare('SELECT COUNT(*) FROM mentorship_requests WHERE user_id = ?');
    $mentorship->execute([$userId]);
    $user['mentorship_requests'] = (int)$mentorship->fetchColumn();

    // Recent activity
    $activity = [];
    // Latest stories
    $latestStory = $db->prepare('SELECT created_at FROM stories WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $latestStory->execute([$userId]);
    if ($row = $latestStory->fetch()) {
        $activity[] = ['type' => 'story', 'icon' => 'fa-book-open', 'color' => '#ED8E89', 'title' => 'Shared a story', 'time' => timeAgo($row['created_at'])];
    }
    // Latest lesson
    $latestLesson = $db->prepare('SELECT ulp.completed_at, l.title FROM user_lesson_progress ulp JOIN lessons l ON l.id = ulp.lesson_id WHERE ulp.user_id = ? ORDER BY ulp.completed_at DESC LIMIT 1');
    $latestLesson->execute([$userId]);
    if ($row = $latestLesson->fetch()) {
        $activity[] = ['type' => 'lesson', 'icon' => 'fa-graduation-cap', 'color' => '#94C691', 'title' => 'Completed: ' . $row['title'], 'time' => timeAgo($row['completed_at'])];
    }

    $user['activity'] = $activity;
    $user['member_since'] = date('F Y', strtotime($user['created_at']));

    jsonSuccess(['user' => $user]);
}

// POST — update profile
if ($method === 'POST') {
    $body = getRequestBody();
    $action = $body['action'] ?? '';

    if ($action === 'update_info') {
        $name       = sanitize($body['name'] ?? '');
        $bio        = sanitize($body['bio'] ?? '');
        $dept       = sanitize($body['department'] ?? '');
        $interests  = sanitize($body['interests'] ?? '');
        $goals      = sanitize($body['goals'] ?? '');
        $gender     = sanitize($body['gender_identity'] ?? '');

        if (!$name) jsonError('Name is required.');

        $stmt = $db->prepare('
            UPDATE users SET name = ?, bio = ?, department = ?, interests = ?, goals = ?, gender_identity = ? WHERE id = ?
        ');
        $stmt->execute([$name, $bio, $dept, $interests, $goals, $gender, $userId]);

        // Update session name
        $_SESSION['user_name'] = $name;
        $_SESSION['user_gender'] = $gender;

        jsonSuccess([], 'Profile updated successfully!');
    }

    if ($action === 'update_color') {
        $allowed = ['#ED8E89','#F7B685','#F3EBA5','#94C691','#9BD6D9','#B4A8E0','#9C8FCC','#7BB87F','#D67670','#E89B65','#7AC0C4','#4A3D7A'];
        $color = $body['avatar_color'] ?? '#B4A8E0';
        if (!in_array($color, $allowed)) jsonError('Invalid color.');

        $db->prepare('UPDATE users SET avatar_color = ? WHERE id = ?')->execute([$color, $userId]);
        $_SESSION['user_avatar_color'] = $color;
        jsonSuccess(['avatar_color' => $color], 'Avatar color updated!');
    }

    if ($action === 'change_password') {
        $current = $body['current_password'] ?? '';
        $new     = $body['new_password'] ?? '';
        $confirm = $body['confirm_password'] ?? '';

        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password_hash'])) jsonError('Current password is incorrect.');
        if (strlen($new) < 6) jsonError('New password must be at least 6 characters.');
        if ($new !== $confirm) jsonError('New passwords do not match.');

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        jsonSuccess([], 'Password changed successfully!');
    }

    jsonError('Invalid action.');
}

jsonError('Invalid request.', 400);
