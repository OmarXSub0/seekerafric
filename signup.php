<?php
require_once 'firebase.php';
session_start();

if (isset($_SESSION['seller_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'email_signup') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $auth = fb_signup($email, $password);

        if (isset($auth['error'])) {
            $msg = $auth['error']['message'] ?? '';
            $error = match (true) {
                str_contains($msg, 'EMAIL_EXISTS') => 'This email is already registered. Please log in.',
                str_contains($msg, 'WEAK_PASSWORD') => 'Password is too weak. Please use at least 6 characters.',
                str_contains($msg, 'INVALID_EMAIL') => 'Please enter a valid email address.',
                default => 'Signup failed. Please try again.',
            };
        } else {
            $uid = $auth['localId'];
            fs_set('sellers', $uid, [
                'uid' => $uid,
                'email' => $email,
                'created_at' => date('c'),
            ]);

            $_SESSION['seller_id'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['display_name'] = explode('@', $email)[0];

            header('Location: dashboard.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'google_signup') {
    $id_token = $_POST['id_token'] ?? '';
    if (!$id_token) {
        $error = 'Google sign-in failed. No token received.';
    } else {
        $request_uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithIdp?key=' . FB_API_KEY;
        $payload = [
            'requestUri' => $request_uri,
            'postBody' => 'id_token=' . $id_token . '&providerId=google.com',
            'returnSecureToken' => true,
            'returnIdpCredential' => true,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($res, true);


        // TEMPORARY DEBUG — remove after fixing
        echo '<pre style="background:#fff;color:#000;padding:20px;position:fixed;
              top:0;left:0;right:0;z-index:9999;overflow:auto;max-height:60vh;">';
        echo "HTTP CODE: $code\n";
        echo "REQUEST URI: $request_uri\n";
        echo "RESPONSE: " . json_encode($result, JSON_PRETTY_PRINT);
        echo '</pre>';
        exit;

        if ($code !== 200 || isset($result['error'])) {
            $error = 'Google sign-in failed. Please try again.';
        } else {
            $uid = $result['localId'];
            $email = $result['email'] ?? '';
            $name = $result['displayName'] ?? explode('@', $email)[0];
            $photo = $result['photoUrl'] ?? '';

            $existing = fs_get('sellers', $uid);
            if (!$existing) {
                fs_set('sellers', $uid, [
                    'uid' => $uid,
                    'email' => $email,
                    'display_name' => $name,
                    'photo_url' => $photo,
                    'created_at' => date('c'),
                    'auth_provider' => 'google',
                ]);
            }

            $_SESSION['seller_id'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['display_name'] = $name;

            header('Location: dashboard.php');
            exit;
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
    <title>Sign Up — SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
    <link rel="apple-touch-icon" href="static/seekerafric_64.png">

    <style>
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: var(--g4);
            font-size: .82rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .google-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 11px 20px;
            border: 2px solid var(--border);
            border-radius: var(--r-sm);
            background: var(--white);
            color: var(--dark-3);
            font-size: .93rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            font-family: inherit;
            margin-bottom: 4px;
        }

        .google-btn:hover {
            border-color: #4285f4;
            box-shadow: 0 2px 8px rgba(66, 133, 244, .2);
        }

        .google-btn svg {
            flex-shrink: 0;
        }

        #g_id_onload {
            display: none;
        }
    </style>
</head>

<body>

    <div class="auth-wrap">
        <div class="auth-box">
            <div class="auth-logo">Seeker<span>Afric</span></div>
            <div class="auth-tagline">Join thousands of sellers across Africa</div>

            <div class="auth-title">Create your account</div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button class="google-btn" onclick="signInWithGoogle()">
                <svg width="18" height="18" viewBox="0 0 18 18">
                    <path fill="#4285F4"
                        d="M16.51 8H8.98v3h4.3c-.18 1-.74 1.48-1.6 2.04v2.01h2.6a7.8 7.8 0 0 0 2.38-5.88c0-.57-.05-.66-.15-1.18z" />
                    <path fill="#34A853"
                        d="M8.98 17c2.16 0 3.97-.72 5.3-1.94l-2.6-2.01c-.72.48-1.63.76-2.7.76-2.08 0-3.84-1.4-4.47-3.29H1.88v2.08A8 8 0 0 0 8.98 17z" />
                    <path fill="#FBBC05"
                        d="M4.51 10.52A4.8 4.8 0 0 1 4.26 9c0-.53.09-1.04.25-1.52V5.4H1.88A8 8 0 0 0 .98 9c0 1.29.31 2.51.9 3.6l2.63-2.08z" />
                    <path fill="#EA4335"
                        d="M8.98 3.58c1.17 0 2.23.4 3.06 1.2l2.3-2.3A8 8 0 0 0 8.98 1a8 8 0 0 0-7.1 4.4l2.63 2.08c.63-1.89 2.39-3.3 4.47-3.3z" />
                </svg>
                Continue with Google
            </button>

            <div class="divider">or sign up with email</div>

            <!-- Email/Password Form -->
            <form method="POST" action="signup.php">
                <input type="hidden" name="action" value="email_signup">
                <div class="form-group">
                    <label for="email">Email Address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="exampleyou@yahoo.com" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="req">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required
                        minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm">Confirm Password <span class="req">*</span></label>
                    <input type="password" id="confirm" name="confirm" placeholder="Repeat your password" required
                        minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    Create Account &rarr;
                </button>
            </form>

            <div class="auth-links">
                Already have an account? <a href="login.php">Log in</a>
                &nbsp;|&nbsp; <a href="index.php">Browse listings</a>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> SeekerAfric &nbsp;|&nbsp; <a href="privacy.php">Privacy Policy</a>
    </footer>

    <form id="googleForm" method="POST" action="signup.php" style="display:none;">
        <input type="hidden" name="action" value="google_signup">
        <input type="hidden" name="id_token" id="googleIdToken">
    </form>
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import { getAuth, GoogleAuthProvider, signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

        const firebaseConfig = {
            apiKey: "AIzaSyD-0J9xQZhvlnaZLEt_I7GQr9nbdxrXxCM",
            authDomain: "nearme-9be4e.firebaseapp.com",
            databaseURL: "https://nearme-9be4e-default-rtdb.firebaseio.com",
            projectId: "nearme-9be4e",
            storageBucket: "nearme-9be4e.appspot.com",
            messagingSenderId: "580421290777",
            appId: "1:580421290777:web:5d5f0ca8238bad5917d388",
            measurementId: "G-F4ZR910JBR"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        window.signInWithGoogle = async function () {
            try {
                const result = await signInWithPopup(auth, provider);
                const user = result.user;

                // Get the ID token to send to PHP
                const idToken = await user.getIdToken();

                // Submit to PHP for session creation
                document.getElementById('googleIdToken').value = idToken;
                document.getElementById('googleForm').submit();

            } catch (err) {
                console.error('Google sign-in error:', err);
                if (err.code === 'auth/popup-closed-by-user') return;
                alert('Google sign-in failed: ' + err.message);
            }
        };
    </script>

</body>

</html>