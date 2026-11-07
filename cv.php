<?php
require __DIR__.'/inc/bootstrap.php';
$f = latest_cv(); $path = $f ? DATA_DIR.'/uploads/'.basename($f['stored_name']) : '';
if (!$f || !is_file($path)) { http_response_code(404); exit('CV not available yet.'); }
header('Content-Type: application/pdf');
header('Content-Disposition: '.(isset($_GET['view']) ? 'inline' : 'attachment').'; filename="Paul_Kihumba_Mbarire_CV.pdf"');
header('Content-Length: '.filesize($path)); header('X-Content-Type-Options: nosniff');
readfile($path);
