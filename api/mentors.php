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
 * Ensure mentor-account linkage exists on older databases.
 */
function ensureMentorSchema(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $col = $db->query("SHOW COLUMNS FROM mentors LIKE 'user_id'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE mentors ADD COLUMN user_id INT NULL AFTER id");
            // Best effort index + FK (ignore if engine/version denies duplicate/constraint)
            try { $db->exec("ALTER TABLE mentors ADD INDEX idx_mentors_user_id (user_id)"); } catch (Throwable $e) {}
            try { $db->exec("ALTER TABLE mentors ADD CONSTRAINT fk_mentors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL"); } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {
        // Keep API usable even if schema patch fails.
    }
}

ensureMentorSchema($db);

/**
 * Remove duplicate mentor profiles (same name + role + expertise).
 * Keeps the oldest row and remaps mentorship requests.
 */
function dedupeMentors(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $dups = $db->query("
            SELECT name, role_title, expertise, MIN(id) AS keep_id
            FROM mentors
            GROUP BY name, role_title, expertise
            HAVING COUNT(*) > 1
        ")->fetchAll();

        $reqRepoint = $db->prepare('UPDATE mentorship_requests SET mentor_id = ? WHERE mentor_id = ?');
        $selectIds  = $db->prepare('SELECT id, user_id FROM mentors WHERE name = ? AND role_title = ? AND expertise = ? ORDER BY id ASC');
        $setUserId  = $db->prepare('UPDATE mentors SET user_id = ? WHERE id = ?');
        $delMentor  = $db->prepare('DELETE FROM mentors WHERE id = ?');

        foreach ($dups as $d) {
            $keepId = (int)$d['keep_id'];
            $selectIds->execute([$d['name'], $d['role_title'], $d['expertise']]);
            $rows = $selectIds->fetchAll();
            if (count($rows) < 2) continue;

            // If the kept row has no linked account but a duplicate has one, keep the linkage.
            $keepUserId = (int)($rows[0]['user_id'] ?? 0);
            if ($keepUserId === 0) {
                foreach ($rows as $r) {
                    $uid = (int)($r['user_id'] ?? 0);
                    if ($uid > 0) {
                        $setUserId->execute([$uid, $keepId]);
                        break;
                    }
                }
            }

            foreach ($rows as $r) {
                $id = (int)$r['id'];
                if ($id === $keepId) continue;
                $reqRepoint->execute([$keepId, $id]);
                $delMentor->execute([$id]);
            }
        }
    } catch (Throwable $e) {
        // Keep API functional if dedupe fails.
    }
}

dedupeMentors($db);

// GET — list mentors
if ($method === 'GET' && !$action) {
    $stmt = $db->query('SELECT * FROM mentors WHERE is_available = 1 ORDER BY rating DESC, id ASC');
    jsonSuccess(['mentors' => $stmt->fetchAll()]);
}

// GET — admin list (includes unavailable mentors)
if ($method === 'GET' && $action === 'admin_list') {
    requireAdmin();
    $stmt = $db->query('SELECT * FROM mentors ORDER BY rating DESC, id ASC');
    jsonSuccess(['mentors' => $stmt->fetchAll()]);
}

// GET — my requests
if ($method === 'GET' && $action === 'my_requests') {
    requireLogin();
    $stmt = $db->prepare('
        SELECT mr.*, m.name AS mentor_name, m.role_title, m.icon
        FROM mentorship_requests mr
        JOIN mentors m ON m.id = mr.mentor_id
        WHERE mr.user_id = ?
        ORDER BY mr.created_at DESC
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['time_ago'] = timeAgo($r['created_at']); }
    jsonSuccess(['requests' => $rows]);
}

