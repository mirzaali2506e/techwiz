<?php require 'config/config.php'; layout_header('Products');
$q=trim($_GET['q']??''); $cat=trim($_GET['category']??''); $where=['p.stock_quantity>0']; $args=[];
if($q){$where[]='(p.name LIKE ? OR p.description LIKE ?)'; $args[]="%$q%"; $args[]="%$q%";}
if($cat){$where[]='p.category=?';$args[]=$cat;}
$sql="SELECT p.*,u.username farmer_name FROM products p JOIN users u ON u.id=p.farmer_id WHERE ".implode(' AND ',$where)." ORDER BY p.id DESC";
$s=db()->prepare($sql);$s->execute($args);$products=$s->fetchAll();$cats=db()->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h2>Products</h2><p class="text-muted">Search weekly stock from local farmers.</p></div></div>
<form class="row g-2 mb-4"><div class="col-md-6"><input name="q" value="<?=e($q)?>" class="form-control" placeholder="Search products..."></div><div class="col-md-4"><select name="category" class="form-select"><option value="">All categories</option><?php foreach($cats as $c):?><option <?=$cat===$c['category']?'selected':''?>><?=e($c['category'])?></option><?php endforeach;?></select></div><div class="col-md-2"><button class="btn btn-success w-100">Filter</button></div></form>
<div class="d-flex flex-wrap gap-2 mb-4">
<a class="btn btn-sm btn-success" href="products.php">All</a>
<?php foreach($cats as $c): ?><a class="btn btn-sm btn-outline-success" href="products.php?category=<?=urlencode($c['category'])?>"><?=e($c['category'])?></a><?php endforeach; ?>
</div>
<div class="row g-4"><?php foreach($products as $p):?><div class="col-md-6 col-lg-3"><div class="card h-100 product-card"><div class="product-img"><img src="<?=e(product_image($p['name'],$p['category'],$p['image_url'] ?? ''))?>" alt="<?=e($p['name'])?>" loading="lazy" onerror="this.onerror=null;this.src='assets/img-placeholder.svg';"></div><div class="card-body"><span class="badge bg-light text-success"><?=e($p['category'])?></span><h5 class="mt-2"><?=e($p['name'])?></h5><p class="small text-muted">Farmer: <?=e($p['farmer_name'])?></p><p><?=e($p['description'])?></p><?php $lp=db()->prepare("SELECT MIN(price) FROM shop_product_prices WHERE product_id=?");$lp->execute([$p['id']]);$low=$lp->fetchColumn(); if($low!==false && $low!==null): ?><div class="small text-success mb-1">Lowest listed shop price: <strong><?=money((float)$low)?></strong></div><?php endif; ?><strong class="price"><?=money((float)$p['price'])?></strong> / <?=e($p['unit'])?><a class="btn btn-success w-100 mt-3" href="product.php?id=<?=$p['id']?>">Details</a></div></div></div><?php endforeach; if(!$products): ?><div class="col-12"><div class="alert alert-warning">No products found in this category.</div></div><?php endif; ?></div>
<?php layout_footer(); ?>