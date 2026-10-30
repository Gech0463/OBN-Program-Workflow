<?php

require_once __DIR__ . '/../config/database.php';
function t(string $key, array $replace = [], string $language = 'en'): string
{
    static $strings = [];
    $language = in_array($language, ['en', 'om', 'am'], true) ? $language : 'en';
    if (!isset($strings[$language])) {
        $path = __DIR__ . '/../lang/' . $language . '.json';
        $strings[$language] = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
    }
    $value = $strings[$language][$key] ?? $strings['en'][$key] ?? $key;
    foreach ($replace as $name => $replacement) {
        $value = str_replace('{' . $name . '}', (string) $replacement, $value);
    }
    return $value;
}
function ensure_workflow_compatibility(): void {
    $pdo = db();
    $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(40) NOT NULL DEFAULT 'JOURNALIST'");
    $pdo->exec("ALTER TABLE programs ADD COLUMN IF NOT EXISTS source VARCHAR(150) NULL AFTER language");
    $pdo->exec("ALTER TABLE programs MODIFY current_status ENUM('WAITING','IN_PROGRESS','BLOCKED','DELAYED','ON_AIR','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING'");
    $pdo->exec("ALTER TABLE programs ADD COLUMN IF NOT EXISTS presenter VARCHAR(150) NULL AFTER producer_id");
    $pdo->exec("ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_stage_id INT UNSIGNED NULL AFTER current_stage_id");
    $pdo->exec("ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_room_id INT UNSIGNED NULL AFTER current_room_id");
    $pdo->exec("ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_employee_id INT UNSIGNED NULL AFTER current_employee_id");
    $pdo->exec("CREATE TABLE IF NOT EXISTS program_journal (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        program_id BIGINT UNSIGNED NOT NULL UNIQUE,
        program_date DATE NOT NULL,
        scheduled_time TIME NULL,
        presenter VARCHAR(150) NULL,
        producer VARCHAR(150) NULL,
        source VARCHAR(150) NULL,
        initial_location VARCHAR(150) NULL,
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_journal_date (program_date)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS program_movements (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        program_id BIGINT UNSIGNED NOT NULL,
        from_stage_id INT UNSIGNED NULL,
        from_room_id INT UNSIGNED NULL,
        to_stage_id INT UNSIGNED NOT NULL,
        to_room_id INT UNSIGNED NULL,
        moved_by INT UNSIGNED NULL,
        moved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        previous_duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
        notes TEXT NULL,
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
        FOREIGN KEY (from_stage_id) REFERENCES workflow_stages(id) ON DELETE SET NULL,
        FOREIGN KEY (from_room_id) REFERENCES rooms(id) ON DELETE SET NULL,
        FOREIGN KEY (to_stage_id) REFERENCES workflow_stages(id),
        FOREIGN KEY (to_room_id) REFERENCES rooms(id) ON DELETE SET NULL,
        FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_movements_program (program_id),
        INDEX idx_movements_date (moved_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS delay_records (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        program_id BIGINT UNSIGNED NOT NULL,
        stage_id INT UNSIGNED NOT NULL,
        reason VARCHAR(100) NOT NULL,
        minutes INT UNSIGNED NOT NULL DEFAULT 0,
        notes TEXT NULL,
        recorded_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
        FOREIGN KEY (stage_id) REFERENCES workflow_stages(id),
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS program_performance (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        program_id BIGINT UNSIGNED NOT NULL UNIQUE,
        total_minutes INT UNSIGNED NOT NULL DEFAULT 0,
        total_delay_minutes INT UNSIGNED NOT NULL DEFAULT 0,
        completed_stages INT UNSIGNED NOT NULL DEFAULT 0,
        total_stages INT UNSIGNED NOT NULL DEFAULT 9,
        score DECIMAL(5,2) NOT NULL DEFAULT 0,
        performance_label VARCHAR(40) NOT NULL DEFAULT 'GOOD',
        calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
    )");
    $pdo->exec("ALTER TABLE workflow_stages ADD COLUMN IF NOT EXISTS icon VARCHAR(40) NULL AFTER name");
    $pdo->exec("ALTER TABLE workflow_stages ADD COLUMN IF NOT EXISTS description VARCHAR(255) NULL AFTER icon");
    $pdo->exec("UPDATE workflow_stages SET active=0 WHERE stage_key NOT IN ('JOURNALIST','INGEST','PREVIEW','EDITING','GRAPHICS','AUDIO','QUALITY_CONTROL','MASTER_CONTROL','UPLINK')");
    $pdo->exec("INSERT INTO workflow_stages (stage_key, name, icon, description, sequence_no, expected_minutes, active)
        VALUES ('JOURNALIST','Journalist submission','fa-pen-to-square','The program is registered and submitted by the journalist.',1,0,1),('INGEST','Ingest','fa-upload','Media is received and transferred into the broadcast system.',2,30,1),('PREVIEW','Preview','fa-eye','The material is checked before editing.',3,20,1),('EDITING','Editing','fa-scissors','The program is assembled and edited.',4,120,1),('GRAPHICS','Graphics','fa-palette','Graphics and visual finishing are completed.',5,60,1),('AUDIO','Audio','fa-volume-high','Audio processing and mixing are completed.',6,60,1),('QUALITY_CONTROL','Quality control','fa-circle-check','Video, audio, graphics, and completeness are checked.',7,30,1),('MASTER_CONTROL','Master control','fa-tv','The final program is prepared for transmission.',8,30,1),('UPLINK','Uplink','fa-satellite-dish','The program reaches the transmission endpoint.',9,20,1)
        ON DUPLICATE KEY UPDATE name=VALUES(name), icon=VALUES(icon), description=VALUES(description), sequence_no=VALUES(sequence_no), expected_minutes=VALUES(expected_minutes), active=VALUES(active)");
    $pdo->exec("UPDATE programs p JOIN workflow_stages old_stage ON old_stage.id=p.current_stage_id JOIN workflow_stages new_stage ON new_stage.stage_key=CASE old_stage.stage_key WHEN 'PROGRAM_CREATED' THEN 'JOURNALIST' WHEN 'JOURNAL' THEN 'JOURNALIST' WHEN 'INITIATION' THEN 'JOURNALIST' WHEN 'DATA_CAPTURE' THEN 'INGEST' WHEN 'PRODUCTION_STUDIO' THEN 'GRAPHICS' WHEN 'CONTROL_PLAYOUT' THEN 'MASTER_CONTROL' WHEN 'TRANSMISSION' THEN 'MASTER_CONTROL' WHEN 'QC' THEN 'QUALITY_CONTROL' WHEN 'PLAYOUT' THEN 'MASTER_CONTROL' WHEN 'UPLINK_FINAL' THEN 'UPLINK' ELSE old_stage.stage_key END SET p.current_stage_id=new_stage.id WHERE old_stage.stage_key NOT IN ('JOURNALIST','INGEST','PREVIEW','EDITING','GRAPHICS','AUDIO','QUALITY_CONTROL','MASTER_CONTROL','UPLINK')");
    $pdo->exec("UPDATE program_stage_history h JOIN workflow_stages old_stage ON old_stage.id=h.stage_id JOIN workflow_stages new_stage ON new_stage.stage_key=CASE old_stage.stage_key WHEN 'PROGRAM_CREATED' THEN 'JOURNALIST' WHEN 'JOURNAL' THEN 'JOURNALIST' WHEN 'INITIATION' THEN 'JOURNALIST' WHEN 'DATA_CAPTURE' THEN 'INGEST' WHEN 'PRODUCTION_STUDIO' THEN 'GRAPHICS' WHEN 'CONTROL_PLAYOUT' THEN 'MASTER_CONTROL' WHEN 'TRANSMISSION' THEN 'MASTER_CONTROL' WHEN 'QC' THEN 'QUALITY_CONTROL' WHEN 'PLAYOUT' THEN 'MASTER_CONTROL' WHEN 'UPLINK_FINAL' THEN 'UPLINK' ELSE old_stage.stage_key END SET h.stage_id=new_stage.id WHERE old_stage.stage_key NOT IN ('JOURNALIST','INGEST','PREVIEW','EDITING','GRAPHICS','AUDIO','QUALITY_CONTROL','MASTER_CONTROL','UPLINK')");
    $pdo->exec("UPDATE programs SET current_status = 'IN_PROGRESS' WHERE current_status IN ('JOURNALED','IN_INGEST','IN_EDITING','IN_GRAPHICS','IN_AUDIO','IN_QC','READY_FOR_PLAYOUT','IN_MASTER_CONTROL','IN_UPLINK')");
    }
ensure_workflow_compatibility();
function workflow_stage_role(string $stageKey): string
{
    return [
        'JOURNALIST' => 'JOURNALIST',
        'INGEST' => 'INGEST_OPERATOR',
        'PREVIEW' => 'PREVIEW_OPERATOR',
        'EDITING' => 'EDITOR',
        'GRAPHICS' => 'GRAPHICS_OPERATOR',
        'AUDIO' => 'AUDIO_OPERATOR',
        'QUALITY_CONTROL' => 'QC_OPERATOR',
        'MASTER_CONTROL' => 'MASTER_CONTROL_OPERATOR',
        'UPLINK' => 'UPLINK_OPERATOR',
    ][$stageKey] ?? '';
}
function workflow_stage_keys_for_role(string $role): array
{
    $stageRoles = [
        'JOURNALIST' => 'JOURNALIST',
        'INGEST' => 'INGEST_OPERATOR',
        'PREVIEW' => 'PREVIEW_OPERATOR',
        'EDITING' => 'EDITOR',
        'GRAPHICS' => 'GRAPHICS_OPERATOR',
        'AUDIO' => 'AUDIO_OPERATOR',
        'QUALITY_CONTROL' => 'QC_OPERATOR',
        'MASTER_CONTROL' => 'MASTER_CONTROL_OPERATOR',
        'UPLINK' => 'UPLINK_OPERATOR',
    ];

    return array_keys(array_filter($stageRoles, static fn(string $stageRole): bool => $stageRole === $role));
}
function workflow_stage_room_types(string $stageKey): array
{
    return [
        'JOURNALIST' => ['News Room'],
        'INGEST' => ['Ingest Room'],
        'PREVIEW' => ['Ingest Room', 'Editing Room'],
        'EDITING' => ['Editing Room'],
        'GRAPHICS' => ['Editing Room', 'Studio'],
        'AUDIO' => ['Studio'],
        'QUALITY_CONTROL' => ['Control Room'],
        'MASTER_CONTROL' => ['Control Room'],
        'UPLINK' => ['Uplink Room'],
    ][$stageKey] ?? [];
}
function dashboard_stats(): array
{
    $pdo = db();
    $row = $pdo->query("SELECT COUNT(*) total, SUM(DATE(planned_start)=CURDATE()) today, SUM(current_status IN ('WAITING','IN_PROGRESS','BLOCKED','DELAYED','ON_AIR')) active, SUM(current_status='COMPLETED' AND DATE(completed_at)=CURDATE()) completed_today, SUM(current_status IN ('DELAYED','BLOCKED')) AS delayed_count, SUM(current_stage_id=(SELECT id FROM workflow_stages WHERE stage_key='INGEST' LIMIT 1)) ingest, SUM(current_stage_id=(SELECT id FROM workflow_stages WHERE stage_key='UPLINK' LIMIT 1)) uplink, SUM(planned_start >= CURDATE()-INTERVAL 6 DAY) week_total FROM programs")->fetch() ?: [];
    $average = $pdo->query('SELECT COALESCE(AVG(actual_minutes),0) FROM program_stage_history WHERE actual_end IS NOT NULL')->fetchColumn();
    return [
    'total' => (int)($row['total'] ?? 0),
    'today' => (int)($row['today'] ?? 0),
    'active' => (int)($row['active'] ?? 0),
    'completed_today' => (int)($row['completed_today'] ?? 0),
    'delayed' => (int)($row['delayed_count'] ?? 0),
    'ingest' => (int)($row['ingest'] ?? 0),
    'uplink' => (int)($row['uplink'] ?? 0),
    'week_total' => (int)($row['week_total'] ?? 0),
    'average_minutes' => (int)round((float)$average),
    ];
    }
function live_programs(): array
{
    return db()->query("SELECT p.*, s.name stage_name, r.name room_name, e.full_name responsible, TIMESTAMPDIFF(MINUTE,p.current_started_at,NOW()) elapsed_minutes, s.expected_minutes FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id WHERE p.current_status IN ('WAITING','IN_PROGRESS','BLOCKED','DELAYED','ON_AIR') ORDER BY p.priority='URGENT' DESC,p.updated_at DESC")->fetchAll();
    }
function all_programs(string $search = ''): array
{
    $stmt = db()->prepare("SELECT p.*,s.name stage_name,r.name room_name,e.full_name responsible,pt.name type_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id LEFT JOIN program_types pt ON pt.id=p.program_type_id WHERE p.title LIKE ? OR p.program_code LIKE ? ORDER BY p.updated_at DESC");
    $term = '%' . $search . '%';
    $stmt->execute([$term, $term]);
    return $stmt->fetchAll();
    }
function journalist_programs(int $userId, string $search = ''): array
{
    $stmt = db()->prepare("SELECT p.*,s.name stage_name,r.name room_name,e.full_name responsible,pt.name type_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id LEFT JOIN program_types pt ON pt.id=p.program_type_id INNER JOIN users u ON u.employee_id=p.journalist_id WHERE u.id=? AND (p.title LIKE ? OR p.program_code LIKE ?) ORDER BY p.updated_at DESC");
    $term = '%' . $search . '%';
    $stmt->execute([$userId, $term, $term]);
    return $stmt->fetchAll();
    }
function operator_programs(int $userId, string $search = ''): array
{
    $role = (string) (user()['role'] ?? '');
    $stageKeys = workflow_stage_keys_for_role($role);
    if (!$stageKeys) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($stageKeys), '?'));
    $stmt = db()->prepare("SELECT p.*,s.name stage_name,s.sequence_no,r.name room_name,e.full_name responsible,pt.name type_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id LEFT JOIN program_types pt ON pt.id=p.program_type_id INNER JOIN users current_user ON current_user.id = ? WHERE p.current_employee_id = current_user.employee_id AND s.stage_key IN ({$placeholders}) AND (p.title LIKE ? OR p.program_code LIKE ?) ORDER BY p.updated_at DESC");
    $term = '%' . $search . '%';
    $stmt->execute(array_merge([$userId], $stageKeys, [$term, $term]));
    return $stmt->fetchAll();
    }
function paginated_programs(string $role, int $userId, string $search, string $status, string $stage, string $sort, int $page, int $perPage = 10): array
{
    $pdo = db();
    $where = ['1=1'];
    $params = [];
    if ($role === 'JOURNALIST') {
        $where[] = 'u.id = ?';
        $params[] = $userId;
    } elseif ($role !== 'ADMIN') {
        $where[] = 'p.current_employee_id = (SELECT employee_id FROM users WHERE id = ?)';
        $params[] = $userId;
        $stageKeys = workflow_stage_keys_for_role($role);
        if (!$stageKeys) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'pages' => 1];
        }
        $where[] = 's.stage_key IN (' . implode(',', array_fill(0, count($stageKeys), '?')) . ')';
        array_push($params, ...$stageKeys);
    }
    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.program_code LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    if ($status !== '') {
        $where[] = 'p.current_status = ?';
        $params[] = $status;
    }
    if ($stage !== '') {
        $where[] = 'p.current_stage_id = ?';
        $params[] = (int) $stage;
    }
    $sortMap = ['title' => 'p.title', 'stage' => 's.sequence_no', 'broadcast' => 'p.planned_broadcast', 'status' => 'p.current_status', 'updated' => 'p.updated_at'];
    $sortKey = $sortMap[$sort] ?? $sortMap['updated'];
    $whereSql = implode(' AND ', $where);
    $count = $pdo->prepare("SELECT COUNT(*) FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id INNER JOIN users u ON u.employee_id=p.journalist_id WHERE {$whereSql}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $page = max(1, $page);
    $perPage = min(50, max(1, $perPage));
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT p.*,s.name stage_name,s.sequence_no,r.name room_name,e.full_name responsible,pt.name type_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id LEFT JOIN program_types pt ON pt.id=p.program_type_id INNER JOIN users u ON u.employee_id=p.journalist_id WHERE {$whereSql} ORDER BY {$sortKey} ASC, p.updated_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int) ceil($total / $perPage))];
}
function minutes_label(int $minutes): string
{
    return $minutes < 60
    ? $minutes . 'm'
    : floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm';
    }
