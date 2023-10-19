<?php
date_default_timezone_set('America/Denver');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REMOTE_ADDR'] !== '68.145.8.184') {
    die('Access denied.');
}


session_start();
if (!isset($_SESSION['manager_logged_in']) || !$_SESSION['manager_logged_in']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

require 'config_trip.php';
$pdo = connectTripDB();

$searchTerm = '';
$trips = [];
$error = '';
$message = '';

// Handling the deletion of a destination
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header('Location: manager.php');
    exit;
}

// Handling the addition of a new trip
if (isset($_POST['add_trip'])) {
    try {
        $driver_name = $_POST['driver_name'];
        $unit_number = $_POST['unit_number'];
        $date_time = isset($_POST['date_time']) && !empty($_POST['date_time']) ? date('Y-m-d H:i:s', strtotime($_POST['date_time'])) : date('Y-m-d H:i:s');

        // Insert new trip
        $stmt = $pdo->prepare("INSERT INTO trips (driver_name, unit_number, date_time) VALUES (:driver_name, :unit_number, :date_time)");
        $stmt->execute(['driver_name' => $driver_name, 'unit_number' => $unit_number, 'date_time' => $date_time]);

        $trip_id = $pdo->lastInsertId();

        // Insert destinations for this trip
        foreach ($_POST['destinations'] as $index => $destination) {
            // Handle the image upload (Multiple Files)
if (isset($_FILES['destinations']['name'][$index]['image']) && is_array($_FILES['destinations']['name'][$index]['image']) && !empty($_FILES['destinations']['name'][$index]['image'][0])) {
    $filenames = $_FILES['destinations']['name'][$index]['image'];
    $tmp_names = $_FILES['destinations']['tmp_name'][$index]['image'];
    $image_list = [];

foreach ($filenames as $key => $filename) {
    // Determine the extension (and thus, file type)
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    // Generate a unique name for the file
    $clean_filename = str_replace(' ', '_', $filename);
    $unique_name = uniqid() . '_' . $clean_filename;

    // Path to upload the original image
    $original_image_path = "/home/bbp/triplogdb/uploads/$unique_name";

    // Move the uploaded file to your directory
    move_uploaded_file($tmp_names[$key], $original_image_path);

    // Compress the image
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $source = imagecreatefromjpeg($original_image_path);
        imagejpeg($source, $original_image_path, 60); // 60 is the quality percentage
    } elseif ($ext === 'png') {
        $source = imagecreatefrompng($original_image_path);
        imagepng($source, $original_image_path, 6); // Compression level: from 0 (no compression) to 9
    }

    $image_list[] = $unique_name;
}
    $filename = json_encode($image_list);
} else {
    $filename = null;  // This ensures that if no file is uploaded, null is saved into the database.
}

            $stmt = $pdo->prepare("INSERT INTO destinations (trip_id, destination_address, invoice_number, comments, stage_1, stage_2, sheets, pick_up, returns, back_order, image) VALUES (:trip_id, :destination_address, :invoice_number, :comments, :stage_1, :stage_2, :sheets, :pick_up, :returns, :back_order, :image)");
            $stmt->execute([
                'trip_id' => $trip_id,
                'destination_address' => $destination['address'],
                'invoice_number' => $destination['invoice'],
                'comments' => $destination['comments'],
                'stage_1' => isset($destination['stage_1']) ? 1 : 0,
                'stage_2' => isset($destination['stage_2']) ? 1 : 0,
                'sheets' => isset($destination['sheets']) ? 1 : 0,
                'pick_up' => isset($destination['pick_up']) ? 1 : 0,
                'returns' => isset($destination['returns']) ? 1 : 0,
                'back_order' => isset($destination['back_order']) ? 1 : 0,
                'image' => $filename,
            ]);
        }

        $message = "New trip added successfully!";
    } catch (Exception $e) {
        $error = "Error adding new trip: " . $e->getMessage();
    }
}

