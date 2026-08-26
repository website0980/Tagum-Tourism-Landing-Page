-- Hotel table schema for luxury hotels
CREATE TABLE IF NOT EXISTS luxury_hotels (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sample data: Big 8 Rufina's Leisure Center (1 entry)
INSERT OR REPLACE INTO luxury_hotels (id, name, description, image) VALUES
(1, 'Big 8 Rufina''s Leisure Center', 'Luxury 5-star resort with world-class amenities, pools, and beachfront access in Tagum City. Perfect for relaxation and events.', '../assets/images/hotels/big8-preview.jpg');

-- === LOCATION SORTING MIGRATION FOR hotel_items (SQLite) ===
-- Add columns for location sorting (idempotent)
ALTER TABLE hotel_items ADD COLUMN latitude REAL DEFAULT NULL;
ALTER TABLE hotel_items ADD COLUMN longitude REAL DEFAULT NULL;
ALTER TABLE hotel_items ADD COLUMN rating REAL DEFAULT 4.0;

-- Sample data for existing hotels (Tagum City/Samal Island approx coords)
-- Update based on category; refine via admin panel
UPDATE hotel_items 
SET latitude = 7.443, longitude = 125.807, rating = 4.5 
WHERE category LIKE '%Luxury%' OR category LIKE '%5-star%' OR name LIKE '%Big 8%';

UPDATE hotel_items 
SET latitude = 7.400, longitude = 125.850, rating = 4.2 
WHERE category LIKE '%Resort%' OR category LIKE '%Resorts%';

UPDATE hotel_items 
SET latitude = 7.460, longitude = 125.790, rating = 3.8 
WHERE category LIKE '%Budget%' OR category LIKE '%Stay%';

-- Verification query (run manually: sqlite3 database.db "SELECT id, name, category, latitude, longitude, rating FROM hotel_items;")
-- Note: ALTER is one-time; UPDATE safe to rerun.
