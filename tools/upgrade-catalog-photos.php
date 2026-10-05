<?php
/** Upgrade only untouched sample SVG images; preserve uploads and customer data.
 * Run from the project folder: php tools/upgrade-catalog-photos.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/database.php';
$root=dirname(__DIR__);
$subjects=['electronics','shoes','clothing','gaming','accessories','bags'];
foreach($subjects as $subject) {
    if(!is_file($root.'/assets/images/'.$subject.'-photo.png')) exit("Missing photograph: $subject. Copy all catalog photos before running this upgrade.\n");
}
$pdo=db();
$pdo->beginTransaction();
try {
    foreach($subjects as $subject) {
        $old='assets/images/'.$subject.'.svg';
        $photo='assets/images/'.$subject.'-photo.png';
        foreach(['products','categories','product_images'] as $table) {
            $stmt=$pdo->prepare("UPDATE $table SET image=? WHERE image=?");
            $stmt->execute([$photo,$old]);
        }
        // Remove old sketch gallery entries rather than claim they are alternate photos.
        $stmt=$pdo->prepare('DELETE FROM product_images WHERE image=?');
        $stmt->execute(['assets/images/'.$subject.'-detail.svg']);
    }
    $pdo->commit();
    echo "Sample catalog photographs updated. Stock, prices, uploads, accounts and orders preserved.\n";
} catch(Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
