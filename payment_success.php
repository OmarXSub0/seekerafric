<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box" style="text-align:center;">
        <div style="font-size:4rem;margin-bottom:16px;">✅</div>
        <div class="auth-logo">Seeker<span style="color:var(--red)">Afric</span></div>
        <div style="font-size:1.3rem;font-weight:800;color:var(--dark);margin:16px 0 8px;">
            Payment Successful!
        </div>
        <p style="color:var(--gray-2);font-size:0.9rem;margin-bottom:24px;">
            Thank you — your payment has been received and confirmed.
        </p>
        <a href="index.php" class="btn btn-primary btn-block">Back to SeekerAfric</a>
    </div>
</div>
</body>
</html>
