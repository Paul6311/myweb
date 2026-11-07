<?php
// ===== SETTINGS: edit these =====
const ADMIN_EMAIL = 'paulmbarire0@gmail.com';   // new-message alerts go here
const SETUP_CODE  = 'paulkihumbambarire';                // choose a secret code; needed once to create your admin account
// MySQL / MariaDB (XAMPP defaults: user root, empty password)
const DB_HOST = '127.0.0.1';
const DB_NAME = 'portfolio';
const DB_USER = 'root';
const DB_PASS = '';
// ================================
const DATA_DIR = __DIR__ . '/../data';
function start_session() {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_name('pkm_admin');
  session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])]);
  session_start();
}
function db() {
  static $p;
  if ($p) return $p;
  if (!is_dir(DATA_DIR.'/uploads')) mkdir(DATA_DIR.'/uploads', 0775, true);
  $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
  try { $p = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, $opt); }
  catch (PDOException $e) {
    if ((int)$e->getCode() !== 1049) throw $e;            // 1049 = database does not exist yet: create it
    $x = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS, $opt);
    $x->exec('CREATE DATABASE IF NOT EXISTS `'.DB_NAME.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $p = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, $opt);
  }
  $p->exec("SET time_zone='+00:00'");
  foreach ([
   "CREATE TABLE IF NOT EXISTS messages(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100),email VARCHAR(255),subject VARCHAR(255),message TEXT,ip VARCHAR(45),is_read TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX(ip,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS admin(id INT PRIMARY KEY,username VARCHAR(100),pass VARCHAR(255)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS files(id INT AUTO_INCREMENT PRIMARY KEY,kind VARCHAR(20),stored_name VARCHAR(100),original VARCHAR(255),uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS attempts(ip VARCHAR(45),created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX(ip,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  ] as $q) $p->exec($q);
  return $p;
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf() { start_session(); return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(16))); }
function check_csrf() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad request'); } }
function require_admin() { start_session(); if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; } }
function latest_cv() { return db()->query("SELECT * FROM files WHERE kind='cv' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC); }
