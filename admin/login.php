<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// If admin is already logged in, open dashboard
if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

// Handle login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Check admin credentials in database
    $stmt = $conn->prepare(
        "SELECT id, username, password FROM admin WHERE username = ? LIMIT 1"
    );

    if (!$stmt) {
        die("Login query error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        $storedPassword = $admin["password"];

        // Supports hashed passwords and existing plain-text passwords
        $passwordMatches = password_verify($password, $storedPassword)
            || hash_equals((string)$storedPassword, $password);

        if ($passwordMatches) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = (int)$admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            $stmt->close();

            header("Location: dashboard.php");
            exit();
        }
    }

    $stmt->close();
    $error = "Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - QuickBite</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f9fc;
            color: #252525;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            border-top: 6px solid #FF8C00;
            box-shadow: 0 5px 22px #00000012;
        }

        h1 {
            text-align: center;
            margin-bottom: 8px;
            color: #252525;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 14px 0 6px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d6d6d6;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #FFD199;
            border-color: #FF8C00;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #FF8C00;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #E07800;
        }

        .error {
            background: #ffe8e8;
            color: #a00000;
            padding: 10px;
            margin-bottom: 14px;
            border-radius: 6px;
            text-align: center;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #b86200;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="login-box">
    <h1>QuickBite Admin</h1>
    <p class="subtitle">Login to manage your canteen</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Login</button>
    </form>

    <a class="back-link" href="../index.php">Back to Student Website</a>
</div>

</body>
</html>
