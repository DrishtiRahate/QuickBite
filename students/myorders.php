
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION["student_id"];

$stmt = $conn->prepare(
    "SELECT id, total_amount, status, order_date, pickup_time
     FROM orders
     WHERE student_id = ?
     ORDER BY id DESC"
);

$stmt->bind_param("i", $student_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - QuickBite</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    color: #222;
}

.navbar {
    background: #ffb703;
    padding: 15px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.navbar a {
    color: #222;
    text-decoration: none;
    margin-left: 15px;
    font-size: 14px;
    font-weight: 500;
}

.navbar a:hover {
    color: #fff;
}

.navbar .brand {
    font-size: 21px;
    font-weight: bold;
    color: #222;
    margin-left: 0;
}

.container {
    max-width: 900px;
    margin: 25px auto;
    padding: 0 15px;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.page-heading h2 {
    margin: 0;
    font-size: 24px;
    color: #222;
}

.count {
    background: white;
    border: 1px solid #ffb703;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-row {
    background: white;
    border: 1px solid #ffb703;
    border-radius: 10px;
    padding: 16px 18px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
}

.order-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ffb703;
}

.order-id {
    font-size: 17px;
    font-weight: bold;
}

.order-date {
    color: #777;
    font-size: 12px;
    margin-top: 4px;
}

.status {
    display: inline-block;
    border-radius: 20px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: bold;
    background: #eee;
}

.status-pending {
    color: #875900;
    background: #fff0c2;
}

.status-preparing {
    color: #0759a5;
    background: #dceeff;
}

.status-ready {
    color: #18713b;
    background: #d9f7e4;
}

.status-delivered {
    color: #555;
    background: #e8e8e8;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 20px;
    padding: 12px 0;
}

.detail-label {
    color: #777;
    font-size: 12px;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 14px;
    overflow-wrap: anywhere;
}

.order-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    border-top: 1px solid #ffb703;
    padding-top: 12px;
}

.receipt-btn {
    background: #ffb703;
    color: #222;
    text-decoration: none;
    padding: 9px 14px;
    border-radius: 6px;
    display: inline-block;
    font-size: 13px;
    font-weight: bold;
    transition: 0.2s;
}

.receipt-btn:hover {
    background: #f4a900;
}

.empty {
    background: white;
    border: 1px solid #ffb703;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    color: #666;
}

.menu-btn {
    display: inline-block;
    margin-top: 12px;
    background: #ffb703;
    color: #222;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

@media (max-width: 600px) {
    .order-details {
        grid-template-columns: 1fr;
    }

    .order-bottom {
        align-items: flex-start;
    }

    .navbar a {
        margin-left: 8px;
    }

    .page-heading h2 {
        font-size: 21px;
    }
}
</style>
</head>

<body>

<div class="navbar">
    <a class="brand" href="../index.php">QuickBite</a>

    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../index.php">Menu</a>
        <a href="cart.php">Cart</a>
        <a href="myorders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

    <div class="page-heading">
        <h2>My Orders</h2>

        <span class="count">
            Total Orders: <?= $orders->num_rows ?>
        </span>
    </div>

    <div class="order-list">

        <?php if ($orders->num_rows > 0): ?>

            <?php while ($order = $orders->fetch_assoc()): ?>

                <?php
                $order_id = (int) $order["id"];

                $itemStmt = $conn->prepare(
                    "SELECT food_name, quantity
                     FROM order_items
                     WHERE order_id = ?"
                );

                $itemStmt->bind_param("i", $order_id);
                $itemStmt->execute();

                $items = $itemStmt->get_result();
                $food_details = [];

                while ($item = $items->fetch_assoc()) {
                    $food_details[] =
                        htmlspecialchars($item["food_name"]) .
                        " × " .
                        (int) $item["quantity"];
                }

                $itemStmt->close();

                $status_class = strtolower(
                    preg_replace('/[^a-zA-Z0-9]+/', '-', $order["status"])
                );

                $pickup = !empty($order["pickup_time"])
                    ? date("d M Y, h:i A", strtotime($order["pickup_time"]))
                    : "Not selected";
                ?>

                <div class="order-row">

                    <div class="order-top">
                        <div>
                            <div class="order-id">
                                Order #<?= $order_id ?>
                            </div>

                            <div class="order-date">
                                <?= date(
                                    "d M Y, h:i A",
                                    strtotime($order["order_date"])
                                ) ?>
                            </div>
                        </div>

                        <span class="status status-<?= htmlspecialchars($status_class) ?>">
                            <?= htmlspecialchars($order["status"]) ?>
                        </span>
                    </div>

                    <div class="order-details">

                        <div>
                            <div class="detail-label">Food Ordered</div>

                            <div class="detail-value">
                                <?= !empty($food_details)
                                    ? implode(", ", $food_details)
                                    : "Food details unavailable" ?>
                            </div>
                        </div>

                        <div>
                            <div class="detail-label">Total Amount</div>

                            <div class="detail-value">
                                <strong>
                                    ₹<?= number_format(
                                        (float) $order["total_amount"],
                                        2
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                        <div>
                            <div class="detail-label">Pickup Date &amp; Time</div>

                            <div class="detail-value">
                                <?= htmlspecialchars($pickup) ?>
                            </div>
                        </div>

                    </div>

                    <div class="order-bottom">
                        <span class="detail-value">
                            Order #<?= $order_id ?>
                        </span>

                        <a
                            class="receipt-btn"
                            href="receipt.php?id=<?= $order_id ?>"
                        >
                            View Receipt
                        </a>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <h3>No orders yet!</h3>
                <p>Your placed orders will appear here.</p>

                <a class="menu-btn" href="../index.php">
                    Order Food
                </a>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
?>
