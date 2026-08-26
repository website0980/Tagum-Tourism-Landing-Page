-- Tagum Promotional Website - Admin Database Schema
-- Import this file in phpMyAdmin or via mysql CLI.
--
-- Creates:
-- - tagum_admin database
-- - tables for destinations, experiences, cuisine categories + items,
--   natural wonders, cultural sites, festivals, and media files.

CREATE DATABASE IF NOT EXISTS tagum_admin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tagum_admin;

-- =========================
-- Destinations
-- =========================
CREATE TABLE IF NOT EXISTS destinations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NULL,
  accessibility VARCHAR(255) NULL,
  features TEXT NULL,
  facilities TEXT NULL,
  entrance_fee VARCHAR(100) NULL,
  contact VARCHAR(255) NULL,
  best_time TEXT NULL,
  what_to_pack TEXT NULL,
  visiting_rules TEXT NULL,
  image VARCHAR(500) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_destinations_featured (featured),
  KEY idx_destinations_type (type)
);

-- =========================
-- Experiences
-- =========================
CREATE TABLE IF NOT EXISTS experiences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  date DATE NULL,
  link VARCHAR(500) NULL,
  image VARCHAR(500) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_experiences_featured (featured),
  KEY idx_experiences_type (type)
);

-- =========================
-- Cuisine (categories + items)
-- =========================
CREATE TABLE IF NOT EXISTS cuisine_categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuisine_category (category)
);

CREATE TABLE IF NOT EXISTS cuisine_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  image VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cuisine_items_category (category_id),
  CONSTRAINT fk_cuisine_items_category
    FOREIGN KEY (category_id) REFERENCES cuisine_categories(id)
    ON DELETE CASCADE
);

-- =========================
-- Natural Wonders
-- =========================
CREATE TABLE IF NOT EXISTS natural_wonders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(500) NULL,
  location VARCHAR(255) NULL,
  features TEXT NULL,
  best_time VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_natural_wonders_name (name)
);

-- =========================
-- Cultural Sites
-- =========================
CREATE TABLE IF NOT EXISTS cultural_sites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(500) NULL,
  location VARCHAR(255) NULL,
  history TEXT NULL,
  highlights TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cultural_sites_name (name)
);

-- =========================
-- Festivals
-- =========================
CREATE TABLE IF NOT EXISTS festivals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  date VARCHAR(100) NULL,
  highlights TEXT NULL,
  activities TEXT NULL,
  image VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_festivals_name (name)
);

-- =========================
-- Media manager (optional)
-- =========================
CREATE TABLE IF NOT EXISTS media_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type ENUM('image','audio','video') NOT NULL DEFAULT 'image',
  file_path VARCHAR(500) NOT NULL,
  upload_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_media_type (type),
  KEY idx_media_upload_date (upload_date)
);

