<?php
require __DIR__.'/../inc/bootstrap.php'; start_session(); $db = db();
$setup = !$db->query("SELECT COUNT(*) FROM admin")->fetchColumn(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $a = $db->prepare("SELECT COUNT(*) FROM attempts WHERE ip=? AND created_at>(NOW() - INTERVAL 15 MINUTE)"); $a->execute([$ip]);
  if ($a->fetchColumn() >= 5) { $err = 'Too many attempts. Wait 15 minutes.'; }
  elseif ($setup) {
    if (SETUP_CODE === 'change-me') $err = 'Set SETUP_CODE in inc/bootstrap.php first.';
    elseif (!hash_equals(SETUP_CODE, $_POST['code'] ?? '')) $err = 'Wrong setup code.';
    elseif (strlen($_POST['password'] ?? '') < 10 || trim($_POST['username'] ?? '') === '') $err = 'Username required; password at least 10 characters.';
    else { $db->prepare("INSERT INTO admin(id,username,pass) VALUES(1,?,?)")->execute([trim($_POST['username']), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
      session_regenerate_id(true); $_SESSION['admin'] = 1; header('Location: index.php'); exit; }
  } else {
    $u = $db->query("SELECT * FROM admin WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    if ($u && $u['username'] === ($_POST['username'] ?? '') && password_verify($_POST['password'] ?? '', $u['pass'])) {
      session_regenerate_id(true); $_SESSION['admin'] = 1; header('Location: index.php'); exit; }
    $err = 'Incorrect username or password.';
  }
  if ($err) { $db->prepare("INSERT INTO attempts(ip) VALUES(?)")->execute([$ip]); }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login</title>
<style>body{font:16px system-ui;background:#0b1b3a;display:grid;place-items:center;min-height:100vh;margin:0}form{background:#fff;padding:28px;border-radius:12px;width:min(340px,90vw);display:grid;gap:12px}h1{margin:0;font-size:20px;color:#0b1b3a}input,button{padding:10px;font-size:15px;border-radius:8px;border:1px solid #ccd}button{background:#d4ae5a;border:0;font-weight:700;cursor:pointer}.e{color:#b00020;font-size:14px}</style></head><body>
<form method="post"><h1><?= $setup ? 'Create admin account' : 'Admin login' ?></h1><?php if($err): ?><div class="e"><?= h($err) ?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
<?php if($setup): ?><input name="code" type="password" placeholder="Setup code" required><?php endif; ?>
<input name="username" placeholder="Username" required autocomplete="username"><input name="password" type="password" placeholder="Password" required autocomplete="<?= $setup?'new-password':'current-password' ?>">
<button>Continue</button></form></body></html>
