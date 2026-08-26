<?php
/**
 * Feedback and Review System Database Setup
 * Creates the feedback table for hotels and restaurants
 */

function ensureFeedbackTable() {
    $dbFile = __DIR__ . '/../database.db';
    $db = new SQLite3($dbFile);

    // Create feedback table
    $db->exec('
        CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            establishment_type TEXT NOT NULL,
            establishment_id INTEGER NOT NULL,
            overall_rating INTEGER NOT NULL CHECK(overall_rating >= 1 AND overall_rating <= 5),
            comment TEXT,
            anonymous INTEGER DEFAULT 0,
            email TEXT NOT NULL,
            recommendation TEXT NOT NULL,
            data_privacy_consent INTEGER NOT NULL DEFAULT 0,
            status TEXT DEFAULT "Pending Review" CHECK(status IN ("Pending Review", "Approved", "Rejected")),
            moderator_notes TEXT,
            reviewed_by TEXT,
            reviewed_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Create index for faster queries
    $db->exec('CREATE INDEX IF NOT EXISTS idx_feedback_establishment ON feedback(establishment_type, establishment_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_feedback_status ON feedback(status)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_feedback_created ON feedback(created_at)');

    $db->close();
}

// If executed directly, run the setup
if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    ensureFeedbackTable();
    echo "Feedback table ready.\n";
}
