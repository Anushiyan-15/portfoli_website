-- ============================================================
-- Portfolio database — create from scratch (compatible upgrade)
-- Project: PHP portfolio (portfolio_db)
-- Usage:
--   mysql -u root -p < schema.sql
--   -- or visit install.php in the browser (recommended on UniServerZ)
-- This file is safe to run multiple times (IF NOT EXISTS + ALTER guards
-- are applied in install.php for existing installs).
-- Preserves legacy tables `projects` and `contact_submissions`
-- where they already exist.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `portfolio_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `portfolio_db`;

-- ---------- admin users ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- single-row profile (id = 1) ----------
CREATE TABLE IF NOT EXISTS `profile` (
  `id` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(120) NOT NULL DEFAULT '[your name]',
  `role` VARCHAR(160) NOT NULL DEFAULT '[e.g., Full-stack developer / UI designer / Student]',
  `tagline` VARCHAR(255) NOT NULL DEFAULT 'I build fast, accessible web experiences with a playful 3D touch.',
  `bio` TEXT NULL,
  `photo_path` VARCHAR(255) NULL,
  `resume_path` VARCHAR(255) NULL,
  `email` VARCHAR(160) NOT NULL DEFAULT 'you@example.com',
  `phone` VARCHAR(60) NULL,
  `location` VARCHAR(160) NOT NULL DEFAULT 'Your City, Country',
  `availability` VARCHAR(160) NOT NULL DEFAULT 'Available for freelance',
  `linkedin` VARCHAR(255) NULL,
  `github` VARCHAR(255) NULL,
  `twitter` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `tone` VARCHAR(40) NOT NULL DEFAULT '[professional / creative / minimal]',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_profile_single` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- skills (grouped) ----------
CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skill_group` ENUM('Frontend','Backend','Tools') NOT NULL DEFAULT 'Frontend',
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(100) NOT NULL DEFAULT 'bi-code-slash',
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 80,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_skills_group` (`skill_group`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- projects ----------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'Web',
  `tags` VARCHAR(255) NOT NULL DEFAULT '',
  `live_url` VARCHAR(255) NULL,
  `github_url` VARCHAR(255) NULL,
  `code_snippet` MEDIUMTEXT NULL,
  `code_lang` VARCHAR(30) NOT NULL DEFAULT 'php',
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_projects_slug` (`slug`),
  KEY `idx_projects_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- uploaded project files (browseable source) ----------
CREATE TABLE IF NOT EXISTS `project_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(120) NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pf_project` (`project_id`),
  CONSTRAINT `fk_pf_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- experience / education timeline ----------
CREATE TABLE IF NOT EXISTS `experience` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('experience','education') NOT NULL DEFAULT 'experience',
  `title` VARCHAR(160) NOT NULL,
  `organization` VARCHAR(160) NOT NULL,
  `location` VARCHAR(160) NULL,
  `start_label` VARCHAR(50) NOT NULL DEFAULT '',
  `end_label` VARCHAR(50) NOT NULL DEFAULT 'Present',
  `description` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_exp_type` (`type`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- testimonials / achievements ----------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author` VARCHAR(120) NOT NULL,
  `role` VARCHAR(160) NULL,
  `quote` TEXT NOT NULL,
  `avatar_path` VARCHAR(255) NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- blog / articles (optional; hidden when empty/unpublished) ----------
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(180) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `excerpt` VARCHAR(300) NULL,
  `body` MEDIUMTEXT NOT NULL,
  `cover_path` VARCHAR(255) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- contact submissions (extends legacy table) ----------
-- Legacy code used: name, email, message. We keep those columns and add
-- subject / read-flag / timestamp so old data keeps working.
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
