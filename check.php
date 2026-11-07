<?php
// DIAGNOSTIC PAGE. Put in C:\xampp\htdocs\myweb\ and open http://localhost/myweb/check.php (localhost only). Delete it when done.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1','::1'], true)) { http_response_code(403); exit('Localhost only.'); }
$root = __DIR__; $rows = [];
function add(&$rows, $ok, $what, $fix = '') { $rows[] = [$ok, $what, $fix]; }
// 1. files
foreach (['index.html','cv.php','admin/index.php','admin/login.php','admin/logout.php','api/contact.php','inc/bootstrap.php','data/.htaccess','inc/.htaccess'] as $f)
  add($rows, is_file("$root/$f"), "File $f", "Missing. Reinstall with install.php or extract portfolio.zip into this folder.");
foreach (['login_1.php','index_1.php','logout_1.php','schema.sql'] as $f)
  if (is_file("$root/$f") || is_file("$root/admin/$f")) add($rows, false, "Leftover old file $f", "Delete it.");
add($rows, !is_file("$root/.htaccess"), "No stray .htaccess in myweb root", "Delete myweb\\.htaccess (it causes 403 Forbidden).");
// 2. php
add($rows, version_compare(PHP_VERSION, '7.4', '>='), "PHP version ".PHP_VERSION, "Need 7.4 or newer.");
add($rows, extension_loaded('pdo_mysql'), "PHP extension pdo_mysql", "In C:\\xampp\\php\\php.ini remove the ; before extension=pdo_mysql, then restart Apache.");
add($rows, ini_get('file_uploads'), "File uploads enabled", "Set file_uploads=On in php.ini and restart Apache.");
$up = ini_get('upload_max_filesize'); $pm = ini_get('post_max_size');
add($rows, true, "Upload limits: upload_max_filesize=$up, post_max_size=$pm (CV limit is 5 MB)");
add($rows, true, "Extension fileinfo: ".(extension_loaded('fileinfo') ? 'on' : 'off (optional, PDF check still works)'));
// 3. settings + database
if (is_file("$root/inc/bootstrap.php")) {
  $src = file_get_contents("$root/inc/bootstrap.php");
  add($rows, strpos($src, "'change-me'") === false, "SETUP_CODE changed from default", "Open inc\\bootstrap.php and replace change-me with your own secret (or run install.php).");
  add($rows, strpos($src, 'sqlite') === false && strpos($src, 'mysql:') !== false, "bootstrap.php is the MySQL version", "It is the OLD SQLite version. Reinstall with install.php.");
  try {
    require_once "$root/inc/bootstrap.php"; $db = db();
    add($rows, true, "Database connection OK (".DB_NAME." on ".DB_HOST.")");
    $need = ['messages','admin','files','attempts']; $have = [];
    foreach ($db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM) as $t) $have[] = $t[0];
    foreach ($need as $t) add($rows, in_array($t, $have, true), "Table $t exists", "Open any page once, or run database.sql.");
    $cols = array_column($db->query("SHOW COLUMNS FROM files")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    add($rows, in_array('stored_name', $cols, true), "files table has column stored_name", "Old table layout. In phpMyAdmin run: DROP DATABASE portfolio; then reload any page.");
    $hasAdmin = (int)$db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
    add($rows, true, $hasAdmin ? "Admin account exists (log in at admin/login.php)" : "No admin account yet (admin/login.php will show 'Create admin account')");
    $m = (int)$db->query("SELECT COUNT(*) FROM messages")->fetchColumn(); $u = (int)$db->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetchColumn();
    add($rows, true, "Messages stored: $m ($u unread)");
    $cv = latest_cv(); add($rows, true, $cv ? "CV uploaded: ".$cv['original'] : "No CV uploaded yet");
  } catch (Throwable $e) {
    add($rows, false, "Database error: ".$e->getMessage(), "Start MySQL in XAMPP. If the login differs, edit DB_USER / DB_PASS / DB_HOST in inc\\bootstrap.php (if Workbench uses port 3307 use DB_HOST '127.0.0.1:3307').");
  }
}
$up = "$root/data/uploads"; if (!is_dir($up)) @mkdir($up, 0775, true);
add($rows, is_dir($up) && is_writable($up), "data\\uploads exists and is writable", "Create the folder data\\uploads, and give 'Users' Modify permission (right-click, Properties, Security).");
add($rows, true, "Email alerts: ".(ini_get('sendmail_path') || ini_get('SMTP') ? 'mail() is configured in php.ini (delivery still depends on your mail server)' : 'not configured. Messages will show in the dashboard only'));
$bad = count(array_filter($rows, fn($r) => !$r[0]));
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Site check</title><style>body{font:15px system-ui;max-width:820px;margin:30px auto;padding:0 14px}li{margin:6px 0}.ok{color:#0a7a2f}.no{color:#b00020}small{display:block;color:#444;margin-left:22px}</style></head><body>
<h1><?= $bad ? "$bad problem".($bad>1?'s':'')." found" : 'Everything looks good' ?></h1><ul style="list-style:none;padding:0">
<?php foreach ($rows as [$ok,$what,$fix]): ?><li class="<?= $ok?'ok':'no' ?>"><?= $ok?'✔':'✘' ?> <?= htmlspecialchars($what) ?><?php if(!$ok && $fix): ?><small>Fix: <?= htmlspecialchars($fix) ?></small><?php endif; ?></li><?php endforeach; ?></ul>
<p>Next: <a href="admin/login.php">admin login</a> · <a href="index.html">website</a>. Delete check.php when finished.</p></body></html>
