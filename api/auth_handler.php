<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$body   = getRequestBody();
$action = $body['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    // -------------------------------------------------------
    case 'login':
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            jsonError('Email and password are required.');
        }

        $db   = getPDO();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonError('Invalid email or password.', 401);
        }

        // Update last_login
        $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

        setUserSession($user);

        jsonSuccess([
            'user' => [
                'id'           => $user['id'],
                'name'         => $user['name'],
                'email'        => $user['email'],
                'role'         => $user['role'],
                'avatar_color' => $user['avatar_color'],
            ],
            'redirect' => '/equalvoice/dashboard.php',
        ], 'Login successful');

    // -------------------------------------------------------
    case 'register':
        $name     = sanitize($body['name'] ?? '');
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $confirm  = $body['confirm_password'] ?? '';
        $gender   = sanitize($body['gender_identity'] ?? '');
        $dept     = sanitize($body['department'] ?? '');

        if (!$name || !$email || !$password) {
            jsonError('Name, email, and password are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Please enter a valid email address.');
        }
        if (strlen($password) < 6) {
            jsonError('Password must be at least 6 characters.');
        }
        if ($password !== $confirm) {
            jsonError('Passwords do not match.');
        }

        $db   = getPDO();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonError('An account with this email already exists.', 409);
        }

        $avatarColors = ['#ED8E89','#F7B685','#F3EBA5','#94C691','#9BD6D9','#B4A8E0','#9C8FCC','#7BB87F'];
        $color = $avatarColors[array_rand($avatarColors)];

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins  = $db->prepare('
            INSERT INTO users (name, email, password_hash, role, gender_identity, avatar_color, department)
            VALUES (:name, :email, :hash, :role, :gender, :color, :dept)
        ');
        $ins->execute([
            ':name'   => $name,
            ':email'  => $email,
            ':hash'   => $hash,
            ':role'   => 'user',
            ':gender' => $gender ?: null,
            ':color'  => $color,
            ':dept'   => $dept ?: null,
        ]);
        $userId = (int)$db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        setUserSession($user);

        jsonSuccess([
            'user'     => ['id' => $userId, 'name' => $name, 'role' => 'user'],
            'redirect' => '/equalvoice/dashboard.php',
        ], 'Account created successfully!');

    // -------------------------------------------------------
    case 'logout':
        clearUserSession();
        jsonSuccess(['redirect' => '/equalvoice/index.php'], 'Logged out');

    // -------------------------------------------------------
    default:
        jsonError('Invalid action.', 400);
}
