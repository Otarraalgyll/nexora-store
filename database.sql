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
CREATE TABLE categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE, image VARCHAR(255) NOT NULL DEFAULT 'assets/images/electronics.svg') ENGINE=InnoDB;
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
INSERT INTO categories(name,image) VALUES ('Clothing','assets/images/clothing.svg'),('Shoes','assets/images/shoes.svg'),('Electronics','assets/images/electronics.svg'),('Accessories','assets/images/accessories.svg'),('Gaming','assets/images/gaming.svg'),('Bags','assets/images/bags.svg');
INSERT INTO products(category_id,name,description,keywords,price,sale_price,stock,image,sizes,colors) VALUES
(3,'Aura Wireless Headphones','Find your focus. Cushioned over-ear headphones with rich audio, wireless connectivity and a fold-flat silhouette. Includes a charging cable and protective pouch.','audio music bluetooth headphones',3490,2790,30,'assets/images/electronics.svg','','Midnight,Silver'),
(2,'Orbit Everyday Sneakers','Move your own way. Lightweight everyday sneakers with a cushioned sole, breathable upper and sculpted profile.','trainers footwear casual',2990,2490,40,'assets/images/shoes.svg','38,39,40,41,42,43','Cloud,Midnight'),
(1,'Essential Oversized Hoodie','Your off-duty uniform. A relaxed-fit cotton blend hoodie with a spacious front pocket, soft brushed lining and clean tonal detailing.','streetwear sweatshirt fashion',1890,NULL,35,'assets/images/clothing.svg','S,M,L,XL','Lilac,Midnight'),
(5,'Nova Mechanical Keyboard','Make every keystroke count. A compact mechanical keyboard with tactile switches, adjustable lighting and a detachable USB cable.','keyboard rgb desk mechanical',3990,3290,18,'assets/images/gaming.svg','','Midnight,Ice'),
(4,'Pulse Smart Watch','A fresh perspective on your day. A lightweight watch with activity tracking, customizable faces and a comfortable silicone band.','wearable watch fitness',2490,1990,25,'assets/images/accessories.svg','','Midnight,Silver'),
(6,'Metro Everyday Backpack','Ready for what is next. An organized daily backpack with a padded laptop compartment, adjustable straps and a water-resistant outer shell.','bag travel laptop commuter',2290,NULL,22,'assets/images/bags.svg','','Midnight,Slate'),
(3,'Aura Studio Headphones','Your personal listening space. Over-ear wireless headphones with deep ear cushions and a refined metallic finish.','audio studio sound',4490,3990,12,'assets/images/electronics.svg','','Silver'),
(2,'Velocity Street Sneakers','Take the long way home. Everyday sneakers with sculpted cushioning, textured panels and an easy-to-style monochrome finish.','running shoes sports',3290,NULL,16,'assets/images/shoes.svg','38,39,40,41,42,43','Cloud'),
(1,'Weekend Cloud Hoodie','A softer kind of statement. An oversized hoodie with a dropped shoulder, lined hood and generously cut sleeves.','cotton hoodie casual',2190,1790,28,'assets/images/clothing.svg','S,M,L,XL','Lilac'),
(5,'Nova Pro Keyboard','Built for your next level. A compact wired keyboard with a sturdy case, colorful backlighting and a comfortable typing angle.','gaming rgb keyboard computer',4990,NULL,7,'assets/images/gaming.svg','','Midnight'),
(4,'Pulse Active Watch','Keep your day in rhythm. A lightweight everyday watch with a clear display, timer and interchangeable silicone band.','accessory fitness wearable',1990,NULL,5,'assets/images/accessories.svg','','Silver,Midnight'),
(6,'Weekender Explorer Bag','Carry a little more possibility. A spacious backpack with internal organizers and comfortable padded shoulder straps.','travel luggage backpack',2790,2390,14,'assets/images/bags.svg','','Slate');
INSERT INTO product_images(product_id,image) SELECT id,image FROM products;
INSERT INTO product_images(product_id,image) SELECT id,REPLACE(image,'.svg','-detail.svg') FROM products;
INSERT INTO coupons(code,type,value,minimum,expires_at) VALUES ('WELCOME10','percent',10,1000,'2030-12-31');
