<?php require 'config/config.php';
if(user()) redirect('index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??''; $role=$_POST['role']??'customer'; $phone=trim($_POST['phone']??''); $address=trim($_POST['address']??'');
if(!in_array($role,['customer','farmer'],true) || !$name || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<6){ flash('danger','Please fill all fields correctly. Password must be at least 6 characters.'); }
else { try { $s=db()->prepare("INSERT INTO users(username,email,password_hash,role,phone,address,status) VALUES(?,?,?,?,?,?,?)"); $s->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$phone,$address,$role==='farmer'?'pending':'active']); flash('success','Registration successful. You can now log in.'); redirect('login.php'); } catch(PDOException $e){ flash('danger','Email already exists or registration failed.'); } }
}
layout_header('Register'); ?>
<div class="row justify-content-center"><div class="col-lg-7"><div class="card p-4"><h2>Create account</h2><p class="text-muted">Register as a customer or farmer.</p><form method="post"><?=csrf_field()?>
<div class="row"><div class="col-md-6 mb-3"><label>Name / Stall contact</label><input name="name" class="form-control" required></div><div class="col-md-6 mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
<div class="col-md-6 mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div><div class="col-md-6 mb-3"><label>Role</label><select name="role" class="form-select"><option value="customer">Customer</option><option value="farmer">Farmer</option></select></div>
<div class="col-md-6 mb-3"><label>Phone</label><input name="phone" class="form-control"></div><div class="col-md-6 mb-3"><label>Address</label><input name="address" class="form-control"></div></div>
<button class="btn btn-success">Create account</button></form></div></div></div><?php layout_footer(); ?>