<?php
require 'config_trip.php';

$pdo = connectTripDB();

$msg = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM managers WHERE username = :username');
    $stmt->execute(['username' => $username]);

    $manager = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($manager && password_verify($password, $manager['password'])) {
        session_start();
        $_SESSION['manager_logged_in'] = true;
        $_SESSION['manager_username'] = $manager['username'];

        header('Location: manager.php');
        exit;
    } else {
        $msg = 'Incorrect username or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Manager Login</h1>
    <?php if ($msg): ?>
        <p class="error-message"><?php echo $msg; ?></p>
    <?php endif; ?>
    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="submit" name="login" value="Login" class="login-button">
    </form>
</div>
</body>
</html>