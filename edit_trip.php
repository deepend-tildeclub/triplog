<?php
require_once "config_trip.php";
$pdo = connectTripDB();

if ($_SERVER['REMOTE_ADDR'] !== '68.145.8.184') {
    die('Access denied.');
}

session_start();
if (!isset($_SESSION['manager_logged_in']) || !$_SESSION['manager_logged_in']) {
    header('Location: login.php');
    exit;
}

$trip = [
    'destination_address' => '',
    'invoice_number' => '',
    'comments' => '',
    'image' => '',
    'driver_name' => '',
    'unit_number' => '',
    'stage_1' => 0,
    'stage_2' => 0,
    'sheets' => 0,
    'pick_up' => 0,
    'returns' => 0,
    'back_order' => 0
];

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT destinations.*, trips.driver_name, trips.unit_number,trips.date_time FROM destinations LEFT JOIN trips ON destinations.trip_id = trips.id WHERE destinations.id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $trip = array_merge($trip, $data);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trip Entry</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Edit Trip Entry</h1>

    <form action="save_changes.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($trip['id']); ?>">
      
        Date and Time:
        <input type="datetime-local" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($trip['date_time'])); ?>">
      
        Driver Name:
        <input type="text" name="driver_name" value="<?php echo htmlspecialchars($trip['driver_name']); ?>">

        Unit Number:
        <input type="text" name="unit_number" value="<?php echo htmlspecialchars($trip['unit_number']); ?>">

        Destination Address:
        <input type="text" name="destination_address" value="<?php echo htmlspecialchars($trip['destination_address']); ?>">

        Invoice Number:
        <input type="text" name="invoice_number" value="<?php echo htmlspecialchars($trip['invoice_number']); ?>">

        Comments:
        <textarea name="comments"><?php echo htmlspecialchars($trip['comments']); ?></textarea>

        Image:
        <?php 
        if($trip['image']): 
            $imageArray = json_decode($trip['image'], true);
            if (is_array($imageArray)) {
                foreach ($imageArray as $image) {
                    echo "<img src='serve_image.php?filename=" . urlencode($image) . "' width='100'>";
                    echo "<label><input type='checkbox' name='delete_images[]' value='" . htmlspecialchars($image) . "'> Delete</label><br>";
                }
            }
        endif;
        ?>
        <input type="file" name="images[]" multiple>

        Stages & Status:
        <br>
        <label>
            <input type="checkbox" name="stage_1" <?php echo $trip['stage_1'] ? 'checked' : ''; ?>> Stage 1
        </label>
        <label>
            <input type="checkbox" name="stage_2" <?php echo $trip['stage_2'] ? 'checked' : ''; ?>> Stage 2
        </label>
        <label>
            <input type="checkbox" name="sheets" <?php echo $trip['sheets'] ? 'checked' : ''; ?>> Sheets
        </label>
        <label>
            <input type="checkbox" name="pick_up" <?php echo $trip['pick_up'] ? 'checked' : ''; ?>> Pick Up
        </label>
        <label>
            <input type="checkbox" name="returns" <?php echo $trip['returns'] ? 'checked' : ''; ?>> Returns
        </label>
        <label>
            <input type="checkbox" name="back_order" <?php echo $trip['back_order'] ? 'checked' : ''; ?>> Back Order
        </label>

        <input type="submit" value="Save Changes"><a href="manager.php" class="cancel-button">Cancel</a>
    </form>

</div>
</body>
</html>

