<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/database.php';
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((int)$n,0,',','.').'đ'; }
function csrf_token(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(){ return '<input type="hidden" name="csrf" value="'.csrf_token().'">'; }
function csrf_check(){ if(!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')){ http_response_code(419); die('CSRF token không hợp lệ.'); } }
function current_user(){ global $pdo;
  if(empty($_SESSION['uid'])) return null;
  $s=$pdo->prepare("SELECT * FROM users WHERE id=? AND status='active'"); $s->execute([$_SESSION['uid']]);
  $u=$s->fetch(); if(!$u){ session_destroy(); return null; } return $u; }
function require_login(){ $u=current_user(); if(!$u){ header('Location: /quan-ly-san-bong/login.php'); exit; } return $u; }
// Kiểm tra quyền Admin ở phía máy chủ
function require_admin(){ $u=require_login(); if($u['role']!=='admin'){ http_response_code(403); header('Location: /quan-ly-san-bong/index.php'); exit; } return $u; }
function flash($type,$msg){ $_SESSION['flash']=[$type,$msg]; }
function notify($uid,$msg){ global $pdo; $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES(?,?)")->execute([$uid,$msg]); }
function img_src($p){ return preg_match('#^https?://#',$p)?$p:'/quan-ly-san-bong/'.ltrim((string)$p,'/'); }
// Khoảng ngày lọc (mặc định 30 ngày gần nhất), có kiểm tra định dạng
function get_range(){ $f=$_GET['from']??''; $t=$_GET['to']??''; $ok=fn($d)=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)&&strtotime($d);
  if(!$ok($f)) $f=date('Y-m-d',strtotime('-29 day')); if(!$ok($t)) $t=date('Y-m-d'); if($f>$t) [$f,$t]=[$t,$f]; return [$f,$t]; }
function range_form($extra=''){ [$f,$t]=get_range(); return '<form class="sbar" method="get" style="background:#fff;margin:0 0 14px"><label>Từ <input type="date" name="from" value="'.$f.'"></label><label>Đến <input type="date" name="to" value="'.$t.'"></label>'.$extra.'<button class="btn">Lọc</button></form>'; }
