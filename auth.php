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
                $confirm_password = trim($_POST['confirm_password'] ?? '');

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
                        // Redirect to your custom dashboard HTML page after successful login
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Login Page</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Dongle&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family:"Poppins",sans-serif;
    }

    body{
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-size: cover;
        background-position: center;
        background-image: url('background.svg'); /* add the svg background */
    background-size: cover;   /* scales the background image to cover the entire area */
    background-position: center;
    background-repeat: no-repeat;
    }

    .loginpage{
        position: relative;
        width: 400px;
        height: 420px;
        background: transparent;
        border: 2px solid rgba(255,255,255,0.5);
        border-radius: 20px;
        backdrop-filter: blur(40px);
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        transform: scale(1);
        transition: transform .3s ease, height .2s ease;
    }

    .loginpage .form-box-login {
        width: 100%;
        padding: 40px;
        transition: transform .3s ease;
        transform: translateX(0);
    }

    .loginpage.active {
        height: 500px;
    }

    .loginpage.active .form-box-login{
        transition: none;
        transform: translateX(-400px);
    }

    .loginpage .icon-close{
        position: absolute;
        top: 0;
        right: 0;
        width: 45px;
        height: 45px;
        background: #162938;
        font-size: 2em;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        border-bottom-left-radius: 20px;
        cursor: pointer;
        z-index: 1;
    }

    .form-box-login h2{
        font-size: 2em;
        color:rgb(255, 255, 255);
        text-align: center;
    }

    .form-box-register h2{
        font-size: 2em;
        color:rgb(255, 255, 255);
        text-align: center;
    }

    .loginpage .form-box-register{
        width: 100%;
        padding: 40px;
        position: absolute;
        transition: none;
        transform: translateX(400px);
    }

    .loginpage.active .form-box-register{
        transition: transform .3s ease;
        transform: translateX(0);
    }

    .input-box{
        position: relative;
        width: 100%;
        height: 50px;
        border-bottom: 2px solid #162938;
        margin: 30px 0;
    }

    .input-box label{
        position: absolute;
        top: 50%;
        left: 5px;
        transform: translateY(-50%);
        font-size: 1em;
        color: #ffffff;
        font-weight: 500;
        pointer-events: none;
        transition: 0.5s;
    }

    .input-box input:focus ~ label,
    .input-box input:valid ~ label{
        top: 5px;
        color: #ffffff;
    }

    .input-box input{
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        font-size: 1em;
        color: #ffffff;
        padding: 0 35px 0 5px;
        font-weight: 600;
    }

    .input-box .icon{
        position: absolute;
        right: 8px;
        font-size: 1.2em;
        
        line-height: 57px;
        color: #ffffff;
    }

    .remember-forgot{
        font-size: 0.9em;
        color: #ffffff;
        font-weight: 500;
        margin: -15px 0 15px;
        display: flex;
        justify-content: space-between;
    }

    .remember-forgot label input{
        accent-color: #ffffff;
        margin-right: 3px;
    }

    .remember-forgot a{
        color: #ffffff;
        text-decoration: none;
    }

    .remember-forgot a:hover{
        text-decoration: underline;
    }

    .btn{
        width: 100%;
        height: 45px;
        background:rgb(25, 76, 163);
        border: none;
        outline: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1em;
        color: white;
        font-weight: 500;
    }

    .login-register{
        font-size: 0.9em;
        color:rgb(255, 255, 255);
        text-align: center;
        font-weight: 500;
        margin: 25px 0 10px;
    }

    .login-register p a{
        text-decoration: none;
        color:rgb(248, 10, 10);
        font-weight: 600;
    }

    .login-register p a:hover{
        text-decoration: underline;
    }

    /* Error message styling for login/register forms */
    .error-msg {
        font-size: 0.8em;
        color:rgb(249, 6, 6);
        text-align: center;
        margin-top: 10px;
    }
  </style>
</head>
<body>

  <div class="loginpage">
      <div class="form-box-login">
          <span class="icon-close"><ion-icon name="close"></ion-icon></span>
          <h2>Login</h2>
          <form method="POST" action="">
              <input type="hidden" name="action" value="login">
              <div class="input-box">
                  <span class="icon"><ion-icon name="mail"></ion-icon></span>
                  <input type="email" name="email" required>
                  <label>Email</label>
              </div>
              <div class="input-box">
                  <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                  <input type="password" name="password" required>
                  <label>Password</label>
              </div>
              <div class="remember-forgot">
                  <label><input type="checkbox">Remember me</label>
                  <a href="#">Forgot Password?</a>
              </div>
              <button type="submit" class="btn">Login</button>
              <!-- Display error message (if any) inside the login form -->
              <?php if(isset($_SESSION['error']) && $_SESSION['error'] === 'Invalid credentials'): ?>
                  <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
              <?php endif; ?>
              <div class="login-register">
                  <p>Don't have any account? <a href="#" class="register-link">Register</a></p>
              </div>
          </form>
      </div>

      <div class="form-box-register">
          <span class="icon-close"><ion-icon name="close"></ion-icon></span>
          <h2>Registration</h2>
          <form method="POST" action="">
              <input type="hidden" name="action" value="register">
              <div class="input-box">
                  <span class="icon"><ion-icon name="person-circle"></ion-icon></span>
                  <input type="text" name="username" required>
                  <label>Username</label>
              </div>
              <div class="input-box">
                  <span class="icon"><ion-icon name="mail"></ion-icon></span>
                  <input type="email" name="email" required>
                  <label>Email</label>
              </div>
              <div class="input-box">
                  <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                  <input type="password" name="password" required>
                  <label>Password</label>
              </div>
              <div class="input-box">
                  <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                  <input type="password" name="confirm_password" required>
                  <label>Confirm Password</label>
              </div>
              <div class="remember-forgot">
                  <label><input type="checkbox">I agree to the terms & conditions</label>
              </div>
              <button type="submit" class="btn">Register</button>
              <!-- Display error message (if any) inside the register form -->
              <?php if(isset($_SESSION['error']) && $_SESSION['error'] !== 'Invalid credentials'): ?>
                  <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
              <?php endif; ?>
              <div class="login-register">
                  <p>Already have an account? <a href="#" class="login-link">Login</a></p>
              </div>
          </form>
      </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
  const registerLink = document.querySelector('.register-link');
  const loginLink = document.querySelector('.login-link');
  const loginPage = document.querySelector('.loginpage');

  // When the register link is clicked, show the register form
  registerLink.addEventListener('click', function (e) {
    e.preventDefault();
    loginPage.classList.add('active');
  });

  // When the login link is clicked, show the login form
  loginLink.addEventListener('click', function (e) {
    e.preventDefault();
    loginPage.classList.remove('active');
  });
});
</script>
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
