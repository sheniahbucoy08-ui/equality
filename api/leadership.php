<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = getPDO();
$body   = getRequestBody();

// GET — fetch all leadership data
if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM leadership_data ORDER BY id ASC');
    $rows = $stmt->fetchAll();
    jsonSuccess(['data' => $rows]);
}

// POST — update leadership data (admin only)
if ($method === 'POST') {
    requireAdmin();

    $action  = $body['action'] ?? '';
    $orgName = sanitize($body['org_name'] ?? '');

    if ($action === 'update') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) jsonError('Invalid record ID.');

        $stmt = $db->prepare('
            UPDATE leadership_data
            SET org_name = ?, women_pct = ?, men_pct = ?, nonbinary_pct = ?,
                transgender_pct = ?, gay_pct = ?, lesbian_pct = ?,
                total_leaders = ?, updated_by = ?
            WHERE id = ?
        ');
        $stmt->execute([
            sanitize($body['org_name'] ?? ''),
            (float)($body['women_pct'] ?? 0),
            (float)($body['men_pct'] ?? 0),
            (float)($body['nonbinary_pct'] ?? 0),
            (float)($body['transgender_pct'] ?? 0),
            (float)($body['gay_pct'] ?? 0),
            (float)($body['lesbian_pct'] ?? 0),
            (int)($body['total_leaders'] ?? 0),
            $_SESSION['user_id'],
            $id,
        ]);
        jsonSuccess([], 'Leadership data updated.');
    }

    if ($action === 'create') {
        $stmt = $db->prepare('
            INSERT INTO leadership_data
            (org_name, women_pct, men_pct, nonbinary_pct, transgender_pct, gay_pct, lesbian_pct, total_leaders, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            sanitize($body['org_name'] ?? 'New Organization'),
            (float)($body['women_pct'] ?? 0),
            (float)($body['men_pct'] ?? 0),
            (float)($body['nonbinary_pct'] ?? 0),
            (float)($body['transgender_pct'] ?? 0),
            (float)($body['gay_pct'] ?? 0),
            (float)($body['lesbian_pct'] ?? 0),
            (int)($body['total_leaders'] ?? 0),
            $_SESSION['user_id'],
        ]);
        jsonSuccess(['id' => $db->lastInsertId()], 'Organization added.');
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) jsonError('Invalid ID.');
        $db->prepare('DELETE FROM leadership_data WHERE id = ?')->execute([$id]);
        jsonSuccess([], 'Record deleted.');
    }

    jsonError('Invalid action.');
}

jsonError('Invalid request.', 400);
