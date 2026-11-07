<?php
require __DIR__.'/../inc/bootstrap.php'; require_admin(); $db = db(); $note = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
  if ($act === 'read' || $act === 'unread') $db->prepare("UPDATE messages SET is_read=? WHERE id=?")->execute([$act==='read'?1:0, $id]);
  elseif ($act === 'delete') $db->prepare("DELETE FROM messages WHERE id=?")->execute([$id]);
  elseif ($act === 'cv') {
    $f = $_FILES['cv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) $note = 'Upload failed. Choose a PDF under 5 MB.';
    elseif ($f['size'] > 5*1024*1024) $note = 'File too large (max 5 MB).';
    elseif (substr(file_get_contents($f['tmp_name'], false, null, 0, 5), 0, 4) !== '%PDF' || (function_exists('finfo_open') && finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) !== 'application/pdf')) $note = 'Only PDF files are accepted.';
    else {
      $old = latest_cv(); $name = bin2hex(random_bytes(8)).'.pdf';
      if (move_uploaded_file($f['tmp_name'], DATA_DIR.'/uploads/'.$name)) {
        $db->prepare("INSERT INTO files(kind,stored_name,original) VALUES('cv',?,?)")->execute([$name, basename($f['name'])]);
        if ($old) @unlink(DATA_DIR.'/uploads/'.basename($old['stored_name']));
        $note = 'CV updated. The website now serves the new file.';
      } else $note = 'Could not save the file. Check that data/uploads is writable.';
    }
  } elseif ($act === 'password') {
    $u = $db->query("SELECT * FROM admin WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($_POST['old'] ?? '', $u['pass'])) $note = 'Current password is wrong.';
    elseif (strlen($_POST['new'] ?? '') < 10) $note = 'New password must be at least 10 characters.';
    else { $db->prepare("UPDATE admin SET pass=? WHERE id=1")->execute([password_hash($_POST['new'], PASSWORD_DEFAULT)]); $note = 'Password changed.'; }
  }
  if (!$note) { header('Location: index.php'); exit; }
}
$msgs = $db->query("SELECT * FROM messages ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
$unread = (int)$db->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetchColumn(); $cv = latest_cv(); $tok = csrf();
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= $unread ? "($unread) " : '' ?>Admin</title>
<style>body{font:15px/1.5 system-ui;margin:0;background:#f4f6fb;color:#0b1b3a}header{background:#0b1b3a;color:#fff;padding:14px 20px;display:flex;justify-content:space-between;align-items:center}header a{color:#d4ae5a}main{max-width:900px;margin:auto;padding:20px}.c{background:#fff;border:1px solid #dfe4ef;border-radius:10px;padding:16px;margin-bottom:14px}.u{border-left:5px solid #d4ae5a}h2{margin:0 0 10px;font-size:18px}button,input{font:inherit;padding:7px 12px;border-radius:7px;border:1px solid #ccd}button{cursor:pointer;background:#fff}.p{background:#d4ae5a;border:0;font-weight:700}small{color:#58657f}pre{white-space:pre-wrap;font:inherit;margin:8px 0}form.i{display:inline}.n{background:#fff7e0;border:1px solid #d4ae5a;padding:10px;border-radius:8px;margin-bottom:14px}</style></head><body>
<header><b>Portfolio admin <?= $unread ? '· '.$unread.' unread' : '' ?></b><span><a href="../" target="_blank">View site</a> · <a href="logout.php">Log out</a></span></header><main>
<?php if($note): ?><div class="n"><?= h($note) ?></div><?php endif; ?>
<div class="c"><h2>Your CV</h2><p><?= $cv ? 'Current file: <b>'.h($cv['original']).'</b> <small>(uploaded '.h($cv['uploaded_at']).' UTC)</small> · <a href="../cv.php?view=1" target="_blank">Preview</a>' : 'No CV uploaded yet. The website\'s download button will show "not available" until you upload one.' ?></p>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= h($tok) ?>"><input type="hidden" name="action" value="cv"><input type="file" name="cv" accept="application/pdf" required> <button class="p">Upload / replace CV</button></form></div>
<div class="c"><h2>Messages (<?= count($msgs) ?>)</h2><?php if(!$msgs) echo '<p><small>No messages yet.</small></p>'; ?></div>
<?php foreach($msgs as $m): ?><div class="c <?= $m['is_read'] ? '' : 'u' ?>"><b><?= h($m['subject']) ?></b><br><small><?= h($m['name']) ?> · <a href="mailto:<?= h($m['email']) ?>?subject=<?= rawurlencode('Re: '.$m['subject']) ?>"><?= h($m['email']) ?></a> · <?= h($m['created_at']) ?> UTC</small><pre><?= h($m['message']) ?></pre>
<form class="i" method="post"><input type="hidden" name="csrf" value="<?= h($tok) ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button name="action" value="<?= $m['is_read']?'unread':'read' ?>"><?= $m['is_read']?'Mark unread':'Mark read' ?></button>
<button name="action" value="delete" onclick="return confirm('Delete this message?')">Delete</button></form></div><?php endforeach; ?>
<div class="c"><h2>Change password</h2><form method="post"><input type="hidden" name="csrf" value="<?= h($tok) ?>"><input type="hidden" name="action" value="password"><input type="password" name="old" placeholder="Current password" required> <input type="password" name="new" placeholder="New password (10+ chars)" required> <button class="p">Change</button></form></div>
</main></body></html>
