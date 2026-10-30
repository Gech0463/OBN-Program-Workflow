USE obn_workflow;

ALTER TABLE users MODIFY role VARCHAR(40) NOT NULL DEFAULT 'JOURNALIST';
ALTER TABLE programs ADD COLUMN IF NOT EXISTS source VARCHAR(150) NULL AFTER language;
ALTER TABLE programs ADD COLUMN IF NOT EXISTS presenter VARCHAR(150) NULL AFTER producer_id;
ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_stage_id INT UNSIGNED NULL AFTER current_stage_id;
ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_room_id INT UNSIGNED NULL AFTER current_room_id;
ALTER TABLE programs ADD COLUMN IF NOT EXISTS previous_employee_id INT UNSIGNED NULL AFTER current_employee_id;
ALTER TABLE programs MODIFY current_status ENUM('WAITING','IN_PROGRESS','BLOCKED','DELAYED','ON_AIR','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING';
ALTER TABLE workflow_stages ADD COLUMN IF NOT EXISTS icon VARCHAR(40) NULL AFTER name;
ALTER TABLE workflow_stages ADD COLUMN IF NOT EXISTS description VARCHAR(255) NULL AFTER icon;

CREATE TABLE IF NOT EXISTS program_journal (
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
);

CREATE TABLE IF NOT EXISTS program_movements (
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
);

CREATE TABLE IF NOT EXISTS delay_records (
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
);

CREATE TABLE IF NOT EXISTS program_performance (
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
);

UPDATE workflow_stages SET active=0 WHERE stage_key NOT IN ('JOURNALIST','INGEST','PREVIEW','EDITING','GRAPHICS','AUDIO','QUALITY_CONTROL','MASTER_CONTROL','UPLINK');

INSERT INTO workflow_stages (stage_key,name,sequence_no,expected_minutes,active)
VALUES ('JOURNALIST','Journalist submission',1,0,1),('INGEST','Ingest',2,30,1),('PREVIEW','Preview',3,20,1),('EDITING','Editing',4,120,1),('GRAPHICS','Graphics',5,60,1),('AUDIO','Audio',6,60,1),('QUALITY_CONTROL','Quality control',7,30,1),('MASTER_CONTROL','Master control',8,30,1),('UPLINK','Uplink',9,20,1)
ON DUPLICATE KEY UPDATE name=VALUES(name), sequence_no=VALUES(sequence_no), expected_minutes=VALUES(expected_minutes), active=VALUES(active);

INSERT IGNORE INTO program_journal (program_id,program_date,scheduled_time,presenter,producer,source,initial_location,notes,created_by)
SELECT id,DATE(planned_start),TIME(planned_broadcast),presenter,producer_id,source,'Journal',description,created_by FROM programs;
