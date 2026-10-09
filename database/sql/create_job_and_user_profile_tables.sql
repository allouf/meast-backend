-- =============================================================================
-- Create Job & User Profile Tables
-- Generated from Laravel migrations. Run after users table exists.
-- Charset: utf8mb4. Engine: InnoDB.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. job_categories
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_categories_name_unique` (`name`),
  UNIQUE KEY `job_categories_slug_unique` (`slug`),
  KEY `job_categories_is_active_index` (`is_active`),
  KEY `job_categories_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. job_templates
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employer_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `is_system_template` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_templates_employer_id_index` (`employer_id`),
  KEY `job_templates_category_id_index` (`category_id`),
  KEY `job_templates_is_system_template_index` (`is_system_template`),
  KEY `job_templates_is_active_index` (`is_active`),
  CONSTRAINT `job_templates_employer_id_foreign` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_templates_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. job_template_fields
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_template_fields` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_template_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `validation_rules` JSON NULL,
  `options` JSON NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_template_fields_job_template_id_index` (`job_template_id`),
  KEY `job_template_fields_sort_order_index` (`sort_order`),
  CONSTRAINT `job_template_fields_job_template_id_foreign` FOREIGN KEY (`job_template_id`) REFERENCES `job_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. job_postings
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_postings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employer_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `employment_type` VARCHAR(32) NOT NULL,
  `experience_level` VARCHAR(32) NOT NULL,
  `country_code` VARCHAR(2) NOT NULL,
  `region` VARCHAR(255) NULL,
  `work_mode` VARCHAR(32) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `salary_min` DECIMAL(10,2) NULL,
  `salary_max` DECIMAL(10,2) NULL,
  `hide_salary` TINYINT(1) NOT NULL DEFAULT 0,
  `application_deadline` DATE NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `application_link` VARCHAR(255) NULL,
  `job_template_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_postings_employment_type_index` (`employment_type`),
  KEY `job_postings_experience_level_index` (`experience_level`),
  KEY `job_postings_work_mode_index` (`work_mode`),
  KEY `job_postings_status_index` (`status`),
  KEY `job_postings_country_code_index` (`country_code`),
  KEY `job_postings_employer_id_index` (`employer_id`),
  KEY `job_postings_category_id_index` (`category_id`),
  CONSTRAINT `job_postings_employer_id_foreign` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_postings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_postings_job_template_id_foreign` FOREIGN KEY (`job_template_id`) REFERENCES `job_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. job_applications
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'new',
  `resume_path` VARCHAR(255) NOT NULL,
  `cover_letter` TEXT NULL,
  `rejection_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_applications_candidate_id_job_id_unique` (`candidate_id`, `job_id`),
  KEY `job_applications_status_index` (`status`),
  KEY `job_applications_job_id_index` (`job_id`),
  KEY `job_applications_candidate_id_index` (`candidate_id`),
  CONSTRAINT `job_applications_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. job_application_notes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_application_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_application_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_application_notes_job_application_id_index` (`job_application_id`),
  CONSTRAINT `job_application_notes_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_application_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. job_application_tags
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_application_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `color` VARCHAR(16) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_application_tags_job_id_name_unique` (`job_id`, `name`),
  KEY `job_application_tags_job_id_index` (`job_id`),
  CONSTRAINT `job_application_tags_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. job_application_tag_pivot
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_application_tag_pivot` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_application_id` BIGINT UNSIGNED NOT NULL,
  `job_application_tag_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_app_tag_pivot_unique` (`job_application_id`, `job_application_tag_id`),
  CONSTRAINT `job_application_tag_pivot_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_application_tag_pivot_job_application_tag_id_foreign` FOREIGN KEY (`job_application_tag_id`) REFERENCES `job_application_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. job_application_internal_attachments
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_application_internal_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_application_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(128) NULL,
  `size` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `job_application_internal_attachments_job_application_id_index` (`job_application_id`),
  CONSTRAINT `job_application_internal_attachments_job_application_id_foreign` FOREIGN KEY (`job_application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_application_internal_attachments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. user_work_experiences
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_work_experiences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `job_title` VARCHAR(255) NOT NULL,
  `industry` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `duration_months` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `user_work_experiences_user_id_index` (`user_id`),
  KEY `user_work_experiences_start_date_index` (`start_date`),
  CONSTRAINT `user_work_experiences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. user_educations
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_educations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `degree_type` ENUM('diploma', 'bachelor', 'master', 'phd', 'other') NOT NULL,
  `university_name` VARCHAR(255) NOT NULL,
  `major` VARCHAR(255) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `expected_graduation_date` DATE NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `user_educations_user_id_index` (`user_id`),
  KEY `user_educations_start_date_index` (`start_date`),
  CONSTRAINT `user_educations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. user_languages
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_languages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `language` VARCHAR(255) NOT NULL,
  `level` TINYINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_languages_user_id_language_unique` (`user_id`, `language`),
  CONSTRAINT `user_languages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. user_skills
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `skill_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_skills_user_id_skill_name_unique` (`user_id`, `skill_name`),
  CONSTRAINT `user_skills_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. user_certifications
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_certifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `issuer` VARCHAR(255) NULL,
  `issue_date` DATE NULL,
  `expiration_date` DATE NULL,
  `credential_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `user_certifications_user_id_index` (`user_id`),
  CONSTRAINT `user_certifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. user_projects
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `project_url` VARCHAR(255) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `user_projects_user_id_index` (`user_id`),
  KEY `user_projects_start_date_index` (`start_date`),
  CONSTRAINT `user_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. user_links
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `label` VARCHAR(255) NULL,
  `url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `user_links_user_id_index` (`user_id`),
  KEY `user_links_type_index` (`type`),
  CONSTRAINT `user_links_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
