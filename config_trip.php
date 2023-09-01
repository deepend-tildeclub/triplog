<?php

function connectTripDB() {
    $database = "/home/bbp/checklistdb/trip_logtesttest.db";

    try {
        $pdo = new PDO("sqlite:" . $database);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the trips table if it does not exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS trips (
            id INTEGER PRIMARY KEY,
            driver_name TEXT NOT NULL,
            unit_number TEXT NOT NULL,
            date_time TEXT NOT NULL,
            destinations TEXT NOT NULL  -- This column stores a JSON string which will also include new fields like stage1, stage2, sheets, pickup, backorder, and image_path
        )");
      $pdo->exec("CREATE TABLE IF NOT EXISTS managers (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
)");

        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}