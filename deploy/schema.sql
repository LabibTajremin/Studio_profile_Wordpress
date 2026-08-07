-- =====================================================================
-- Maapkathi Studio — custom tables
-- =====================================================================
--
-- YOU DO NOT NORMALLY NEED THIS FILE.
--
-- Activating the maapkathi-core plugin creates these tables automatically
-- (Support\Migrations, run on activation and re-checked on every page
-- load). Use this script only when you cannot activate the plugin
-- normally, for example:
--
--   * restoring onto a database that lost the mk_ tables
--   * pre-creating the schema before a bulk import
--   * inspecting the exact structure in phpMyAdmin
--
-- Safe to run more than once: every statement uses IF NOT EXISTS, so an
-- existing table is left completely untouched and no data is lost.
--
-- ---------------------------------------------------------------------
-- BEFORE YOU RUN IT — table prefix
-- ---------------------------------------------------------------------
-- This script uses the prefix `wp_`, matching DB_TABLE_PREFIX in
-- deploy.env. If your wp-config.php sets a different $table_prefix, do a
-- find-and-replace of `wp_mk_` with `<yourprefix>mk_` first, or the
-- plugin will not see these tables.
--
-- ---------------------------------------------------------------------
-- HOW TO RUN IT ON HOSTINGER
-- ---------------------------------------------------------------------
--   hPanel → Databases → Management → Enter phpMyAdmin
--     → select <your_db_name>
--     → SQL tab → paste this file → Go
--
--   Or over SSH:
--     mysql -u <your_db_user> -p <your_db_name> < schema.sql
--
-- These three tables are the ONLY custom storage this build uses.
-- Projects, services, team, testimonials and everything else live in
-- native WordPress tables (wp_posts / wp_postmeta / wp_terms), which
-- WordPress itself creates.
-- =====================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';


-- ---------------------------------------------------------------------
-- Contact-form submissions.
-- Read in wp-admin under Maapkathi → Enquiries.
--
-- Indexed on is_read (the unread filter and the menu bubble count) and
-- created_at (the inbox is always sorted newest-first).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_mk_inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `message` longtext NOT NULL,
  `source` varchar(191) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


-- ---------------------------------------------------------------------
-- Audit trail of content changes and approval decisions.
--
-- `diff` holds JSON as LONGTEXT, written with wp_json_encode() and read
-- with json_decode() in PHP only. Deliberately NOT a native JSON column:
-- MariaDB's JSON type is an alias for LONGTEXT with a CHECK constraint,
-- not MySQL 8's binary JSON, and its path functions differ — so no SQL
-- anywhere in this build queries inside a JSON column.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_mk_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) unsigned NOT NULL,
  `action` varchar(191) NOT NULL,
  `entity` varchar(191) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `diff` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `entity_lookup` (`entity`,`entity_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


-- ---------------------------------------------------------------------
-- Pending-edit queue for the editor approval workflow.
--
-- When an editor edits already-published content and the verification
-- toggle is on, the live version stays untouched and the edit is stored
-- here as a pending revision until an admin approves it.
--
-- Indexed on status (the Approvals screen lists pending rows) and on
-- entity+entity_id (finding the outstanding revisions for one item).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_mk_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(191) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `payload` longtext NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `submitted_by` bigint(20) unsigned NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `entity_lookup` (`entity`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


-- ---------------------------------------------------------------------
-- Record the schema version so the plugin's migration runner knows the
-- tables are current and skips re-creating them.
--
-- Harmless if the row already exists.
-- ---------------------------------------------------------------------
INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`)
VALUES ('mk_db_version', '1.0.0', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = '1.0.0';


-- ---------------------------------------------------------------------
-- Verify (optional) — should list all three tables.
-- ---------------------------------------------------------------------
-- SHOW TABLES LIKE 'wp_mk_%';
