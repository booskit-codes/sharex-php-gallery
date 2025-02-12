<?php
session_start();
require_once '../config.php';

header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self';");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

$validUsername = USERNAME;
$validPassword = PASSWORD;

$loginAttemptsThreshold = 5; // Number of failed attempts allowed
$lockoutTime = 300; // Lockout time in seconds (5 minutes)

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_until'])) {
    $_SESSION['lockout_until'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (time() < $_SESSION['lockout_until']) {
        $error = 'Too many failed login attempts. Please try again later.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === $validUsername && $password === $validPassword) {
            $_SESSION['loggedin'] = true;
            $_SESSION['login_attempts'] = 0; // Reset attempts on successful login
            $_SESSION['lockout_until'] = 0;
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $loginAttemptsThreshold) {
                $_SESSION['lockout_until'] = time() + $lockoutTime;
                $error = 'Invalid username or password. Too many failed attempts. Account locked for 5 minutes.';
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="resources/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="resources/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="resources/favicon-16x16.png">
    <link rel="manifest" href="resources/site.webmanifest">
    <link rel="stylesheet" href="resources/login.css">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <h1>Welcome Back</h1>
        <?php if (!empty($error)): ?>
            <div class="error"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="form-footer">
            <p><a href="https://github.com/booskit-codes/sharex-php-gallery">Github</a></p>
        </div>
    </div>
</body>
</html>

