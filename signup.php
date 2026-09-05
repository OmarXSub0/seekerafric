<?php
require_once 'mydb.php';
session_start();

if (isset($_SESSION['seller_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$form_data = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'email_signup') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $form_data['email'] = $email;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $auth = fb_signup($email, $password);

        if (isset($auth['error'])) {
            $msg = $auth['error']['message'] ?? '';
            $error = match (true) {
                str_contains($msg, 'EMAIL_EXISTS') => 'This email is already registered. <a href="login.php">Log in here</a>.',
                str_contains($msg, 'WEAK_PASSWORD') => 'Password is too weak. Please use at least 8 characters with uppercase, lowercase, and numbers.',
                str_contains($msg, 'INVALID_EMAIL') => 'Please enter a valid email address.',
                default => 'Signup failed. Please try again.',
            };
        } else {
            $uid = $auth['localId'];
            
            fs_set('sellers', $uid, [
                'uid' => $uid,
                'email' => $email,
                'display_name' => explode('@', $email)[0],
                'created_at' => date('c'),
                'auth_provider' => 'email',
                'email_verified' => $auth['emailVerified'] ?? false,
            ]);

            $_SESSION['seller_id'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['display_name'] = explode('@', $email)[0];
            $_SESSION['logged_in_at'] = time();

            session_regenerate_id(true);

            header('Location: dashboard.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'google_signup') {
    $id_token = $_POST['id_token'] ?? '';
    $uid = $_POST['uid'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $photo = $_POST['photo'] ?? '';

    if (!$id_token) {
        $error = 'Google sign-in verification failed. Please try again.';
    } elseif (!$uid || !$email) {
        $error = 'Google sign-in failed. Please try again.';
    } else {
        // Verify token with database
        // $verified = verify_google_token($id_token);
        // if (!$verified) { $error = 'Invalid token.'; }
        
        $existing = fs_get('sellers', $uid);
        if (!$existing) {
            fs_set('sellers', $uid, [
                'uid' => $uid,
                'email' => $email,
                'display_name' => $name ?: explode('@', $email)[0],
                'photo_url' => $photo ?: '',
                'created_at' => date('c'),
                'auth_provider' => 'google',
            ]);
        }

        $_SESSION['seller_id'] = $uid;
        $_SESSION['email'] = $email;
        $_SESSION['display_name'] = $name ?: explode('@', $email)[0];
        $_SESSION['logged_in_at'] = time();
        
        session_regenerate_id(true);

        header('Location: dashboard.php');
        exit;
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
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .auth-box {
            max-width: 420px;
            width: 100%;
            background: white;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .auth-logo {
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4px;
            color: #1a1a2e;
        }
        .auth-logo span {
            color: #e94560;
        }
        .auth-tagline {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 24px;
        }
        .auth-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a1a2e;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #fee;
            color: #c0392b;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 6px;
            color: #333;
        }
        .form-group .req {
            color: #e94560;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #e94560;
        }
        .form-group .password-hint {
            font-size: 0.8rem;
            color: #888;
            margin-top: 4px;
        }
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 6px;
            transition: background 0.3s, width 0.3s;
        }
        .password-strength.weak { background: #e94560; width: 33%; }
        .password-strength.medium { background: #f39c12; width: 66%; }
        .password-strength.strong { background: #27ae60; width: 100%; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: #e94560;
            color: white;
            width: 100%;
        }
        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #999;
            font-size: 0.82rem;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }
        .google-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 11px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 0.93rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .google-btn:hover {
            border-color: #4285f4;
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.2);
            transform: translateY(-2px);
        }
        .google-btn:active {
            transform: translateY(0);
        }
        .google-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        .auth-links a {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .site-footer {
            margin-top: 30px;
            text-align: center;
            color: #888;
            font-size: 0.85rem;
        }
        .site-footer a {
            color: #888;
            text-decoration: none;
        }
        .site-footer a:hover {
            color: #e94560;
        }
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.85rem;
            color: #666;
            margin: 16px 0;
        }
        .terms-check input[type="checkbox"] {
            margin-top: 2px;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .terms-check label {
            cursor: pointer;
        }
        .terms-check a {
            color: #e94560;
            text-decoration: none;
        }
        .terms-check a:hover {
            text-decoration: underline;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 480px) {
            .auth-box {
                padding: 24px 16px;
            }
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

            <button class="google-btn" id="googleBtn" onclick="signInWithGoogle()">
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
                <span id="googleBtnText">Continue with Google</span>
            </button>

            <div class="divider">or sign up with email</div>

            <form method="POST" action="signup.php" id="signupForm" novalidate>
                <input type="hidden" name="action" value="email_signup">
                
                <div class="form-group">
                    <label for="email">Email Address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($form_data['email']) ?>"
                           placeholder="" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="req">*</span></label>
                    <input type="password" id="password" name="password" 
                           placeholder="Minimum 8 characters" required minlength="8"
                           autocomplete="new-password">
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="password-hint">
                        <span id="passwordHint">Use 8+ chars with uppercase, lowercase, and numbers</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm">Confirm Password <span class="req">*</span></label>
                    <input type="password" id="confirm" name="confirm" 
                           placeholder="Repeat your password" required minlength="8"
                           autocomplete="new-password">
                    <div class="password-hint" id="matchHint"></div>
                </div>

                <div class="terms-check">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the Terms of Service and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
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
        &copy; <?= date('Y') ?> SeekerAfric
    </footer>

    <form id="googleForm" method="POST" action="signup.php" style="display:none;">
        <input type="hidden" name="action" value="google_signup">
        <input type="hidden" name="id_token" id="googleIdToken">
        <input type="hidden" name="uid" id="googleUid">
        <input type="hidden" name="email" id="googleEmail">
        <input type="hidden" name="name" id="googleName">
        <input type="hidden" name="photo" id="googlePhoto">
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

        window.signInWithGoogle = async function() {
            const btn = document.getElementById('googleBtn');
            const btnText = document.getElementById('googleBtnText');
            
            btn.disabled = true;
            btnText.textContent = 'Connecting...';

            try {
                const result = await signInWithPopup(auth, provider);
                const user = result.user;
                const idToken = await user.getIdToken();

                document.getElementById('googleIdToken').value = idToken;
                document.getElementById('googleUid').value = user.uid;
                document.getElementById('googleEmail').value = user.email;
                document.getElementById('googleName').value = user.displayName || '';
                document.getElementById('googlePhoto').value = user.photoURL || '';
                
                document.getElementById('googleForm').submit();
            } catch (err) {
                console.error('Google sign-in error:', err);
                if (err.code === 'auth/popup-closed-by-user') {
                } else if (err.code === 'auth/cancelled-popup-request') {
                } else {
                    alert('Google sign-in failed: ' + err.message);
                }
                btn.disabled = false;
                btnText.textContent = 'Continue with Google';
            }
        };

        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm');
        const strengthBar = document.getElementById('passwordStrength');
        const passwordHint = document.getElementById('passwordHint');
        const matchHint = document.getElementById('matchHint');

        passwordInput.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            let hint = '';

            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[a-z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            strengthBar.className = 'password-strength';
            if (val.length === 0) {
                strengthBar.style.width = '0';
                hint = 'Use 8+ chars with uppercase, lowercase, and numbers';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
                hint = 'Weak - add uppercase, numbers, or special characters';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                hint = 'Medium - add more variety';
            } else {
                strengthBar.classList.add('strong');
                hint = 'Strong password!';
            }
            passwordHint.textContent = hint;
            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            const pass = passwordInput.value;
            const confirm = confirmInput.value;
            if (confirm.length === 0) {
                matchHint.textContent = '';
                matchHint.style.color = '';
            } else if (pass === confirm) {
                matchHint.textContent = '✓ Passwords match';
                matchHint.style.color = '#27ae60';
            } else {
                matchHint.textContent = '✗ Passwords do not match';
                matchHint.style.color = '#e94560';
            }
        }

        const signupForm = document.getElementById('signupForm');
        signupForm.addEventListener('submit', function(e) {
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('Please agree to the Terms of Service and Privacy Policy.');
                return;
            }

            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm').value;
            if (pass !== confirm) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Creating...';
        });
    </script>

</body>

</html>
