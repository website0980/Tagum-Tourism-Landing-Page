-- Restaurant table schema matching hotel_items structure
-- For location-based sorting, categories, ratings

CREATE TABLE IF NOT EXISTS restaurant_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  price TEXT,  -- e.g., "₱500-1000" (parse numeric for sorting)
  category TEXT,  -- e.g., 'Filipino', 'Fine Dining', 'Fast Food'
  location TEXT,  -- "Tagum City Center"
  contact TEXT,
  information TEXT,
  latitude REAL DEFAULT NULL,
  longitude REAL DEFAULT NULL,
  rating REAL DEFAULT 4.0,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sample restaurant data (Tagum City area)
INSERT OR IGNORE INTO restaurant_items (id, name, description, price, category, location, contact, latitude, longitude, rating, image) VALUES
(1, 'Grand Palace Filipino Cuisine', 'Authentic Filipino dishes with local flavors from Tagum. Must-try: Sinugba and Kinilaw.', '₱300-600', 'Filipino', 'Tagum City Center', '+63 912 345 6780', 7.443, 125.807, 4.5, '../assets/images/restaurants/filipino-preview.jpg'),
(2, 'Samal Seafood Haven', 'Fresh Samal Island seafood specialties. Grilled squid and fresh catch daily.', '₱800-1500', 'Seafood', 'Samal Island', '+63 917 123 4567', 7.400, 125.850, 4.7, '../assets/images/restaurants/seafood-preview.jpg'),
(3, 'Tagum Quick Bites', 'Fast food favorites and local street eats for quick meals.', '₱150-400', 'Fast Food', 'Tagum Market Area', '+63 998 765 4321', 7.460, 125.790, 3.9, '../assets/images/restaurants/fastfood-preview.jpg'),
(4, 'Riverside Fine Dining', 'Elegant dining with Tagum River views. International fusion cuisine.', '₱1200-2500', 'Fine Dining', 'Tagum Riverside', '+63 955 111 2222', 7.445, 125.810, 4.8, '../assets/images/restaurants/fine-dining-preview.jpg'),
(5, 'Lechon Hub Tagum', 'Specialty lechon and grilled meats. Party trays available.', '₱500-1200', 'Grill', 'Tagum Highway', '+63 933 444 5555', 7.450, 125.800, 4.3, '../assets/images/restaurants/grill-preview.jpg');

-- Verification
-- SELECT id, name, category, latitude, longitude, rating FROM restaurant_items ORDER BY dist_km or rating;
