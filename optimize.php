<?php

function correctImageOrientation($filePath) {
    $exif = exif_read_data($filePath);
    if (!empty($exif['Orientation'])) {
        $image = imagecreatefromjpeg($filePath);
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }
        imagejpeg($image, $filePath, 75);
        imagedestroy($image);
    }
}

$uploadDir = '/home/bbp/triplogdb/uploads/';
$logFile = 'optimized_images.log';

$optimizedImages = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES) : [];

$files = scandir($uploadDir);

foreach ($files as $file) {
    if (in_array($file, $optimizedImages)) {
        continue;
    }

    $filePath = $uploadDir . $file;
    $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($fileType === 'jpg' || $fileType === 'jpeg') {
        correctImageOrientation($filePath); // Correct orientation before optimizing
        $image = imagecreatefromjpeg($filePath);
        imagejpeg($image, $filePath, 75);
        imagedestroy($image);
    } elseif ($fileType === 'png') {
        $image = imagecreatefrompng($filePath);
        imagepng($image, $filePath, 6);
        imagedestroy($image);
    } else {
        continue;
    }

    file_put_contents($logFile, $file . PHP_EOL, FILE_APPEND);
}

echo "Image optimization complete.";

?>
