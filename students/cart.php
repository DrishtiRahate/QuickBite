<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$message = "";

// Update cart quantities
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_cart"])
) {
    $quantities = $_POST["quantity"] ?? [];

    if (is_array($quantities)) {
        $update = $conn->prepare(
            "UPDATE cart
             SET quantity = ?
             WHERE id = ? AND student_id = ?"
        );

        foreach ($quantities as $cartId => $quantity) {
            $cartId = filter_var($cartId, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

            if (
                $cartId !== false && $cartId > 0 &&
                $quantity !== false &&
                $quantity >= 1 && $quantity <= 20
            ) {
                $update->bind_param(
                    "iii",
                    $quantity,
                    $cartId,
                    $student_id
                );

                $update->execute();
            }
        }

        $update->close();
    }

    header("Location: cart.php?updated=1");
    exit;
}

// Success message after update
if (isset($_GET["updated"])) {
    $message = "Cart updated successfully!";
}

// Get cart items
$sql = "SELECT
            cart.id AS cart_id,
            food.name,
            food.price,
            cart.quantity,
            (food.price * cart.quantity) AS subtotal
        FROM cart
        INNER JOIN food ON cart.food_id = food.id
        WHERE cart.student_id = ?
        ORDER BY cart.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$cartItems = [];

while ($item = $result->fetch_assoc()) {
    $cartItems[] = $item;
    $total += (float) $item["subtotal"];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart - QuickBite</title>

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

        .food-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
        }

        .food-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .09);
        }

        .food-card h3 {
            margin-top: 0;
        }

        .food-card p {
            color: #555;
        }

        .food-card h4 {
            color: #e96514;
            font-size: 19px;
        }

        .quantity-input {
            width: 80px;
            padding: 9px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .cart-total {
            text-align: center;
            margin: 28px 0;
            color: #e96514;
        }

        .cart-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border: none;
            border-radius: 7px;
            background: #f47721;
            color: white;
            text-decoration: none;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            box-sizing: border-box;
        }

        .btn:hover {
            background: #d95f0c;
        }

        .remove-btn {
            background: #dc3545;
            margin-top: 12px;
        }

        .remove-btn:hover {
            background: #b02a37;
        }

        .message {
            text-align: center;
            color: #218838;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .empty-cart {
            text-align: center;
            padding: 30px 0;
        }

        @media (max-width: 500px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .cart-actions {
                flex-direction: column;
            }

            .cart-actions .btn {
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

    <h2>My Cart</h2>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if (count($cartItems) > 0): ?>

        <form method="POST" action="cart.php">

            <div class="food-container">

                <?php foreach ($cartItems as $item): ?>

                    <div class="food-card">

                        <h3>
                            <?= htmlspecialchars(
                                $item["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h3>

                        <p>
                            Price:
                            ₹<?= number_format(
                                (float) $item["price"],
                                2
                            ) ?>
                        </p>

                        <label>
                            Quantity:
                            <br>

                            <input
                                class="quantity-input"
                                type="number"
                                name="quantity[<?= (int) $item["cart_id"] ?>]"
                                value="<?= (int) $item["quantity"] ?>"
                                min="1"
                                max="20"
                                required
                            >
                        </label>

                        <h4>
                            Subtotal:
                            ₹<?= number_format(
                                (float) $item["subtotal"],
                                2
                            ) ?>
                        </h4>

                        <button
                            type="submit"
                            class="btn remove-btn"
                            formaction="remove_from_cart.php"
                            formmethod="POST"
                            name="cart_id"
                            value="<?= (int) $item["cart_id"] ?>"
                            formnovalidate
                        >
                            Remove
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

            <h2 class="cart-total">
                Total Amount: ₹<?= number_format($total, 2) ?>
            </h2>

            <div class="cart-actions">

                <button
                    type="submit"
                    name="update_cart"
                    value="1"
                    class="btn"
                >
                    Update Cart
                </button>

                <a href="menu.php" class="btn">
                    Continue Shopping
                </a>

                <a href="checkout.php" class="btn">
                    Place Order
                </a>

            </div>

        </form>

    <?php else: ?>

        <div class="empty-cart">
            <h3>Your cart is empty!</h3>

            <p>Add your favourite food to continue.</p>

            <a href="menu.php" class="btn">
                Explore Menu
            </a>
        </div>

    <?php endif; ?>

</section>

</body>
</html>
