<?php
require_once "config_trip.php";
$pdo = connectTripDB();

session_start();
if (!isset($_SESSION['manager_logged_in']) || !$_SESSION['manager_logged_in']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $destination_address = $_POST['destination_address'];
    $invoice_number = $_POST['invoice_number'];
    $comments = $_POST['comments'];
    $driver_name = $_POST['driver_name'];
    $unit_number = $_POST['unit_number'];

    $stage_1 = isset($_POST['stage_1']) ? 1 : 0;
    $stage_2 = isset($_POST['stage_2']) ? 1 : 0;
    $sheets = isset($_POST['sheets']) ? 1 : 0;
    $pick_up = isset($_POST['pick_up']) ? 1 : 0;
    $returns = isset($_POST['returns']) ? 1 : 0;
    $back_order = isset($_POST['back_order']) ? 1 : 0;

    $existingImages = $pdo->query("SELECT image FROM destinations WHERE id = $id")->fetch(PDO::FETCH_ASSOC)['image'];
    $existingImagesArray = json_decode($existingImages, true) ?: [];

    if (isset($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $deleteImage) {
            if (($key = array_search($deleteImage, $existingImagesArray)) !== false) {
                unset($existingImagesArray[$key]);
                if (file_exists("/home/bbp/triplogdb/uploads/$deleteImage")) {
                    unlink("/home/bbp/triplogdb/uploads/$deleteImage");
                }
            }
        }
    }

    $uploadedFiles = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $filename = $_FILES['images']['name'][$key];
                $filename = str_replace(" ", "_", $filename);
                $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                $newFileName = $filename . '_' . $randomString;
                $location = "/home/bbp/triplogdb/uploads/$newFileName";
                move_uploaded_file($tmp_name, $location);
                $uploadedFiles[] = $newFileName;
            }
        }
    }

    $finalImageArray = array_merge($existingImagesArray, $uploadedFiles);
    $finalImageJSON = json_encode($finalImageArray);

    $stmt = $pdo->prepare("UPDATE destinations SET image = :image WHERE id = :id");
    $stmt->execute(['image' => $finalImageJSON, 'id' => $id]);

    $stmt = $pdo->prepare("UPDATE destinations SET destination_address = :destination_address, invoice_number = :invoice_number, comments = :comments, stage_1 = :stage_1, stage_2 = :stage_2, sheets = :sheets, pick_up = :pick_up, returns = :returns, back_order = :back_order WHERE id = :id");
    $stmt->execute(['destination_address' => $destination_address, 'invoice_number' => $invoice_number, 'comments' => $comments, 'stage_1' => $stage_1, 'stage_2' => $stage_2, 'sheets' => $sheets, 'pick_up' => $pick_up, 'returns' => $returns, 'back_order' => $back_order, 'id' => $id]);

    $stmt = $pdo->prepare("SELECT trip_id FROM destinations WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $trip_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE trips SET driver_name = :driver_name, unit_number = :unit_number WHERE id = :trip_id");
    $stmt->execute(['driver_name' => $driver_name, 'unit_number' => $unit_number, 'trip_id' => $trip_id]);

    // New code to update date_time
    if (isset($_POST['date_time']) && !empty($_POST['date_time'])) {
        $date_time = $_POST['date_time'];
        $date_time = date('Y-m-d H:i:s', strtotime($date_time));
        $stmt = $pdo->prepare("UPDATE trips SET date_time = :date_time WHERE id = :trip_id");
        $stmt->execute(['date_time' => $date_time, 'trip_id' => $trip_id]);
    }

    header("Location: manager.php");
    exit;
}
?>
