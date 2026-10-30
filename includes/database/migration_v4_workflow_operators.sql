USE obn_workflow;

-- Add the staff accounts required to advance programs through every workflow stage.
INSERT IGNORE INTO employees (employee_code, full_name, email, department_id, position_id, position, profile_completed_at)
VALUES
    ('EMP-0007', 'Biniam Tola', 'biniam@obn.local', 4, 7, 'Ingest Operator', NOW()),
    ('EMP-0008', 'Rahel Bekele', 'rahel@obn.local', 4, 7, 'Preview Operator', NOW()),
    ('EMP-0009', 'Liya Gemechu', 'liya@obn.local', 2, 3, 'Graphics Operator', NOW()),
    ('EMP-0010', 'Nati Worku', 'nati@obn.local', 4, 6, 'Audio Operator', NOW()),
    ('EMP-0011', 'Meron Abate', 'meron@obn.local', 5, 7, 'Quality Control Operator', NOW());

INSERT IGNORE INTO users (employee_id, username, password_hash, role, active)
SELECT e.id, account.username, '$2y$10$crFkZM3FeLBA2mgH3cCDDuO88GGm6DMjx7f1mQRu8paOhDomS354a', account.role, 1
FROM (
    SELECT 'EMP-0003' employee_code, 'editor' username, 'EDITOR' role
    UNION ALL SELECT 'EMP-0004', 'mastercontrol', 'MASTER_CONTROL_OPERATOR'
    UNION ALL SELECT 'EMP-0005', 'transmission', 'MASTER_CONTROL_OPERATOR'
    UNION ALL SELECT 'EMP-0006', 'uplink', 'UPLINK_OPERATOR'
    UNION ALL SELECT 'EMP-0007', 'ingest', 'INGEST_OPERATOR'
    UNION ALL SELECT 'EMP-0008', 'preview', 'PREVIEW_OPERATOR'
    UNION ALL SELECT 'EMP-0009', 'graphics', 'GRAPHICS_OPERATOR'
    UNION ALL SELECT 'EMP-0010', 'audio', 'AUDIO_OPERATOR'
    UNION ALL SELECT 'EMP-0011', 'qualitycontrol', 'QC_OPERATOR'
) account
INNER JOIN employees e ON e.employee_code = account.employee_code;

-- Preserve existing administrator accounts and provide non-conflicting operator accounts.
INSERT IGNORE INTO users (employee_id, username, password_hash, role, active)
SELECT 3, 'editor_operator', '$2y$10$crFkZM3FeLBA2mgH3cCDDuO88GGm6DMjx7f1mQRu8paOhDomS354a', 'EDITOR', 1
FROM employees WHERE id = 3;

INSERT IGNORE INTO employees (employee_code, full_name, email, department_id, position_id, position, profile_completed_at)
VALUES ('EMP-0012', 'Saron Daba', 'saron@obn.local', 6, 10, 'Uplink Operator', NOW());

INSERT IGNORE INTO users (employee_id, username, password_hash, role, active)
SELECT id, 'uplink', '$2y$10$crFkZM3FeLBA2mgH3cCDDuO88GGm6DMjx7f1mQRu8paOhDomS354a', 'UPLINK_OPERATOR', 1
FROM employees WHERE employee_code = 'EMP-0012';

-- Restore legacy programs that have no current stage to the journalist submission stage.
UPDATE programs p
SET p.current_stage_id = (SELECT id FROM workflow_stages WHERE stage_key = 'JOURNALIST' AND active = 1 LIMIT 1),
        p.current_employee_id = p.journalist_id,
        p.current_status = 'WAITING',
        p.current_started_at = COALESCE(p.current_started_at, NOW()),
        p.updated_at = NOW()
WHERE p.current_stage_id IS NULL AND p.journalist_id IS NOT NULL;

-- Repair active programs whose current employee does not have the role required by its stage.
UPDATE programs p
INNER JOIN workflow_stages s ON s.id = p.current_stage_id
SET p.current_employee_id = (
        SELECT e.id
        FROM employees e
        INNER JOIN users u ON u.employee_id = e.id
        WHERE e.active = 1 AND u.active = 1
            AND u.role = CASE s.stage_key
                WHEN 'JOURNALIST' THEN 'JOURNALIST'
                WHEN 'INGEST' THEN 'INGEST_OPERATOR'
                WHEN 'PREVIEW' THEN 'PREVIEW_OPERATOR'
                WHEN 'EDITING' THEN 'EDITOR'
                WHEN 'GRAPHICS' THEN 'GRAPHICS_OPERATOR'
                WHEN 'AUDIO' THEN 'AUDIO_OPERATOR'
                WHEN 'QUALITY_CONTROL' THEN 'QC_OPERATOR'
                WHEN 'MASTER_CONTROL' THEN 'MASTER_CONTROL_OPERATOR'
                WHEN 'UPLINK' THEN 'UPLINK_OPERATOR'
            END
        ORDER BY e.id
        LIMIT 1
),
p.updated_at = NOW()
WHERE p.current_status NOT IN ('COMPLETED', 'CANCELLED')
    AND NOT EXISTS (
        SELECT 1
        FROM employees e
        INNER JOIN users u ON u.employee_id = e.id
        WHERE e.id = p.current_employee_id
            AND e.active = 1 AND u.active = 1
            AND u.role = CASE s.stage_key
                WHEN 'JOURNALIST' THEN 'JOURNALIST'
                WHEN 'INGEST' THEN 'INGEST_OPERATOR'
                WHEN 'PREVIEW' THEN 'PREVIEW_OPERATOR'
                WHEN 'EDITING' THEN 'EDITOR'
                WHEN 'GRAPHICS' THEN 'GRAPHICS_OPERATOR'
                WHEN 'AUDIO' THEN 'AUDIO_OPERATOR'
                WHEN 'QUALITY_CONTROL' THEN 'QC_OPERATOR'
                WHEN 'MASTER_CONTROL' THEN 'MASTER_CONTROL_OPERATOR'
                WHEN 'UPLINK' THEN 'UPLINK_OPERATOR'
            END
    );
