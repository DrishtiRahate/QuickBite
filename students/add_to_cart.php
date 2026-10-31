
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// Check student login
if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

// Check form submission
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: menu.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$food_id = (int) ($_POST["food_id"] ?? 0);
$quantity = (int) ($_POST["quantity"] ?? 1);

// Validate input
if ($food_id <= 0 || $quantity < 1 || $quantity > 20) {
    die("Invalid food or quantity.");
}

// Check food availability
$stmt = $conn->prepare(
    "SELECT id FROM food WHERE id = ? AND available = 1"
);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $food_id);

if (!$stmt->execute()) {
    die("Food check failed: " . $stmt->error);
}

$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    die("Food item is not available.");
}

$stmt->close();

// Check whether item already exists in cart
$stmt = $conn->prepare(
    "SELECT id, quantity
     FROM cart
     WHERE student_id = ? AND food_id = ?"
);

if (!$stmt) {
    die("Cart check failed: " . $conn->error);
}

$stmt->bind_param("ii", $student_id, $food_id);

if (!$stmt->execute()) {
    die("Cart check failed: " . $stmt->error);
}

$stmt->bind_result($cart_id, $old_quantity);
$exists = $stmt->fetch();
$stmt->close();

if ($exists) {
    // Update existing item quantity
    $new_quantity = min($old_quantity + $quantity, 20);

    $stmt = $conn->prepare(
        "UPDATE cart
         SET quantity = ?
         WHERE id = ? AND student_id = ?"
    );

    if (!$stmt) {
        die("Update prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iii",
        $new_quantity,
        $cart_id,
        $student_id
    );

    if (!$stmt->execute()) {
        die("Cart update failed: " . $stmt->error);
    }

    $stmt->close();

} else {
    // Add new item to cart
    $stmt = $conn->prepare(
        "INSERT INTO cart (student_id, food_id, quantity)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        die("Insert prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iii",
        $student_id,
        $food_id,
        $quantity
    );

    if (!$stmt->execute()) {
        die("Insert failed: " . $stmt->error);
    }

    $stmt->close();
}

// Go to cart after successful operation
header("Location: cart.php");
exit;
?>
