<?php require 'config/config.php';
if(user()) redirect('index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
$s=db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $s->execute([$email]); $u=$s->fetch();
if($u && password_verify($pass,$u['password_hash']) && $u['status']!=='suspended'){ $_SESSION['user']=$u; redirect($u['role']==='admin'?'admin.php':($u['role']==='farmer'?'farmer.php':'customer.php')); }
flash('danger','Invalid credentials or account unavailable.'); }
layout_header('Login'); ?>
<div class="row justify-content-center"><div class="col-md-5"><div class="card p-4"><h2>Login</h2><form method="post"><?=csrf_field()?><label>Email</label><input type="email" name="email" class="form-control mb-3" required><label>Password</label><input type="password" name="password" class="form-control mb-3" required><button class="btn btn-success w-100">Login</button></form><p class="small text-muted mt-3 mb-0">Demo admin: admin@marketlink.com / MarketLink@123</p></div></div></div><?php layout_footer(); ?>