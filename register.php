<?php
require_once 'includes/auth.php';
$err=[]; $d=['username'=>'','full_name'=>'','phone'=>'','email'=>''];
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  foreach($d as $k=>$_) $d[$k]=trim($_POST[$k]??'');
  $p=$_POST['password']??'';
  if(!preg_match('/^[A-Za-z0-9_]{4,30}$/',$d['username'])) $err[]='Tên đăng nhập 4-30 ký tự (chữ, số, _).';
  if(mb_strlen($d['full_name'])<2) $err[]='Vui lòng nhập họ tên.';
  if(!preg_match('/^0\d{9}$/',$d['phone'])) $err[]='Số điện thoại phải gồm 10 số, bắt đầu bằng 0.';
  if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $err[]='Email không hợp lệ.';
  if(strlen($p)<6) $err[]='Mật khẩu tối thiểu 6 ký tự.';
  if($p!==($_POST['password2']??'')) $err[]='Mật khẩu nhập lại không khớp.';
  if(!$err){
    $s=$pdo->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?"); $s->execute([$d['username'],$d['email']]);
    if($s->fetchColumn()) $err[]='Tên đăng nhập hoặc email đã tồn tại.';
    else { $pdo->prepare("INSERT INTO users(username,password,full_name,phone,email) VALUES(?,?,?,?,?)")
      ->execute([$d['username'],password_hash($p,PASSWORD_DEFAULT),$d['full_name'],$d['phone'],$d['email']]);
      flash('ok','Đăng ký thành công, hãy đăng nhập.'); header('Location: login.php'); exit; }
  }
}
?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng ký</title>
<link rel="stylesheet" href="assets/css/style.css"></head><body class="auth"><form class="auth-box" method="post"><h2>⚽ Đăng ký</h2>
<?php foreach($err as $m): ?><div class="alert err"><?=e($m)?></div><?php endforeach; ?><?=csrf_field()?>
<label>Tên đăng nhập</label><input name="username" required value="<?=e($d['username'])?>"><label>Họ tên</label><input name="full_name" required value="<?=e($d['full_name'])?>">
<label>Số điện thoại</label><input name="phone" required value="<?=e($d['phone'])?>"><label>Email</label><input type="email" name="email" required value="<?=e($d['email'])?>">
<label>Mật khẩu</label><input type="password" name="password" required><label>Nhập lại mật khẩu</label><input type="password" name="password2" required>
<button class="btn">Tạo tài khoản</button><p><a href="login.php">Đã có tài khoản? Đăng nhập</a></p></form></body></html>
