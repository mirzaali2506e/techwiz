<?php require 'config/config.php';
$type=trim($_GET['type']??'');
$allowed=['vegetable','fruit','meat','chicken','grocery','supermarket','farmers_market'];
$where=$type && in_array($type,$allowed,true)?'WHERE s.shop_type=?':'';
$stmt=db()->prepare("SELECT s.*, COUNT(DISTINCT spp.product_id) product_count FROM shops s LEFT JOIN shop_product_prices spp ON spp.shop_id=s.id $where GROUP BY s.id ORDER BY s.shop_type,s.area,s.shop_name");
$stmt->execute($type && in_array($type,$allowed,true)?[$type]:[]);$shops=$stmt->fetchAll();
$markets=db()->query("SELECT m.*,COUNT(mp.product_id) product_count FROM markets m LEFT JOIN market_products mp ON mp.market_id=m.id GROUP BY m.id ORDER BY m.market_name")->fetchAll();
layout_header('Markets & Shops');
?>
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
  <div><h2>Markets & Shops</h2><p class="text-muted mb-0">Vegetable, fruit, meat and chicken markets with locations and shop price comparisons.</p></div>
  <button class="btn btn-success" onclick="findNearMe()">📍 Find shops near me</button>
</div>
<div class="alert alert-info"><strong>Price note:</strong> “Lowest listed” compares the prices stored in this demo database. Shop prices change, so confirm before buying.</div>
<div class="d-flex flex-wrap gap-2 mb-4">
<a class="btn btn-sm <?=!$type?'btn-success':'btn-outline-success'?>" href="markets.php">All</a>
<?php foreach(['vegetable'=>'🥬 Vegetables','fruit'=>'🍎 Fruits','meat'=>'🥩 Meat','chicken'=>'🍗 Chicken','supermarket'=>'🛒 Supermarkets','farmers_market'=>'🌱 Farmers Markets'] as $k=>$label): ?><a class="btn btn-sm <?=$type===$k?'btn-success':'btn-outline-success'?>" href="markets.php?type=<?=$k?>"><?=e($label)?></a><?php endforeach; ?>
</div>
<h3 class="mb-3">Markets</h3>
<div class="row g-3 mb-5">
<?php foreach($markets as $m): ?><div class="col-md-6 col-lg-4"><div class="card market-card h-100 p-3"><span class="badge bg-light text-success mb-2">Market</span><h5><?=e($m['market_name'])?></h5><p class="text-muted mb-2">📍 <?=e($m['address'])?></p><div class="small mb-2">🛍️ <?=e((string)$m['product_count'])?> products • <?=e($m['operating_days'])?></div><small><?=e($m['timings'])?></small><a target="_blank" class="btn btn-outline-success mt-3" href="https://www.openstreetmap.org/?mlat=<?=$m['latitude']?>&mlon=<?=$m['longitude']?>#map=16/<?=$m['latitude']?>/<?=$m['longitude']?>">Open location</a></div></div><?php endforeach; ?>
</div>
<h3 class="mb-3">Shops</h3>
<div class="row g-4" id="shopGrid">
<?php foreach($shops as $s):
  $low=db()->prepare("SELECT MIN(price) low_price FROM shop_product_prices WHERE shop_id=?");$low->execute([$s['id']]);$lowPrice=$low->fetchColumn();
  $label=ucwords(str_replace('_',' ',$s['shop_type']));
?><div class="col-md-6 col-lg-4 shop-item" data-lat="<?=$s['latitude']?>" data-lng="<?=$s['longitude']?>">
  <div class="card h-100 p-3 shop-card">
    <div class="d-flex justify-content-between gap-2"><span class="badge bg-success-subtle text-success"><?=e($label)?></span><?php if($lowPrice!==false && $lowPrice!==null): ?><span class="small fw-bold text-success">From <?=money((float)$lowPrice)?></span><?php endif; ?></div>
    <h5 class="mt-2"><?=e($s['shop_name'])?></h5><p class="mb-1">📍 <?=e($s['address'])?></p><p class="text-muted small mb-2">Area: <?=e($s['area'])?></p>
    <p class="small mb-2">🛒 <?=e((string)$s['product_count'])?> products listed</p>
    <?php if($s['phone']): ?><p class="small mb-2">☎ <?=e($s['phone'])?></p><?php endif; ?>
    <div class="mt-auto d-flex gap-2"><a target="_blank" class="btn btn-outline-success flex-grow-1" href="https://www.openstreetmap.org/?mlat=<?=$s['latitude']?>&mlon=<?=$s['longitude']?>#map=16/<?=$s['latitude']?>/<?=$s['longitude']?>">Map</a><button class="btn btn-light" onclick="distanceFromMe(this,<?=$s['latitude']?>,<?=$s['longitude']?>)">Distance</button></div>
  </div></div><?php endforeach; ?>
</div>
<div id="map" class="map mt-5"></div>
<script>
const shopMap=L.map('map').setView([24.8607,67.0011],11);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(shopMap);
<?php foreach($shops as $s): ?>L.marker([<?=$s['latitude']?>,<?=$s['longitude']?>]).addTo(shopMap).bindPopup('<strong><?=addslashes(e($s['shop_name']))?></strong><br><?=addslashes(e($s['area']))?>');<?php endforeach; ?>
function hav(a,b,c,d){const R=6371,rad=x=>x*Math.PI/180;const x=rad(c-a),y=rad(d-b);const h=Math.sin(x/2)**2+Math.cos(rad(a))*Math.cos(rad(c))*Math.sin(y/2)**2;return 2*R*Math.asin(Math.sqrt(h));}
function findNearMe(){if(!navigator.geolocation){alert('Your browser does not support location.');return;} navigator.geolocation.getCurrentPosition(pos=>{const a=pos.coords.latitude,b=pos.coords.longitude;document.querySelectorAll('.shop-item').forEach(el=>el.dataset.distance=hav(a,b,+el.dataset.lat,+el.dataset.lng));[...document.querySelectorAll('.shop-item')].sort((x,y)=>+x.dataset.distance-+y.dataset.distance).forEach(x=>document.getElementById('shopGrid').appendChild(x));alert('Shops sorted from nearest to farthest.');},()=>alert('Please allow location access to find nearby shops.'));}
function distanceFromMe(btn,lat,lng){if(!navigator.geolocation){alert('Location not supported.');return;}navigator.geolocation.getCurrentPosition(pos=>{btn.textContent=hav(pos.coords.latitude,pos.coords.longitude,lat,lng).toFixed(1)+' km';},()=>alert('Please allow location access.'));}
</script>
<?php layout_footer(); ?>
