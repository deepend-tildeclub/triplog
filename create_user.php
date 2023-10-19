<?php
require 'config_trip.php';

if ($_SERVER['REMOTE_ADDR'] !== '68.145.8.184') {
    die('Access denied.');
}

$pdo = connectTripDB();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullName = $_POST['full_name'];

    $stmt = $pdo->prepare("INSERT INTO managers (username, password, full_name) VALUES (:username, :password, :full_name)");

    try {
        $stmt->execute(['username' => $username, 'password' => $password, 'full_name' => $fullName]);
        $message = "User {$username} created successfully!";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Create Manager User</h1>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form action="create_user.php" method="post">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <br>
        
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <br>

        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" required>
        <br>

        <input type="submit" value="Create User">
    </form>
</div>
</body>
</html>