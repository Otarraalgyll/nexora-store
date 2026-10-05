<?php
/** Add the extended demo collection once, preserving existing products and orders.
 * Usage: php tools/expand-catalog.php
 * Export seed SQL without changing the database: php tools/expand-catalog.php --sql
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/database.php';
// Category, name, description, keywords, price, sale price, stock, image, sizes, colors.
$products=[
 ['Clothing','Everyday Relaxed Hoodie','A laid-back lilac hoodie for daily layering. Soft cotton-blend fabric, a roomy front pocket and ribbed cuffs keep the look comfortable and simple.','hoodie cotton relaxed everyday lilac',1590,null,32,'clothing','S,M,L,XL','Lilac'],
 ['Clothing','Cloud Heavyweight Hoodie','A substantial fleece hoodie with an oversized silhouette, dropped shoulders and a lined hood. Made for cool evenings and relaxed weekends.','hoodie heavyweight fleece oversized warm',2590,2190,20,'clothing','S,M,L,XL,XXL','Lilac'],
 ['Clothing','Campus Comfort Hoodie','A versatile pullover with a relaxed fit and a spacious kangaroo pocket. Pair its soft lilac finish with denim or your favorite everyday sneakers.','hoodie campus pullover casual streetwear',1790,1490,26,'clothing','XS,S,M,L,XL','Lilac'],
 ['Shoes','Orbit Cloud Walk Sneakers','Cream mesh and lavender panels give these everyday sneakers a fresh look. A cushioned sole, padded collar and lace-up fit make an easy daily combination.','sneakers cream lavender mesh casual footwear',2690,2290,24,'shoes','36,37,38,39,40,41,42','Cloud'],
 ['Shoes','Motion Daily Sneakers','An everyday low-top with a sculpted cream sole and textured lavender accents. The padded tongue and breathable mesh upper keep the finish clean and comfortable.','sneakers low top mesh daily walking',2890,null,18,'shoes','37,38,39,40,41,42,43','Cloud'],
 ['Electronics','Aura Everyday Wireless Headphones','Charcoal over-ear headphones with violet metallic accents and padded ear cushions. A comfortable everyday listening companion for music, podcasts and a tidy desk setup.','headphones wireless audio music over ear',2490,1990,22,'electronics','','Midnight'],
 ['Electronics','Aura Comfort Over-Ear Headphones','Deep cushioned earcups and a padded headband define this listening essential. Its understated graphite finish brings a considered touch to your home or work setup.','headphones over ear comfort audio graphite',3790,null,15,'electronics','','Midnight'],
 ['Accessories','Pulse Everyday Smart Watch','A graphite watch with a clear clock face and a soft silicone strap. The rounded case and adjustable band make a simple addition to an everyday outfit.','watch smartwatch graphite silicone wearable',2290,1890,30,'accessories','','Midnight'],
 ['Accessories','Pulse Urban Watch','A modern wristwatch with a dark rounded case and a minimal purple display. Its flexible silicone band and streamlined profile suit a clean everyday look.','watch urban wristwatch silicone accessory',2790,null,17,'accessories','','Midnight'],
 ['Gaming','Nova Desk Mechanical Keyboard','A compact mechanical keyboard with dark keycaps and soft purple-and-cyan illumination. Its angled frame and uncluttered layout bring a focused look to your desk.','keyboard mechanical compact gaming desk rgb',3490,2990,21,'gaming','','Midnight'],
 ['Gaming','Nova Nightfall Keyboard','A charcoal mechanical keyboard with vivid accent lighting and a sturdy low-profile frame. A distinctive centerpiece for a coordinated gaming or creative workspace.','keyboard gaming nightfall mechanical backlit',4290,3690,13,'gaming','','Midnight'],
 ['Bags','Metro Campus Backpack','A slate-blue daily backpack with a roomy main compartment, front organizer pocket and padded shoulder straps. Keep your study or work essentials together in a simple silhouette.','backpack campus school slate bag organizer',1890,1590,27,'bags','','Slate'],
 ['Bags','Metro Commuter Backpack','A structured slate-blue backpack with easy-access front storage, side pockets and a reinforced top handle. A practical carry for your daily route and weekend plans.','backpack commuter travel slate everyday bag',2490,null,19,'bags','','Slate'],
];
$pdo=db();
if(in_array('--sql',$argv,true)) {
 echo "\n-- Extended NEXORA demonstration collection (25 products total).\n";
 foreach($products as [$category,$name,$description,$keywords,$price,$sale,$stock,$image,$sizes,$colors]) {
  $values=[$name,$description,$keywords,$price,$sale,$stock,'assets/images/'.$image.'-photo.png',$sizes,$colors];
  $quoted=array_map(fn($v)=>$v===null?'NULL':$pdo->quote((string)$v),$values);
  echo 'INSERT INTO products(category_id,name,description,keywords,price,sale_price,stock,image,sizes,colors) SELECT id,'.implode(',',$quoted).' FROM categories WHERE name='.$pdo->quote($category).";\n";
 }
 exit;
}
$added=0;
$pdo->beginTransaction();
try {
 foreach($products as [$category,$name,$description,$keywords,$price,$sale,$stock,$image,$sizes,$colors]) {
  $stmt=$pdo->prepare('SELECT id FROM products WHERE name=?');$stmt->execute([$name]);if($stmt->fetch())continue;
  $stmt=$pdo->prepare('SELECT id FROM categories WHERE name=?');$stmt->execute([$category]);$cat=$stmt->fetch();
  if(!$cat)throw new RuntimeException('Missing category: '.$category);
  $path='assets/images/'.$image.'-photo.png';
  if(!is_file(dirname(__DIR__).'/'.$path))throw new RuntimeException('Missing image: '.$path);
  $stmt=$pdo->prepare('INSERT INTO products(category_id,name,description,keywords,price,sale_price,stock,image,sizes,colors) VALUES(?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute([$cat['id'],$name,$description,$keywords,$price,$sale,$stock,$path,$sizes,$colors]);
  $id=$pdo->lastInsertId();
  $stmt=$pdo->prepare('INSERT INTO product_images(product_id,image) VALUES(?,?)');$stmt->execute([$id,$path]);
  $added++;
 }
 $pdo->commit();
 echo "Added $added products. Active catalog: ".$pdo->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn()." products.\n";
} catch(Throwable $e) { $pdo->rollBack(); throw $e; }
