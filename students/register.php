
<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" ||
        $password === "" || $confirmPassword === "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
              !str_ends_with($email, "@gmail.com")) {
        $message = "Please enter a valid Gmail address.";
        $messageType = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM students WHERE email = ?"
        );

        if (!$check) {
            $message = "Database query error: " . $conn->error;
            $messageType = "error";
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "This Gmail is already registered.";
                $messageType = "error";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO students (name, email, password)
                     VALUES (?, ?, ?)"
                );

                if (!$stmt) {
                    $message = "Database query error: " . $conn->error;
                    $messageType = "error";
                } else {
                    $stmt->bind_param(
                        "sss",
                        $name,
                        $email,
                        $hashedPassword
                    );

                    if ($stmt->execute()) {
                        header("Location: login.php?registered=1");
                        exit;
                    } else {
                        $message = "Registration failed: " . $stmt->error;
                        $messageType = "error";
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuickBite</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .register-container {
            max-width: 430px;
            margin: 40px auto;
            padding: 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,.10);
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 8px;
        }

        .register-container .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 22px;
        }

        .register-container .form-group {
            margin-bottom: 17px;
        }

        .register-container label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .register-container input {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        .register-container .password-field {
            display: flex;
            gap: 8px;
        }

        .register-container .password-field input {
            min-width: 0;
            flex: 1;
        }

        .register-container .password-field button {
            padding: 0 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            background: #f5f5f5;
            cursor: pointer;
        }

        .register-container .submit-btn {
            width: 100%;
            padding: 13px;
            background: #f47721;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .register-container .message {
            padding: 11px;
            border-radius: 6px;
            margin-bottom: 15px;
            overflow-wrap: anywhere;
        }

        .register-container .error {
            color: #842029;
            background: #f8d7da;
        }

        .register-container .login-link {
            text-align: center;
            margin-top: 18px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Create Account</h2>
    <p class="subtitle">Join QuickBite today!</p>

    <?php if ($message !== ""): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name"
                   placeholder="Enter your full name"
                   value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="email">Gmail Address</label>
            <input type="email" id="email" name="email"
                   placeholder="example@gmail.com"
                   pattern="[a-zA-Z0-9._%+-]+@gmail\.com"
                   value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                   title="Please use a Gmail address"
                   required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password"
                       placeholder="Minimum 8 characters"
                       minlength="8" autocomplete="new-password"
                       required>
                <button type="button"
                        onclick="togglePassword('password', this)">Show</button>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-field">
                <input type="password" id="confirm_password"
                       name="confirm_password"
                       placeholder="Re-enter your password"
                       minlength="8" autocomplete="new-password"
                       required>
                <button type="button"
                        onclick="togglePassword('confirm_password', this)">Show</button>
            </div>
        </div>

        <button type="submit" class="submit-btn">Register</button>

        <p class="login-link">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>
    </form>
</div>

<script>
function togglePassword(id, button) {
    const field = document.getElementById(id);

    if (field.type === "password") {
        field.type = "text";
        button.textContent = "Hide";
    } else {
        field.type = "password";
        button.textContent = "Show";
    }
}
</script>

</body>
</html>
