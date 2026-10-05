<?php
require __DIR__.'/includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('POST required'); }
check_csrf(); $action=post('action'); $id=(int)post('id'); $redirect='cart.php'; $message='Saved.';
try {
 switch($action) {
 case 'add': case 'buy': case 'cart_update':
    $qty=filter_var(post('quantity','1'),FILTER_VALIDATE_INT); if($qty===false || $qty<0 || $qty>99 || ($action!=='cart_update' && $qty<1)) throw new DomainException('Choose a valid quantity.');
    cart_set($id,$qty,post('size'),post('color'),$action!=='cart_update'); $message=$action==='cart_update'?'Bag updated.':'Product added to cart successfully!'; $redirect=$action==='buy'?'checkout.php':'cart.php'; break;
 case 'cart_remove': cart_write($id,0,post('size'),post('color')); $message='Item removed.'; break;
 case 'coupon':
    $code=strtoupper(post('code')); $coupon=one('SELECT * FROM coupons WHERE code=? AND active=1 AND expires_at>=CURDATE()',[$code]);
    if(!$coupon || cart_totals()['subtotal']<(float)$coupon['minimum']) throw new DomainException('Coupon unavailable or minimum purchase not met.'); $_SESSION['coupon']=$code; $message='Coupon applied.'; break;
 case 'remove_coupon': unset($_SESSION['coupon']); $message='Coupon removed.'; break;
 case 'wishlist':
    $u=require_user(); if(!product($id)) throw new DomainException('Product not found.');
    if(one('SELECT user_id FROM wishlist WHERE user_id=? AND product_id=?',[$u['id'],$id])) { q('DELETE FROM wishlist WHERE user_id=? AND product_id=?',[$u['id'],$id]); $message='Removed from wishlist.'; }
    else { q('INSERT INTO wishlist(user_id,product_id) VALUES(?,?)',[$u['id'],$id]); $message='Saved to wishlist.'; } $redirect='wishlist.php'; break;
 case 'wishlist_move':
    $u=require_user(); $p=product($id); if(!$p) throw new DomainException('Product unavailable.'); cart_set($id,1,options($p['sizes'])[0] ?? '',options($p['colors'])[0] ?? '',true); q('DELETE FROM wishlist WHERE user_id=? AND product_id=?',[$u['id'],$id]); $redirect='wishlist.php'; $message='Moved to your bag.'; break;
 case 'review':
    $u=require_user(); if(!product($id)) throw new DomainException('Product not found.'); $rating=(int)post('rating'); if($rating<1 || $rating>5) throw new DomainException('Choose 1–5 stars.'); $comment=require_text(post('comment'),'Review',5,2000);
    db()->beginTransaction(); q('SELECT id FROM products WHERE id=? FOR UPDATE',[$id]);
    q('INSERT INTO reviews(user_id,product_id,rating,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),created_at=CURRENT_TIMESTAMP',[$u['id'],$id,$rating,$comment]); q('UPDATE products SET rating=(SELECT AVG(rating) FROM reviews WHERE product_id=?) WHERE id=?',[$id,$id]); db()->commit(); $message='Review saved.'; $redirect='product.php?id='.$id.'#reviews'; break;
 case 'logout': unset($_SESSION['user_id'],$_SESSION['cart'],$_SESSION['coupon']); session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(32)); $redirect='login.php'; $message='You are signed out.'; break;
 default: throw new DomainException('Unknown action.');
 }
 if(wants_json()) json_response(['ok'=>true,'message'=>$message,'count'=>cart_count(),'totals'=>cart_totals(),'redirect'=>$action==='buy'?url($redirect):null]);
 flash($message); go($redirect);
} catch(DomainException $ex) { if(db()->inTransaction()) db()->rollBack(); if(wants_json()) json_response(['ok'=>false,'message'=>$ex->getMessage()],422); flash($ex->getMessage()); go($action==='review'?'product.php?id='.$id:'cart.php'); }
