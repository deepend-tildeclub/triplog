<?php

require_once "config_trip.php";

$pdo = connectTripDB();

$driver_name = $_POST["driver_name"];
$unit_number = $_POST["unit_number"];
$date_time = $_POST["date_time"];

$destination_addresses = $_POST["destination_address"];
$invoice_numbers = $_POST["invoice_number"];
$comments_array = $_POST["comments"];

$destinations = [];

$uploadDir = 'uploads/';

// Capture the raw POST data
file_put_contents('debug.log', "Raw POST data:\n" . print_r($_POST, true) . "\n", FILE_APPEND);

function isCheckboxChecked($checkboxArray, $index) {
    return isset($checkboxArray[$index]) && $checkboxArray[$index] == "1" ? 1 : 0;
}

for ($i = 0; $i < count($destination_addresses); $i++) {
    $imagePath = "";

    // Check if an image was uploaded
    if (isset($_FILES['destination_image']) && isset($_FILES['destination_image']['tmp_name'][$i]) && $_FILES['destination_image']['tmp_name'][$i]) {
        $fileTmpPath = $_FILES['destination_image']['tmp_name'][$i];
        $fileName = uniqid() . $_FILES['destination_image']['name'][$i];
        $dest_path = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $imagePath = $dest_path;
        }
    }

    // Debugging: capture the checkbox data before processing
    file_put_contents('debug.log', "Processing destination $i\n", FILE_APPEND);
    file_put_contents('debug.log', "POST data for destination $i: " . print_r([
        'stage1' => $_POST['stage1_checked'][$i] ?? "0",
        'stage2' => $_POST['stage2_checked'][$i] ?? "0",
        'sheets' => $_POST['sheets_checked'][$i] ?? "0",
        'pickup' => $_POST['pickup_checked'][$i] ?? "0",
        'backorder' => $_POST['backorder_checked'][$i] ?? "0",
    ], true) . "\n", FILE_APPEND);

    $destinations[] = [
        "destination_address" => $destination_addresses[$i],
        "invoice_number" => $invoice_numbers[$i],
        "comments" => $comments_array[$i],
        "image_path" => $imagePath,
        "stage1" => isCheckboxChecked($_POST['stage1_checked'], $i),
        "stage2" => isCheckboxChecked($_POST['stage2_checked'], $i),
        "sheets" => isCheckboxChecked($_POST['sheets_checked'], $i),
        "pickup" => isCheckboxChecked($_POST['pickup_checked'], $i),
        "backorder" => isCheckboxChecked($_POST['backorder_checked'], $i),
    ];
    file_put_contents('debug.log', "Processed data for destination $i: " . print_r($destinations[$i], true) . "\n", FILE_APPEND);
}

$destinations_json = json_encode($destinations);

$sql = "INSERT INTO trips (driver_name, unit_number, date_time, destinations) VALUES (:driver_name, :unit_number, :date_time, :destinations)";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(":driver_name", $driver_name, PDO::PARAM_STR);
$stmt->bindParam(":unit_number", $unit_number, PDO::PARAM_STR);
$stmt->bindParam(":date_time", $date_time, PDO::PARAM_STR);
$stmt->bindParam(":destinations", $destinations_json, PDO::PARAM_STR);

try {
    $stmt->execute();
    // Successful execution
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Handle the error and send a failure response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
