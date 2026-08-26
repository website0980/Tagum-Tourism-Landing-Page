-- Fixed SQLite Schema for tagum_admin (converted from MySQL)
-- Run: sqlite3 database.db < fixed_schema.sql

-- Destinations
CREATE TABLE [destinations] (
  [id] INTEGER PRIMARY KEY AUTOINCREMENT,
  [name] TEXT NOT NULL,
  [type] TEXT NOT NULL,
  [description] TEXT NOT NULL,
  [location] TEXT,
  [accessibility] TEXT,
  [features] TEXT,
  [facilities] TEXT,
  [entrance_fee] TEXT,
  [contact] TEXT,
  [best_time] TEXT,
  [what_to_pack] TEXT,
  [visiting_rules] TEXT,
  [image] TEXT,
  [featured] INTEGER DEFAULT 0,
  [created_at] DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Experiences
CREATE TABLE IF NOT EXISTS experiences (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT NOT NULL,
  description TEXT NOT NULL,
  date TEXT,
  link TEXT,
  image TEXT,
  featured INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Cuisine Categories
CREATE TABLE IF NOT EXISTS cuisine_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category TEXT NOT NULL UNIQUE,
  description TEXT NOT NULL,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Cuisine Items
CREATE TABLE IF NOT EXISTS cuisine_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT,
  sort_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Natural Wonders
CREATE TABLE IF NOT EXISTS natural_wonders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT NOT NULL,
  image TEXT,
  location TEXT,
  features TEXT,
  best_time TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Cultural Sites
CREATE TABLE IF NOT EXISTS cultural_sites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT NOT NULL,
  image TEXT,
  location TEXT,
  history TEXT,
  highlights TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Festivals
CREATE TABLE IF NOT EXISTS festivals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT NOT NULL,
  date TEXT,
  highlights TEXT,
  activities TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Media Files
CREATE TABLE IF NOT EXISTS media_files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT DEFAULT 'image',
  file_path TEXT NOT NULL,
  upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

