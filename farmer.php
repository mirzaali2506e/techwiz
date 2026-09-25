<?php require 'config/config.php'; require_role('farmer');$fid=user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$action=$_POST['action']??'';
if($action==='add'){
    try {
        $imagePath = save_product_upload($_FILES['product_image'] ?? [], $fid);
        $stmt=db()->prepare("INSERT INTO products(farmer_id,name,category,description,price,unit,stock_quantity,image_url) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->execute([$fid,trim($_POST['name']),trim($_POST['category']),trim($_POST['description']),floatval($_POST['price']),trim($_POST['unit']),intval($_POST['stock_quantity']),$imagePath]);
        flash('success','Product added successfully with its picture.');
    } catch(Throwable $e) {
        flash('danger',$e->getMessage());
    }
}
elseif($action==='edit'){
    try {
        $pid=(int)($_POST['product_id']??0);
        $check=db()->prepare("SELECT image_url FROM products WHERE id=? AND farmer_id=?");
        $check->execute([$pid,$fid]);
        $existing=$check->fetch();
        if(!$existing) throw new RuntimeException('Product not found.');

        $imagePath=$existing['image_url']??'';
        if(isset($_FILES['product_image']) && ($_FILES['product_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            $imagePath=save_product_upload($_FILES['product_image'],$fid);
        }
        $stmt=db()->prepare("UPDATE products SET name=?,category=?,description=?,price=?,unit=?,stock_quantity=?,image_url=? WHERE id=? AND farmer_id=?");
        $stmt->execute([trim($_POST['name']),trim($_POST['category']),trim($_POST['description']),floatval($_POST['price']),trim($_POST['unit']),intval($_POST['stock_quantity']),$imagePath,$pid,$fid]);
        flash('success','Product updated successfully.');
    } catch(Throwable $e) {
        flash('danger',$e->getMessage());
    }
}
elseif($action==='delete'){
    $pid=(int)($_POST['product_id']??0);
    db()->prepare("DELETE FROM products WHERE id=? AND farmer_id=?")->execute([$pid,$fid]);
    flash('success','Product deleted.');
}
elseif($action==='status'){db()->prepare("UPDATE orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id SET o.order_status=? WHERE o.id=? AND p.farmer_id=?")->execute([$_POST['status'],(int)$_POST['order_id'],$fid]);flash('success','Order status updated.');} redirect('farmer.php');}
$products=db()->prepare("SELECT * FROM products WHERE farmer_id=? ORDER BY id DESC");$products->execute([$fid]);$products=$products->fetchAll();
$orders=db()->prepare("SELECT DISTINCT o.*,u.username customer_name FROM orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id JOIN users u ON u.id=o.customer_id WHERE p.farmer_id=? ORDER BY o.id DESC");$orders->execute([$fid]);$orders=$orders->fetchAll();
$revenue=(float)(db()->query("SELECT COALESCE(SUM(o.total_amount),0) FROM orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id WHERE p.farmer_id=".$fid." AND o.order_status='completed'")->fetchColumn());
layout_header('Farmer Dashboard');?>
<div class="d-flex justify-content-between"><div><h2>Farmer Dashboard</h2><p class="text-muted">Manage your weekly stock and pre-orders.</p></div></div>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="stat"><small>Products</small><h3><?=count($products)?></h3></div></div><div class="col-md-4"><div class="stat"><small>Orders</small><h3><?=count($orders)?></h3></div></div><div class="col-md-4"><div class="stat"><small>Revenue (completed)</small><h3><?=money($revenue)?></h3></div></div></div>
<?php
$editId=(int)($_GET['edit']??0);
$editProduct=null;
if($editId){$es=db()->prepare("SELECT * FROM products WHERE id=? AND farmer_id=?");$es->execute([$editId,$fid]);$editProduct=$es->fetch();}
?>
<?php if($editProduct): ?>
<div class="card p-4 mb-4 border-success">
  <div class="d-flex justify-content-between align-items-center"><div><h4 class="mb-1">Edit product</h4><p class="text-muted mb-0">Name, category, price, stock and picture can all be changed.</p></div><a href="farmer.php" class="btn btn-sm btn-outline-secondary">Cancel</a></div>
  <form method="post" enctype="multipart/form-data" class="mt-3">
    <?=csrf_field()?><input type="hidden" name="action" value="edit"><input type="hidden" name="product_id" value="<?=$editProduct['id']?>">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Product name</label><input name="name" class="form-control" value="<?=e($editProduct['name'])?>" required></div>
      <div class="col-md-6"><label class="form-label">Category</label><input name="category" class="form-control" value="<?=e($editProduct['category'])?>" required></div>
      <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control"><?=e($editProduct['description'])?></textarea></div>
      <div class="col-md-4"><label class="form-label">Price</label><input name="price" type="number" step=".01" class="form-control" value="<?=e((string)$editProduct['price'])?>" required></div>
      <div class="col-md-4"><label class="form-label">Unit</label><input name="unit" class="form-control" value="<?=e($editProduct['unit'])?>" required></div>
      <div class="col-md-4"><label class="form-label">Stock</label><input name="stock_quantity" type="number" class="form-control" value="<?=e((string)$editProduct['stock_quantity'])?>" required></div>
      <div class="col-md-6"><label class="form-label">Current picture</label><div><img src="<?=e(product_image($editProduct['name'],$editProduct['category'],$editProduct['image_url']??''))?>" alt="" style="width:110px;height:90px;object-fit:cover;border-radius:12px"></div></div>
      <div class="col-md-6"><label class="form-label">Replace picture (optional)</label><input name="product_image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text">Leave empty to keep current picture. JPG/PNG/WEBP, max 5MB.</div></div>
    </div>
    <button class="btn btn-success mt-3">Save product changes</button>
  </form>
</div>
<?php endif; ?>

<div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h4>Add product</h4><form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="add"><input name="name" class="form-control mb-2" placeholder="Product name" required><input name="category" class="form-control mb-2" placeholder="Category" required><textarea name="description" class="form-control mb-2" placeholder="Description"></textarea><div class="row"><div class="col"><input name="price" type="number" step=".01" class="form-control mb-2" placeholder="Price" required></div><div class="col"><input name="unit" class="form-control mb-2" placeholder="kg / dozen" required></div></div><input name="stock_quantity" type="number" class="form-control mb-2" placeholder="Stock quantity" required><label class="form-label fw-semibold mt-1">Product picture</label><input name="product_image" type="file" class="form-control mb-1" accept="image/jpeg,image/png,image/webp" required><div class="form-text mb-3">JPG, PNG or WEBP • maximum 5MB</div><button class="btn btn-success">Add product with picture</button></form></div></div>
<div class="col-lg-7"><div class="card p-4"><h4>Your products</h4><div class="table-responsive"><table class="table align-middle"><tr><th>Picture</th><th>Name</th><th>Price</th><th>Stock</th><th>Action</th></tr><?php foreach($products as $p):?><tr>
<td><img src="<?=e(product_image($p['name'],$p['category'],$p['image_url'] ?? ''))?>" alt="<?=e($p['name'])?>" style="width:55px;height:55px;object-fit:cover;border-radius:10px"></td>
<td><strong><?=e($p['name'])?></strong><div class="small text-muted"><?=e($p['category'])?></div></td>
<td><?=money((float)$p['price'])?> / <?=e($p['unit'])?></td><td><?=$p['stock_quantity']?></td>
<td><a class="btn btn-sm btn-outline-success" href="?edit=<?=$p['id']?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Delete this product?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach;?></table></div></div></div></div>
<div class="card p-4 mt-4"><h4>Incoming orders</h4><div class="table-responsive"><table class="table"><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr><?php foreach($orders as $o):?><tr><td>#<?=$o['id']?></td><td><?=e($o['customer_name'])?></td><td><?=money((float)$o['total_amount'])?></td><td><?=e($o['order_status'])?></td><td><form method="post" class="d-flex gap-1"><?=csrf_field()?><input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="<?=$o['id']?>"><select name="status" class="form-select form-select-sm"><option>accepted</option><option>ready for pickup</option><option>completed</option><option>declined</option></select><button class="btn btn-sm btn-success">Save</button></form></td></tr><?php endforeach;?></table></div></div>
<?php layout_footer();?>