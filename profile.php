<?php
require __DIR__.'/includes/functions.php'; $u=require_user(); $error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  if(post('action')==='profile') {
   $name=require_text(post('full_name'),'Name',2,100); $email=email_value(post('email')); $phone=phone_value(post('phone'));
   if(one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$u['id']])) throw new DomainException('Email already registered.');
   q('UPDATE users SET full_name=?,email=?,phone=? WHERE id=?',[$name,$email,$phone,$u['id']]);
  } elseif(post('action')==='address') { save_address((int)$u['id'],validated_address()); }
  elseif(post('action')==='password') {
   rate_limit('profile-password',10);
   if(!password_verify(post('current_password'),$u['password'])) throw new DomainException('Current password is incorrect.');
   $password=password_value(post('password')); if($password!==post('confirm_password')) throw new DomainException('Passwords do not match.');
   $hash=password_hash($password,PASSWORD_DEFAULT);
   q('UPDATE users SET password=? WHERE id=?',[$hash,$u['id']]);
   $_SESSION['user_auth']=hash('sha256',$hash);
   q('DELETE FROM password_resets WHERE user_id=?',[$u['id']]); session_regenerate_id(true);
  } else throw new DomainException('Unknown form.');
  flash('Your changes have been saved.'); go('profile.php');
 } catch(DomainException $ex) { $error=$ex->getMessage(); }
 catch(PDOException $ex) { if($ex->getCode()!=='23000') throw $ex; $error='Email already registered.'; }
}
$address=one('SELECT * FROM addresses WHERE user_id=?',[$u['id']]) ?? []; $title='Your account'; require ROOT.'/includes/header.php'; ?>
<div class="page-heading"><p class="eyebrow">YOUR PERSONAL SPACE</p><h1>Hello, <span class="gradient-text"><?= e(explode(' ',$u['full_name'])[0]) ?>.</span></h1></div>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="account-layout"><aside class="glass account-menu"><div class="avatar"><?= e(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></div><h3><?= e($u['full_name']) ?></h3><p>@<?= e($u['username']) ?></p><a href="#information">Profile information</a><a href="<?= url('orders.php') ?>">My orders ↗</a><a href="<?= url('wishlist.php') ?>">Wishlist ↗</a><a href="#address">Saved address</a><a href="#password">Change password</a><form action="<?= url('action.php') ?>" method="post"><?= csrf() ?><button class="btn btn-soft w-100 mt-3" name="action" value="logout">Sign out</button></form></aside><div><section id="information" class="glass p-4 mb-4"><h2>Profile information</h2><form method="post"><?= csrf() ?><?php foreach(['full_name'=>'Full name','email'=>'Email','phone'=>'Phone number'] as $key=>$label): ?><label><?= $label ?><input class="form-control" name="<?= $key ?>" type="<?= $key==='email'?'email':'text' ?>" value="<?= e($u[$key]) ?>" required></label><?php endforeach; ?><button class="btn btn-accent" name="action" value="profile">Save profile</button></form></section><section id="address" class="glass p-4 mb-4"><h2>Saved address</h2><form method="post"><?= csrf() ?><?php require ROOT.'/includes/address-fields.php'; ?><button class="btn btn-accent" name="action" value="address">Save address</button></form></section><section id="password" class="glass p-4"><h2>Change password</h2><form method="post"><?= csrf() ?><?php foreach(['current_password'=>'Current password','password'=>'New password (10–72 characters)','confirm_password'=>'Confirm new password'] as $key=>$label): ?><label><?= $label ?><input type="password" name="<?= $key ?>" class="form-control" maxlength="72" autocomplete="<?= $key==='current_password'?'current-password':'new-password' ?>" required></label><?php endforeach; ?><button class="btn btn-accent" name="action" value="password">Update password</button></form></section></div></div>
<?php require ROOT.'/includes/footer.php'; ?>
