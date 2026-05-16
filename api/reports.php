<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$body   = getRequestBody();
$action = $body['action'] ?? ($_GET['action'] ?? '');

// GET — admin: all reports
if ($method === 'GET') {
    requireAdmin();
    $status = $_GET['status'] ?? '';
    $sql    = 'SELECT r.*, u.name AS reporter_name FROM reports r LEFT JOIN users u ON u.id = r.user_id';
    if ($status) $sql .= " WHERE r.status = " . $db->quote($status);
    $sql .= ' ORDER BY r.created_at DESC';
    $rows = $db->query($sql)->fetchAll();
    foreach ($rows as &$r) { $r['time_ago'] = timeAgo($r['created_at']); }
    jsonSuccess(['reports' => $rows, 'total' => count($rows)]);
}

// POST — create report
if ($method === 'POST' && $action === 'create') {
    $issueType   = sanitize($body['issue_type'] ?? '');
    $description = sanitize($body['description'] ?? '');
    $isAnon      = !empty($body['is_anonymous']) ? 1 : 0;
    $userId      = $_SESSION['user_id'] ?? null;

    if (!$issueType) jsonError('Please select an issue type.');
    if (strlen($description) < 20) jsonError('Please describe the issue in at least 20 characters.');

    $stmt = $db->prepare('INSERT INTO reports (user_id, issue_type, description, is_anonymous) VALUES (?, ?, ?, ?)');
    $stmt->execute([$isAnon ? null : $userId, $issueType, $description, $isAnon]);
    jsonSuccess(['id' => $db->lastInsertId()], 'Report submitted. Our team will review it shortly.');
}

// POST — resolve report (admin)
if ($method === 'POST' && $action === 'resolve') {
    requireAdmin();
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonError('Invalid report ID.');
    $db->prepare('UPDATE reports SET status = "resolved" WHERE id = ?')->execute([$id]);
    jsonSuccess([], 'Report marked as resolved.');
}

// POST — delete report (admin)
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $id = (int)($body['id'] ?? 0);
    $db->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
    jsonSuccess([], 'Report deleted.');
}

jsonError('Invalid request.', 400);
