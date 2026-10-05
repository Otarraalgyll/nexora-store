<?php
/** Real HTTP + MySQL checks. Only run against a dedicated *_test database.
 * See README for commands. Tests intentionally create disposable records.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!str_ends_with(getenv('DB_NAME') ?: '', '_test')) exit("Set DB_NAME to a dedicated database ending in _test.\n");
require dirname(__DIR__).'/config/database.php';
$base=rtrim(getenv('TEST_URL') ?: 'http://127.0.0.1:8081','/');
$passed=0;
function expect(bool $ok,string $message): void {
    global $passed;
    if(!$ok) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL; $passed++;
}
function row(string $sql,array $values=[]): array { $s=db()->prepare($sql); $s->execute($values); return $s->fetch() ?: []; }
class Browser {
    public string $csrf='';
    private string $jar;
    public function __construct() { $this->jar=tempnam(sys_get_temp_dir(),'nexora-test-'); }
    public function __destruct() { @unlink($this->jar); }
    public function request(string $path,?array $data=null,bool $ajax=false): array {
        global $base;
        $ch=curl_init($base.$path);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$this->jar,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20,CURLOPT_HEADER=>true]);
        if($data!==null) {
            $multipart=(bool)array_filter($data,fn($v)=>$v instanceof CURLFile);
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$multipart?$data:http_build_query($data));
        }
        if($ajax) curl_setopt($ch,CURLOPT_HTTPHEADER,['X-Requested-With: XMLHttpRequest']);
        $response=curl_exec($ch);
        if($response===false) throw new RuntimeException(curl_error($ch));
        $status=curl_getinfo($ch,CURLINFO_HTTP_CODE); $size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $header=substr($response,0,$size); $html=substr($response,$size); curl_close($ch);
        if(preg_match('/name="csrf-token" content="([a-f0-9]+)"/',$html,$m)) $this->csrf=$m[1];
        return ['status'=>$status,'body'=>$html,'headers'=>$header,'json'=>json_decode($html,true)];
    }
    public function post(string $path,array $data,bool $ajax=false): array { return $this->request($path,array_merge(['csrf'=>$this->csrf],$data),$ajax); }
    public function action(array $data): array { return $this->post('/action.php',$data,true); }
}
$customer=new Browser(); $guest=new Browser(); $admin=new Browser();
foreach(['/','/shop.php','/shop.php?category=2&sort=low','/shop.php?min=1000&max=3000&rating=4','/shop.php?page=2','/product.php?id=1','/cart.php','/login.php','/register.php','/about.php','/contact.php','/help.php','/terms.php','/privacy.php','/forgot-password.php','/admin/login.php'] as $path) {
    $r=$guest->request($path); expect($r['status']===200 && !str_contains($r['body'],'could not complete'),"Public route $path");
}
expect($guest->request('/product.php?id=999999')['status']===404,'Missing product returns 404');
foreach(['/database.sql','/config/database.php','/storage/mail.log','/tools/router.php','/tests/integration.php','/.gitignore','/admin/_header.php'] as $path) expect(in_array($guest->request($path)['status'],[403,404]),"Private file protected: $path");
foreach(['/admin/','/admin/products.php','/admin/orders.php','/admin/customers.php','/profile.php','/orders.php','/wishlist.php','/checkout.php'] as $path) expect($guest->request($path)['status']===302,"Authentication required: $path");
$r=$guest->request('/api.php?action=search&q=audio'); expect(count($r['json']['products'])>=1,'Search finds product keywords');
$r=$guest->request('/api.php?action=search&q=Clothing'); expect(count($r['json']['products'])===6,'Search finds six clothing products');
$r=$guest->request('/api.php?action=search&q=%27%20OR%201%3D1%20--'); expect($r['json']['products']===[],'Search treats SQL syntax as text');
expect($guest->request('/api.php?action=quick&id=1')['json']['name']==='Aura Wireless Headphones','Quick view returns live product');
$customer->request('/');
$r=$customer->request('/action.php',['action'=>'add','id'=>1,'quantity'=>1,'color'=>'Midnight'],true); expect($r['status']===403,'Missing CSRF token rejected');
$r=$customer->action(['action'=>'add','id'=>1,'quantity'=>2,'color'=>'Midnight']); expect($r['json']['ok'] && $r['json']['count']===2,'Guest cart adds product');
$r=$customer->action(['action'=>'add','id'=>1,'quantity'=>99,'color'=>'Midnight']); expect($r['status']===422,'Stock limit enforced');
$r=$customer->action(['action'=>'add','id'=>2,'quantity'=>1,'size'=>'INVALID','color'=>'Cloud']); expect($r['status']===422,'Invalid product variant rejected');
$r=$customer->action(['action'=>'cart_update','id'=>1,'quantity'=>1,'color'=>'Midnight']); expect($r['json']['count']===1,'AJAX cart quantity updates');
$r=$customer->action(['action'=>'coupon','code'=>'WELCOME10']); expect((float)$r['json']['totals']['discount']===279.0 && (float)$r['json']['totals']['total']===2631.0,'Coupon and shipping calculated server-side');
$r=$customer->action(['action'=>'coupon','code'=>'INVALID']); expect($r['status']===422,'Invalid coupon rejected');
$suffix=bin2hex(random_bytes(4)); $email="test_$suffix@example.test"; $username="test_$suffix"; $password='Customer@2026!';
$customer->request('/register.php');
$r=$customer->post('/register.php',['full_name'=>'Integration Customer','username'=>$username,'email'=>$email,'phone'=>'09123456789','password'=>$password,'confirm_password'=>$password]); expect($r['status']===302,'Registration succeeds');
$u=row('SELECT * FROM users WHERE email=?',[$email]); expect(!empty($u) && password_verify($password,$u['password']),'Password securely hashed in MySQL');
expect((int)row('SELECT quantity FROM cart WHERE user_id=?',[$u['id']])['quantity']===1,'Guest cart merges into persistent customer cart');
$customer->request('/profile.php');
expect($customer->request('/admin/products.php')['status']===302,'Customer cannot access admin management');
$r=$customer->action(['action'=>'wishlist','id'=>2]); expect($r['json']['ok'] && !empty(row('SELECT * FROM wishlist WHERE user_id=? AND product_id=2',[$u['id']])),'Wishlist persists in MySQL');
$r=$customer->action(['action'=>'wishlist_move','id'=>2]); expect($r['json']['ok'] && !row('SELECT * FROM wishlist WHERE user_id=? AND product_id=2',[$u['id']]),'Wishlist moves item to cart');
$customer->action(['action'=>'cart_remove','id'=>2,'size'=>'38','color'=>'Cloud']);
$r=$customer->post('/profile.php',['action'=>'address','province'=>'Metro Manila','city'=>'Quezon City','barangay'=>'Central','street'=>'123 Test Street','postal_code'=>'1100']); expect($r['status']===302 && row('SELECT city FROM addresses WHERE user_id=?',[$u['id']])['city']==='Quezon City','Saved address persists');
$r=$customer->action(['action'=>'review','id'=>1,'rating'=>5,'comment'=>'Great headphones <script>alert(1)</script>']); expect($r['json']['ok'] && (float)row('SELECT rating FROM products WHERE id=1')['rating']===5.0,'Review updates average rating');
$r=$customer->request('/product.php?id=1'); expect(str_contains($r['body'],'&lt;script&gt;alert(1)&lt;/script&gt;'),'Review HTML is escaped');
$before=(int)row('SELECT stock FROM products WHERE id=1')['stock'];
$r=$customer->request('/checkout.php'); preg_match('/name="checkout_token" value="([a-f0-9]+)"/',$r['body'],$m);
expect(isset($m[1]),'Checkout provides idempotency token');
$checkout=['checkout_token'=>$m[1],'full_name'=>'Integration Customer','email'=>$email,'phone'=>'09123456789','province'=>'Metro Manila','city'=>'Quezon City','barangay'=>'Central','street'=>'123 Test Street','postal_code'=>'1100','payment_method'=>'cod'];
$r=$customer->post('/checkout.php',$checkout); expect($r['status']===302 && str_contains($r['headers'],'order-success.php?id='),'Checkout redirects to order success');
$order=row('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[$u['id']]); expect((float)$order['total']===2631.0 && $order['status']==='Pending','Order totals and initial status persisted');
expect((int)row('SELECT stock FROM products WHERE id=1')['stock']===$before-1,'Checkout decrements inventory');
expect((int)row('SELECT COUNT(*) n FROM order_items WHERE order_id=?',[$order['id']])['n']===1,'Order items persisted');
expect((int)row('SELECT COUNT(*) n FROM cart WHERE user_id=?',[$u['id']])['n']===0,'Checkout empties cart');
$customer->post('/checkout.php',$checkout); expect((int)row('SELECT COUNT(*) n FROM orders WHERE user_id=?',[$u['id']])['n']===1,'Repeat checkout cannot create duplicate order');
expect($customer->request('/orders.php?id='.$order['id'])['status']===200,'Customer can view own order');
$other=new Browser(); $other->request('/register.php');
$other->post('/register.php',['full_name'=>'Other Customer','username'=>'other_'.$suffix,'email'=>'other_'.$email,'phone'=>'09123456789','password'=>$password,'confirm_password'=>$password]);
expect($other->request('/orders.php?id='.$order['id'])['status']===404,'Other customer cannot read order');
$admin->request('/admin/login.php');
$r=$admin->post('/admin/login.php',['email'=>'admin@nexora.local','password'=>'Nexora@2026!']); expect($r['status']===302 && str_contains($r['headers'],'password.php'),'Admin initial login requires new password');
expect(str_contains($admin->request('/admin/products.php')['headers'],'password.php'),'Admin cannot bypass password change');
$admin->request('/admin/password.php');
$r=$admin->post('/admin/password.php',['current_password'=>'Nexora@2026!','password'=>'AdminChanged@2026!','confirm_password'=>'AdminChanged@2026!']); expect($r['status']===302,'Admin changes initial password');
foreach(['/admin/','/admin/products.php','/admin/categories.php','/admin/orders.php','/admin/customers.php','/admin/coupons.php','/admin/messages.php'] as $path) expect($admin->request($path)['status']===200,"Admin route $path");
$r=$admin->post('/admin/orders.php?id='.$order['id'],['id'=>$order['id'],'status'=>'Cancelled']); expect($r['status']===302,'Admin cancels order');
expect((int)row('SELECT stock FROM products WHERE id=1')['stock']===$before,'Cancellation restores inventory');
$admin->post('/admin/orders.php?id='.$order['id'],['id'=>$order['id'],'status'=>'Cancelled']); expect((int)row('SELECT stock FROM products WHERE id=1')['stock']===$before,'Repeated cancellation cannot inflate inventory');
$categoryName='Integration '.$suffix;
$r=$admin->post('/admin/categories.php',['action'=>'save','name'=>$categoryName]); expect($r['status']===302,'Admin creates category');
$cat=row('SELECT id FROM categories WHERE name=?',[$categoryName]);
$temp=tempnam(sys_get_temp_dir(),'nx-image-'); file_put_contents($temp,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aV1cAAAAASUVORK5CYII='));
$product=['action'=>'save','name'=>'Test product '.$suffix,'description'=>'A product created by the integration test.','category_id'=>$cat['id'],'price'=>'1500','sale_price'=>'1200','stock'=>8,'sizes'=>'S,M','colors'=>'Black','keywords'=>'test','active'=>1,'image'=>new CURLFile($temp,'image/png','test.png')];
$r=$admin->post('/admin/products.php?new=1',$product); expect($r['status']===302,'Admin creates product and uploads valid image');
$p=row('SELECT * FROM products WHERE name=?',[$product['name']]); expect(is_file(dirname(__DIR__).'/'.$p['image']),'Uploaded image saved with randomized filename');
$product['id']=$p['id']; $product['stock']=11; unset($product['image']);
$r=$admin->post('/admin/products.php?edit='.$p['id'],$product); expect($r['status']===302 && (int)row('SELECT stock FROM products WHERE id=?',[$p['id']])['stock']===11,'Admin edits inventory');
file_put_contents($temp,'<?php echo "bad upload";'); $product['image']=new CURLFile($temp,'image/png','evil.php');
$r=$admin->post('/admin/products.php?edit='.$p['id'],$product); expect(str_contains($r['body'],'Upload a valid JPG'),'Executable upload rejected'); @unlink($temp);
$r=$admin->post('/admin/products.php',['action'=>'delete','id'=>$p['id']]); expect($r['status']===302 && (int)row('SELECT active FROM products WHERE id=?',[$p['id']])['active']===0,'Product delete archives safely');
expect($guest->request('/product.php?id='.$p['id'])['status']===404,'Archived product unavailable to shoppers');
$r=$admin->post('/admin/coupons.php',['code'=>'TEST_'.$suffix,'type'=>'fixed','value'=>'150','minimum'=>'1000','expires_at'=>'2030-12-31','active'=>1]); expect($r['status']===302,'Admin creates coupon');
$c=row('SELECT * FROM coupons WHERE code=?',['TEST_'.$suffix]);
$r=$admin->post('/admin/coupons.php',['id'=>$c['id'],'code'=>$c['code'],'type'=>'fixed','value'=>'150','minimum'=>'1000','expires_at'=>'2030-12-31']); expect($r['status']===302 && (int)row('SELECT active FROM coupons WHERE id=?',[$c['id']])['active']===0,'Admin disables coupon');
$r=$customer->post('/contact.php',['name'=>'Integration Customer','email'=>$email,'subject'=>'Test contact','message'=>'Please help with this test order.']); expect($r['status']===302 && !empty(row('SELECT * FROM contact_messages WHERE email=?',[$email])),'Contact message stored');
// Exercise all demo methods; no raw payment credentials exist in these requests.
foreach(['gcash','maya','card'] as $method) {
    $customer->action(['action'=>'add','id'=>1,'quantity'=>1,'color'=>'Midnight']);
    $r=$customer->request('/checkout.php'); preg_match('/name="checkout_token" value="([a-f0-9]+)"/',$r['body'],$m);
    $checkout['checkout_token']=$m[1]; $checkout['payment_method']=$method;
    $r=$customer->post('/checkout.php',$checkout);
    $demo=row('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[$u['id']]);
    expect($r['status']===302 && $demo['payment_status']==='Demo - unpaid' && $demo['payment_method']===$method,"$method records unpaid demo order");
}
$admin->post('/admin/customers.php',['id'=>$u['id']]); expect($customer->request('/profile.php')['status']===302,'Disabled customer loses authenticated access');
$admin->post('/admin/customers.php',['id'=>$u['id']]);
$customer->request('/login.php'); $r=$customer->post('/login.php',['identity'=>$email,'password'=>$password]); expect($r['status']===302,'Enabled customer can sign in');
$customer->request('/profile.php');
$customer->post('/forgot-password.php',['email'=>$email]);
$log=file_get_contents(dirname(__DIR__).'/storage/mail.log'); preg_match_all('/reset-password.php\?token=([a-f0-9]{64})/',$log,$tokens); $token=end($tokens[1]);
expect((bool)$token,'Local password reset writes delivery outbox');
$reset=new Browser(); $reset->request('/reset-password.php?token='.$token);
$r=$reset->post('/reset-password.php?token='.$token,['password'=>'ResetCustomer@2026!','confirm_password'=>'ResetCustomer@2026!']); expect($r['status']===302,'Password reset succeeds');
expect($customer->request('/profile.php')['status']===302,'Password reset invalidates previous session');
$r=$reset->post('/reset-password.php?token='.$token,['password'=>'ResetCustomer@2026!','confirm_password'=>'ResetCustomer@2026!']); expect(str_contains($r['body'],'expired or already been used'),'Password reset token is single-use');
$customer->request('/login.php'); $r=$customer->post('/login.php',['identity'=>$email,'password'=>'ResetCustomer@2026!']); expect($r['status']===302,'New password authenticates');
$customer->request('/profile.php'); $r=$customer->action(['action'=>'logout']); expect($r['json']['ok'] && $customer->request('/profile.php')['status']===302,'Customer logout revokes access');
echo "\n$passed integration checks passed.\n";
