<?php
define('FB_PROJECT', getenv('FB_PROJECT') ?: 'YOUR_PROJECT_ID');
define('FB_API_KEY',  getenv('FB_API_KEY')  ?: 'YOUR_WEB_API_KEY');
define('FB_BUCKET',   getenv('FB_BUCKET')   ?: 'YOUR_PROJECT_ID.firebasestorage.app');

define('FB_FS', 'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT . '/databases/(default)/documents');
define('FB_AUTH', 'https://identitytoolkit.googleapis.com/v1/accounts');
define('FB_STORE', 'https://firebasestorage.googleapis.com/v0/b/' . FB_BUCKET . '/o');
function get_categories(): array
{
    return [
        'delicacy' => [
            'label' => 'Delicacy',
            'icon' => '🍽️',
            'description' => 'Food vendors, caterers & chefs',
            'multi_image' => true,
            'max_images' => 2,
            'profile_fields' => [
                ['name' => 'business_name', 'label' => 'Business / Restaurant Name', 'type' => 'text', 'required' => true],
                ['name' => 'specialty', 'label' => 'Food Specialty', 'type' => 'select', 'required' => true, 'options' => ['Olden Day Dishes', 'Modern Dishes', 'Continental Dishes', 'Sauce', 'Desserts & Pastries']],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone', 'placeholder' => 'country code first before number', 'type' => 'tel', 'required' => true],
                ['name' => 'delivery', 'label' => 'Delivery Available?', 'type' => 'select', 'required' => true, 'options' => ['Delivery Available']],
                ['name' => 'opening_hours', 'label' => 'Opening Hours', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. Mon–Sat || 7am–9pm'],
            ],
            'listing_fields' => [
                ['name' => 'name', 'label' => 'Dish / Item Name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Aprapransa, Akyeke,Akple,jollof'],
                ['name' => 'price', 'label' => 'Price (GH₵)', 'type' => 'number', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
        ],

        'fashion' => [
            'label' => 'Fashion',
            'icon' => '👗',
            'description' => 'Clothing, accessories & footwear',
            'multi_image' => true,
            'max_images' => 2,
            'profile_fields' => [
                ['name' => 'business_name', 'label' => 'Shop / Brand Name', 'type' => 'text', 'required' => true],
                ['name' => 'style_type', 'label' => 'Style Focus', 'type' => 'select', 'required' => false, 'options' => ['Men', 'Women', 'Kids']],
                ['name' => 'location', 'label' => 'Location / Ships From', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone / WhatsApp', 'placeholder' => 'country code first before number', 'type' => 'tel', 'required' => true],
                ['name' => 'instagram', 'label' => 'Instagram Handle', 'type' => 'text', 'required' => false, 'placeholder' => '@yourhandle'],
            ],
            'listing_fields' => [
                ['name' => 'name', 'label' => 'Item Name', 'type' => 'text', 'required' => true],
                ['name' => 'price', 'label' => 'Price (GH₵)', 'type' => 'number', 'required' => true],
                ['name' => 'size', 'label' => 'Sizes Available', 'type' => 'select', 'required' => true, 'options' => ['Small', 'Medium', 'Large', 'XL', 'XXL', 'XXXL']],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
        ],

        'electronics' => [
            'label' => 'Electronics',
            'icon' => '📱',
            'description' => 'Phones, laptops, gadgets & appliances',
            'multi_image' => true,
            'max_images' => 2,
            'profile_fields' => [
                ['name' => 'business_name', 'label' => 'Shop Name', 'type' => 'text', 'required' => true],
                ['name' => 'specialty', 'label' => 'Speciality', 'type' => 'select', 'required' => true, 'options' => ['Phones', 'Laptops', 'Appliances', 'Gaming', 'All Electronics']],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone / WhatsApp', 'placeholder' => 'country code first before number', 'type' => 'tel', 'required' => true],
                ['name' => 'warranty', 'label' => 'Warranty Offered?', 'type' => 'select', 'required' => true, 'options' => ['Warranted', 'Not Warranted']],
            ],
            'listing_fields' => [
                ['name' => 'name', 'label' => 'Product Name', 'type' => 'text', 'required' => true],
                ['name' => 'price', 'label' => 'Price (GH₵)', 'type' => 'number', 'required' => true],
                ['name' => 'condition', 'label' => 'Condition', 'type' => 'select', 'required' => true, 'options' => ['Brand New', 'Foreign Used', 'Local Used']],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
        ],

        'special_artisans' => [
            'label' => 'Special Artisans',
            'icon' => '🛠️',
            'description' => 'Skilled tradespeople & craftsmen',
            'multi_image' => true,
            'max_images' => 2,
            'profile_fields' => [
                ['name' => 'full_name', 'label' => 'Business Name', 'type' => 'text', 'required' => true],
                ['name' => 'trade', 'label' => 'Trade / Skill', 'type' => 'select', 'required' => true, 'options' => ['Own Soap', 'Own Paint', 'Own Oil', 'Own Softdrinks', 'Honey Farmer', 'Artist', 'Local Furnitures']],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone', 'placeholder' => 'country code first before number', 'type' => 'tel', 'required' => true],
            ],
            'listing_fields' => [
                ['name' => 'name', 'label' => 'Product Name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Custom Furniture'],
                ['name' => 'price', 'label' => 'Starting Price (GH₵)', 'type' => 'number', 'required' => false],
                ['name' => 'description', 'label' => 'Service Description', 'type' => 'textarea', 'required' => true],
            ],
        ],

        'employees' => [
            'label' => 'Employee',
            'icon' => '👤',
            'description' => 'Job seekers, freelancers & professionals',
            'multi_image' => false,
            'no_image' => true,
            'profile_fields' => [
                ['name' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
                ['name' => 'job_title', 'label' => 'Role', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Accountant'],
                ['name' => 'experience', 'label' => 'Years of Experience', 'type' => 'number', 'required' => true],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone', 'placeholder' => 'country code before number', 'type' => 'tel', 'required' => true],
                ['name' => 'work_type', 'label' => 'Preferred Work Type', 'type' => 'select', 'required' => true, 'options' => ['Full-Time', 'Part-Time', 'Freelance', 'Remote']],
            ],
            'listing_fields' => [
                ['name' => 'education', 'label' => 'Education', 'type' => 'select', 'options' => ['Grade School', 'High School', 'Diploma', 'Tertiary', 'Self Taught'], 'required' => true],
                ['name' => 'language', 'label' => 'Languages', 'type' => 'text', 'required' => true],
                ['name' => 'price', 'label' => 'Expected Pay (GH₵)', 'type' => 'number', 'required' => false],
                ['name' => 'age', 'label' => 'Age', 'type' => 'number', 'required' => true],
                ['name' => 'objective', 'label' => 'Objective', 'type' => 'textarea', 'required' => true],
                ['name' => 'skills', 'label' => 'Key Skills', 'type' => 'textarea', 'required' => true, 'placeholder' => 'e.g. Scripting with PHP, Analyzing data with Excel, Professional Driving for 3 years'],
                ['name' => 'description', 'label' => 'Bio / Refrence', 'type' => 'textarea', 'placeholder' => 'e.g · Positivity & Enthusiasm. · Reporting sales to owner. · Marketing and advertising. · Punctual. ', 'required' => true],
            ],
            'Notice' => [
                ['name' => 'notice', 'label' => '- Remember to delete your listing after you have been employed.',],
            ],
        ],

        'new_home' => [
            'label' => 'New Home',
            'icon' => '🏠',
            'description' => 'Houses, apartments & land for rent or sale',
            'multi_image' => true,
            'max_images' => 5,
            'profile_fields' => [
                ['name' => 'business_name', 'label' => 'Name / EstateName', 'type' => 'text', 'required' => true],
                ['name' => 'agent_type', 'label' => 'Agent Type', 'type' => 'select', 'required' => true, 'options' => ['Owner', 'Real Estate', 'Airbnb', 'Current Occupant']],
                ['name' => 'location', 'label' => 'Operating Area', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone / WhatsApp', 'type' => 'tel', 'placeholder' => 'country code first before number', 'required' => true],
            ],
            'listing_fields' => [
                ['name' => 'name', 'label' => 'Property Title', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. 3 Bed House at Haatso'],
                ['name' => 'price', 'label' => 'Price / Rent (GH₵)', 'type' => 'number', 'required' => true],
                ['name' => 'listing_type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['For Rent', 'For Sale', 'Leasehold']],
                ['name' => 'bedrooms', 'label' => 'Bedrooms', 'type' => 'number', 'required' => false],
                ['name' => 'bathrooms', 'label' => 'Bathrooms', 'type' => 'number', 'required' => false],
                ['name' => 'kitchen', 'label' => 'Kitchen', 'type' => 'number', 'required' => false],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
            'Notice' => [
                [
                    'name' => 'notice',
                    'label' => '- As long as you intend to post a property here the only amount you agree to take as agent commission is 400 cedis(FIXED).'                ],
            ],
        ],
    ];
}
function get_seller_by_id(string $uid): ?array
{
    return fs_get('sellers', $uid);
}
function fb_signup(string $email, string $password): array
{
    return fb_post(FB_AUTH . ':signUp?key=' . FB_API_KEY, [
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true,
    ]);
}

function fb_login(string $email, string $password): array
{
    return fb_post(FB_AUTH . ':signInWithPassword?key=' . FB_API_KEY, [
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true,
    ]);
}

// ─────────────────────────────────────────────────────────
//  Firestore CRUD
// ─────────────────────────────────────────────────────────
function fs_set(string $col, string $id, array $data): bool
{
    $url = FB_FS . '/' . $col . '/' . $id . '?key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode(['fields' => fs_encode($data)]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

function fs_add(string $col, array $data): ?string
{
    $url = FB_FS . '/' . $col . '?key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['fields' => fs_encode($data)]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200)
        return null;
    $doc = json_decode($res, true);
    return basename($doc['name'] ?? '');
}

function fs_get(string $col, string $id): ?array
{
    $url = FB_FS . '/' . $col . '/' . $id . '?key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200)
        return null;
    return fs_decode_doc(json_decode($res, true));
}

function fs_delete(string $col, string $id): void
{
    $url = FB_FS . '/' . $col . '/' . $id . '?key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function fs_collection(string $col): array
{
    $url = FB_FS . '/' . rawurlencode($col) . '?key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200)
        return [];
    $data = json_decode($res, true);
    if (empty($data['documents']))
        return [];
    return array_map(function ($doc) {
        $item = fs_decode_doc($doc);
        $item['id'] = basename($doc['name']);
        return $item;
    }, $data['documents']);
}

function fs_search(string $col, string $q): array
{
    $all = fs_collection($col);
    if (!trim($q))
        return $all;
    $q = strtolower(trim($q));
    return array_values(array_filter($all, function ($item) use ($q) {
        foreach ($item as $v) {
            if (is_string($v) && str_contains(strtolower($v), $q))
                return true;
            if (is_array($v)) {
                foreach ($v as $sv) {
                    if (is_string($sv) && str_contains(strtolower($sv), $q))
                        return true;
                }
            }
        }
        return false;
    }));
}

function fs_seller_listings(string $col, string $seller_id): array
{
    return array_values(array_filter(
        fs_collection($col),
        fn($i) => ($i['seller_id'] ?? '') === $seller_id
    ));
}

// ─────────────────────────────────────────────────────────
//  Category Profile helpers
//  Stored as: seller_profiles/{uid}_{cat_key}
// ─────────────────────────────────────────────────────────
function get_cat_profile(string $uid, string $cat): ?array
{
    return fs_get('seller_profiles', $uid . '_' . $cat);
}

function save_cat_profile(string $uid, string $cat, array $data): bool
{
    $data['seller_id'] = $uid;
    $data['category'] = $cat;
    return fs_set('seller_profiles', $uid . '_' . $cat, $data);
}

// ─────────────────────────────────────────────────────────
//  Firebase Storage — Image Upload
// ─────────────────────────────────────────────────────────
function upload_image(array $file, string $folder = 'listing_images'): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return null;
    if ($file['size'] > 5 * 1024 * 1024)
        return null;

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
        return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
    $path = $folder . '/' . uniqid('img_', true) . '.' . $ext;

    $url = FB_STORE . '?uploadType=media&name=' . rawurlencode($path) . '&key=' . FB_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($file['tmp_name']),
        CURLOPT_HTTPHEADER => ['Content-Type: ' . $mime],
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200)
        return null;

    $result = json_decode($res, true);
    $token = $result['downloadTokens'] ?? '';
    return FB_STORE . '/' . rawurlencode($path) . '?alt=media' . ($token ? '&token=' . $token : '');
}

// ─────────────────────────────────────────────────────────
//  Firestore Value Encoding / Decoding
// ─────────────────────────────────────────────────────────
function fs_encode(array $data): array
{
    $fields = [];
    foreach ($data as $k => $v) {
        $fields[$k] = fs_encode_value($v);
    }
    return $fields;
}

function fs_encode_value(mixed $v): array
{
    if (is_null($v))
        return ['nullValue' => null];
    if (is_bool($v))
        return ['booleanValue' => $v];
    if (is_int($v))
        return ['integerValue' => $v];
    if (is_float($v))
        return ['doubleValue' => $v];
    if (is_array($v)) {
        // Associative array (string keys) → mapValue
        if (array_keys($v) !== range(0, count($v) - 1)) {
            return ['mapValue' => ['fields' => fs_encode($v)]];
        }
        // Indexed array → arrayValue
        return ['arrayValue' => ['values' => array_map('fs_encode_value', array_values($v))]];
    }
    return ['stringValue' => (string) $v];
}

function fs_decode_doc(array $doc): array
{
    $out = [];
    foreach ($doc['fields'] ?? [] as $k => $v) {
        $out[$k] = fs_decode_val($v);
    }
    return $out;
}

function fs_decode_val(array $v): mixed
{
    if (isset($v['stringValue']))
        return $v['stringValue'];
    if (isset($v['integerValue']))
        return (int) $v['integerValue'];
    if (isset($v['doubleValue']))
        return (float) $v['doubleValue'];
    if (isset($v['booleanValue']))
        return (bool) $v['booleanValue'];
    if (isset($v['nullValue']))
        return null;
    if (isset($v['arrayValue']))
        return array_map('fs_decode_val', $v['arrayValue']['values'] ?? []);
    if (isset($v['mapValue']))
        return fs_decode_doc(['fields' => $v['mapValue']['fields'] ?? []]);
    return null;
}

// ─────────────────────────────────────────────────────────
//  Generic HTTP POST helper
// ─────────────────────────────────────────────────────────
function fb_post(string $url, array $payload): array
{
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
    $data = json_decode($res, true) ?? [];
    $data['_code'] = $code;
    return $data;
}
