
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - School Sutra</title>
    <style>
        body {
            background-color: #e3f0ff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 350px;
            margin: 100px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 32px 24px;
            text-align: center;
        }
        h2 {
            color: #1565c0;
            margin-bottom: 16px;
        }
        .input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #b3c6e7;
            border-radius: 4px;
            font-size: 15px;
        }
        .login-btn {
            background: #1565c0;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 16px;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: #003c8f;
        }
        .error {
            color: #d32f2f;
            margin-bottom: 10px;
        }
        .success {
            color: #388e3c;
            margin-bottom: 10px;
        }
        a {
            color: #1565c0;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php
        // Hardcoded credentials
        $valid_email = "admin@gmail.com";
        $valid_password = "admin";
        $error = "";
        $success = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";

            if ($email === $valid_email && $password === $valid_password) {
                header("Location: pages/dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }
        if ($error) {
            echo "<div class='error'>$error</div>";
        }
        if ($success) {
            echo "<div class='success'>$success</div>";
        }
        ?>
        <form method="post" action="">
            <input class="input" type="email" name="email" placeholder="Email" required><br>
            <input class="input" type="password" name="password" placeholder="Password" required><br>
            <button class="login-btn" type="submit">Login</button>
        </form>
        <br>
        <a href="index.php">Back to Home</a>
    </div>
</body>
</html>