<?php
require_once 'firebase.php';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="SeekerAfric Privacy Policy">
    <title>Privacy Policy — SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
    <link rel="apple-touch-icon" href="static/seekerafric_64.png">
</head>

<body>

    <header class="site-header">
        <div class="header-top">
            <div>
                <a href="index.php" class="header-brand">Seeker<span>Afric</span></a>
                <div class="header-tagline">Find anything across Africa</div>
            </div>
            <div class="account-bar">
                <a href="index.php" class="btn-signin">← Back to Site</a>
            </div>
        </div>
    </header>

    <div class="privacy-wrap">
        <p class="last-updated">Last updated: July 3, <?= $year ?></p>

        <p>Welcome to SeekerAfric ("we", "our", or "us"). SeekerAfric is an online marketplace
            connecting buyers and sellers across Africa across six categories. This Privacy Policy
            explains what information we collect, how we use it, and what rights you have.</p>

        <h2>1. Information We Collect</h2>
        <ul>
            <li><strong>Account information</strong> — your email address when you register.</li>
            <li><strong>Category profile data</strong> — business name, phone, location, trade, skills, or other fields
                you fill in when setting up a category profile on your dashboard.</li>
            <li><strong>Listing data</strong> — product names, descriptions, prices, locations, and images you upload.
            </li>
            <li><strong>Usage data</strong> — pages visited, search queries, browser type, device, and IP address.</li>
        </ul>

        <div class="highlight-box">
            🔒 We do <strong>not</strong> store your password. Passwords are handled entirely by
            Firebase Authentication (Google) and never written to our database.
        </div>

        <h2>2. Categories We Serve</h2>
        <ul class="cat-chips">
            <?php foreach (get_categories() as $cat): ?>
                <li class="cat-chip"><?= $cat['icon'] ?>     <?= $cat['label'] ?></li>
            <?php endforeach; ?>
        </ul>
        <p>Each category collects different profile fields relevant to that trade. Only the
            fields you fill in for your chosen categories are stored.</p>

        <h2>3. How We Use Your Information</h2>
        <ul>
            <li>Create and manage your seller account.</li>
            <li>Display your listings and profile to buyers on the marketplace.</li>
            <li>Allow buyers to contact you via your listed phone number.</li>
            <li>Send important account notices such as security alerts.</li>
            <li>Improve site performance and user experience.</li>
            <li>Detect and prevent fraud or abuse of the platform.</li>
            <li>Comply with legal obligations.</li>
        </ul>

        <h2>4. How We Store Your Information</h2>
        <p>Your data is stored securely using Google Firebase (Firestore and Cloud Storage),
            which provides encrypted storage with strict access controls. Images you upload are
            stored in Google Cloud Storage. We take reasonable technical and organisational measures
            to protect your data against unauthorised access, loss, or disclosure.</p>

        <h2>5. Publicly Visible Information</h2>
        <p>The following is visible to all visitors by design — it is the core function of the marketplace:</p>
        <ul>
            <li>Your business name, shop name, or personal name (depending on category)</li>
            <li>Listing titles, descriptions, prices, and locations</li>
            <li>Your phone number (used by buyers to contact you)</li>
            <li>Uploaded photos</li>
        </ul>
        <p>Your <strong>email address and password</strong> are never displayed publicly.</p>

        <h2>6. Sharing Your Information</h2>
        <p>We do not sell your personal information. We may share data with:</p>
        <ul>
            <li><strong>Google Firebase / Google Cloud</strong> — our infrastructure for authentication, database, and
                file storage.</li>
            <li><strong>Law enforcement or regulators</strong> — if required by law or to protect our legal rights.</li>
        </ul>

        <h2>7. Cookies and Tracking</h2>
        <p>We use session cookies to keep you logged in while you use the site. These are
            essential cookies and cannot be disabled without breaking core functionality. We may
            also serve third-party advertisements (such as Google AdSense) which may use cookies
            to show relevant ads. You can manage ad personalisation via your
            <a href="https://adssettings.google.com" target="_blank">Google Ad Settings</a>.
        </p>

        <h2>8. Your Rights</h2>
        <ul>
            <li>Access the personal information we hold about you.</li>
            <li>Request correction of inaccurate information.</li>
            <li>Request deletion of your account and all associated data.</li>
            <li>Withdraw consent at any time by deactivating your account.</li>
        </ul>
        <p>To exercise any of these rights, contact us using the details below.</p>

        <h2>9. Data Retention</h2>
        <p>We retain your account and listing data for as long as your account is active.
            If you request deletion, we will remove your personal information within 30 days.
            Uploaded images are deleted from Cloud Storage when a listing is removed.</p>

        <h2>10. Third-Party Links</h2>
        <p>Listings may contain phone numbers or social media handles that lead to external
            platforms. We are not responsible for the privacy practices of those platforms.</p>

        <h2>11. Children's Privacy</h2>
        <p>SeekerAfric is not directed at children under 13. If you believe a child has
            provided us with personal information, contact us and we will delete it promptly.</p>

        <h2>12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. When we do, we will update
            the date at the top of this page. Continued use of the site after changes constitutes
            your acceptance of the updated policy.</p>

        <h2>13. Contact Us</h2>
        <div class="contact-box">
            <p><strong>SeekerAfric</strong></p>
            <p>Email: <a href="mailto:@mesickisicki@gmail.com">support@seekerafric.com</a></p>
            <p>Website: <a href="index.php">seekerafric.com</a></p>
        </div>
    </div>

    <footer class="site-footer">
        &copy; <?= $year ?> SeekerAfric — Find anything across Africa.
        &nbsp;|&nbsp; <a href="index.php">Home</a>
        &nbsp;|&nbsp; <a href="signup.php">Sign Up</a>
        &nbsp;|&nbsp; <a href="login.php">Log In</a>
    </footer>

</body>

</html>
