<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "success";

// Handle all food management actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    // Add Food
    if ($action === "add") {
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $category = trim($_POST["category"] ?? "");

        if ($name === "" || $category === "" || $price <= 0) {
            $message = "Please enter valid food details and price.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO food
                (name, description, price, category, available)
                VALUES (?, ?, ?, ?, 1)"
            );

            $stmt->bind_param(
                "ssds",
                $name,
                $description,
                $price,
                $category
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: food.php?msg=added");
                exit;
            } else {
                $message = "Unable to add food item.";
                $messageType = "error";
                $stmt->close();
            }
        }
    }

    // Edit Food
    elseif ($action === "edit") {
        $id = (int)($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $category = trim($_POST["category"] ?? "");

        if ($id <= 0 || $name === "" || $category === "" || $price <= 0) {
            $message = "Please enter valid food details.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "UPDATE food
                 SET name = ?, description = ?, price = ?, category = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "ssdsi",
                $name,
                $description,
                $price,
                $category,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: food.php?msg=updated");
                exit;
            } else {
                $message = "Unable to update food item.";
                $messageType = "error";
                $stmt->close();
            }
        }
    }

    // Toggle Availability
    elseif ($action === "toggle") {
        $id = (int)($_POST["id"] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare(
                "UPDATE food
                 SET available = IF(available = 1, 0, 1)
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: food.php?msg=availability");
                exit;
            }

            $message = "Unable to update availability.";
            $messageType = "error";
            $stmt->close();
        }
    }

    // Delete Food
    elseif ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);

        if ($id <= 0) {
            $message = "Invalid food item.";
            $messageType = "error";
        } else {
            // Prevent deleting food that is in cart or old orders.
            $stmt = $conn->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM cart WHERE food_id = ?) +
                    (SELECT COUNT(*) FROM order_items WHERE food_id = ?)"
            );

            if (!$stmt) {
                $message = "Unable to check food usage.";
                $messageType = "error";
            } else {
                $stmt->bind_param("ii", $id, $id);
                $stmt->execute();
                $stmt->bind_result($usageCount);
                $stmt->fetch();
                $stmt->close();

                if ($usageCount > 0) {
                    $message = "This food item is used in a cart or order. Mark it Unavailable instead.";
                    $messageType = "error";
                } else {
                    $stmt = $conn->prepare(
                        "DELETE FROM food WHERE id = ?"
                    );

                    $stmt->bind_param("i", $id);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        header("Location: food.php?msg=deleted");
                        exit;
                    } else {
                        $message = "Food item not found or could not be deleted.";
                        $messageType = "error";
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Success messages after redirect
if (isset($_GET["msg"])) {
    $messages = [
        "added" => "Food item added successfully!",
        "updated" => "Food item updated successfully!",
        "deleted" => "Food item deleted successfully!",
        "availability" => "Food availability updated successfully!"
    ];

    $message = $messages[$_GET["msg"]] ?? "";
}

// Get food list
$result = $conn->query(
    "SELECT id, name, description, price, category, available
     FROM food ORDER BY id DESC"
);

if (!$result) {
    die("Unable to load food items: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Food - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body {
            background: #f7f7f7;
        }

        header {
            background: #ffb703;
            color: #222;
            padding: 15px 22px;
        }

        header h2 {
            color: #222;
        }

        header nav a {
            color: #222;
            font-weight: bold;
            margin-right: 14px;
            text-decoration: none;
        }

        .food-form {
            max-width: 480px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border: 2px solid #ffb703;
            border-radius: 10px;
        }

        .food-form input,
        .food-form select,
        .food-form textarea {
            width: 100%;
            padding: 12px;
            margin: 7px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .food-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
        }

        .food-card {
            background: white;
            border: 2px solid #ffb703;
            border-radius: 10px;
            padding: 18px;
            overflow-wrap: anywhere;
        }

        .food-card h3 {
            margin-top: 0;
        }

        .action-btn {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .edit-btn,
        .toggle-btn {
            background: #ffb703;
            color: #222;
        }

        .delete-btn {
            background: #c62828;
            color: white;
        }

        .save-btn {
            background: #2e7d32;
            color: white;
        }

        .message {
            max-width: 700px;
            margin: 15px auto;
            padding: 12px;
            border-radius: 5px;
            background: #e8f5e9;
            color: #1b5e20;
        }

        .error-message {
            background: #ffebee;
            color: #b71c1c;
        }

        .edit-form {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
        }

        .edit-form input,
        .edit-form select,
        .edit-form textarea {
            width: 100%;
            padding: 9px;
            margin: 5px 0;
            box-sizing: border-box;
        }

        .status-label {
            font-weight: bold;
        }

        @media (max-width: 600px) {
            header nav a {
                display: inline-block;
                margin: 7px 8px 0 0;
            }

            .food-container {
                grid-template-columns: 1fr;
            }
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

<section class="menu">
    <h2>Add Food Item</h2>

    <?php if ($message !== ""): ?>
        <p class="message <?= $messageType === "error" ? "error-message" : "" ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="food-form">
        <input type="hidden" name="action" value="add">

        <input type="text" name="name" placeholder="Food Name" required>

        <textarea name="description" placeholder="Description" rows="3"></textarea>

        <input type="number" name="price" placeholder="Price in ₹"
               min="0.01" step="0.01" required>

        <select name="category" required>
            <option value="">Select Category</option>
            <option value="Snacks">Snacks</option>
            <option value="Meals">Meals</option>
            <option value="Drinks">Drinks</option>
            <option value="Desserts">Desserts</option>
        </select>

        <button type="submit" class="action-btn edit-btn">
            Add Food
        </button>
    </form>
</section>

<section class="menu">
    <h2>All Food Items</h2>

    <div class="food-container">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($food = $result->fetch_assoc()): ?>

                <div class="food-card">

                    <h3><?= htmlspecialchars($food["name"]) ?></h3>

                    <p>
                        <?= htmlspecialchars($food["description"] ?? "") ?>
                    </p>

                    <p>
                        Category:
                        <?= htmlspecialchars($food["category"] ?? "") ?>
                    </p>

                    <h4>
                        ₹<?= number_format((float)$food["price"], 2) ?>
                    </h4>

                    <p class="status-label">
                        Status:
                        <?= (int)$food["available"] === 1
                            ? "Available"
                            : "Unavailable" ?>
                    </p>

                    <button type="button"
                            class="action-btn edit-btn"
                            onclick="toggleEdit(<?= (int)$food['id'] ?>)">
                        Edit Food
                    </button>

                    <form method="POST">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id"
                               value="<?= (int)$food["id"] ?>">

                        <button type="submit" class="action-btn toggle-btn">
                            <?= (int)$food["available"] === 1
                                ? "Mark Unavailable"
                                : "Mark Available" ?>
                        </button>
                    </form>

                    <form method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this food item?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id"
                               value="<?= (int)$food["id"] ?>">

                        <button type="submit" class="action-btn delete-btn">
                            Delete Food
                        </button>
                    </form>

                    <form method="POST"
                          class="edit-form"
                          id="edit-<?= (int)$food["id"] ?>">

                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id"
                               value="<?= (int)$food["id"] ?>">

                        <input type="text" name="name"
                               value="<?= htmlspecialchars($food["name"]) ?>"
                               placeholder="Food Name" required>

                        <textarea name="description" rows="3"
                                  placeholder="Description"><?= htmlspecialchars($food["description"] ?? "") ?></textarea>

                        <input type="number" name="price"
                               value="<?= htmlspecialchars((string)$food["price"]) ?>"
                               min="0.01" step="0.01" required>

                        <select name="category" required>
                            <?php
                            $categories = ["Snacks", "Meals", "Drinks", "Desserts"];
                            foreach ($categories as $categoryOption):
                            ?>
                                <option
                                    value="<?= htmlspecialchars($categoryOption) ?>"
                                    <?= $food["category"] === $categoryOption
                                        ? "selected"
                                        : "" ?>>
                                    <?= htmlspecialchars($categoryOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="action-btn save-btn">
                            Save Changes
                        </button>
                    </form>

                </div>

            <?php endwhile; ?>

        <?php else: ?>
            <p>No food items found.</p>
        <?php endif; ?>

    </div>
</section>

<script>
function toggleEdit(id) {
    const form = document.getElementById("edit-" + id);

    if (form) {
        form.style.display =
            form.style.display === "block" ? "none" : "block";
    }
}
</script>

</body>
</html>
