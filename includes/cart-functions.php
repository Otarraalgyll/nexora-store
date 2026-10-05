<?php
// Guests keep a session cart; authenticated users have a persistent MySQL cart.
function cart_raw(): array {
    if(user()) return all('SELECT product_id,quantity,size,color FROM cart WHERE user_id=? ORDER BY id',[user()['id']]);
    return array_values($_SESSION['cart'] ?? []);
}
function cart_key(int $id,string $size,string $color): string { return hash('sha256',$id.'|'.$size.'|'.$color); }
function cart_write(int $id,int $qty,string $size,string $color): void {
    if(user()) {
        if($qty<=0) q('DELETE FROM cart WHERE user_id=? AND product_id=? AND size=? AND color=?',[user()['id'],$id,$size,$color]);
        else q('INSERT INTO cart(user_id,product_id,quantity,size,color) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)',[user()['id'],$id,$qty,$size,$color]);
    } else { $key=cart_key($id,$size,$color); if($qty<=0) unset($_SESSION['cart'][$key]); else $_SESSION['cart'][$key]=['product_id'=>$id,'quantity'=>$qty,'size'=>$size,'color'=>$color]; }
}
function cart_set(int $id,int $qty,string $size,string $color,bool $add=false): void {
    $p=product($id); if(!$p) throw new DomainException('This product is no longer available.');
    foreach(['sizes'=>$size,'colors'=>$color] as $field=>$v) { $choices=options($p[$field]); if(($choices && !in_array($v,$choices,true)) || (!$choices && $v!=='')) throw new DomainException('Choose an available size and color.'); }
    $other=0;
    foreach(cart_raw() as $r) if((int)$r['product_id']===$id) { if($r['size']===$size && $r['color']===$color) { if($add) $qty+=(int)$r['quantity']; } else $other+=(int)$r['quantity']; }
    if($qty<0 || $qty>99 || ($qty>0 && $qty+$other>(int)$p['stock'])) throw new DomainException('Requested quantity exceeds available stock ('.$p['stock'].').');
    cart_write($id,$qty,$size,$color);
}
function merge_guest_cart(int $uid): void {
    foreach($_SESSION['cart'] ?? [] as $r) {
        $p=product((int)$r['product_id']); if(!$p) continue;
        $used=(int)one('SELECT COALESCE(SUM(quantity),0) n FROM cart WHERE user_id=? AND product_id=?',[$uid,$p['id']])['n'];
        $qty=min((int)$r['quantity'],max(0,(int)$p['stock']-$used));
        if($qty>0) q('INSERT INTO cart(user_id,product_id,quantity,size,color) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE quantity=LEAST(99,quantity+VALUES(quantity))',[$uid,$p['id'],$qty,$r['size'],$r['color']]);
    } unset($_SESSION['cart']);
}
function cart_items(bool $lock=false): array {
    $items=[]; $raw=cart_raw(); usort($raw,fn($a,$b)=>(int)$a['product_id']<=>(int)$b['product_id']);
    foreach($raw as $r) { $p=product((int)$r['product_id'],$lock); if(!$p) { if($lock) throw new DomainException('An item is no longer available. Update your bag.'); $p=one('SELECT * FROM products WHERE id=?',[$r['product_id']]); if(!$p) continue; }
        $items[]=array_merge($p,['quantity'=>(int)$r['quantity'],'size'=>$r['size'],'color'=>$r['color'],'line_total'=>price($p)*(int)$r['quantity'],'key'=>cart_key((int)$p['id'],$r['size'],$r['color'])]); }
    return $items;
}
function cart_count(): int { return array_sum(array_column(cart_raw(),'quantity')); }
function cart_totals(?array $items=null,bool $strict=false): array {
    $items ??= cart_items(); $sub=0; foreach($items as $item) $sub+=cents(price($item))*$item['quantity'];
    $discount=0; $code=$_SESSION['coupon'] ?? ''; $coupon=null;
    if($code) { $coupon=one('SELECT * FROM coupons WHERE code=? AND active=1 AND expires_at>=CURDATE()',[$code]);
        if(!$coupon || $sub<cents($coupon['minimum'])) { if($strict) throw new DomainException('Your coupon is expired or its minimum purchase is not met. Remove it from your bag.'); $coupon=null; }
        if($coupon) $discount=min($sub,$coupon['type']==='percent' ? (int)round($sub*(float)$coupon['value']/100) : cents($coupon['value']));
    }
    $shipping=$sub>0 && $sub<300000 ? 12000 : 0;
    return ['subtotal'=>$sub/100,'shipping'=>$shipping/100,'discount'=>$discount/100,'total'=>($sub+$shipping-$discount)/100,'coupon'=>$coupon?$code:''];
}
