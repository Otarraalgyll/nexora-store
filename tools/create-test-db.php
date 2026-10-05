<?php
// Creates a NEW test database only. Never drops or resets an existing database.
if(PHP_SAPI!=='cli') { http_response_code(403); exit; }
$name=getenv('DB_NAME') ?: '';
if(!preg_match('/^[a-zA-Z0-9_]+_test$/',$name)) exit("DB_NAME must end in _test.\n");
$host=getenv('DB_HOST') ?: '127.0.0.1'; $port=getenv('DB_PORT') ?: '3306';
$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",getenv('DB_USER') ?: 'root',getenv('DB_PASS') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$s=$pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?'); $s->execute([$name]);
if($s->fetch()) exit("Database already exists. Choose a fresh *_test name.\n");
$sql=str_replace('ecommerce_db',$name,file_get_contents(dirname(__DIR__).'/database.sql'));
$pdo->exec($sql);
echo "Created $name. Start PHP with the same DB_NAME and run tests/integration.php.\n";
