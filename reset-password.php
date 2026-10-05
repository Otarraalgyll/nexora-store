<?php
require __DIR__.'/includes/functions.php'; $error=''; $token=get('token');
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  rate_limit('reset-submit',10); $password=password_value(post('password')); if($password!==post('confirm_password')) throw new DomainException('Passwords do not match.');
  db()->beginTransaction();
  $reset=one('SELECT * FROM password_resets WHERE token_hash=? AND expires_at>NOW() FOR UPDATE',[hash('sha256',$token)]);
  if(!$reset) throw new DomainException('This reset link has expired or already been used.');
  q('UPDATE users SET password=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$reset['user_id']]);
  q('DELETE FROM password_resets WHERE user_id=?',[$reset['user_id']]); db()->commit();
  flash('Password reset. Sign in with your new password.'); go('login.php');
 } catch(DomainException $ex) { if(db()->inTransaction()) db()->rollBack(); $error=$ex->getMessage(); }
}
$title='Choose a new password'; require ROOT.'/includes/header.php'; ?>
<div class="glass auth-card mx-auto my-5"><h1>A fresh start.</h1><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf() ?><label>New password<input type="password" name="password" autocomplete="new-password" minlength="10" maxlength="72" class="form-control" required></label><label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" class="form-control" required></label><button class="btn btn-accent">Save password</button></form></div>
<?php require ROOT.'/includes/footer.php'; ?>
