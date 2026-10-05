<?php
require __DIR__.'/includes/functions.php'; $error=''; $done=false;
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  rate_limit('password-reset',5); $email=email_value(post('email')); $u=one('SELECT id FROM users WHERE email=? AND active=1',[$email]);
  if($u) {
   $token=bin2hex(random_bytes(32));
   q('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))',[$u['id'],hash('sha256',$token)]);
   $link=APP_URL.'/reset-password.php?token='.$token;
   $body="Reset your NEXORA password using this single-use link (valid for 30 minutes):\n$link\nIgnore this message if you did not request it.";
   // Local XAMPP has no mail server. Keep the message in a protected local outbox.
   if(APP_ENV==='local') file_put_contents(ROOT.'/storage/mail.log',"To: $email\n$body\n\n",FILE_APPEND|LOCK_EX);
   else if(!mail($email,'Reset your NEXORA password',$body,'From: '.MAIL_FROM)) error_log('NEXORA reset email delivery failed. Configure PHP mail transport.');
  }
  $done=true;
 } catch(DomainException $ex) { $error=$ex->getMessage(); }
}
$title='Reset your password'; require ROOT.'/includes/header.php'; ?>
<div class="glass auth-card mx-auto my-5"><p class="eyebrow">LET’S GET YOU BACK IN</p><h1>Forgot password?</h1><p>Enter your account email to request a reset link.</p><?php if($done): ?><div class="alert alert-info">If an active account matches, a reset link has been sent. Check your inbox.</div><?php endif; if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf() ?><label>Email<input name="email" type="email" autocomplete="email" class="form-control" required></label><button class="btn btn-accent w-100">Send reset link</button></form><a class="d-block mt-3" href="<?= url('login.php') ?>">Back to sign in</a></div>
<?php require ROOT.'/includes/footer.php'; ?>
