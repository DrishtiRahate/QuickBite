
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$message = "";

// Calculate cart total and check available food
$sql = "SELECT SUM(food.price * cart.quantity) AS total
        FROM cart
        JOIN food ON cart.food_id = food.id
        WHERE cart.student_id = ? AND food.available = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total = (float) ($row["total"] ?? 0);
$stmt->close();

// Available pickup time slots
$pickup_slots = [
    "10:00", "10:30",
    "11:00", "11:30",
    "12:00", "12:30",
    "13:00", "13:30",
    "14:00", "14:30",
    "15:00", "15:30",
    "16:00", "16:30"
];

// Place order
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["place_order"])
) {
    $payment_method = $_POST["payment_method"] ?? "";
    $pickup_date = $_POST["pickup_date"] ?? "";
    $pickup_slot = $_POST["pickup_slot"] ?? "";

    if ($total <= 0) {
        $message = "Your cart is empty. Please add available food first.";
    } elseif (!in_array(
        $payment_method,
        ["Cash on Pickup", "Online Payment (Demo)"],
        true
    )) {
        $message = "Please select a valid payment method.";
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickup_date)
        || !checkdate(
            (int) substr($pickup_date, 5, 2),
            (int) substr($pickup_date, 8, 2),
            (int) substr($pickup_date, 0, 4)
        )
        || $pickup_date < date("Y-m-d")
    ) {
        $message = "Please select a valid pickup date.";
    } elseif (!in_array($pickup_slot, $pickup_slots, true)) {
        $message = "Please select a valid pickup time.";
    } else {
        $pickup_datetime = $pickup_date . " " . $pickup_slot . ":00";

        if (strtotime($pickup_datetime) <= time()) {
            $message = "Please select a future pickup time.";
        } else {
            $payment_status = ($payment_method === "Cash on Pickup")
                ? "Pending"
                : "Demo - Not Paid";

            $conn->begin_transaction();

            try {
                // Create order
                $orderStmt = $conn->prepare(
                    "INSERT INTO orders
                    (student_id, total_amount, status,
                     payment_method, payment_status, pickup_time)
                    VALUES (?, ?, 'Pending', ?, ?, ?)"
                );

                $orderStmt->bind_param(
                    "idsss",
                    $student_id,
                    $total,
                    $payment_method,
                    $payment_status,
                    $pickup_datetime
                );

                $orderStmt->execute();

                $order_id = (int) $conn->insert_id;
                $orderStmt->close();

                // Save order items
                $itemStmt = $conn->prepare(
                    "INSERT INTO order_items
                    (order_id, food_id, food_name, price,
                     quantity, subtotal)
                    SELECT ?, food.id, food.name, food.price,
                           cart.quantity, (food.price * cart.quantity)
                    FROM cart
                    JOIN food ON cart.food_id = food.id
                    WHERE cart.student_id = ?
                      AND food.available = 1"
                );

                $itemStmt->bind_param("ii", $order_id, $student_id);
                $itemStmt->execute();

                if ($itemStmt->affected_rows <= 0) {
                    throw new Exception("No available items found.");
                }

                $itemStmt->close();

                // Clear cart after saving order
                $deleteStmt = $conn->prepare(
                    "DELETE FROM cart WHERE student_id = ?"
                );

                $deleteStmt->bind_param("i", $student_id);
                $deleteStmt->execute();
                $deleteStmt->close();

                $conn->commit();

                header("Location: myorders.php");
                exit;

            } catch (Throwable $e) {
                $conn->rollback();
                error_log("QuickBite checkout error: " . $e->getMessage());
                $message = "Order could not be placed. Please check the database setup and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">
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
    <h2>Checkout</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
        <br>
        <a class="btn" href="cart.php">Back to Cart</a>

    <?php elseif ($total > 0): ?>

        <div class="food-card">
            <h3>Order Summary</h3>

            <p>
                Student:
                <?= htmlspecialchars($_SESSION["student_name"] ?? "Student") ?>
            </p>

            <h2>Total Amount: ₹<?= number_format($total, 2) ?></h2>
            <p>Order Status: Pending</p>

            <form method="POST">
                <h3>Select Pickup Date &amp; Time</h3>

                <label for="pickup_date">Pickup Date:</label>
                <br>
                <input
                    type="date"
                    id="pickup_date"
                    name="pickup_date"
                    min="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($_POST['pickup_date'] ?? '') ?>"
                    required
                >

                <br><br>

                <label for="pickup_slot">Pickup Time:</label>
                <br>
                <select id="pickup_slot" name="pickup_slot" required>
                    <option value="">-- Select Pickup Time --</option>

                    <?php foreach ($pickup_slots as $slot): ?>
                        <option
                            value="<?= htmlspecialchars($slot) ?>"
                            <?= (($_POST['pickup_slot'] ?? '') === $slot)
                                ? 'selected' : '' ?>
                        >
                            <?= date("g:i A", strtotime($slot)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p>
                    <small>
                        Choose a future pickup time during the available slots.
                    </small>
                </p>

                <hr>

                <h3>Select Payment Method</h3>

                <label>
                    <input
                        type="radio"
                        name="payment_method"
                        value="Cash on Pickup"
                        <?= (($_POST['payment_method'] ?? 'Cash on Pickup')
                            === 'Cash on Pickup') ? 'checked' : '' ?>
                        required
                    >
                    Cash on Pickup
                </label>

                <br><br>

                <label>
                    <input
                        type="radio"
                        name="payment_method"
                        value="Online Payment (Demo)"
                        <?= (($_POST['payment_method'] ?? '')
                            === 'Online Payment (Demo)') ? 'checked' : '' ?>
                    >
                    Online Payment (Demo)
                </label>

                <p>
                    <small>
                        Online payment is for demonstration only.
                        No real payment will be processed.
                    </small>
                </p>

                <button
                    type="submit"
                    name="place_order"
                    value="1"
                    class="btn"
                >
                    Confirm Order
                </button>
            </form>
        </div>

        <br>
        <a class="btn" href="cart.php">Back to Cart</a>

    <?php else: ?>
        <p>Your cart is empty or the selected food is unavailable.</p>
        <br>
        <a class="btn" href="menu.php">Explore Menu</a>
    <?php endif; ?>
</section>

</body>
</html>
