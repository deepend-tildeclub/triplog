<?php
date_default_timezone_set('America/Denver');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REMOTE_ADDR'] !== '68.145.8.184') {
    die('Access denied.');
}


require '../config_trip.php';
$pdo = connectTripDB();

$searchTerm = '';
$trips = [];
$error = '';

// Handling the search
if (isset($_POST['search'])) {
    $searchTerm = trim($_POST['search']);
    $searchDate = isset($_POST['search_date']) ? $_POST['search_date'] : null;

    $queryParams = [];
    $queryParts = [];

    if (!empty($searchTerm)) {
        $queryParts[] = "(destination_address LIKE :search OR invoice_number LIKE :search OR comments LIKE :search OR trips.driver_name LIKE :search OR trips.unit_number LIKE :search)";
        $queryParams['search'] = "%$searchTerm%";
    }

    if (!empty($searchDate)) {
        $queryParts[] = "DATE(trips.date_time) = :search_date";
        $queryParams['search_date'] = $searchDate;
    }

    if (empty($queryParts)) {
        $error = "Please enter a search term or date.";
    } else {
        $stmt = $pdo->prepare("SELECT destinations.*, trips.driver_name, trips.unit_number, trips.date_time FROM destinations LEFT JOIN trips ON destinations.trip_id = trips.id WHERE " . implode(' AND ', $queryParts));
        $stmt->execute($queryParams);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bigfoot Delivery Log Search</title>
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
        <h1>Bigfoot Delivery Log Search</h1>
    </div>

    <p>Welcome, Search for trip entries below:</p><br>

    <!-- Displaying messages or errors -->
    <?php
    if (!empty($message)) {
        echo '<p style="color:green;">' . $message . '</p>';
    }
    if (!empty($error)) {
        echo '<p style="color:red;">' . $error . '</p>';
    }
    ?>

    <!-- Search Form -->
    <form action="index.php" method="POST">
        <input type="text" name="search" placeholder="Search...">
        <input type="date" name="search_date" placeholder="Search by date...">
        <input type="submit" value="Search">
    </form><br>

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
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($trips as $trip) {
        echo "<tr>";
        echo "<td>" . $trip['driver_name'] . "</td>";
        echo "<td>" . $trip['unit_number'] . "</td>";
        echo "<td>" . $trip['destination_address'] . "</td>";
        echo "<td>" . $trip['invoice_number'] . "</td>";
        echo "<td>" . $trip['comments'] . "</td>";
if ($trip['image']) {
    $imageArray = json_decode($trip['image'], true);

    if ($imageArray && is_array($imageArray)) {
        echo "<td>";
        foreach ($imageArray as $image) {
echo "<img src='../serve_image.php?filename=" . urlencode($image) . "' class='thumbnail' onclick='showModal(this.src, \"{$trip['destination_address']}\", \"{$trip['destination_address']}\")' data-destination='{$trip['destination_address']}'>";
        }
        echo "</td>";
    } else {
        echo "<td>No Image</td>";
    }
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
        echo "</tr>";
    }
    ?>
    </tbody>
</table>
    
<!-- The Modal -->
<div id="myModal" class="modal">
    <span class="arrow" id="prevArrow" onclick="navigate(-1)">&#9664;</span>
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="img01">
    <div id="caption"></div>
    <span class="arrow" id="nextArrow" onclick="navigate(1)">&#9654;</span>
</div>


<script>
let imgArray = [];
let currentImgIndex = 0;
let currentDestination = '';

var modal = document.getElementById("myModal");
var modalImg = document.getElementById("img01");
var captionText = document.getElementById("caption");

function showModal(imgSrc, altText, destination) {
    currentDestination = destination;
    imgArray = Array.from(document.querySelectorAll('.thumbnail'))
        .filter(img => img.getAttribute('data-destination') === currentDestination)
        .map(img => img.src);
    currentImgIndex = imgArray.indexOf(imgSrc);
    modal.style.display = "block";
    modalImg.src = imgSrc;
    captionText.innerHTML = altText;
}

function closeModal() {
    modal.style.display = "none";
}

function navigate(step) {
    currentImgIndex += step;
    currentImgIndex = (currentImgIndex + imgArray.length) % imgArray.length;
    modalImg.src = imgArray[currentImgIndex];
}

var span = document.getElementsByClassName("close")[0];
span.onclick = closeModal;

</script>

</body>
</html>
