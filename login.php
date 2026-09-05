<?php
require_once 'mydb.php';
session_start();

if (isset($_SESSION['seller_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in both fields.';
    } else {
        $auth = fb_login($email, $password);

        if (isset($auth['error'])) {
            $msg = $auth['error']['message'] ?? '';
            $error = match (true) {
                str_contains($msg, 'EMAIL_NOT_FOUND') => 'No account found with this email.',
                str_contains($msg, 'INVALID_PASSWORD') => 'Incorrect password. Please try again.',
                str_contains($msg, 'INVALID_LOGIN_CREDENTIALS') => 'Invalid email or password.',
                str_contains($msg, 'TOO_MANY_ATTEMPTS') => 'Too many failed attempts. Please try again later.',
                default => 'Login failed. Please try again.',
            };
        } else {
            $uid = $auth['localId'];
            $profile = fs_get('sellers', $uid);

            if (!$profile) {
                $error = 'Account not found. Please sign up first.';
            } else {
                $_SESSION['seller_id'] = $uid;
                $_SESSION['email'] = $email;
                $_SESSION['display_name'] = $profile['display_name'] ?? explode('@', $email)[0];

                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="color-scheme" content="light">
    <title>Log In &mdash; SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
    <link rel="apple-touch-icon" href="static/seekerafric_64.png">
</head>

<body>

    <div class="auth-wrap">
        <div class="auth-box">
            <div class="auth-logo">Seeker<span>Afric</span></div>
            <div class="auth-tagline">Welcome</div>

            <div class="auth-title">&#128272; Log In</div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </form>

            <div class="auth-links">
                Don&apos;t have an account? <a href="signup.php">Sign up free</a>
                &nbsp;|&nbsp; <a href="index.php">Browse listings</a>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> SeekerAfric &nbsp;|&nbsp; <a href="privacy.php">Privacy Policy</a>
    </footer>

</body>

</html>