// GET — incoming requests for mentor accounts (or admin)
if ($method === 'GET' && $action === 'incoming_requests') {
    requireLogin();
    $userId = (int)$_SESSION['user_id'];
    $role   = $_SESSION['user_role'] ?? 'user';

    if ($role === 'admin') {
        $stmt = $db->query('
            SELECT mr.*, m.name AS mentor_name, m.role_title, m.icon,
                   u.name AS requester_name, u.email AS requester_email, u.avatar_color AS requester_color
            FROM mentorship_requests mr
            JOIN mentors m ON m.id = mr.mentor_id
            JOIN users u   ON u.id = mr.user_id
            ORDER BY mr.created_at DESC
        ');
        $rows = $stmt->fetchAll();
    } else {
        $stmt = $db->prepare('
            SELECT mr.*, m.name AS mentor_name, m.role_title, m.icon,
                   u.name AS requester_name, u.email AS requester_email, u.avatar_color AS requester_color
            FROM mentorship_requests mr
            JOIN mentors m ON m.id = mr.mentor_id
            JOIN users u   ON u.id = mr.user_id
            WHERE m.user_id = ?
            ORDER BY mr.created_at DESC
        ');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
    }

    foreach ($rows as &$r) { $r['time_ago'] = timeAgo($r['created_at']); }
    jsonSuccess(['requests' => $rows]);
}

// POST — send mentorship request
if ($method === 'POST' && $action === 'request') {
    requireLogin();
    $mentorId = (int)($body['mentor_id'] ?? 0);
    $message  = sanitize($body['message'] ?? '');
    $userId   = $_SESSION['user_id'];

    if (!$mentorId) jsonError('Please select a mentor.');
    if (strlen($message) < 10) jsonError('Please write a message of at least 10 characters.');

    // Block self-requests when the logged-in account is linked to this mentor profile.
    $mentor = $db->prepare('SELECT user_id FROM mentors WHERE id = ? LIMIT 1');
    $mentor->execute([$mentorId]);
    $mentorRow = $mentor->fetch();
    if (!$mentorRow) jsonError('Mentor not found.');
    if (!empty($mentorRow['user_id']) && (int)$mentorRow['user_id'] === $userId) {
        jsonError('You cannot request mentorship from your own mentor account.');
    }

    // Check for duplicate pending request
    $dup = $db->prepare('SELECT id FROM mentorship_requests WHERE user_id = ? AND mentor_id = ? AND status = "pending"');
    $dup->execute([$userId, $mentorId]);
    if ($dup->fetch()) jsonError('You already have a pending request with this mentor.');

    $stmt = $db->prepare('INSERT INTO mentorship_requests (user_id, mentor_id, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $mentorId, $message]);
    jsonSuccess(['id' => $db->lastInsertId()], 'Mentorship request sent!');
}

// POST — mentor/admin responds to a request
if ($method === 'POST' && $action === 'respond') {
    requireLogin();
    $requestId = (int)($body['request_id'] ?? 0);
    $status    = $body['status'] ?? '';
    $userId    = (int)$_SESSION['user_id'];
    $role      = $_SESSION['user_role'] ?? 'user';

    if (!$requestId) jsonError('Invalid request.');
    if (!in_array($status, ['accepted', 'declined'], true)) jsonError('Invalid response status.');

    if ($role === 'admin') {
        $check = $db->prepare('SELECT id FROM mentorship_requests WHERE id = ?');
        $check->execute([$requestId]);
    } else {
        $check = $db->prepare('
            SELECT mr.id
            FROM mentorship_requests mr
            JOIN mentors m ON m.id = mr.mentor_id
            WHERE mr.id = ? AND m.user_id = ?
        ');
        $check->execute([$requestId, $userId]);
    }
    if (!$check->fetch()) {
        jsonError('You are not allowed to respond to this request.', 403);
    }

    $upd = $db->prepare('UPDATE mentorship_requests SET status = ? WHERE id = ?');
    $upd->execute([$status, $requestId]);
    jsonSuccess([], 'Mentorship request updated.');
}

// POST — admin create mentor
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $name       = sanitize(trim((string)($body['name'] ?? '')));
    $roleTitle  = sanitize(trim((string)($body['role_title'] ?? '')));
    $expertise  = sanitize(trim((string)($body['expertise'] ?? '')));
    $bio        = sanitize(trim((string)($body['bio'] ?? '')));
    $rating     = (float)($body['rating'] ?? 0);
    $sessions   = (int)($body['sessions'] ?? 0);
    $icon       = sanitize(trim((string)($body['icon'] ?? 'fa-user')));
    $available  = isset($body['is_available']) ? (int)((bool)$body['is_available']) : 1;
    $email      = strtolower(trim((string)($body['mentor_email'] ?? '')));

    if ($name === '' || $roleTitle === '' || $expertise === '') jsonError('Name, role title, and expertise are required.');
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    if ($sessions < 0) $sessions = 0;

    $userId = null;
    if ($email !== '') {
        $u = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $u->execute([$email]);
        $urow = $u->fetch();
        if (!$urow) jsonError('Mentor account email not found in users table.');
        $userId = (int)$urow['id'];
    }

    $ins = $db->prepare('
        INSERT INTO mentors (user_id, name, role_title, expertise, bio, rating, sessions, is_available, icon)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([$userId, $name, $roleTitle, $expertise, $bio !== '' ? $bio : null, $rating, $sessions, $available, $icon !== '' ? $icon : 'fa-user']);
    jsonSuccess(['id' => (int)$db->lastInsertId()], 'Mentor created successfully.');
}

// POST — admin update mentor
if ($method === 'POST' && $action === 'update') {
    requireAdmin();
    $id         = (int)($body['id'] ?? 0);
    $name       = sanitize(trim((string)($body['name'] ?? '')));
    $roleTitle  = sanitize(trim((string)($body['role_title'] ?? '')));
    $expertise  = sanitize(trim((string)($body['expertise'] ?? '')));
    $bio        = sanitize(trim((string)($body['bio'] ?? '')));
    $rating     = (float)($body['rating'] ?? 0);
    $sessions   = (int)($body['sessions'] ?? 0);
    $icon       = sanitize(trim((string)($body['icon'] ?? 'fa-user')));
    $available  = isset($body['is_available']) ? (int)((bool)$body['is_available']) : 1;
    $email      = strtolower(trim((string)($body['mentor_email'] ?? '')));

    if ($id <= 0) jsonError('Invalid mentor ID.');
    if ($name === '' || $roleTitle === '' || $expertise === '') jsonError('Name, role title, and expertise are required.');
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    if ($sessions < 0) $sessions = 0;

    $userId = null;
    if ($email !== '') {
        $u = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $u->execute([$email]);
        $urow = $u->fetch();
        if (!$urow) jsonError('Mentor account email not found in users table.');
        $userId = (int)$urow['id'];
    }

    $upd = $db->prepare('
        UPDATE mentors
        SET user_id = ?, name = ?, role_title = ?, expertise = ?, bio = ?, rating = ?, sessions = ?, is_available = ?, icon = ?
        WHERE id = ?
    ');
    $upd->execute([$userId, $name, $roleTitle, $expertise, $bio !== '' ? $bio : null, $rating, $sessions, $available, $icon !== '' ? $icon : 'fa-user', $id]);
    jsonSuccess([], 'Mentor updated successfully.');
}

// POST — admin delete mentor
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) jsonError('Invalid mentor ID.');

    // Remove related requests first, then mentor.
    $db->prepare('DELETE FROM mentorship_requests WHERE mentor_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM mentors WHERE id = ?')->execute([$id]);
    jsonSuccess([], 'Mentor deleted successfully.');
}

jsonError('Invalid request.', 400);
