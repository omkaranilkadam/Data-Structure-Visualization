<?php
session_start();

// Database configuration
$host    = 'localhost';
$dbname  = 'user_auth';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                // Registration logic
                $username         = trim($_POST['username']);
                $email            = trim($_POST['email']);
                $password         = trim($_POST['password']);
                $confirm_password = trim($_POST['confirm_password']);

                if (empty($username) || empty($email) || empty($password)) {
                    $_SESSION['error'] = 'All fields are required';
                } elseif ($password !== $confirm_password) {
                    $_SESSION['error'] = 'Passwords do not match';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = 'Invalid email format';
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
                    $stmt->execute([$email, $username]);
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['error'] = 'Username or Email already exists';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                        $stmt->execute([$username, $email, $hashed_password]);
                        $_SESSION['success'] = 'Registration successful! Please login';
                        header('Location: ?page=login');
                        exit;
                    }
                }
                header('Location: ?page=register');
                exit;

            case 'login':
                // Login logic
                $email    = trim($_POST['email']);
                $password = trim($_POST['password']);

                if (empty($email) || empty($password)) {
                    $_SESSION['error'] = 'All fields are required';
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
                    $stmt->execute([$email, $email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        $_SESSION['user_id']  = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        // Redirect to your custom HTML page after successful login
                        header('Location: my_dashboard.html');
                        exit;
                    } else {
                        $_SESSION['error'] = 'Invalid credentials';
                    }
                }
                header('Location: ?page=login');
                exit;
        }
    }
}

// Handle logout
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ?page=login');
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Optional: if you want to restrict dashboard access via the PHP file
if ($page === 'dashboard' && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auth System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 300px;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        button:hover {
            background-color: #166fe5;
        }
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .error {
            background-color: #ffe3e6;
            color: #dc3545;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .logout {
            color: #1877f2;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($page === 'register'): ?>
            <h2>Register</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="?page=login">Login here</a></p>

        <?php elseif ($page === 'login'): ?>
            <h2>Login</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']) ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="text" name="email" placeholder="Email or Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="?page=register">Register here</a></p>

        <?php elseif ($page === 'dashboard'): ?>
            <!-- This section is optional if you want to use the PHP dashboard -->
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
            <p>This is your dashboard.</p>
            <a href="?page=logout" class="logout">Logout</a>
        <?php endif; ?>
    </div>
</body>
</html>
