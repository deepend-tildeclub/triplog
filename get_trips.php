<?php
require_once "config_trip.php";

$pdo = connectTripDB();

$sql = "SELECT * FROM trips ORDER BY date_time DESC";
$stmt = $pdo->query($sql);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="report-table">
    <thead>
        <tr>
            <th>Driver Name</th>
            <th>Unit Number</th>
            <th>Date/Time</th>
            <th>Destination Address</th>
            <th>Invoice Number</th>
            <th>Comments</th>
            <th>Image</th>
            <th>Stage 1</th>
            <th>Stage 2</th>
            <th>Sheets</th>
            <th>Pick-Up</th>
            <th>Back-Order</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($trips as $trip) { 
            $destinations = json_decode($trip["destinations"], true);
            foreach ($destinations as $destination) {
        ?>
            <tr>
                <td><?php echo htmlspecialchars($trip["driver_name"]); ?></td>
                <td><?php echo htmlspecialchars($trip["unit_number"]); ?></td>
                <td><?php echo htmlspecialchars($trip["date_time"]); ?></td>
                <td><?php echo htmlspecialchars($destination["destination_address"]); ?></td>
                <td><?php echo htmlspecialchars($destination["invoice_number"]); ?></td>
                <td><?php echo htmlspecialchars($destination["comments"]); ?></td>
                <td>
                    <?php if (isset($destination["image_path"]) && $destination["image_path"]): ?>
                        <a href="<?php echo htmlspecialchars($destination["image_path"]); ?>" target="_blank">
                            <img src="images/pic.png" alt="View Image" title="Click to view full image" width="20">
                        </a>
                    <?php endif; ?>
                </td>
                <td><?php echo isset($destination["stage1"]) && $destination["stage1"] == 1 ? "✓" : ""; ?></td>
                <td><?php echo isset($destination["stage2"]) && $destination["stage2"] == 1 ? "✓" : ""; ?></td>
                <td><?php echo isset($destination["sheets"]) && $destination["sheets"] == 1 ? "✓" : ""; ?></td>
                <td><?php echo isset($destination["pickup"]) && $destination["pickup"] == 1 ? "✓" : ""; ?></td>
                <td><?php echo isset($destination["backorder"]) && $destination["backorder"] == 1 ? "✓" : ""; ?></td>
            </tr>
        <?php 
            }
        } 
        ?>
    </tbody>
</table>
