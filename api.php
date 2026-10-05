<?php
require __DIR__.'/includes/functions.php';
if(get('action')==='search') { $term=mb_substr(get('q'),0,100); if(mb_strlen($term)<2) json_response(['products'=>[]]); $rows=all('SELECT p.id,p.name,p.price,p.sale_price,p.image FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND (p.name LIKE ? OR p.keywords LIKE ? OR c.name LIKE ?) ORDER BY p.id LIMIT 6',["%$term%","%$term%","%$term%"]); foreach($rows as &$r) { $r['url']=url('product.php?id='.$r['id']); $r['image']=image_url($r['image']); $r['display_price']=money(price($r)); } json_response(['products'=>$rows]); }
if(get('action')==='quick') { $p=product((int)get('id')); if(!$p) json_response(['message'=>'Product not found'],404); json_response(['name'=>$p['name'],'description'=>$p['description'],'price'=>money(price($p)),'image'=>image_url($p['image']),'url'=>url('product.php?id='.$p['id']),'stock'=>$p['stock']]); }
json_response(['message'=>'Not found'],404);
