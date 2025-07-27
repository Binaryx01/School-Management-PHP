<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>School Sutra - Login</title>
    <style>
        /* Reset & base */
        * {
            box-sizing: border-box;
        }
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1565c0, #003c8f);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        /* Container */
        .login-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 320px;
        }
        h1 {
            margin-bottom: 24px;
            font-weight: 700;
            letter-spacing: 1.2px;
        }
        p {
            margin-bottom: 36px;
            font-size: 16px;
            opacity: 0.9;
        }
        /* Button */
        .login-btn {
            background-color: #1e88e5;
            border: none;
            padding: 14px 0;
            width: 100%;
            border-radius: 6px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .login-btn:hover {
            background-color: #0d47a1;
        }
        /* Focus outline */
        .login-btn:focus {
            outline: 3px solid #90caf9;
            outline-offset: 2px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>School Sutra</h1>
        <p>Welcome! Manage your school with ease.</p>
        <form action="login.php" method="get">
            <button class="login-btn" type="submit">Login</button>
        </form>
    </div>

</body>
</html>
