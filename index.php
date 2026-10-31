<?php
require_once __DIR__ . "/includes/db.php";

$sql = "SELECT * FROM food WHERE available = 1";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickBite | Smart Canteen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h2>QuickBite</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="students/menu.php">Menu</a>
        <a href="students/login.php">Login</a>
        <a href="students/register.php">Register</a>
    </nav>
</header>

<section class="hero">
    <h1>Hungry? Skip the Queue!</h1>
    <p>Pre-Order Your Favourite Food Online</p>
    <a class="btn" href="students/menu.php">Order Now</a>
</section>

<section class="menu">
    <h2>Our Menu</h2>

    <div class="food-container">

        <?php if ($result && $result->num_rows > 0): ?>

            <?php while ($food = $result->fetch_assoc()): ?>

                <div class="food-card">
                    <h3><?= htmlspecialchars($food['name']) ?></h3>

                    <p>
                        <?= htmlspecialchars($food['description'] ?? '') ?>
                    </p>

                    <h4>
                        ₹<?= number_format((float)$food['price'], 2) ?>
                    </h4>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No food items available.</p>

        <?php endif; ?>

    </div>
</section>

<footer>
    <p>© 2026 QuickBite | College Smart Canteen</p>
</footer>

</body>
</html>
