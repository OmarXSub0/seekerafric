<?php
require_once 'firebase.php';
session_start();

$categories = get_categories();
$is_logged_in = isset($_SESSION['seller_id']);
$active_cat = $_GET['cat'] ?? 'delicacy';
$search_query = $_GET['search'] ?? '';

$active_cat = array_key_exists($active_cat, $categories) ? $active_cat : 'delicacy';
$search_query = htmlspecialchars(strip_tags(trim($search_query)));
$cat_info = $categories[$active_cat];
$items = fs_search($active_cat, $search_query);

// Collect unique seller IDs
$seller_ids = array_unique(array_filter(array_column($items, 'seller_id')));
// Fetch all seller profiles at once from seller_profiles collection
$sellers = [];
foreach ($seller_ids as $sid) {
    // try category-specific profile first
    $profile = fs_get('seller_profiles', $sid . '_' . $active_cat) ?? [];

    // fallback to flat sellers document
    if (empty($profile)) {
        $profile = fs_get('sellers', $sid) ?? [];
    }

    $sellers[$sid] = $profile;
}

// Merge seller profile into each listing
foreach ($items as &$item) {
    $sid = $item['seller_id'] ?? '';
    $seller = $sellers[$sid] ?? [];

    // merge all seller fields into the listing
    // (profile fields are saved flat, no prefix)
    foreach ($seller as $key => $val) {
        if (empty($item[$key]) && !empty($val)) {
            $item[$key] = $val;
        }
    }

    // always pull phone from seller profile if listing doesn't have it
    if (empty($item['phone'])) {
        $item['phone'] = $seller[$active_cat . '_phone'] ?? '';
    }
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="SeekerAfric — Find anything across Africa.">
    <title>SeekerAfric — Find scarce and limited products and service across Africa</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
    <link rel="apple-touch-icon" href="static/seekerafric_64.png">
    <meta name="color-scheme" content="light">
</head>

<body>

    <header class="site-header">
        <div class="header-top">
            <div>
                <a href="index.php" class="header-brand">Seeker<span>Afric</span></a>
                <div class="header-tagline">Find scarce and limited products and service across Africa</div>
            </div>
            <div class="account-bar">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-dash">Dashboard</a>
                    <a href="logout.php" class="btn-logout">Log Out</a>
                <?php else: ?>
                    <a href="login.php" class="btn-signin">Sign In</a>
                    <a href="signup.php" class="btn-signup">Sign Up Free</a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="cat-nav">
            <?php foreach ($categories as $key => $cat): ?>
                <a href="?cat=<?= $key ?>" class="<?= $key === $active_cat ? 'active' : '' ?>">
                    <?= $cat['icon'] ?>     <?= $cat['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>

    <div class="search-wrap">
        <form class="search-form" method="GET" action="">
            <input type="hidden" name="cat" value="<?= $active_cat ?>">
            <input class="search-input" type="text" name="search" value="<?= $search_query ?>"
                placeholder="Search in <?= $cat_info['label'] ?>..." autocomplete="off">
            <button class="search-btn" type="submit">Search</button>
        </form>
    </div>

    <div class="cat-heading">
        <h2><?= $cat_info['label'] ?></h2>
        <span class="result-count">
            has <?= count($items) ?> listing<?= count($items) !== 1 ? 's' : '' ?>
            <?= $search_query ? ' for "' . $search_query . '"' : '' ?>
        </span>
        <h2 style="color: var(--red); margin-right: 2px;"></h2>
    </div>

    <div class="main-wrap">
        <?php if ($active_cat === 'new_home'): ?>
            <div
                style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; margin: 10px 0; border-radius: 4px;">
                <strong>⚠️ Notice:</strong> -Every property placed under new home has agreed to ONLY 400 cedis as agent
                commission,
                DO NOT PAY ANY AMOUNT ABOVE THAT.
            </div>
        <?php endif; ?>
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-title">No listings found</div>
                <div class="empty-text">
                    <?php if ($search_query): ?>
                        No results for "<?= $search_query ?>" in <?= $cat_info['label'] ?>.
                        <a href="?cat=<?= $active_cat ?>">Clear search</a>
                    <?php else: ?>
                        No listings in <?= $cat_info['label'] ?> yet — check back soon.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>

            <div class="grid">

                <?php foreach ($items as $idx => $item): ?>
                    <?php
                    $images = [];
                    if (!empty($item['image_urls']) && is_array($item['image_urls'])) {
                        $images = array_values(array_filter($item['image_urls']));
                    } elseif (!empty($item['image_url'])) {
                        $images = [$item['image_url']];
                    }
                    $img_count = count($images);
                    ?>
                    <div class="card" onclick="openModal(<?= $idx ?>)" style="cursor:pointer;">
                        <?php if ($active_cat !== 'employees'): ?>
                            <?php if ($img_count > 0): ?>
                                <div class="card-slider" id="sl<?= $idx ?>">
                                    <div class="slider-track" id="tr<?= $idx ?>">
                                        <?php foreach ($images as $img): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name'] ?? '') ?>"
                                                loading="lazy" onerror="this.style.display='none'">
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($img_count > 1): ?>
                                        <button class="slide-btn slide-prev"
                                            onclick="event.stopPropagation();slide(<?= $idx ?>,-1)">&#8249;</button>
                                        <button class="slide-btn slide-next"
                                            onclick="event.stopPropagation();slide(<?= $idx ?>,1)">&#8250;</button>
                                        <span class="slide-count" id="sc<?= $idx ?>">1/<?= $img_count ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="card-no-img"><?= $cat_info['icon'] ?></div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="card-title"><?= htmlspecialchars($item['name'] ?? 'Listing') ?></div>

                            <?php if (!empty($item['business_name'])): ?>
                                <div class="card-desc"><?= htmlspecialchars($item['business_name']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['price'])): ?>
                                <div class="card-price">GH&#8373; <?= number_format((float) $item['price'], 2) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['job_title'])): ?>
                                <div class="card-desc"><?= htmlspecialchars($item['job_title']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['education'])): ?>
                                <div class="card-desc"><?= htmlspecialchars($item['education']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['opening_hours'])): ?>
                                <div class="card-desc"><?= htmlspecialchars($item['opening_hours']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['instagram'])): ?>
                                <div class="card-desc"><?= htmlspecialchars($item['instagram']) ?> on Instagram</div>
                            <?php endif; ?>
                            <?php if (!empty($item['location'])): ?>
                                <span class="card-tag">&#128205; <?= htmlspecialchars($item['location']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['listing_type'])): ?>
                                <span class="card-tag"><?= htmlspecialchars($item['listing_type']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="listingModal" class="listing-modal" onclick="if(event.target===this)closeModal()">
        <div class="listing-modal-box">
            <button class="modal-close-btn" onclick="closeModal()">&#10005;</button>
            <div class="modal-gallery" id="modalGallery"></div>
            <div class="modal-thumbs" id="modalThumbs"></div>
            <div class="modal-details">
                <div class="modal-title" id="modalTitle"></div>
                <div class="modal-price" id="modalPrice"></div>
                <div class="modal-desc" id="modalDesc"></div>
                <div class="modal-meta" id="modalMeta"></div>
                <div class="modal-actions" id="modalActions"></div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> SeekerAfric &mdash;
        &nbsp;|&nbsp; <a href="about.php">About Page</a>
        &nbsp;|&nbsp; <a href="signup.php" style="color: black; background:ash; text-decoration: none;">Sell on
            SeekerAfric</a>
        &nbsp;|&nbsp; <a href="https://emperorgarage.onrender.com"
            style="color: darkgreen; text-decoration: none;">Check Out Our Garage </a>
        &nbsp;|&nbsp;
    </footer>

    <script>
        const _sp = {};
        function slide(i, d) {
            const tr = document.getElementById('tr' + i);
            const imgs = tr.querySelectorAll('img');
            if (!imgs.length) return;
            _sp[i] = ((_sp[i] ?? 0) + d + imgs.length) % imgs.length;
            tr.style.transform = 'translateX(-' + (_sp[i] * 100) + '%)';
            const sc = document.getElementById('sc' + i);
            if (sc) sc.textContent = (_sp[i] + 1) + '/' + imgs.length;
        }

        const listings = <?= json_encode(array_values(array_map(function ($item) use ($cat_info, $active_cat) {
            $images = [];
            if (!empty($item['image_urls']) && is_array($item['image_urls'])) {
                $images = array_values(array_filter($item['image_urls']));
            } elseif (!empty($item['image_url'])) {
                $images = [$item['image_url']];
            }
            return [
                'category' => $active_cat,
                'name' => $item['name'] ?? 'Listing',
                'full_name' => $item['full_name'] ?? '',
                'price' => !empty($item['price']) ? 'GH₵ ' . number_format((float) $item['price'], 2) : '',
                'description' => $item['description'] ?? '',
                'location' => $item['location'] ?? '',
                'phone' => $item['phone'] ?? '',
                'condition' => $item['condition'] ?? '',
                'job_title' => $item['job_title'] ?? '',
                'experience' => !empty($item['experience']) ? $item['experience'] . ' years' : '',
                'work_type' => $item['work_type'] ?? '',
                'education' => $item['education'] ?? '',
                'language' => $item['language'] ?? '',
                'age' => $item['age'] ?? '',
                'objective' => $item['objective'] ?? '',
                'business_name' => $item['business_name'] ?? '',
                'opening_hours' => $item['opening_hours'] ?? '',
                'delivery' => $item['delivery'] ?? '',
                'style_type' => !empty($item['style_type']) ? 'For ' . $item['style_type'] : '',
                'instagram' => $item['instagram'] ?? '',
                'warrant' => $item['warrant'] ?? '',
                'agent_type' => $item['agent_type'] ?? '',
                'whatsapp' => $item['whatsapp'] ?? '',
                'listing_type' => $item['listing_type'] ?? '',
                'skills' => $item['skills'] ?? '',
                'size' => $item['size'] ?? '',
                'bedrooms' => !empty($item['bedrooms']) ? $item['bedrooms'] . ' bed' : '',
                'bathrooms' => !empty($item['bathrooms']) ? $item['bathrooms'] . ' bath' : '',
                'specialty' => $item['specialty'] ?? '',
                'trade' => $item['trade'] ?? '',
                'icon' => $cat_info['icon'],
                'images' => $images,
            ];

        }, $items)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let modalImgIdx = 0;
        let modalImgs = [];
        function openModal(idx) {
            const d = listings[idx];
            if (!d) return;

            const gallery = document.getElementById('modalGallery');
            const thumbs = document.getElementById('modalThumbs');

            gallery.innerHTML = '';
            thumbs.innerHTML = '';
            thumbs.style.display = 'none';
            gallery.style.display = 'flex';
            gallery.style.minHeight = '260px';
            gallery.style.background = '#111';

            if (d.category === 'employees') {
                gallery.style.display = 'none';
                gallery.style.minHeight = '0';
                gallery.style.background = 'transparent';
                modalImgs = [];
            } else {
                modalImgs = d.images || [];
            }

            modalImgIdx = 0;

            if (d.category !== 'employees' && modalImgs.length) {
                const mainImg = document.createElement('img');
                mainImg.src = modalImgs[0];
                mainImg.id = 'modalMainImg';
                mainImg.className = 'modal-main-img';
                gallery.appendChild(mainImg);

                if (modalImgs.length > 1) {
                    const prev = document.createElement('button');
                    prev.className = 'modal-nav-btn modal-prev';
                    prev.innerHTML = '&#8249;';
                    prev.onclick = () => modalNav(-1);
                    gallery.appendChild(prev);

                    const next = document.createElement('button');
                    next.className = 'modal-nav-btn modal-next';
                    next.innerHTML = '&#8250;';
                    next.onclick = () => modalNav(1);
                    gallery.appendChild(next);

                    modalImgs.forEach((src, i) => {
                        const t = document.createElement('img');
                        t.src = src;
                        t.className = 'modal-thumb' + (i === 0 ? ' active' : '');
                        t.onclick = () => setModalImg(i);
                        thumbs.appendChild(t);
                    });
                    thumbs.style.display = 'flex';
                }
            } else if (d.category !== 'employees') {
                gallery.innerHTML = '<div class="modal-no-img">' + (d.icon || '📷') + '</div>';
            }

            document.getElementById('modalTitle').textContent = d.name || 'Listing';

            // Price (use salary for employees)
            const priceEl = document.getElementById('modalPrice');
            const price = d.category === 'employees' ? d.salary : d.price;
            priceEl.textContent = price || '';
            priceEl.style.display = price ? 'block' : 'none';

            const descEl = document.getElementById('modalDesc');
            descEl.textContent = d.description || '';
            descEl.style.display = d.description ? 'block' : 'none';

            // Meta tags
            const meta = document.getElementById('modalMeta');
            meta.innerHTML = '';
            [d.full_name ? d.full_name : '',
            d.business_name ? d.business_name : '',
            d.age ? '🎂 ' + d.age : '',
            d.job_title ? '💼 ' + d.job_title : '',
            d.work_type ? d.work_type : '',
            d.experience ? '📅 ' + d.experience : '',
            d.education ? '🎓 ' + d.education : '',
            d.language ? '🗣 ' + d.language : '',
            d.skills ? '🛠 ' + d.skills : '',
            d.trade ? '✨ ' + d.trade : '',
            d.specialty ? '🍴 ' + d.specialty : '',
            d.opening_hours ? '🕐' + d.opening_hours : '',
            d.delivery ? '🏍️ ' + d.delivery : '',
            d.style_type ? d.style_type : '',
            d.instagram ? '🖼️ ' + d.instagram : '',
            d.warranty ? '🏷️ ' + d.warranty : '',
            d.agent_type ? '👨‍💻 ' + d.agent_type : '',
            d.condition ? '📊 ' + d.condition : '',
            d.bedrooms ? '🛏 ' + d.bedrooms : '',
            d.bathrooms ? '🚿 ' + d.bathrooms : '',
            d.size ? '📏 ' + d.size : '',
            d.objective ? '🎯 ' + d.objective : '',
            d.listing_type ? d.listing_type : '',
            d.location ? '📍 ' + d.location : '',
            ].filter(Boolean).forEach(tag => {
                const span = document.createElement('span');
                span.className = 'card-tag';
                span.textContent = tag;
                meta.appendChild(span);
            });

            const actions = document.getElementById('modalActions');
            if (d.phone) {
                actions.innerHTML = `
            <a href="tel:${d.phone}" class="modal-call-btn">Call ${d.phone}</a>
        `;
            } else {
                actions.innerHTML = '';
            }

            document.getElementById('listingModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('listingModal').classList.remove('open');
            document.body.style.overflow = '';
        }

        function setModalImg(i) {
            modalImgIdx = i;
            const main = document.getElementById('modalMainImg');
            if (main) main.src = modalImgs[i];
            document.querySelectorAll('.modal-thumb').forEach((t, ti) => {
                t.classList.toggle('active', ti === i);
            });
        }

        function modalNav(dir) {
            const next = (modalImgIdx + dir + modalImgs.length) % modalImgs.length;
            setModalImg(next);
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>
</body>

</html>
