-- 13 Nov 2026: automatic SMS / WhatsApp (fee and installment reminders), owner panel Messaging,
-- your own SMS gateway phone, and "Automatic SMS / WhatsApp" as a plan feature.
-- Safe to run again: it skips what is already there (MariaDB "IF NOT EXISTS").
-- Same as: php artisan migrate --force

-- 1) Reminders
ALTER TABLE `messages`
  ADD COLUMN IF NOT EXISTS `channel` VARCHAR(10) NOT NULL DEFAULT 'whatsapp' AFTER `category`,
  ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `guardian_id`,
  ADD COLUMN IF NOT EXISTS `error` VARCHAR(255) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `dedupe_key` VARCHAR(120) NULL AFTER `error`;
CREATE UNIQUE INDEX IF NOT EXISTS `messages_school_id_dedupe_key_unique` ON `messages` (`school_id`, `dedupe_key`);
ALTER TABLE `schools` ADD COLUMN IF NOT EXISTS `reminder_settings` JSON NULL;

-- 2) Messaging settings and the gateway phones
CREATE TABLE IF NOT EXISTS `messaging_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `driver` VARCHAR(20) NOT NULL DEFAULT 'off',
  `config` TEXT NULL,
  `school_daily_limit` INT UNSIGNED NOT NULL DEFAULT 500,
  `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gateway_devices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `sim_number` VARCHAR(20) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `daily_limit` INT UNSIGNED NOT NULL DEFAULT 300,
  `last_seen_at` TIMESTAMP NULL,
  `last_status` JSON NULL,
  `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `gateway_devices_token_hash_unique` (`token_hash`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `messages`
  ADD COLUMN IF NOT EXISTS `device_id` BIGINT UNSIGNED NULL AFTER `channel`,
  ADD COLUMN IF NOT EXISTS `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `dedupe_key`,
  ADD COLUMN IF NOT EXISTS `claimed_at` TIMESTAMP NULL AFTER `attempts`;
CREATE INDEX IF NOT EXISTS `messages_channel_status_id_index` ON `messages` (`channel`, `status`, `id`);
ALTER TABLE `messages` MODIFY `school_id` BIGINT UNSIGNED NULL;

-- 3) New plan feature: off for existing schools until you give it (Plans, or the school's page). Only the first time.
UPDATE `schools`
SET `features_off` = JSON_ARRAY_APPEND(COALESCE(NULLIF(`features_off`, ''), '[]'), '$', 'sms')
WHERE NOT JSON_CONTAINS(COALESCE(NULLIF(`features_off`, ''), '[]'), '"sms"')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_13_000002_add_messaging_settings_and_sms_gateway');

-- 4) Mark both as done, so "php artisan migrate" does not run them again
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.name, b.next FROM
  (SELECT '2026_11_13_000001_add_automatic_sms_reminders' AS name
   UNION ALL SELECT '2026_11_13_000002_add_messaging_settings_and_sms_gateway') m
  CROSS JOIN (SELECT COALESCE(MAX(`batch`), 0) + 1 AS next FROM `migrations`) b
WHERE m.name NOT IN (SELECT `migration` FROM `migrations`);