// Handling the search
if (isset($_POST['search'])) {
    $searchTerm = trim($_POST['search']);
    $searchDate = isset($_POST['search_date']) ? $_POST['search_date'] : null;

    $queryParams = [];
    $queryParts = [];

    if(!empty($searchTerm)) {
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
        // Changed 'OR' to 'AND' to allow search by both term and date.
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
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="style.css">
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
        <h1>Manager Dashboard</h1>
        <div class="user-section">
            <span class="username-display">Logged in as: <?php echo $_SESSION['manager_username']; ?></span>
            <form action="manager.php" method="POST" style="display: inline;">
                <input type="submit" name="logout" value="Logout" class="logout-button">
            </form>
        </div>
    </div>

    <p>Welcome, <?php echo $_SESSION['manager_username']; ?>! Search for trip entries below:</p>

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
<form action="manager.php" method="POST">
    <input type="text" name="search" placeholder="Search...">
    <input type="date" name="search_date" placeholder="Search by date...">
    <input type="submit" value="Search">
</form><br>
<button id="toggle-button">+</button>

<div id="add-trip-section" style="display: none;">
    <!-- Add Trip Form -->
    <h2>Add New Trip</h2>
    <form action="manager.php" method="POST" enctype="multipart/form-data">
        <label for="driver_name">Driver Name:</label>
        <input type="text" name="driver_name" required>
        <label for="unit_number">Unit Number:</label>
        <input type="text" name="unit_number" required>
        <label for="date_time">Date and Time:</label>
        <small>Enter date and time only if different from the current date/time.</small>
        <input type="datetime-local" name="date_time">


<h3>Destinations:</h3>
<div id="destinations">
    <div class="destination">
        <label for="destination_address">Address:</label>
        <input type="text" name="destinations[0][address]" required>
        <label for="invoice_number">Invoice:</label>
        <input type="text" name="destinations[0][invoice]" required>
        <label for="comments">Comments:</label>
        <input type="text" name="destinations[0][comments]">
        <!-- Stages -->
        <br><br>
        <label><input type="checkbox" name="destinations[0][stage_1]" value="1"> Stage 1</label>
        <label><input type="checkbox" name="destinations[0][stage_2]" value="1"> Stage 2</label>
        <label><input type="checkbox" name="destinations[0][sheets]" value="1"> Sheets</label>
        <label><input type="checkbox" name="destinations[0][pick_up]" value="1"> Pick Up</label>
        <label><input type="checkbox" name="destinations[0][returns]" value="1"> Returns</label>
        <label><input type="checkbox" name="destinations[0][back_order]" value="1"> Back Order</label>
            <label for="image">Images:</label>
            <input type="file" name="destinations[0][image][]" multiple> <!-- Changed to multiple -->
    </div>
</div>
        <button type="button" onclick="addDestination()">Add Another Destination</button>
        <br>
        <input type="submit" name="add_trip" value="Add Trip">
    </form>
</div>

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
        <th>Edit</th>
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
//                    echo "<img src='/home/bbp/triplogdb/uploads/" . urlencode($image) . "' class='thumbnail' onclick='showModal(this.src, \"{$trip['destination_address']}\", \"{$trip['destination_address']}\")' data-destination='{$trip['destination_address']}'>";
                    echo "<img src='serve_image.php?filename=" . urlencode($image) . "' class='thumbnail' onclick='showModal(this.src, \"{$trip['destination_address']}\", \"{$trip['destination_address']}\")' data-destination='{$trip['destination_address']}'>";
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
      echo "<td><a href='edit_trip.php?id=" . $trip['id'] . "'>Edit</a> <a href='manager.php?action=delete&id=" . $trip['id'] . "' onclick='return confirm(\"Are you sure you want to delete this destination?\");'>Delete</a></td>";

        echo "</tr>";
    }
    ?>
    </tbody>
</table>

</div>
<!-- The Modal -->
<div id="myModal" class="modal">
    <span class="arrow" id="prevArrow" onclick="navigate(-1)">&#9664;</span>
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="img01">
    <div id="caption"></div>
    <span class="arrow" id="nextArrow" onclick="navigate(1)">&#9654;</span>
</div>

<script>
    let destinationCount = 1;

function addDestination() {
    const destinationsDiv = document.getElementById('destinations');
    const newDestination = document.createElement('div');
    newDestination.className = 'destination';
    newDestination.innerHTML = `
        <label for="destination_address">Address:</label>
        <input type="text" name="destinations[${destinationCount}][address]" required>
        <label for="invoice_number">Invoice:</label>
        <input type="text" name="destinations[${destinationCount}][invoice]" required>
        <label for="comments">Comments:</label>
        <input type="text" name="destinations[${destinationCount}][comments]">
        <br><br>
        <label><input type="checkbox" name="destinations[${destinationCount}][stage_1]" value="1"> Stage 1</label>
        <label><input type="checkbox" name="destinations[${destinationCount}][stage_2]" value="1"> Stage 2</label>
        <label><input type="checkbox" name="destinations[${destinationCount}][sheets]" value="1"> Sheets</label>
        <label><input type="checkbox" name="destinations[${destinationCount}][pick_up]" value="1"> Pick Up</label>
        <label><input type="checkbox" name="destinations[${destinationCount}][returns]" value="1"> Returns</label>
        <label><input type="checkbox" name="destinations[${destinationCount}][back_order]" value="1"> Back Order</label>
         <label for="image">Images:</label>
        <input type="file" name="destinations[${destinationCount}][image][]" multiple>
    `;
        const removeLink = document.createElement('a');
        removeLink.innerHTML = '-';
        removeLink.href = 'javascript:void(0);';
        removeLink.className = 'remove-link';  // Add the same class as the "+" button
        removeLink.onclick = function() {
            newDestination.remove();
        };
        newDestination.appendChild(removeLink);
    destinationsDiv.appendChild(newDestination);
    destinationCount++;
}
    var modal = document.getElementById("myModal");
    var modalImg = document.getElementById("img01");
    var captionText = document.getElementById("caption");

    function showModal(imgSrc, altText) {
        modal.style.display = "block";
        modalImg.src = imgSrc;
        captionText.innerHTML = altText;
    }

    var span = document.getElementsByClassName("close")[0];

    span.onclick = function() {
        modal.style.display = "none";
    }
    
     const toggleButton = document.getElementById("toggle-button");
  const addTripSection = document.getElementById("add-trip-section");

  toggleButton.addEventListener("click", function() {
    if (addTripSection.style.display === "none") {
      addTripSection.style.display = "block";
      toggleButton.innerHTML = "-";
    } else {
      addTripSection.style.display = "none";
      toggleButton.innerHTML = "+";
    }
  });
 let imgArray = [];
let currentImgIndex = 0;
let currentDestination = '';

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