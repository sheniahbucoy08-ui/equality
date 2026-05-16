<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getPDO();
$sort = $_GET['sort'] ?? 'fairness_score';
$allowed = ['fairness_score','rating','gender_pay_gap','women_in_leadership','name'];
if (!in_array($sort, $allowed)) $sort = 'fairness_score';

$dir = $sort === 'gender_pay_gap' ? 'ASC' : 'DESC';
$stmt = $db->query("SELECT * FROM companies ORDER BY $sort $dir");
$companies = $stmt->fetchAll();

// Assign fairness color based on score
foreach ($companies as &$c) {
    $score = (int)$c['fairness_score'];
    if ($score >= 90) { $c['score_color'] = '#94C691'; $c['score_grade'] = 'A'; }
    elseif ($score >= 80) { $c['score_color'] = '#9BD6D9'; $c['score_grade'] = 'B'; }
    elseif ($score >= 70) { $c['score_color'] = '#F7B685'; $c['score_grade'] = 'C'; }
    else                  { $c['score_color'] = '#ED8E89'; $c['score_grade'] = 'D'; }
}

jsonSuccess(['companies' => $companies]);
