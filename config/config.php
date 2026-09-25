<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'marketlink';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header("Location: $url"); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type,$msg]; }
function flashes(): void {
    foreach ($_SESSION['flash'] ?? [] as [$type,$msg]) {
        echo '<div class="alert alert-'.e($type).' alert-dismissible fade show">'.$msg.'<button class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) redirect('login.php'); }
function require_role(string $role): void {
    require_login();
    if ((user()['role'] ?? '') !== $role) { http_response_code(403); exit('Access denied'); }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid CSRF token'); }
}
function cart_count(): int {
    return array_sum(array_map('intval', $_SESSION['cart'] ?? []));
}
function money(float $n): string { return 'Rs. '.number_format($n,2); }

function product_image(string $name, string $category = '', string $stored = ''): string {
    // Database-stored local upload takes priority.
    if ($stored !== '' && strpos($stored, 'http') !== 0) {
        $rel = ltrim(str_replace('\\\\','/',$stored), '/');
        $abs = __DIR__ . '/../' . $rel;
        if (is_file($abs)) return $rel;
    }
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    $local = __DIR__ . '/../assets/img/products/' . $slug . '.jpg';
    if (is_file($local)) {
        return 'assets/img/products/' . $slug . '.jpg';
    }
    return 'assets/img-placeholder.svg';
}

function save_product_upload(array $file, int $farmerId): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Image must be 5MB or smaller.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    $dir = __DIR__ . '/../assets/uploads/products';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = 'farmer_' . $farmerId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save the image.');
    return 'assets/uploads/products/' . $name;
}

function layout_header(string $title): void { ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> | MarketLink</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top"><div class="container">
<a class="navbar-brand fw-bold" href="index.php">🌿 MarketLink</a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nav"><ul class="navbar-nav ms-auto align-items-lg-center">
<li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
<li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
<li class="nav-item"><a class="nav-link" href="markets.php">Markets</a></li>
<?php if(user()): ?>
<li class="nav-item"><a class="nav-link" href="cart.php">Cart (<?=cart_count()?>)</a></li>
<li class="nav-item"><a class="nav-link" href="<?=user()['role']==='admin'?'admin.php':(user()['role']==='farmer'?'farmer.php':'customer.php')?>">Dashboard</a></li>
<li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2" href="logout.php">Logout</a></li>
<?php else: ?><li class="nav-item"><a class="nav-link" href="login.php">Login</a></li><li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2" href="register.php">Register</a></li><?php endif; ?>
</ul></div></div></nav>
<main class="container py-4"><?php flashes(); }
function layout_footer(): void { ?>
</main><footer class="bg-dark text-white py-4 mt-5"><div class="container d-flex justify-content-between flex-wrap">
<div><strong>MarketLink</strong><div class="small text-secondary">Local farmers. Fresh products. Easy pickup.</div></div>
<div class="small text-secondary">PHP + MySQL • OpenStreetMap</div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body></html><?php }
?>