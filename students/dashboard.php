
<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["student_name"] ?? "Student");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <h2>QuickBite</h2>
    <nav>
        <a href="../index.php">Home</a>
        <a href="menu.php">Menu</a>
        <a href="myorders.php">My Orders</a>
        <a href="cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="hero">
    <h1>Welcome, <?= $name ?>!</h1>
    <p>What would you like to eat today?</p>
    <a class="btn" href="menu.php">Explore Menu</a>
</section>

<section class="menu">
    <h2>Your QuickBite Dashboard</h2>
    <div class="food-container">
        <div class="food-card">
            <h3>Food Menu</h3>
            <p>Explore delicious food.</p>
            <a href="menu.php">View Menu</a>
        </div>

        <div class="food-card">
            <h3>My Cart</h3>
            <p>Check your selected items.</p>
            <a href="cart.php">Open Cart</a>
        </div>

        <div class="food-card">
            <h3>My Orders</h3>
            <p>Track your canteen orders.</p>
            <a href="myorders.php">View Orders</a>
        </div>
    </div>
</section>

</body>
</html>
