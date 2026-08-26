CREATE TABLE IF NOT EXISTS carousel_slides (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tagline TEXT,
    title TEXT NOT NULL,
    description TEXT,
    image TEXT NOT NULL,
    btn_primary_text TEXT DEFAULT 'Explore Now',
    btn_primary_link TEXT DEFAULT '#plan',
    btn_secondary_text TEXT DEFAULT 'Learn More',
    btn_secondary_link TEXT DEFAULT '#explore',
    sort_order INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
