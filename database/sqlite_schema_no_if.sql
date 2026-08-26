-- SQLite Schema (MSSQL Editor Compatible - No "IF NOT EXISTS")
-- Run: sqlite3 database.db < sqlite_schema_no_if.sql
-- DROP tables first if needed: DELETE FROM sqlite_master WHERE type='table';

DROP TABLE IF EXISTS media_files;
DROP TABLE IF EXISTS festivals;
DROP TABLE IF EXISTS cultural_sites;
DROP TABLE IF EXISTS natural_wonders;
DROP TABLE IF EXISTS cuisine_items;
DROP TABLE IF EXISTS cuisine_categories;
DROP TABLE IF EXISTS experiences;
DROP TABLE IF EXISTS destinations;

CREATE TABLE destinations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT NOT NULL,
  description TEXT,
  location TEXT,
  accessibility TEXT,
  features TEXT,
  facilities TEXT,
  entrance_fee TEXT,
  contact TEXT,
  best_time TEXT,
  what_to_pack TEXT,
  visiting_rules TEXT,
  image TEXT,
  featured INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE experiences (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT NOT NULL,
  description TEXT,
  date TEXT,
  link TEXT,
  image TEXT,
  featured INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cuisine_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category TEXT NOT NULL,
  description TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cuisine_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT,
  sort_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE natural_wonders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT,
  location TEXT,
  features TEXT,
  best_time TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cultural_sites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT,
  location TEXT,
  history TEXT,
  highlights TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE festivals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  date TEXT,
  highlights TEXT,
  activities TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE media_files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT DEFAULT 'image',
  file_path TEXT NOT NULL,
  upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

