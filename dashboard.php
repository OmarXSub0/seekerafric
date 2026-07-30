<?php
require_once 'firebase.php';
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: login.php');
    exit;
}

$seller_id = $_SESSION['seller_id'];
$email = $_SESSION['email'] ?? '';
$categories = get_categories();
$error = '';
$success = '';

$panel = $_GET['panel'] ?? '';
$panel = array_key_exists($panel, $categories) ? $panel : '';

$seller = fs_get('sellers', $seller_id) ?? ['uid' => $seller_id, 'email' => $email];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_profile') {
    $cat_key = $_POST['cat_key'] ?? '';
    if (array_key_exists($cat_key, $categories)) {
        $cat = $categories[$cat_key];
        $profile_data = ['activated' => 'true'];

        foreach ($cat['profile_fields'] as $f) {
            $val = trim($_POST[$f['name']] ?? '');
            if ($f['required'] && !$val) {
                $error = $f['label'] . ' is required.';
                break;
            }
            $profile_data[$f['name']] = $val;
        }

        if (!$error) {
            save_cat_profile($seller_id, $cat_key, $profile_data);
            $success = $cat['label'] . ' profile saved!';
            $panel = $cat_key;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_listing') {
    $cat_key = $_POST['cat_key'] ?? '';
    if (array_key_exists($cat_key, $categories)) {
        $cat = $categories[$cat_key];
        $profile = get_cat_profile($seller_id, $cat_key);

        if (empty($profile['activated'])) {
            $error = 'Please complete and save your ' . $cat['label'] . ' profile first.';
        } else {
            $listing = [
                'seller_id' => $seller_id,
                'category' => $cat_key,
                'created_at' => date('c'),
            ];

            foreach ($cat['listing_fields'] as $f) {
                $val = trim($_POST[$f['name']] ?? '');
                if ($f['required'] && !$val) {
                    $error = $f['label'] . ' is required.';
                    break;
                }
                if ($f['type'] === 'number' && $val !== '')
                    $val = (float) $val;
                $listing[$f['name']] = $val;
            }

            if (!$error) {
                if ($cat['multi_image']) {
                    $max = $cat['max_images'] ?? 5;
                    $urls = [];
                    for ($i = 0; $i < $max; $i++) {
                        $key = 'image_' . $i;
                        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                            $url = upload_image($_FILES[$key], $cat_key . '_images');
                            if ($url)
                                $urls[] = $url;
                            else {
                                $error = 'Image ' . ($i + 1) . ' failed to upload. Use JPG/PNG/GIF/WEBP under 5MB.';
                                break;
                            }
                        }
                    }
                    if (!$error)
                        $listing['image_urls'] = $urls;
                } else {
                    // Single image
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $url = upload_image($_FILES['image'], $cat_key . '_images');
                        if ($url)
                            $listing['image_url'] = $url;
                        else
                            $error = 'Image failed to upload. Use JPG/PNG/GIF/WEBP under 5MB.';
                    }
                }
            }

            if (!$error) {
                $doc_id = fs_add($cat_key, $listing);
                if ($doc_id) {
                    $success = 'Listing added successfully!';
                    $panel = $cat_key;
                } else {
                    $error = 'Failed to add listing. Please try again.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_listing') {
    $cat_key = $_POST['cat_key'] ?? '';
    $doc_id = $_POST['doc_id'] ?? '';
    if (array_key_exists($cat_key, $categories) && $doc_id) {
        fs_delete($cat_key, $doc_id);
        $success = 'Listing deleted.';
        $panel = $cat_key;
    }
}

$display_name = $seller['display_name'] ?? explode('@', $email)[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard — SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
    <link rel="apple-touch-icon" href="static/seekerafric_64.png">
</head>

<body>

    <header class="dash-header">
        <a href="index.php" class="dash-brand">Seeker<span>Afric</span></a>
        <div class="dash-user">
            <span class="dash-email"><?= htmlspecialchars($email) ?></span>
            <a href="index.php" class="btn btn-ghost btn-sm">View Site</a>
            <!--  <a href="payment.php" class="btn btn-ghost btn-sm">Payment</a>-->
            <a href="logout.php" class="btn btn-ghost btn-sm">Log Out</a>
        </div>
    </header>

    <div class="dash-page">

        <div class="section-title">My Categories</div>
        <p style="font-size:0.88rem;color:var(--gray-3);margin-bottom:18px;">
            Click any category to set up your profile and start listing. You can be active in as many as you like.
        </p>

        <div class="cat-overview">
            <?php foreach ($categories as $key => $cat):
                $is_active = !empty(get_cat_profile($seller_id, $key)['activated']);
                ?>
                <a href="dashboard.php?panel=<?= $key ?>"
                    class="cat-mgmt-card <?= $is_active ? 'activated' : '' ?> <?= $panel === $key ? 'selected' : '' ?>">
                    <span class="cat-mgmt-icon"><?= $cat['icon'] ?></span>
                    <span class="cat-mgmt-label"><?= $cat['label'] ?></span>
                    <span class="cat-mgmt-status">
                        <?= $is_active ? 'Active' : 'Not set up' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($panel && array_key_exists($panel, $categories)):
            $cat = $categories[$panel];
            $profile = get_cat_profile($seller_id, $panel) ?? [];
            $listings = fs_seller_listings($panel, $seller_id);
            $is_active = !empty($profile['activated']);
            ?>
            <div class="manage-panel">
                <div class="panel-head">
                    <h2><?= $cat['label'] ?> — Manage</h2>
                    <?php if ($is_active): ?>
                        <span style="font-size:0.8rem;color:#aaa;">Profile active &nbsp;·&nbsp; <?= count($listings) ?>
                            listing<?= count($listings) !== 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">

                    <div class="profile-form-title">
                        <?= $is_active ? '✏️ Update your ' . $cat['label'] . ' profile' : '👤 Set up your ' . $cat['label'] . ' profile first' ?>
                    </div>

                    <form method="POST" action="dashboard.php?panel=<?= $panel ?>">
                        <input type="hidden" name="action" value="save_profile">
                        <input type="hidden" name="cat_key" value="<?= $panel ?>">

                        <div class="form-grid">
                            <?php foreach ($cat['profile_fields'] as $f): ?>
                                <div class="form-group <?= $f['type'] === 'textarea' ? 'full' : '' ?>">
                                    <label>
                                        <?= $f['label'] ?>
                                        <?php if ($f['required']): ?><span class="req">*</span><?php endif; ?>
                                    </label>
                                    <?php
                                    $saved_val = $profile[$f['name']] ?? '';
                                    ?>
                                    <?php if ($f['type'] === 'select'): ?>
                                        <select name="<?= $f['name'] ?>" <?= $f['required'] ? 'required' : '' ?>>
                                            <option value="">— Select —</option>
                                            <?php foreach ($f['options'] as $opt): ?>
                                                <option value="<?= $opt ?>" <?= $saved_val === $opt ? 'selected' : '' ?>>
                                                    <?= $opt ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($f['type'] === 'textarea'): ?>
                                        <textarea name="<?= $f['name'] ?>" placeholder="<?= $f['placeholder'] ?? '' ?>"
                                            <?= $f['required'] ? 'required' : '' ?>><?= htmlspecialchars($saved_val) ?></textarea>
                                    <?php else: ?>
                                        <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>"
                                            value="<?= htmlspecialchars($saved_val) ?>" placeholder="<?= $f['placeholder'] ?? '' ?>"
                                            <?= $f['required'] ? 'required' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                            <?= $is_active ? 'Update Profile' : 'Activate' ?>
                        </button>
                    </form>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_active): ?>
                        <div class="add-listing-section">
                            <div class="section-title">➕ Add New <?= $cat['label'] ?> Listing</div>

                            <form method="POST" action="dashboard.php?panel=<?= $panel ?>" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_listing">
                                <input type="hidden" name="cat_key" value="<?= $panel ?>">

                                <div class="form-grid">
                                    <?php foreach ($cat['listing_fields'] as $f): ?>
                                        <div class="form-group <?= $f['type'] === 'textarea' ? 'full' : '' ?>">
                                            <label>
                                                <?= $f['label'] ?>
                                                <?php if ($f['required']): ?><span class="req">*</span><?php endif; ?>
                                            </label>

                                            <?php if ($f['type'] === 'select'): ?>
                                                <select name="<?= $f['name'] ?>" <?= $f['required'] ? 'required' : '' ?>>
                                                    <option value="">— Select —</option>
                                                    <?php foreach ($f['options'] as $opt): ?>
                                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>

                                            <?php elseif ($f['type'] === 'textarea'): ?>
                                                <textarea name="<?= $f['name'] ?>" placeholder="<?= $f['placeholder'] ?? '' ?>"
                                                    <?= $f['required'] ? 'required' : '' ?>></textarea>

                                            <?php else: ?>
                                                <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>"
                                                    placeholder="<?= $f['placeholder'] ?? '' ?>" <?= $f['required'] ? 'required' : '' ?>>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (empty($cat['no_image'])): ?>
                                    <div class="form-group" style="margin-top:16px;">
                                        <?php if ($cat['multi_image']): ?>
                                            <label>
                                                Photos
                                                <span style="font-size:0.78rem;color:var(--gray-4);font-weight:400;">
                                                    — up to <?= $cat['max_images'] ?> images
                                                </span>
                                            </label>
                                            <div class="upload-grid">
                                                <?php for ($i = 0; $i < $cat['max_images']; $i++): ?>
                                                    <div class="upload-slot" id="slot-<?= $panel ?>-<?= $i ?>">
                                                        <input type="file" name="image_<?= $i ?>"
                                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                                            onchange="previewSlot(this, 'slot-<?= $panel ?>-<?= $i ?>')">
                                                        <span class="upload-slot-icon">＋</span>
                                                        <span class="upload-slot-label">Photo <?= $i + 1 ?></span>
                                                        <img src="" alt="">
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="file-hint">JPG, PNG, GIF or WEBP · max 5MB each</span>
                                        <?php else: ?>
                                            <label>Photo</label>
                                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                                                onchange="previewSingle(this, 'single-preview-<?= $panel ?>')">
                                            <img id="single-preview-<?= $panel ?>" class="single-upload-preview" src="" alt="">
                                            <span class="file-hint">JPG, PNG, GIF or WEBP · max 5MB</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($cat['Notice'][0]['label'])): ?>
                                    <div
                                        style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; margin: 10px 0; border-radius: 4px;">
                                        <strong>Notice:</strong>
                                        <?= htmlspecialchars($cat['Notice'][0]['label']) ?>
                                    </div>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                                    Add Listing
                                </button>

                            </form>
                        </div>

                        <div class="listings-section">
                            <div class="section-title">My <?= $cat['label'] ?> Listings</div>
                            <table class="listings-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Price</th>
                                        <th>Date Added</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listings)): ?>
                                        <tr class="empty-table-row">
                                            <td colspan="5">
                                                <?= $cat['icon'] ?> No listings yet — add your first one above.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($listings as $listing):
                                            $thumb = null;
                                            if (!empty($listing['image_urls']) && is_array($listing['image_urls'])) {
                                                $thumb = $listing['image_urls'][0] ?? null;
                                            } elseif (!empty($listing['image_url'])) {
                                                $thumb = $listing['image_url'];
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php if ($thumb): ?>
                                                        <img class="listing-thumb" src="<?= htmlspecialchars($thumb) ?>" alt=""
                                                            onerror="this.style.display='none'">
                                                    <?php else: ?>
                                                        <div class="listing-thumb-placeholder"><?= $cat['icon'] ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="listing-name">
                                                        <?= htmlspecialchars($listing['name'] ?? '—') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($listing['price'])): ?>
                                                        <span class="listing-price">
                                                            GH₵ <?= number_format((float) $listing['price'], 2) ?>
                                                        </span>
                                                    <?php else: ?>—<?php endif; ?>
                                                </td>
                                                <td class="listing-date">
                                                    <?= date('d M Y', strtotime($listing['created_at'] ?? 'now')) ?>
                                                </td>
                                                <td>
                                                    <form method="POST" action="dashboard.php?panel=<?= $panel ?>"
                                                        onsubmit="return confirm('Delete this listing?')">
                                                        <input type="hidden" name="action" value="delete_listing">
                                                        <input type="hidden" name="cat_key" value="<?= $panel ?>">
                                                        <input type="hidden" name="doc_id" value="<?= $listing['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

    </div>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> SeekerAfric &nbsp;|&nbsp; <a href="privacy.php">Privacy Policy</a>
    </footer>

    <script>
        // Preview a slot in the 5-image upload grid
        function previewSlot(input, slotId) {
            const slot = document.getElementById(slotId);
            if (!slot) return;
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    slot.querySelector('img').src = e.target.result;
                    slot.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            } else {
                slot.querySelector('img').src = '';
                slot.classList.remove('has-image');
            }
        }

        // Preview a single image upload
        function previewSingle(input, previewId) {
            const preview = document.getElementById(previewId);
            if (!preview) return;
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '0.6';
                        btn.style.cursor = 'not-allowed';
                        const original = btn.innerHTML;
                        btn.innerHTML = 'Please wait...';

                        setTimeout(function () {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.cursor = 'default';
                            btn.innerHTML = original;
                        }, 30000);
                    }
                });
            });
        });

    </script>

</body>

</html>