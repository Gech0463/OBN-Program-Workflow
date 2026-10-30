USE obn_workflow;

ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS position VARCHAR(100) NULL AFTER position_id,
  ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL AFTER position,
  ADD COLUMN IF NOT EXISTS bio VARCHAR(200) NULL AFTER avatar_path,
  ADD COLUMN IF NOT EXISTS preferred_language ENUM('en','om','am') NOT NULL DEFAULT 'en' AFTER bio,
  ADD COLUMN IF NOT EXISTS theme_preference ENUM('light','dark') NOT NULL DEFAULT 'light' AFTER preferred_language,
  ADD COLUMN IF NOT EXISTS profile_completed_at DATETIME NULL AFTER theme_preference;

CREATE TABLE IF NOT EXISTS password_reset_requests (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('PENDING','ACTIONED','DISMISSED') NOT NULL DEFAULT 'PENDING',
  actioned_by INT UNSIGNED NULL,
  actioned_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (actioned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_password_reset_status (status, requested_at)
);
