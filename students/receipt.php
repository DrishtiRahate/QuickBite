<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

$isStudent = isset($_SESSION["student_id"]);
$isAdmin = isset($_SESSION["admin_id"]);

if (!$isStudent && !$isAdmin) {
    header("Location: login.php");
    exit;
}

$order_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    exit("Invalid order ID.");
}

// Student can view only their own receipt.
// Admin can view any order receipt.
if ($isAdmin) {
    $stmt = $conn->prepare(
        "SELECT id, student_id, total_amount, status, order_date,
                payment_method, payment_status, pickup_time
         FROM orders
         WHERE id = ?"
    );
    $stmt->bind_param("i", $order_id);
} else {
    $student_id = (int) $_SESSION["student_id"];

    $stmt = $conn->prepare(
        "SELECT id, student_id, total_amount, status, order_date,
                payment_method, payment_status, pickup_time
         FROM orders
         WHERE id = ? AND student_id = ?"
    );
    $stmt->bind_param("ii", $order_id, $student_id);
}

$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

// Fetch food items for this order
$itemStmt = $conn->prepare(
    "SELECT food_name, price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);
$itemStmt->bind_param("i", $order_id);
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Receipt - QuickBite</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }

        .receipt {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ddd;
        }

        h1 {
            color: #e65c00;
            text-align: center;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        h3 {
            margin-top: 25px;
            color: #333;
        }

        .row {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            overflow-wrap: anywhere;
        }

        .row strong {
            display: inline-block;
            min-width: 145px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px 6px;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .items-table th {
            background: #fff0e6;
        }

        .items-table .number {
            text-align: right;
        }

        .total {
            text-align: right;
            font-size: 19px;
            font-weight: bold;
            color: #e65c00;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #e65c00;
        }

        .no-items {
            background: #fff4e8;
            padding: 12px;
            border-radius: 5px;
        }

        .buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 22px;
        }

        .print-btn,
        .back-btn {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: none;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .print-btn {
            background: #e65c00;
        }

        .print-btn:hover {
            background: #c94d00;
        }

        .back-btn {
            background: #333;
        }

        .back-btn:hover {
            background: #555;
        }

        @media (max-width: 480px) {
            .receipt {
                padding: 15px;
            }

            .items-table {
                font-size: 12px;
            }

            .items-table th,
            .items-table td {
                padding: 7px 4px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt {
                box-shadow: none;
                margin: 0 auto;
                max-width: 100%;
            }

            .buttons {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="receipt">

    <h1>QuickBite</h1>
    <h2>Order Receipt</h2>

    <div class="row">
        <strong>Order ID:</strong>
        #<?= (int) $order["id"] ?>
    </div>

    <div class="row">
        <strong>Order Date:</strong>
        <?= htmlspecialchars($order["order_date"] ?? "") ?>
    </div>

    <h3>Food Items Ordered</h3>

    <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Food Item</th>
                    <th class="number">Price</th>
                    <th class="number">Qty</th>
                    <th class="number">Subtotal</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item["food_name"]) ?>
                        </td>

                        <td class="number">
                            ₹<?= number_format((float)$item["price"], 2) ?>
                        </td>

                        <td class="number">
                            <?= (int)$item["quantity"] ?>
                        </td>

                        <td class="number">
                            ₹<?= number_format((float)$item["subtotal"], 2) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>

        <p class="no-items">
            Food item details are not available for this order.
        </p>

    <?php endif; ?>

    <div class="total">
        Total Amount: ₹<?= number_format(
            (float)$order["total_amount"], 2
        ) ?>
    </div>

    <div class="row">
        <strong>Pickup Date &amp; Time:</strong>

        <?php if (!empty($order["pickup_time"])): ?>
            <?= htmlspecialchars(
                date("d M Y, h:i A", strtotime($order["pickup_time"]))
            ) ?>
        <?php else: ?>
            Not selected
        <?php endif; ?>
    </div>

    <div class="row">
        <strong>Order Status:</strong>
        <?= htmlspecialchars($order["status"] ?? "") ?>
    </div>

    <div class="row">
        <strong>Payment Method:</strong>
        <?= htmlspecialchars(
            $order["payment_method"] ?? "Cash on Pickup"
        ) ?>
    </div>

    <div class="row">
        <strong>Payment Status:</strong>
        <?= htmlspecialchars(
            $order["payment_status"] ?? "Pending"
        ) ?>
    </div>

    <div class="buttons">

        <?php if ($isAdmin): ?>

            <a class="back-btn" href="../admin/orders.php">
                ← Back to Manage Orders
            </a>

        <?php else: ?>

            <a class="back-btn" href="myorders.php">
                ← Back to My Orders
            </a>

        <?php endif; ?>

        <button class="print-btn" onclick="window.print()">
            Print / Save as PDF
        </button>

    </div>

</div>

</body>
</html>

<?php
$itemStmt->close();
$stmt->close();
$conn->close();
?>