function user_notifications(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND read_at IS NULL ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
    }
function notify_user(int $userId, ?int $programId, string $type, string $message): void
    {
    if ($userId < 1) {
        return;
        }
    $stmt = db()->prepare('INSERT INTO notifications(user_id,program_id,type,message) VALUES (?,?,?,?)');
    $stmt->execute([$userId, $programId, $type, $message]);
    }
function notify_role(string $role, ?int $programId, string $type, string $message, ?int $exceptUserId = null): void
    {
    $stmt = db()->prepare('SELECT id FROM users WHERE role = ? AND active = 1');
    $stmt->execute([$role]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        if ($exceptUserId !== null && (int)$userId === $exceptUserId) {
            continue;
            }
        notify_user((int)$userId, $programId, $type, $message);
        }
    }
function unread_notifications_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND read_at IS NULL');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
    }
function calculate_performance(int $programId): array {
    if ($programId < 1) {
        return ['total_minutes'=>0,'total_delay'=>0,'completed'=>0,'stages'=>9,'score'=>0,'label'=>'WARNING'];
        }
    $stmt = db()->prepare('SELECT COALESCE(SUM(COALESCE(actual_minutes, TIMESTAMPDIFF(MINUTE, actual_start, NOW()))),0) total_minutes, COALESCE(SUM(delay_minutes),0) total_delay, SUM(status = "COMPLETED") completed, COUNT(*) stages FROM program_stage_history WHERE program_id = ?');
    $stmt->execute([$programId]);
    $row = $stmt->fetch() ?: ['total_minutes'=>0,'total_delay'=>0,'completed'=>0,'stages'=>0];
    $score = max(0, min(100, 100 - ((int)$row['total_delay'] * 0.5)));
    $label = $score >= 90 ? 'EXCELLENT' : ($score >= 75 ? 'GOOD' : ($score >= 50 ? 'WARNING' : 'DELAYED'));
    $stageCount = (int) db()->query("SELECT COUNT(*) FROM workflow_stages WHERE active=1")->fetchColumn();
    db()->prepare('INSERT INTO program_performance(program_id,total_minutes,total_delay_minutes,completed_stages,total_stages,score,performance_label) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_minutes=VALUES(total_minutes),total_delay_minutes=VALUES(total_delay_minutes),completed_stages=VALUES(completed_stages),total_stages=VALUES(total_stages),score=VALUES(score),performance_label=VALUES(performance_label)')->execute([$programId,(int)$row['total_minutes'],(int)$row['total_delay'],(int)$row['completed'],$stageCount,$score,$label]);
    return ['total_minutes'=>(int)$row['total_minutes'],'total_delay'=>(int)$row['total_delay'],'completed'=>(int)$row['completed'],'stages'=>$stageCount,'score'=>$score,'label'=>$label];
    }
