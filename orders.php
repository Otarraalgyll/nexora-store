<?php
require __DIR__.'/includes/functions.php'; $u=require_user(); $title='Your orders';
$order=get('id')?one('SELECT * FROM orders WHERE id=? AND user_id=?',[(int)get('id'),$u['id']]):null;
if(get('id') && !$order) http_response_code(404);
require ROOT.'/includes/header.php'; ?>
<div class="page-heading"><p class="eyebrow">EVERY FIND HAS A STORY</p><h1>Your <span class="gradient-text">orders.</span></h1></div>
<?php if($order): require ROOT.'/includes/order-detail.php'; ?>
<a class="btn btn-link mt-4" href="<?= url('orders.php') ?>">← All orders</a>
<?php elseif(get('id')): ?><div class="empty-state"><h2>Order not found.</h2></div><?php else: $rows=all('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[$u['id']]); if(!$rows): ?><div class="glass empty-state"><h2>Your story starts with a first find.</h2><a class="btn btn-accent" href="<?= url('shop.php') ?>">Start exploring</a></div><?php endif; foreach($rows as $row): ?><a class="glass order-list-row" href="<?= url('orders.php?id='.$row['id']) ?>"><div><strong><?= e($row['order_number']) ?></strong><small><?= e(date('M j, Y',strtotime($row['created_at']))) ?></small></div><span class="status status-<?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span><strong><?= money($row['total']) ?> ↗</strong></a><?php endforeach; endif; require ROOT.'/includes/footer.php'; ?>
