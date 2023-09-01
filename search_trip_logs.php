<?php
require_once "config_trip.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["search"])) {
    $search = $_GET["search"];

    $pdo = connectTripDB();

    $sql = "SELECT * FROM trips WHERE 
        driver_name LIKE :search OR 
        unit_number LIKE :search OR 
        destinations LIKE :search";

    $stmt = $pdo->prepare($sql);
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For date search
    $searchDate = strtotime($search);
    if ($searchDate !== false) {
        $sql_date = "SELECT * FROM trips WHERE DATE(date_time) = :search_date";
        $stmt_date = $pdo->prepare($sql_date);
        $search_date_param = date('Y-m-d', $searchDate);
        $stmt_date->bindParam(':search_date', $search_date_param, PDO::PARAM_STR);
        $stmt_date->execute();

        $date_results = $stmt_date->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $date_results);
    }

    // Decode destinations and check for matches in each field
    $output_results = [];
    foreach ($results as $result) {
        $destinations = json_decode($result["destinations"], true);
        $matched_destinations = [];

        $driverMatch = stripos($result["driver_name"], $search) !== false;
        $unitMatch = stripos($result["unit_number"], $search) !== false;

        foreach ($destinations as $destination) {
            $destinationMatch = stripos($destination["destination_address"], $search) !== false;
            $invoiceMatch = stripos($destination["invoice_number"], $search) !== false;
            $commentMatch = stripos($destination["comments"], $search) !== false;
            
            // New line for image match
            $imageMatch = isset($destination["image_path"]) && stripos($destination["image_path"], $search) !== false;
            
            // Checking for matches in the new checkbox fields
            $stage1Match = isset($destination["stage1"]) && $destination["stage1"] && stripos("Stage 1", $search) !== false;
            $stage2Match = isset($destination["stage2"]) && $destination["stage2"] && stripos("Stage 2", $search) !== false;
            $sheetsMatch = isset($destination["sheets"]) && $destination["sheets"] && stripos("Sheets", $search) !== false;
            $pickupMatch = isset($destination["pickup"]) && $destination["pickup"] && stripos("Pick-Up", $search) !== false;
            $backorderMatch = isset($destination["backorder"]) && $destination["backorder"] && stripos("Back-Order", $search) !== false;

            if ($driverMatch || $unitMatch || $destinationMatch || $invoiceMatch || $commentMatch || $imageMatch || $stage1Match || $stage2Match || $sheetsMatch || $pickupMatch || $backorderMatch) {
                $matched_destinations[] = $destination;
            }
        }

        if (count($matched_destinations) > 0) {
            $result["destinations"] = $matched_destinations;
            $output_results[] = $result;
        }
    }

    echo json_encode($output_results);
}