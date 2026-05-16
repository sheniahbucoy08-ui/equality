<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getPDO();
$stmt = $db->query('SELECT * FROM help_resources ORDER BY type, title');
$resources = $stmt->fetchAll();

// Assign icon per type
$typeIcons = [
    'Counselor'    => ['icon' => 'fa-heart', 'color' => '#94C691'],
    'Lawyer'       => ['icon' => 'fa-gavel', 'color' => '#B4A8E0'],
    'Crisis Line'  => ['icon' => 'fa-phone-alt', 'color' => '#ED8E89'],
    'Support Group'=> ['icon' => 'fa-users', 'color' => '#F7B685'],
    'Advocacy'     => ['icon' => 'fa-fist-raised', 'color' => '#F3EBA5'],
];

foreach ($resources as &$r) {
    $meta = $typeIcons[$r['type']] ?? ['icon' => 'fa-info-circle', 'color' => '#B4A8E0'];
    $r['icon']  = $meta['icon'];
    $r['color'] = $meta['color'];
}

jsonSuccess(['resources' => $resources]);
