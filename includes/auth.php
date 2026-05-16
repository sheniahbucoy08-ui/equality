<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /equalvoice/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: /equalvoice/dashboard.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function currentUser(): array {
    return [
        'id'           => $_SESSION['user_id']           ?? null,
        'name'         => $_SESSION['user_name']         ?? 'Guest',
        'email'        => $_SESSION['user_email']        ?? '',
        'role'         => $_SESSION['user_role']         ?? 'guest',
        'avatar_color' => $_SESSION['user_avatar_color'] ?? '#B4A8E0',
        'gender_identity' => $_SESSION['user_gender']   ?? '',
    ];
}

function setUserSession(array $user): void {
    $_SESSION['user_id']           = $user['id'];
    $_SESSION['user_name']         = $user['name'];
    $_SESSION['user_email']        = $user['email'];
    $_SESSION['user_role']         = $user['role'];
    $_SESSION['user_avatar_color'] = $user['avatar_color'] ?? '#B4A8E0';
    $_SESSION['user_gender']       = $user['gender_identity'] ?? '';
}

function clearUserSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}
