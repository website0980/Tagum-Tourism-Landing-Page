-- MSSQL Compatible Schema (for VSCode linter)
-- SQLite runtime uses same syntax, ignore AUTOINCREMENT warnings
-- Language Mode: "MSSQL"

CREATE TABLE destinations (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [type] NVARCHAR(100) NOT NULL,
  [description] NVARCHAR(MAX),
  [location] NVARCHAR(255),
  [accessibility] NVARCHAR(255),
  [features] NVARCHAR(MAX),
  [facilities] NVARCHAR(MAX),
  [entrance_fee] NVARCHAR(100),
  [contact] NVARCHAR(255),
  [best_time] NVARCHAR(255),
  [what_to_pack] NVARCHAR(MAX),
  [visiting_rules] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [featured] BIT DEFAULT 0,
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE experiences (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [type] NVARCHAR(100) NOT NULL,
  [description] NVARCHAR(MAX),
  [date] NVARCHAR(50),
  [link] NVARCHAR(500),
  [image] NVARCHAR(500),
  [featured] BIT DEFAULT 0,
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE cuisine_categories (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [category] NVARCHAR(255) NOT NULL,
  [description] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE cuisine_items (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [category_id] INTEGER,
  [name] NVARCHAR(255) NOT NULL,
  [description] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [sort_order] INTEGER DEFAULT 0,
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE natural_wonders (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [description] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [location] NVARCHAR(255),
  [features] NVARCHAR(MAX),
  [best_time] NVARCHAR(255),
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE cultural_sites (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [description] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [location] NVARCHAR(255),
  [history] NVARCHAR(MAX),
  [highlights] NVARCHAR(MAX),
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE festivals (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [description] NVARCHAR(MAX),
  [date] NVARCHAR(100),
  [highlights] NVARCHAR(MAX),
  [activities] NVARCHAR(MAX),
  [image] NVARCHAR(500),
  [created_at] DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE media_files (
  [id] INTEGER IDENTITY(1,1) PRIMARY KEY,
  [name] NVARCHAR(255) NOT NULL,
  [type] NVARCHAR(20) DEFAULT 'image',
  [file_path] NVARCHAR(500) NOT NULL,
  [upload_date] DATETIME2 DEFAULT GETDATE()
);

