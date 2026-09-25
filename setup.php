<?php
declare(strict_types=1);
session_start();
const DB_HOST='localhost';
const DB_USER='root';
const DB_PASS='';
const DB_NAME='marketlink';

$msg=''; $ok=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true
        ]);
        $sql = file_get_contents(__DIR__.'/database.sql');
        if ($sql === false) throw new RuntimeException('database.sql not found.');
        $pdo->exec($sql);
        $pdo->exec("USE `marketlink`");
        $count=(int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $users=(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $ok=true;
        $msg="Database ready. {$count} products and {$users} users are installed.";
    } catch(Throwable $e) {
        $msg='Setup error: '.$e->getMessage();
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MarketLink Setup</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="card shadow-sm mx-auto" style="max-width:700px"><div class="card-body p-4">
<h2 class="text-success">MarketLink Database Setup</h2>
<p>This installs the complete database, demo accounts, markets and all product records.</p>
<?php if($msg): ?><div class="alert alert-<?=$ok?'success':'danger'?>"><?=$msg?></div><?php endif; ?>
<form method="post"><button class="btn btn-success btn-lg">Install / Reset Database</button></form>
<?php if($ok): ?><a class="btn btn-outline-success mt-3" href="index.php">Open MarketLink</a><?php endif; ?>
<hr><small class="text-muted">Demo login: admin@marketlink.com / MarketLink@123</small>
</div></div></div></body></html>
