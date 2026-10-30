<?php

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_admin();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$stmt = db()->prepare(
'SELECT p.program_code, p.title, pt.name program_type, d.name department,
		p.current_status, s.name current_stage, r.name current_room,
		COALESCE(pp.total_minutes, 0) processing_minutes,
		COALESCE(pp.total_delay_minutes, 0) delay_minutes,
		COALESCE(pp.score, 0) performance_score
	 FROM programs p
	 LEFT JOIN program_types pt ON pt.id = p.program_type_id
	 LEFT JOIN departments d ON d.id = p.department_id
	 LEFT JOIN workflow_stages s ON s.id = p.current_stage_id
	 LEFT JOIN rooms r ON r.id = p.current_room_id
	 LEFT JOIN program_performance pp ON pp.program_id = p.id
	 WHERE DATE(p.created_at) BETWEEN ? AND ?
	 ORDER BY p.created_at DESC'
);
$stmt->execute([$from, $to]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="obn-program-report-' . $from . '-to-' . $to . '.csv"');
$output = fopen('php://output', 'w');
fputcsv(
$output,
[
'Program ID',
'Program name',
'Type',
'Department',
'Status',
'Current stage',
'Current room',
'Processing minutes',
'Delay minutes',
'Performance score',
]
);
while ($row = $stmt->fetch()) {
    fputcsv($output, $row);
    }
fclose($output);
