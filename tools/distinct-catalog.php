<?php
// Replace only known duplicate demo listings. Historical order snapshots remain unchanged.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/config/database.php';
$rows=json_decode(file_get_contents(dirname(__DIR__).'/config/distinct-catalog.json'),true,512,JSON_THROW_ON_ERROR);
foreach($rows as $row) if(!is_file(dirname(__DIR__).'/'.$row['image']))throw new RuntimeException('Missing '.$row['image']);
$old=['Aura Studio Headphones','Velocity Street Sneakers','Weekend Cloud Hoodie','Nova Pro Keyboard','Pulse Active Watch','Weekender Explorer Bag','Everyday Relaxed Hoodie','Cloud Heavyweight Hoodie','Campus Comfort Hoodie','Orbit Cloud Walk Sneakers','Motion Daily Sneakers','Aura Everyday Wireless Headphones','Aura Comfort Over-Ear Headphones','Pulse Everyday Smart Watch','Pulse Urban Watch','Nova Desk Mechanical Keyboard','Nova Nightfall Keyboard','Metro Campus Backpack','Metro Commuter Backpack'];
$pdo=db();$pdo->beginTransaction();
try {
 foreach($old as $name){$s=$pdo->prepare("UPDATE products SET active=0 WHERE name=? AND image LIKE 'assets/images/%-photo.png'");$s->execute([$name]);}
 foreach($rows as $row){
  $s=$pdo->prepare('SELECT id FROM products WHERE name=?');$s->execute([$row['name']]);if($s->fetch())continue;
  $s=$pdo->prepare('SELECT id FROM categories WHERE name=?');$s->execute([$row['category']]);$cat=$s->fetch();if(!$cat)throw new RuntimeException('Missing category');
  $s=$pdo->prepare('INSERT INTO products(category_id,name,description,keywords,price,stock,image,sizes,colors) VALUES(?,?,?,?,?,?,?,?,?)');
  $s->execute([$cat['id'],$row['name'],$row['description'],$row['category'].' '.$row['name'],$row['price'],$row['stock'],$row['image'],$row['sizes'],$row['colors']]);
  $id=$pdo->lastInsertId();$s=$pdo->prepare('INSERT INTO product_images(product_id,image) VALUES(?,?)');$s->execute([$id,$row['image']]);
 }
 $pdo->commit();echo 'Active products: '.$pdo->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn().PHP_EOL;
}catch(Throwable $e){$pdo->rollBack();throw $e;}
