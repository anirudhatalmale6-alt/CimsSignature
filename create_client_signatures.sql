-- SQL Script for Client Signatures Table
-- Run this in phpMyAdmin or your MySQL client

CREATE TABLE `client_signatures` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(255) NOT NULL,
    `client_reference` VARCHAR(100) NULL,
    `id_number` VARCHAR(50) NULL,
    `signature_data` LONGTEXT NOT NULL,
    `signature_hash` VARCHAR(64) NULL,
    `purpose` VARCHAR(255) NULL,
    `document_reference` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `device_info` VARCHAR(255) NULL,
    `captured_by` BIGINT UNSIGNED NULL,
    `captured_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `client_signatures_client_reference_index` (`client_reference`),
    INDEX `client_signatures_client_name_index` (`client_name`),
    INDEX `client_signatures_captured_at_index` (`captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
