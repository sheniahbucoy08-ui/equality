<?php
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

function jsonSuccess(array $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return $_POST;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

function initials(string $name): string {
    $parts    = array_filter(explode(' ', trim($name)));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: '?';
}

function genderColor(string $identity): string {
    $map = [
        'woman'         => '#ED8E89',
        'women'         => '#ED8E89',
        'man'           => '#9BD6D9',
        'men'           => '#9BD6D9',
        'non-binary'    => '#F3EBA5',
        'nonbinary'     => '#F3EBA5',
        'transgender'   => '#F7B685',
        'trans'         => '#F7B685',
        'gay'           => '#B4A8E0',
        'lesbian'       => '#94C691',
        'bisexual'      => '#D6A8E0',
        'queer'         => '#F7C685',
    ];
    $key = strtolower(trim($identity));
    foreach ($map as $k => $color) {
        if (str_contains($key, $k)) return $color;
    }
    return '#B4A8E0';
}
