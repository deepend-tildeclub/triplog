<?php
date_default_timezone_set('America/Denver');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config_trip.php';
$pdo = connectTripDB();

$driverName = '';
$trips = [];
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['driver_name'])) {
        $driverName = trim($_POST['driver_name']);  // Added trim() function
    } elseif (isset($_POST['hidden_driver_name'])) {
        $driverName = trim($_POST['hidden_driver_name']);  // Added trim() function
    }

    $currentDate = date('Y-m-d'); 

    $stmt = $pdo->prepare("SELECT destinations.*, trips.driver_name, trips.unit_number, trips.date_time 
                           FROM destinations 
                           LEFT JOIN trips ON destinations.trip_id = trips.id 
                           WHERE LOWER(trips.driver_name) LIKE LOWER(:driver_name_pattern) 
                           AND DATE(trips.date_time) = :current_date");

    $stmt->execute(['driver_name_pattern' => "%{$driverName}%", 'current_date' => $currentDate]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .thumbnail {
            width: 100px;
            height: auto;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>Driver Dashboard</h1>
        </div>

        <form action="index.php" method="POST">
            <label for="driver_name">Enter your Driver Name:</label>
            <input type="text" name="driver_name" value="<?php echo htmlspecialchars($driverName); ?>" required>
            <input type="submit" value="Submit">
            <?php if (!empty($driverName)): ?>
                <input type="hidden" name="hidden_driver_name" value="<?php echo htmlspecialchars($driverName); ?>">
                <input type="submit" name="refresh" value="Refresh">
            <?php endif; ?>
        </form>
        
        <table class="trip-log-table">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Unit Number</th>
                    <th>Destination Address</th>
                    <th>Invoice Number</th>
                    <th>Comments</th>
                    <th>Image</th>
                    <th>Stages & Status</th>
                    <th>Date/Time</th>
                    <th>Upload Images</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($trips as $trip) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($trip['driver_name']) . "</td>";
                echo "<td>" . htmlspecialchars($trip['unit_number']) . "</td>";
                echo "<td>" . htmlspecialchars($trip['destination_address']) . "</td>";
                echo "<td>" . htmlspecialchars($trip['invoice_number']) . "</td>";
                echo "<td>" . htmlspecialchars($trip['comments']) . "</td>";
                if ($trip['image']) {
                    $images = json_decode($trip['image'], true); // decode to an associative array
                    echo "<td>";
                    if (is_array($images)) {
                        foreach($images as $image) {
                            $safeImage = rawurlencode($image);
                            echo "<img src='../serve_image.php?filename=" . htmlspecialchars($safeImage) . "' alt='trip image' class='thumbnail'>";
                        }
                    } else {
                        echo "Invalid Image Data";
                    }
                    echo "</td>";
                } else {
                    echo "<td>No Image</td>";
                }
                echo "<td>";
                echo $trip['stage_1'] ? "Stage 1, " : "";
                echo $trip['stage_2'] ? "Stage 2, " : "";
                echo $trip['sheets'] ? "Sheets, " : "";
                echo $trip['pick_up'] ? "Pick Up, " : "";
                echo $trip['returns'] ? "Returns, " : "";
                echo $trip['back_order'] ? "Back Order" : "";
                echo "</td>";
                echo "<td>" . date('M d, Y g:iA', strtotime($trip['date_time'])) . "</td>";
echo "<td>";
echo "<form action='upload_image.php?id=" . $trip['id'] . "' method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='images[]' multiple>";
echo "<input type='submit' value='Upload'>";
echo "</form>";
echo "</td>";
                echo "</tr>";
            }
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    if (isset($_GET['error'])) {
        echo "<p style='color:red;'>Error: $message</p>";
    } else {
        echo "<p>$message</p>";
    }
}

            ?>
            </tbody>
        </table>
    </div>
</body>
</html>