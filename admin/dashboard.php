<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_row();
    return (int)($row[0] ?? 0);
}

$totalStudents = getCount(
    $conn,
    "SELECT COUNT(*) FROM students"
);

$totalFood = getCount(
    $conn,
    "SELECT COUNT(*) FROM food"
);

$totalOrders = getCount(
    $conn,
    "SELECT COUNT(*) FROM orders"
);

$pendingOrders = getCount(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status = 'Pending'"
);

$completedOrders = getCount(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status = 'Delivered'"
);

$adminName = htmlspecialchars(
    $_SESSION["admin_username"] ?? "Admin"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - QuickBite</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>
        .food-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .food-card {
            border: 2px solid #ffb703;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #fff;
        }

        .food-card h3 {
            margin-bottom: 12px;
        }

        .food-card h2 {
            font-size: 32px;
            margin: 0;
            color: #222;
        }
    </style>
</head>

<body>

<header>
    <h2>QuickBite Admin</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="food.php">Manage Food</a>
        <a href="orders.php">Manage Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="hero">
    <h1>Welcome, <?= $adminName ?>!</h1>
    <p>Manage your college canteen from here.</p>
</section>

<section class="menu">

    <h2>Admin Dashboard</h2>

    <div class="food-container">

        <div class="food-card">
            <h3>Total Students</h3>
            <h2><?= $totalStudents ?></h2>
        </div>

        <div class="food-card">
            <h3>Total Food Items</h3>
            <h2><?= $totalFood ?></h2>
        </div>

        <div class="food-card">
            <h3>Total Orders</h3>
            <h2><?= $totalOrders ?></h2>
        </div>

        <div class="food-card">
            <h3>Pending Orders</h3>
            <h2><?= $pendingOrders ?></h2>
        </div>

        <div class="food-card">
            <h3>Completed Orders</h3>
            <h2><?= $completedOrders ?></h2>
        </div>

    </div>

</section>

</body>
</html>
