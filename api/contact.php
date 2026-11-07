<?php
require __DIR__.'/../inc/bootstrap.php';
header('Content-Type: application/json');
function out($ok, $code = 200, $err = '') { http_response_code($code); exit(json_encode(['ok'=>$ok,'error'=>$err])); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 405, 'POST only');
$d = json_decode(file_get_contents('php://input'), true) ?: [];
if (!empty($d['hp'])) out(true);                       // honeypot: bots filled the hidden field
$n = trim($d['name'] ?? ''); $e = trim($d['email'] ?? ''); $s = trim($d['subject'] ?? ''); $m = trim($d['message'] ?? '');
if (!$n || !$s || !$m || !filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($n)>320 || strlen($e)>254 || strlen($s)>480 || strlen($m)>8000) out(false, 422, 'Invalid input');
$ip = $_SERVER['REMOTE_ADDR'] ?? ''; $db = db();
$c = $db->prepare("SELECT COUNT(*) FROM messages WHERE ip=? AND created_at>(NOW() - INTERVAL 1 HOUR)"); $c->execute([$ip]);
if ($c->fetchColumn() >= 5) out(false, 429, 'Too many messages, try later');
$db->prepare("INSERT INTO messages(name,email,subject,message,ip) VALUES(?,?,?,?,?)")->execute([$n,$e,$s,$m,$ip]);
// email alert (needs mail() enabled on your host; the message is saved in the dashboard either way)
$host = preg_replace('/[^a-z0-9.\-]/i', '', preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$clean = fn($x) => str_replace(["\r","\n"], ' ', $x);
@mail(ADMIN_EMAIL, '[Portfolio] '.$clean($s), "From: ".$clean($n)." <".$e.">\n\n".$m, "From: noreply@$host\r\nReply-To: ".$clean($e));
out(true);
