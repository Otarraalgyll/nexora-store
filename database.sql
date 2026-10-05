-- NEXORA STORE: import into phpMyAdmin. MySQL 8+ / MariaDB 10.6+.
CREATE DATABASE IF NOT EXISTS ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_db;
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(100) NOT NULL,
 username VARCHAR(40) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL UNIQUE,
 phone VARCHAR(30) NOT NULL, password VARCHAR(255) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, must_change_password TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB;
CREATE TABLE categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE, image VARCHAR(255) NOT NULL DEFAULT 'assets/images/electronics-photo.png') ENGINE=InnoDB;
CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NULL,
 name VARCHAR(150) NOT NULL, description TEXT NOT NULL, keywords VARCHAR(255) NOT NULL DEFAULT '',
 price DECIMAL(12,2) NOT NULL, sale_price DECIMAL(12,2) NULL, stock INT UNSIGNED NOT NULL DEFAULT 0,
 image VARCHAR(255) NOT NULL, sizes VARCHAR(255) NOT NULL DEFAULT '', colors VARCHAR(255) NOT NULL DEFAULT '',
 rating DECIMAL(3,2) NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 INDEX idx_product_active_category (active,category_id), CHECK(price>=0), CHECK(sale_price IS NULL OR (sale_price>=0 AND sale_price<=price))
) ENGINE=InnoDB;
CREATE TABLE product_images (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, image VARCHAR(255) NOT NULL, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE cart (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, quantity INT UNSIGNED NOT NULL DEFAULT 1, size VARCHAR(40) NOT NULL DEFAULT '', color VARCHAR(40) NOT NULL DEFAULT '', UNIQUE KEY cart_variant(user_id,product_id,size,color), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE wishlist (user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, PRIMARY KEY(user_id,product_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE addresses (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL UNIQUE, province VARCHAR(100) NOT NULL, city VARCHAR(100) NOT NULL, barangay VARCHAR(100) NOT NULL, street VARCHAR(255) NOT NULL, postal_code VARCHAR(12) NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE coupons (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(40) NOT NULL UNIQUE, type ENUM('percent','fixed') NOT NULL DEFAULT 'percent', value DECIMAL(12,2) NOT NULL, minimum DECIMAL(12,2) NOT NULL DEFAULT 0, expires_at DATE NOT NULL, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB;
CREATE TABLE orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, order_number VARCHAR(40) NOT NULL UNIQUE,
 full_name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(30) NOT NULL,
 shipping_address TEXT NOT NULL, subtotal DECIMAL(12,2) NOT NULL, shipping DECIMAL(12,2) NOT NULL,
 discount DECIMAL(12,2) NOT NULL DEFAULT 0, total DECIMAL(12,2) NOT NULL, coupon_code VARCHAR(40) NULL,
 payment_method ENUM('cod','gcash','maya','card') NOT NULL,
 payment_status ENUM('Due on delivery','Demo - unpaid') NOT NULL,
 status ENUM('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
 checkout_token CHAR(64) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), INDEX idx_orders_created(created_at)
) ENGINE=InnoDB;
CREATE TABLE order_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL, product_name VARCHAR(150) NOT NULL, price DECIMAL(12,2) NOT NULL, quantity INT UNSIGNED NOT NULL, size VARCHAR(40) NOT NULL DEFAULT '', color VARCHAR(40) NOT NULL DEFAULT '', FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE reviews (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, rating TINYINT UNSIGNED NOT NULL, comment TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY review_user_product(user_id,product_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, CHECK(rating BETWEEN 1 AND 5)) ENGINE=InnoDB;
CREATE TABLE contact_messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL, subject VARCHAR(150) NOT NULL, message TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE password_resets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE rate_limits (bucket CHAR(64) PRIMARY KEY, attempts INT NOT NULL DEFAULT 1, started_at DATETIME NOT NULL) ENGINE=InnoDB;
-- Local installation login: admin@nexora.local / Nexora@2026!
-- The dashboard requires changing this password on first login.
INSERT INTO admins(name,email,password) VALUES ('Nexora Admin','admin@nexora.local','$2y$12$2OcFvgthGJhExsw2U6Jt5eI6n2vlH3UhDsq.ntFSBTMNXZ58Sl1n.');
INSERT INTO categories(name,image) VALUES ('Clothing','assets/images/clothing-photo.png'),('Shoes','assets/images/shoes-photo.png'),('Electronics','assets/images/electronics-photo.png'),('Accessories','assets/images/accessories-photo.png'),('Gaming','assets/images/gaming-photo.png'),('Bags','assets/images/bags-photo.png');
-- 25 distinct demonstration products; prices and stock are sample values.
INSERT INTO products(id,category_id,name,description,keywords,price,sale_price,stock,image,sizes,colors) VALUES
('1','3','Aura Wireless Headphones','Find your focus. Cushioned over-ear headphones with rich audio, wireless connectivity and a fold-flat silhouette. Includes a charging cable and protective pouch.','audio music bluetooth headphones','3490.00','2790.00','30','assets/images/electronics-photo.png','','Midnight,Silver'),
('2','2','Orbit Everyday Sneakers','Move your own way. Lightweight everyday sneakers with a cushioned sole, breathable upper and sculpted profile.','trainers footwear casual','2990.00','2490.00','40','assets/images/shoes-photo.png','38,39,40,41,42,43','Cloud,Midnight'),
('3','1','Essential Oversized Hoodie','Your off-duty uniform. A relaxed-fit cotton blend hoodie with a spacious front pocket, soft brushed lining and clean tonal detailing.','streetwear sweatshirt fashion','1890.00',NULL,'35','assets/images/clothing-photo.png','S,M,L,XL','Lilac,Midnight'),
('4','5','Nova Mechanical Keyboard','Make every keystroke count. A compact mechanical keyboard with tactile switches, adjustable lighting and a detachable USB cable.','keyboard rgb desk mechanical','3990.00','3290.00','18','assets/images/gaming-photo.png','','Midnight,Ice'),
('5','4','Pulse Smart Watch','A fresh perspective on your day. A lightweight watch with activity tracking, customizable faces and a comfortable silicone band.','wearable watch fitness','2490.00','1990.00','25','assets/images/accessories-photo.png','','Midnight,Silver'),
('6','6','Metro Everyday Backpack','Ready for what is next. An organized daily backpack with a padded laptop compartment, adjustable straps and a water-resistant outer shell.','bag travel laptop commuter','2290.00',NULL,'22','assets/images/bags-photo.png','','Midnight,Slate'),
('26','1','Blue & Black Check Shirt','A blue-and-black checked button-up shirt with a classic collar. Pair with denim for an easy casual outfit.','Clothing Blue & Black Check Shirt','1290.00',NULL,'20','assets/images/catalog-83.webp','S,M,L,XL',''),
('27','1','Aorus Graphic T-Shirt','A graphic crew-neck T-shirt with a relaxed everyday look. A simple choice for casual outfits and gaming fans.','Clothing Aorus Graphic T-Shirt','790.00',NULL,'20','assets/images/catalog-84.webp','S,M,L,XL',''),
('28','1','Short-Sleeve Casual Shirt','A short-sleeve collared shirt for an easy everyday outfit. Wear on its own or as a light layer over a tee.','Clothing Short-Sleeve Casual Shirt','990.00',NULL,'20','assets/images/catalog-86.webp','S,M,L,XL',''),
('29','1','Black Evening Gown','A black full-length gown with an elegant evening silhouette. A dressier addition to the collection.','Clothing Black Evening Gown','2490.00',NULL,'20','assets/images/catalog-177.webp','S,M,L,XL',''),
('30','1','Summer Day Dress','A lightweight-looking summer dress with a casual silhouette. An easy outfit for daytime occasions.','Clothing Summer Day Dress','1190.00',NULL,'20','assets/images/catalog-163.webp','S,M,L,XL',''),
('31','2','Two-Tone Casual Sandals','Open-toe sandals in black and brown tones, with a simple slip-on design for relaxed everyday styling.','Shoes Two-Tone Casual Sandals','890.00',NULL,'20','assets/images/catalog-185.webp','38,39,40,41,42,43',''),
('32','2','Classic Pampi Shoes','Dress shoes with a polished silhouette for occasions when your outfit needs a more formal finish.','Shoes Classic Pampi Shoes','1790.00',NULL,'20','assets/images/catalog-188.webp','38,39,40,41,42,43',''),
('33','2','Nike Baseball Cleats','A pair of Nike baseball cleats with a sport-specific silhouette. Sample listing; confirm the exact model and sizing before a real purchase.','Shoes Nike Baseball Cleats','3990.00',NULL,'20','assets/images/catalog-89.webp','38,39,40,41,42,43',''),
('34','3','Apple MacBook Pro 14-inch','A space-grey 14-inch MacBook Pro sample listing. Exact processor, memory and storage configuration must be confirmed before a real purchase.','Electronics Apple MacBook Pro 14-inch','79990.00',NULL,'20','assets/images/catalog-78.webp','',''),
('35','3','Amazon Echo Plus','An Amazon Echo Plus smart speaker sample listing, with a cylindrical design suited to a desk or shelf.','Electronics Amazon Echo Plus','5990.00',NULL,'20','assets/images/catalog-99.webp','',''),
('36','3','Apple AirPods','Apple wireless earbuds with a compact charging case. This sample listing does not specify a generation or configuration.','Electronics Apple AirPods','7990.00',NULL,'20','assets/images/catalog-100.webp','',''),
('37','3','Apple iPhone Charger','A compact Apple phone charger sample listing. Confirm connector and power compatibility before a real purchase.','Electronics Apple iPhone Charger','1290.00',NULL,'20','assets/images/catalog-104.webp','',''),
('38','3','Apple MagSafe Battery Pack','An Apple MagSafe battery pack sample listing. Check device compatibility and supplier specifications before purchase.','Electronics Apple MagSafe Battery Pack','5490.00',NULL,'20','assets/images/catalog-105.webp','',''),
('39','4','iPhone 12 Plum Silicone Case','A plum-colored iPhone 12 silicone case with MagSafe styling. A colorful everyday accessory for a matching phone.','Accessories iPhone 12 Plum Silicone Case','1990.00',NULL,'20','assets/images/catalog-108.webp','',''),
('40','4','Black Frame Sunglasses','Black-framed sunglasses with dark lenses and a clean everyday silhouette. No UV protection rating is asserted for this demo listing.','Accessories Black Frame Sunglasses','690.00',NULL,'20','assets/images/catalog-154.webp','',''),
('41','4','Camera Monopod','A single-leg camera support sample, designed for a compact carry. Confirm mounting compatibility and load capacity with the supplier.','Accessories Camera Monopod','990.00',NULL,'20','assets/images/catalog-109.webp','',''),
('42','6','Blue Everyday Handbag','A blue handbag with a structured everyday silhouette. A colorful alternative to a backpack for small essentials.','Bags Blue Everyday Handbag','1690.00',NULL,'20','assets/images/catalog-172.webp','',''),
('43','6','White Faux-Leather Backpack','A white faux-leather backpack with a compact silhouette. An everyday accessory with a bright, minimal finish.','Bags White Faux-Leather Backpack','1890.00',NULL,'20','assets/images/catalog-175.webp','',''),
('44','6','Black City Handbag','A black handbag with a versatile shape for daily styling. Pair with casual or dressier outfits.','Bags Black City Handbag','2190.00',NULL,'20','assets/images/catalog-176.webp','','');
INSERT INTO product_images(product_id,image) SELECT id,image FROM products;
INSERT INTO coupons(code,type,value,minimum,expires_at) VALUES ('WELCOME10','percent',10,1000,'2030-12-31');
