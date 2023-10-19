<?php
session_start();

// Check if the user is authenticated or if the referrer is allowed
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedReferrers = [
    "https://bigfootbuilding.com/triplog/search/",
    "https://bigfootbuilding.com/triplog/driver/"  // Add your second allowed referrer here
];

$referrerAllowed = false;
foreach ($allowedReferrers as $allowed) {
    if (strpos($referrer, $allowed) !== false) {
        $referrerAllowed = true;
        break;
    }
}

if (
    (!isset($_SESSION['manager_logged_in']) || !$_SESSION['manager_logged_in']) &&
    !$referrerAllowed
) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$filename = $_GET['filename'];
$filepath = '/home/bbp/triplogdb/uploads/' . $filename;

if (file_exists($filepath)) {
    header('Content-Type: image/jpeg');
    readfile($filepath);
} else {
    http_response_code(404);
    echo 'File not found';
}
?>
