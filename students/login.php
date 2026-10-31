
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, name, password FROM students WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            session_regenerate_id(true);

            $_SESSION["student_id"] = $id;
            $_SESSION["student_name"] = $name;

            $stmt->close();

            header("Location: dashboard.php");
            exit;
        }
    }

    $message = "Invalid email or password.";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<header>
    <h2>QuickBite</h2>
    <nav>
        <a href="../index.php">Home</a>
    </nav>
</header>

<section class="menu">
    <h2>Student Login</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" style="max-width:400px;margin:auto;">

        <p>
            <input
                type="email"
                name="email"
                placeholder="Gmail Address"
                required
                style="width:100%;padding:12px;margin:8px 0;"
            >
        </p>

        <p class="password-field">
            <input
                type="password"
                id="login_password"
                name="password"
                placeholder="Password"
                required
            >

            <button
                type="button"
                onclick="togglePassword('login_password', this)"
                aria-label="Show password"
            >Show</button>
        </p>

        <button type="submit" class="btn" style="border:0;cursor:pointer;">
            Login
        </button>

    </form>

    <p style="margin-top:20px;">
        New student? <a href="register.php">Register here</a>
    </p>
</section>

<script>
function togglePassword(id, button) {
    const field = document.getElementById(id);

    if (field.type === "password") {
        field.type = "text";
        button.textContent = "Hide";
        button.setAttribute("aria-label", "Hide password");
    } else {
        field.type = "password";
        button.textContent = "Show";
        button.setAttribute("aria-label", "Show password");
    }
}
</script>

</body>
</html>
