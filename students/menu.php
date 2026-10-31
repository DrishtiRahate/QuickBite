<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query(
    "SELECT id, name, description, price, category
     FROM food
     WHERE available = 1
     ORDER BY id"
);

$foodItems = [];

if ($result) {
    while ($food = $result->fetch_assoc()) {
        $foodItems[] = $food;
    }
}

function getFoodImage($foodName) {
    $name = strtolower(trim($foodName));

    if (strpos($name, "pizza") !== false) {
        return "../images/pizza.jpg";
    }

    if (strpos($name, "burger") !== false) {
        return "../images/burger.jpg";
    }

    if (strpos($name, "sandwich") !== false) {
        return "../images/sandwich.jpg";
    }

    if (
        strpos($name, "cold coffee") !== false ||
        strpos($name, "coldcoffee") !== false
    ) {
        return "../images/cold coffee.jpg";
    }

    return "../images/food-placeholder.jpg";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fffaf5;
        }

        header {
            padding: 15px 5%;
            background: #f47721;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
        }

        header nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        header nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .menu {
            padding: 30px 5%;
            max-width: 1200px;
            margin: auto;
        }

        .menu > h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .menu-tools {
            max-width: 700px;
            margin: 0 auto 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .menu-tools input,
        .menu-tools select {
            flex: 1;
            min-width: 200px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
        }

        .food-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
        }

        .food-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,.09);
            padding-bottom: 20px;
        }

        .food-card img {
            display: block;
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: #f3f3f3;
        }

        .food-card h3,
        .food-card p,
        .food-card h4,
        .food-card form {
            margin-left: 16px;
            margin-right: 16px;
        }

        .food-card h3 {
            margin-top: 16px;
        }

        .food-card p {
            color: #555;
            line-height: 1.5;
        }

        .food-card h4 {
            color: #e96514;
            font-size: 20px;
        }

        .food-card label {
            display: block;
            margin-bottom: 12px;
        }

        .food-card input[type="number"] {
            width: 65px;
            padding: 7px;
            margin-left: 5px;
        }

        .food-card .btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 7px;
            background: #f47721;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .food-card .btn:hover {
            background: #d95f0c;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 500px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .menu-tools {
                flex-direction: column;
            }

            .menu-tools input,
            .menu-tools select {
                box-sizing: border-box;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>QuickBite</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="menu.php">Menu</a>
        <a href="cart.php">Cart</a>
        <a href="myorders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="menu">
    <h2>Our Food Menu</h2>

    <div class="menu-tools">
        <input type="search" id="foodSearch"
               placeholder="Search food..."
               aria-label="Search food by name">

        <select id="categoryFilter" aria-label="Filter by category">
            <option value="all">All Categories</option>
            <?php
            $categories = [];

            foreach ($foodItems as $food) {
                $category = trim($food["category"] ?? "");

                if ($category !== "" &&
                    !in_array($category, $categories, true)) {
                    $categories[] = $category;
                }
            }

            sort($categories);

            foreach ($categories as $category):
            ?>
                <option value="<?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>">
                    <?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="food-container" id="foodContainer">
        <?php if (count($foodItems) > 0): ?>

            <?php foreach ($foodItems as $food): ?>
                <div class="food-card"
                     data-name="<?= htmlspecialchars($food["name"], ENT_QUOTES, "UTF-8") ?>"
                     data-category="<?= htmlspecialchars($food["category"] ?? "", ENT_QUOTES, "UTF-8") ?>">

                    <img
                        src="<?= htmlspecialchars(getFoodImage($food["name"]), ENT_QUOTES, "UTF-8") ?>"
                        alt="<?= htmlspecialchars($food["name"], ENT_QUOTES, "UTF-8") ?>"
                        loading="lazy"
                        onerror="this.onerror=null;this.alt='Image unavailable';this.style.display='none';">

                    <h3><?= htmlspecialchars($food["name"], ENT_QUOTES, "UTF-8") ?></h3>

                    <p><?= htmlspecialchars($food["description"] ?? "", ENT_QUOTES, "UTF-8") ?></p>

                    <p>
                        Category:
                        <?= htmlspecialchars($food["category"] ?? "Other", ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <h4>₹<?= number_format((float)$food["price"], 2) ?></h4>

                    <form method="POST" action="add_to_cart.php">
                        <input type="hidden" name="food_id"
                               value="<?= (int)$food["id"] ?>">

                        <label>
                            Quantity:
                            <input type="number" name="quantity"
                                   value="1" min="1" max="20" required>
                        </label>

                        <button type="submit" class="btn">Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <p class="no-results" id="noResults" hidden>
                No matching food items found.
            </p>

        <?php else: ?>
            <p class="no-results">No food items available right now.</p>
        <?php endif; ?>
    </div>
</section>

<script>
const searchInput = document.getElementById("foodSearch");
const categoryFilter = document.getElementById("categoryFilter");
const foodCards = document.querySelectorAll(".food-card");
const noResults = document.getElementById("noResults");

function filterFood() {
    const searchText = searchInput.value.trim().toLowerCase();
    const selectedCategory = categoryFilter.value.toLowerCase();
    let visibleCount = 0;

    foodCards.forEach(function(card) {
        const name = card.dataset.name.toLowerCase();
        const category = card.dataset.category.toLowerCase();

        const matchesName = name.includes(searchText);
        const matchesCategory =
            selectedCategory === "all" ||
            category === selectedCategory;

        const showCard = matchesName && matchesCategory;
        card.hidden = !showCard;

        if (showCard) {
            visibleCount++;
        }
    });

    if (noResults) {
        noResults.hidden = visibleCount !== 0;
    }
}

searchInput.addEventListener("input", filterFood);
categoryFilter.addEventListener("change", filterFood);
</script>

</body>
</html>
