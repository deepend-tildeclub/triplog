<?php
require 'config_trip.php';
$pdo = connectTripDB();
date_default_timezone_set('America/Denver');

$currentDate = date('Y-m-d');
$stmt = $pdo->prepare("SELECT DISTINCT driver_name FROM trips WHERE DATE(date_time) = :current_date");
$stmt->execute(['current_date' => $currentDate]);
$drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Driver Status for Today</h1>
    <table>
        <thead>
            <tr>
                <th>Driver Name</th>
                <th>Last Delivery Time</th>
                <th>Last Known Delivery</th>
            </tr>
        </thead>
        <tbody>
            <?php
foreach ($drivers as $driver) {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE driver_name = :driver_name AND DATE(date_time) = :current_date");
    $stmt->execute(['driver_name' => $driver, 'current_date' => $currentDate]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lastDeliveryTime = 'N/A';
    $lastAddress = 'N/A';
    $latestTimestamp = 0;

    foreach ($trips as $trip) {
        $stmt = $pdo->prepare("SELECT * FROM destinations WHERE trip_id = :trip_id");
        $stmt->execute(['trip_id' => $trip['id']]);
        $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $timestamps = [];

        foreach ($destinations as $destination) {
            $imageArray = json_decode($destination['image'], true);
            if ($imageArray && is_array($imageArray)) {
                foreach ($imageArray as $image) {
                    $imagePath = "/home/bbp/triplogdb/uploads/" . $image;
                    if (file_exists($imagePath)) {
                        $fileTimestamp = filemtime($imagePath);
                        $timestamps[] = $fileTimestamp;
                    }
                }
            }
        }

        if (!empty($timestamps)) {
            sort($timestamps);
            $oldestTimestamp = array_shift($timestamps);
            $timestamps = array_filter($timestamps, function($timestamp) use ($oldestTimestamp) {
                return $timestamp !== $oldestTimestamp;
            });

            $latestTripTimestamp = end($timestamps);
            if ($latestTripTimestamp > $latestTimestamp) {
                $latestTimestamp = $latestTripTimestamp;
                $lastDeliveryTime = date('M d, Y g:iA', $latestTimestamp);
                $lastAddress = $destination['destination_address'];
            }
        }
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($driver) . "</td>";
    echo "<td>" . $lastDeliveryTime . "</td>";
    echo "<td>" . htmlspecialchars($lastAddress) . "</td>";
    echo "</tr>";
}

            ?>
        </tbody>
    </table>
</body>
</html>
