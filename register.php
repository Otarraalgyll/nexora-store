<?php
require __DIR__.'/includes/functions.php'; if(user()) go('profile.php'); $error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  rate_limit('register',10);
  $name=require_text(post('full_name'),'Full name',2,100); $username=post('username');
  if(!preg_match('/^[a-zA-Z0-9_]{3,40}$/',$username)) throw new DomainException('Username must have 3–40 letters, numbers or underscores.');
  $email=email_value(post('email')); $phone=phone_value(post('phone')); $password=password_value(post('password'));
  if($password!==post('confirm_password')) throw new DomainException('Passwords do not match.');
  if(one('SELECT id FROM users WHERE email=? OR username=?',[$email,$username])) throw new DomainException('This email or username is already registered.');
  $hash=password_hash($password,PASSWORD_DEFAULT);
  q('INSERT INTO users(full_name,username,email,phone,password) VALUES(?,?,?,?,?)',[$name,$username,$email,$phone,$hash]);
  $uid=(int)db()->lastInsertId(); session_regenerate_id(true); $_SESSION['user_id']=$uid; $_SESSION['user_auth']=hash('sha256',$hash); $_SESSION['csrf']=bin2hex(random_bytes(32)); merge_guest_cart($uid); flash('Welcome to NEXORA. Your next favorite awaits.'); go('profile.php');
 } catch(DomainException $ex) { $error=$ex->getMessage(); }
 catch(PDOException $ex) { if($ex->getCode()!=='23000') throw $ex; $error='This email or username is already registered.'; }
}
$title='Create your account'; require ROOT.'/includes/header.php'; ?>
<div class="auth-card glass mx-auto my-5"><p class="eyebrow">A NEW EVERYDAY STARTS HERE</p><h1>Join <span class="gradient-text">Nexora.</span></h1><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf() ?><?php foreach(['full_name'=>['Full name','text','name'],'username'=>['Username','text','username'],'email'=>['Email','email','email'],'phone'=>['Phone number','tel','tel'],'password'=>['Password (10–72 characters)','password','new-password'],'confirm_password'=>['Confirm password','password','new-password']] as $key=>[$label,$type,$auto]): ?><label><?= $label ?><input class="form-control" name="<?= $key ?>" type="<?= $type ?>" autocomplete="<?= $auto ?>" value="<?= $type==='password'?'':e(post($key)) ?>" <?= $type==='password'?'minlength="10" maxlength="72"':'' ?> required></label><?php endforeach; ?><button class="btn btn-accent w-100">Create account ↗</button></form><p class="mt-3">Already a member? <a href="<?= url('login.php') ?>">Sign in</a></p></div>
<?php require ROOT.'/includes/footer.php'; ?>
