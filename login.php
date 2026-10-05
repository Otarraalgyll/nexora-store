<?php
require __DIR__.'/includes/functions.php';
if(user()) go('profile.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  rate_limit('customer-login');
  $identity=post('identity');
  $u=one('SELECT * FROM users WHERE email=? OR username=?',[$identity,$identity]);
  if(!$u || !password_verify(post('password'),$u['password']) || !$u['active']) throw new DomainException('Invalid credentials or account unavailable.');
  session_regenerate_id(true); $_SESSION['user_id']=$u['id']; $_SESSION['user_auth']=hash('sha256',$u['password']); $_SESSION['csrf']=bin2hex(random_bytes(32));
  merge_guest_cart((int)$u['id']); go('profile.php');
 } catch(DomainException $ex) { $error=$ex->getMessage(); }
}
$title='Welcome back'; require ROOT.'/includes/header.php'; ?>
<section class="auth-layout"><div class="auth-story"><p class="eyebrow">YOUR WORLD. UPGRADED.</p><h1>Good to have<br>you <span class="gradient-text">back.</span></h1><p>Your favorites, your finds, your next everyday upgrade. All in one place.</p><img src="<?= url('assets/images/shoes.svg') ?>" alt="Orbit sneakers" width="500" height="420"></div><div class="glass auth-card"><span class="logo-mark">N</span><h2>Sign in to Nexora</h2><p>Make yourself at home.</p><?php if($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf() ?><label>Email or username<input name="identity" class="form-control" autocomplete="username" value="<?= e(post('identity')) ?>" required maxlength="190"></label><label>Password<input type="password" name="password" class="form-control" autocomplete="current-password" required maxlength="72"></label><a class="small" href="<?= url('forgot-password.php') ?>">Forgot password?</a><button class="btn btn-accent w-100 mt-4">Sign in ↗</button></form><p class="mt-4">New here? <a href="<?= url('register.php') ?>">Create an account</a></p></div></section>
<?php require ROOT.'/includes/footer.php'; ?>
