<?php
date_default_timezone_set('America/Denver');
require '../config_trip.php';
$pdo = connectTripDB();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images']) && isset($_GET['id'])) {
    $tripId = $_GET['id'];
    
    $uploadDir = '/home/bbp/triplogdb/uploads/';
    
    $existingImages = [];
    $stmt = $pdo->prepare("SELECT image FROM destinations WHERE id = :id");
    $stmt->execute(['id' => $tripId]);
    $existingRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existingRow && $existingRow['image']) {
        $existingImages = json_decode($existingRow['image'], true) ?? [];
    }
    
    $fileNames = $_FILES['images']['name'];
    $total = count($fileNames);
    
    for ($i = 0; $i < $total; $i++) {
        $fileName = $fileNames[$i];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $randomString = bin2hex(random_bytes(8));
        $uniqueFileName = str_replace(' ', '_', pathinfo($fileName, PATHINFO_FILENAME)) . '_' . $randomString . '.' . $fileType;
        
        $targetFilePath = $uploadDir . $uniqueFileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath)) {

                // Watermark code starts here
                $watermarkText = date('Y-m-d H:i:s');
                $fontSize = 60; 
                $font = 'watermark.ttf'; 
                
                if ($fileType === 'jpg' || $fileType === 'jpeg') {
                    $image = imagecreatefromjpeg($targetFilePath);
                } elseif ($fileType === 'png') {
                    $image = imagecreatefrompng($targetFilePath);
                }
                
                $textColor = imagecolorallocate($image, 255, 0, 0);
                $textPositionX = 10;
                $textPositionY = imagesy($image) - 10; 
                
                imagettftext($image, $fontSize, 0, $textPositionX, $textPositionY, $textColor, $font, $watermarkText);

                // Compression code starts here
                $compressionQuality = 75; // Quality from 0 to 100 for JPEG
                if ($fileType === 'jpg' || $fileType === 'jpeg') {
                    imagejpeg($image, $targetFilePath, $compressionQuality);
                } elseif ($fileType === 'png') {
                    imagepng($image, $targetFilePath, 9); // Compression level from 0 to 9 for PNG
                }
                // Compression code ends here
                
                imagedestroy($image);
                
                // Watermark code ends here
                
                $existingImages[] = $uniqueFileName;
            } else {
                $message = 'Sorry, there was an error uploading your file.';
            }
        } else {
            $message = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
        }
    }
    
    if (empty($message)) {
        $stmt = $pdo->prepare("UPDATE destinations SET image = :image WHERE id = :id");
        $stmt->execute(['image' => json_encode($existingImages), 'id' => $tripId]);
        $message = 'Image(s) uploaded successfully.';
    }
}

header('Location: index.php?message=' . urlencode($message));
