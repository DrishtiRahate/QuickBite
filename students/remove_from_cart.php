
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit;
}

$student_id = (int) $_SESSION["student_id"];
$cart_id = (int) ($_POST["cart_id"] ?? 0);

if ($cart_id > 0) {
    $stmt = $conn->prepare(
        "DELETE FROM cart WHERE id = ? AND student_id = ?"
    );

    $stmt->bind_param("ii", $cart_id, $student_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: cart.php");
exit;
?>
