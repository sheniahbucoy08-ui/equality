<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$body   = getRequestBody();

function ensureMentorSchemaForAdminUsers(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $col = $db->query("SHOW COLUMNS FROM mentors LIKE 'user_id'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE mentors ADD COLUMN user_id INT NULL AFTER id");
            try { $db->exec("ALTER TABLE mentors ADD INDEX idx_mentors_user_id (user_id)"); } catch (Throwable $e) {}
            try { $db->exec("ALTER TABLE mentors ADD CONSTRAINT fk_mentors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL"); } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {
        // keep working without hard fail
    }
}

ensureMentorSchemaForAdminUsers($db);

// GET — list all users
if ($method === 'GET') {
    $stmt = $db->query('
        SELECT u.id, u.name, u.email, u.role, u.gender_identity, u.avatar_color, u.department, u.bio, u.interests, u.goals, u.created_at, u.last_login,
               m.id AS mentor_profile_id
        FROM users u
        LEFT JOIN mentors m ON m.user_id = u.id
        ORDER BY u.created_at DESC
    ');
    jsonSuccess(['users' => $stmt->fetchAll()]);
}

// POST — change role, update profile, or delete
if ($method === 'POST') {
    $action = $body['action'] ?? '';
    $id     = (int)($body['id'] ?? 0);
    $myId   = (int)$_SESSION['user_id'];

    if (!$id) jsonError('Invalid user ID.');
    if ($id === $myId) jsonError('You cannot modify your own account this way.');

    if ($action === 'change_role') {
        $role = $body['role'] ?? '';
        if (!in_array($role, ['user', 'admin'])) jsonError('Invalid role.');
        $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        jsonSuccess([], "User role changed to $role.");
    }

    if ($action === 'update_profile') {
        $name           = trim((string)($body['name'] ?? ''));
        $email          = trim((string)($body['email'] ?? ''));
        $department     = trim((string)($body['department'] ?? ''));
        $genderIdentity = trim((string)($body['gender_identity'] ?? ''));
        $bio            = trim((string)($body['bio'] ?? ''));
        $interests      = trim((string)($body['interests'] ?? ''));
        $goals          = trim((string)($body['goals'] ?? ''));
        $avatarColor    = strtoupper(trim((string)($body['avatar_color'] ?? '')));

        if ($name === '' || mb_strlen($name) < 2) jsonError('Name must be at least 2 characters.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address.');

        // Prevent duplicate email collisions.
        $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) jsonError('That email is already used by another account.');

        $allowedColors = ['#ED8E89','#F7B685','#F3EBA5','#94C691','#9BD6D9','#B4A8E0','#9C8FCC','#7BB87F','#D67670','#E89B65','#7AC0C4','#4A3D7A'];
        if (!in_array($avatarColor, $allowedColors, true)) {
            $avatarColor = '#B4A8E0';
        }

        $stmt = $db->prepare('
            UPDATE users
            SET name = ?, email = ?, department = ?, gender_identity = ?, bio = ?, interests = ?, goals = ?, avatar_color = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $name,
            $email,
            $department !== '' ? $department : null,
            $genderIdentity !== '' ? $genderIdentity : null,
            $bio !== '' ? $bio : null,
            $interests !== '' ? $interests : null,
            $goals !== '' ? $goals : null,
            $avatarColor,
            $id,
        ]);

        jsonSuccess([], 'User profile updated successfully.');
    }

    if ($action === 'promote_to_mentor') {
        // Fetch source user.
        $uStmt = $db->prepare('SELECT id, name, email, department, bio FROM users WHERE id = ? LIMIT 1');
        $uStmt->execute([$id]);
        $user = $uStmt->fetch();
        if (!$user) jsonError('User not found.');

        // Already linked?
        $mCheck = $db->prepare('SELECT id FROM mentors WHERE user_id = ? LIMIT 1');
        $mCheck->execute([$id]);
        $existing = $mCheck->fetch();
        if ($existing) {
            jsonSuccess(['mentor_id' => (int)$existing['id']], 'User is already promoted as mentor.');
        }

        $defaultRole = ($user['department'] ?? '') !== '' ? ($user['department'] . ' Mentor') : 'Mentor';
        $defaultExpertise = 'Mentorship and inclusive leadership support';
        $defaultBio = ($user['bio'] ?? '') !== '' ? $user['bio'] : ('Mentor account linked to ' . $user['name'] . '.');

        $ins = $db->prepare('
            INSERT INTO mentors (user_id, name, role_title, expertise, bio, rating, sessions, is_available, icon)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $ins->execute([
            (int)$user['id'],
            $user['name'],
            $defaultRole,
            $defaultExpertise,
            $defaultBio,
            4.5,
            0,
            1,
            'fa-user-graduate',
        ]);

        jsonSuccess(['mentor_id' => (int)$db->lastInsertId()], 'User promoted to mentor account.');
    }

    if ($action === 'delete') {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        jsonSuccess([], 'User deleted.');
    }

    jsonError('Invalid action.');
}

jsonError('Invalid request.', 400);