function current_employee_id_for_user(?int $userId = null): int
{
    $userId = (int) ($userId ?? (user()['id'] ?? 0));
    if ($userId < 1) {
        return 0;
    }

    $stmt = db()->prepare('SELECT employee_id FROM users WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->execute([$userId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

function user_can_access_program(int $programId, ?int $userId = null): bool
{
    $userId = (int) ($userId ?? (user()['id'] ?? 0));
    if ($userId < 1 || $programId < 1) {
        return false;
    }

    $currentUser = user();
    $role = $currentUser['role'] ?? null;
    if ($role === null) {
        $role = db()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $role->execute([$userId]);
        $role = $role->fetchColumn() ?: '';
    }

    if ($role === 'ADMIN') {
        return true;
    }

    $employeeId = current_employee_id_for_user($userId);
    if ($employeeId < 1) {
        return false;
    }

    $program = db()->prepare('SELECT p.journalist_id, p.current_employee_id, s.stage_key FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE p.id = ? LIMIT 1');
    $program->execute([$programId]);
    $programData = $program->fetch();
    if (!$programData) {
        return false;
    }

    if ($role === 'JOURNALIST') {
        return (int) $programData['journalist_id'] === $employeeId;
    }

    if ((int) $programData['current_employee_id'] === $employeeId
        && workflow_stage_role((string) $programData['stage_key']) === $role) {
        return true;
    }
    return false;
}

function __(string $key): string {
    static $lang = null;
    if ($lang === null) {
        $locale = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
        if (!in_array($locale, ['en', 'om'])) $locale = 'en';
        $path = __DIR__ . "/lang/{$locale}.php";
        $lang = file_exists($path) ? require($path) : [];
    }
    return $lang[$key] ?? $key;
}

function role_label(string $role): string
{
    return match ($role) {
        'INGEST_OPERATOR' => 'Ingest Operator',
        'PREVIEW_OPERATOR' => 'Preview Operator',
        'EDITOR' => 'Editor',
        'GRAPHICS_OPERATOR' => 'Graphics Operator',
        'AUDIO_OPERATOR' => 'Audio Operator',
        'QC_OPERATOR' => 'Quality Control',
        'MASTER_CONTROL_OPERATOR' => 'Master Control',
        'UPLINK_OPERATOR' => 'Uplink Operator',
        'JOURNALIST' => 'Journalist',
        'ADMIN' => 'Administrator',
        default => ucwords(strtolower(str_replace('_', ' ', $role))),
    };
}
function report_summary(string $from, string $to): array {
    $pdo = db();
    $programs = $pdo->prepare('SELECT COUNT(*) total, SUM(current_status IN ("WAITING","IN_PROGRESS","BLOCKED","DELAYED","ON_AIR")) AS active_count, SUM(current_status="COMPLETED") AS completed_count, SUM(current_status IN ("BLOCKED","DELAYED")) AS delayed_count FROM programs WHERE DATE(created_at) BETWEEN ? AND ?');
    $programs->execute([$from,$to]);
    $programSummary = $programs->fetch() ?: [];
    $stages = $pdo->prepare('SELECT s.name, COUNT(h.id) movements, ROUND(AVG(COALESCE(h.actual_minutes,0)),0) average_minutes, SUM(h.delay_minutes) delay_minutes FROM workflow_stages s LEFT JOIN program_stage_history h ON h.stage_id=s.id AND DATE(h.actual_start) BETWEEN ? AND ? GROUP BY s.id ORDER BY s.sequence_no');
    $stages->execute([$from,$to]);
    return ['programs'=>['total'=>(int)($programSummary['total']??0),'active'=>(int)($programSummary['active_count']??0),'completed'=>(int)($programSummary['completed_count']??0),'delayed'=>(int)($programSummary['delayed_count']??0)],'stages'=>$stages->fetchAll()];
    }
