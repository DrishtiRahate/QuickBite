
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// Admin login check
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    $order_id = (int)($_POST["order_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    $allowed_statuses = ["Pending", "Preparing", "Ready", "Delivered"];

    if ($order_id > 0 && in_array($status, $allowed_statuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("si", $status, $order_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: orders.php");
    exit();
}

// Fetch all orders and student details
$sql = "SELECT
            o.id,
            o.student_id,
            o.total_amount,
            o.status,
            o.order_date,
            o.payment_method,
            o.payment_status,
            o.pickup_time,
            s.name AS student_name,
            s.email AS student_email
        FROM orders o
        LEFT JOIN students s ON o.student_id = s.id
        ORDER BY o.id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Orders query error: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - QuickBite Admin</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f9fc;
            color: #252525;
        }

        .navbar {
            background: #FF8C00;
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #222;
        }

        .navbar a {
            color: #222;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        .navbar a:hover { text-decoration: underline; }

        .container {
            width: 94%;
            max-width: 1100px;
            margin: 25px auto;
        }

        h2 { margin-bottom: 20px; }

        .order-card {
            background: #fff;
            border: 2px solid #FF8C00;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 2px 5px #0000000d;
        }

        .order-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .order-heading h3 { margin: 0; }

        .details p {
            margin: 9px 0;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            background: #fff0dc;
            font-weight: bold;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 15px;
            padding-top: 14px;
            border-top: 1px solid #ddd;
        }

        select, button, .receipt-btn {
            padding: 10px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            background: #FF8C00;
            color: #222;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #E07800; }

        .receipt-btn {
            background: #252525;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .empty {
            background: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .navbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .navbar a {
                margin: 0 12px 0 0;
                display: inline-block;
                padding-top: 8px;
            }

            .container { width: 92%; }
            .order-card { padding: 13px; }

            .order-actions form {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="brand">QuickBite Admin</div>

    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Manage Orders</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <h2>Manage Orders</h2>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($order = $result->fetch_assoc()): ?>

            <?php
            // Fetch food items for the current order
            $itemStmt = $conn->prepare(
                "SELECT food_name, quantity
                 FROM order_items
                 WHERE order_id = ?
                 ORDER BY id ASC"
            );

            $items = [];

            if ($itemStmt) {
                $itemStmt->bind_param("i", $order["id"]);
                $itemStmt->execute();

                $itemResult = $itemStmt->get_result();

                while ($item = $itemResult->fetch_assoc()) {
                    $items[] = $item;
                }

                $itemStmt->close();
            }
            ?>

            <div class="order-card">

                <div class="order-heading">
                    <h3>Order #<?= (int)$order["id"] ?></h3>

                    <span class="status">
                        <?= htmlspecialchars($order["status"] ?? "Pending") ?>
                    </span>
                </div>

                <div class="details">

                    <p>
                        <strong>Order Date:</strong>
                        <?= htmlspecialchars($order["order_date"] ?? "") ?>
                    </p>

                    <p>
                        <strong>Student Name:</strong>
                        <?= htmlspecialchars($order["student_name"] ?? "Student not found") ?>
                    </p>

                    <p>
                        <strong>Student Email:</strong>
                        <?= htmlspecialchars($order["student_email"] ?? "N/A") ?>
                    </p>

                    <p>
                        <strong>Food Ordered:</strong><br>

                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?= htmlspecialchars($item["food_name"]) ?>
                                × <?= (int)$item["quantity"] ?><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Food item details not found.
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Total Amount:</strong>
                        ₹<?= number_format((float)$order["total_amount"], 2) ?>
                    </p>

                    <p>
                        <strong>Pickup Time:</strong>
                        <?php
                        echo !empty($order["pickup_time"])
                            ? htmlspecialchars(date("d-m-Y h:i A", strtotime($order["pickup_time"])))
                            : "Not selected";
                        ?>
                    </p>

                    <p>
                        <strong>Payment Method:</strong>
                        <?= htmlspecialchars($order["payment_method"] ?? "N/A") ?>
                    </p>

                    <p>
                        <strong>Payment Status:</strong>
                        <?= htmlspecialchars($order["payment_status"] ?? "N/A") ?>
                    </p>

                </div>

                <div class="order-actions">

                    <form method="POST" action="orders.php">
                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= (int)$order["id"] ?>"
                        >

                        <select name="status" required>
                            <?php
                            $statuses = ["Pending", "Preparing", "Ready", "Delivered"];

                            foreach ($statuses as $statusOption):
                            ?>
                                <option
                                    value="<?= htmlspecialchars($statusOption) ?>"
                                    <?= (($order["status"] ?? "Pending") === $statusOption) ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" name="update_status">
                            Update Status
                        </button>
                    </form>

                    <a
                        class="receipt-btn"
                        href="../students/receipt.php?id=<?= (int)$order["id"] ?>"
                    >
                        View Receipt
                    </a>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No orders found.</h3>
            <p>Student orders will appear here after checkout.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$result->free();
$conn->close();
?>
