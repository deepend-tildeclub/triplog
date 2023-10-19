<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

function connectTripDB() {
    $database = "/home/bbp/triplogdb/triplog.db";

    try {
        $pdo = new PDO("sqlite:" . $database);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create trips table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS trips (
            id INTEGER PRIMARY KEY,
            driver_name TEXT NOT NULL,
            unit_number TEXT NOT NULL,
            date_time TEXT NOT NULL
        )");

        // Create destinations table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS destinations (
            id INTEGER PRIMARY KEY,
            trip_id INTEGER,
            destination_address TEXT NOT NULL,
            invoice_number TEXT NOT NULL,
            comments TEXT NOT NULL,
            image TEXT,
            stage_1 INTEGER DEFAULT 0,
            stage_2 INTEGER DEFAULT 0,
            sheets INTEGER DEFAULT 0,
            pick_up INTEGER DEFAULT 0,
            returns INTEGER DEFAULT 0,
            back_order INTEGER DEFAULT 0
        )");

        // Create managers table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS managers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            full_name TEXT
        )");

        // Alter the destinations table to add new columns if they don't exist
        // This logic is written to ensure that the columns are added only if they don't exist
        // This might throw an exception if the column already exists, but the catch block will handle it
        $columnsToAdd = ['stage_1', 'stage_2', 'sheets', 'pick_up', 'returns', 'back_order'];
        foreach ($columnsToAdd as $column) {
            try {
                $pdo->exec("ALTER TABLE destinations ADD COLUMN $column INTEGER DEFAULT 0");
            } catch (PDOException $ex) {
                // Assuming the column already exists and do nothing
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}
?>
