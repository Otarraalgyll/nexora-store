<?php
require __DIR__.'/includes/functions.php'; $u=require_user(); $error='';
$_SESSION['checkout_token'] ??= bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  $token=post('checkout_token');
  if(!preg_match('/^[a-f0-9]{64}$/',$token)) throw new DomainException('Invalid checkout. Reload this page.');
  $existing=one('SELECT id FROM orders WHERE checkout_token=? AND user_id=?',[$token,$u['id']]);
  if($existing) go('order-success.php?id='.$existing['id']);
  if(!hash_equals($_SESSION['checkout_token'],$token)) throw new DomainException('Checkout expired. Reload this page.');
  $name=require_text(post('full_name'),'Full name',2,100); $email=email_value(post('email')); $phone=phone_value(post('phone')); $address=validated_address();
  $method=post('payment_method'); if(!in_array($method,['cod','gcash','maya','card'],true)) throw new DomainException('Choose a payment method.');
  db()->beginTransaction();
  // Serialize checkout for this customer, then lock products in ID order.
  $locked=one('SELECT active FROM users WHERE id=? FOR UPDATE',[$u['id']]);
  if(!$locked['active']) throw new DomainException('Your account is unavailable.');
  $existing=one('SELECT id FROM orders WHERE checkout_token=? AND user_id=?',[$token,$u['id']]);
  if($existing) { db()->commit(); go('order-success.php?id='.$existing['id']); }
  $items=cart_items(true); if(!$items) throw new DomainException('Your bag is empty.');
  $needed=[]; foreach($items as $item) {
   $needed[$item['id']]=($needed[$item['id']] ?? 0)+$item['quantity'];
   foreach(['sizes'=>'size','colors'=>'color'] as $field=>$variant) { $choices=options($item[$field]); if(($choices && !in_array($item[$variant],$choices,true)) || (!$choices && $item[$variant]!=='')) throw new DomainException('A product option has changed. Remove and re-add '.$item['name'].'.'); }
   if($item['quantity']<1 || $needed[$item['id']]>(int)$item['stock']) throw new DomainException('Insufficient stock for '.$item['name'].'. Update your bag.');
  }
  $totals=cart_totals($items,true); $number='NX-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(5)));
  q('INSERT INTO orders(user_id,order_number,full_name,email,phone,shipping_address,subtotal,shipping,discount,total,coupon_code,payment_method,payment_status,checkout_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$u['id'],$number,$name,$email,$phone,implode(', ',array_values($address)),$totals['subtotal'],$totals['shipping'],$totals['discount'],$totals['total'],$totals['coupon'] ?: null,$method,$method==='cod'?'Due on delivery':'Demo - unpaid',$token]);
  $orderId=(int)db()->lastInsertId();
  foreach($items as $item) q('INSERT INTO order_items(order_id,product_id,product_name,price,quantity,size,color) VALUES(?,?,?,?,?,?,?)',[$orderId,$item['id'],$item['name'],price($item),$item['quantity'],$item['size'],$item['color']]);
  foreach($needed as $id=>$quantity) q('UPDATE products SET stock=stock-? WHERE id=?',[$quantity,$id]);
  save_address((int)$u['id'],$address); q('DELETE FROM cart WHERE user_id=?',[$u['id']]); db()->commit();
  unset($_SESSION['coupon']); $_SESSION['checkout_token']=bin2hex(random_bytes(32)); go('order-success.php?id='.$orderId);
 } catch(DomainException $ex) { if(db()->inTransaction()) db()->rollBack(); $error=$ex->getMessage(); }
 catch(Throwable $ex) { if(db()->inTransaction()) db()->rollBack(); throw $ex; }
}
$items=cart_items(); $address=one('SELECT * FROM addresses WHERE user_id=?',[$u['id']]) ?? []; $title='Checkout'; require ROOT.'/includes/header.php'; ?>
<div class="page-heading"><p class="eyebrow">ONE STEP CLOSER</p><h1>Make it <span class="gradient-text">yours.</span></h1><p>Bag <span class="text-secondary">—</span> Checkout <span class="text-secondary">—</span> All yours</p></div>
<?php if($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; if(!$items): ?><div class="empty-state glass"><h2>Your bag is empty.</h2><a class="btn btn-accent" href="<?= url('shop.php') ?>">Explore the collection</a></div><?php else: ?><div class="checkout-layout"><form method="post" id="checkout-form"><?= csrf() ?><input type="hidden" name="checkout_token" value="<?= e($_SESSION['checkout_token']) ?>"><section class="glass p-4 mb-4"><h2><span class="step-number">01</span> Your details</h2><?php foreach(['full_name'=>'Full name','email'=>'Email','phone'=>'Phone number'] as $key=>$label): ?><label><?= $label ?><input name="<?= $key ?>" type="<?= $key==='email'?'email':($key==='phone'?'tel':'text') ?>" class="form-control" value="<?= e(post($key,$u[$key])) ?>" required></label><?php endforeach; ?></section><section class="glass p-4 mb-4"><h2><span class="step-number">02</span> Where to?</h2><?php require ROOT.'/includes/address-fields.php'; ?></section><section class="glass p-4 mb-4"><h2><span class="step-number">03</span> Payment</h2><div class="payment-grid"><?php foreach(['cod'=>['Cash on delivery','Pay when your order arrives','fa-truck'],'gcash'=>['GCash','Demo · no charge','fa-mobile-screen'],'maya'=>['Maya','Demo · no charge','fa-wallet'],'card'=>['Credit / Debit','Demo · no charge','fa-credit-card']] as $key=>[$label,$sub,$icon]): ?><label class="payment-option"><input type="radio" name="payment_method" value="<?= $key ?>" <?= post('payment_method','cod')===$key?'checked':'' ?> required><i class="fa-solid <?= $icon ?>"></i><span><strong><?= $label ?></strong><small><?= $sub ?></small></span></label><?php endforeach; ?></div><div id="payment-demo" class="demo-payment mt-3" hidden><span class="pill">PAYMENT PREVIEW</span><h3 class="mt-3">Your next favorite is almost here.</h3><p>This option simulates checkout. No payment is processed. Your order is recorded as <strong>Demo - unpaid</strong>.</p><div class="demo-card">NEXORA <span>•••• •••• •••• DEMO</span></div><small>Do not enter or send real card or wallet credentials.</small></div></section><button class="btn btn-accent btn-lg w-100" type="submit">Place order ↗</button><p class="small mt-3 text-secondary">Review your details before placing your order. Digital payments are demonstrations.</p></form><div><?php require ROOT.'/includes/summary.php'; ?><div class="glass p-4 mt-4"><h3>In your bag</h3><?php foreach($items as $item): ?><div class="mini-product"><img src="<?= image_url($item['image']) ?>" width="64" height="54" alt=""><div><strong><?= e($item['name']) ?></strong><small><?= e(trim($item['size'].' '.$item['color'])) ?> · Qty <?= $item['quantity'] ?></small><span><?= money($item['line_total']) ?></span></div></div><?php endforeach; ?></div></div></div><?php endif; require ROOT.'/includes/footer.php'; ?>
