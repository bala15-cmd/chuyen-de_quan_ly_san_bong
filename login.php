<?php
require_once 'includes/auth.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $s=$pdo->prepare("SELECT * FROM users WHERE username=?"); $s->execute([trim($_POST['username']??'')]);
  $u=$s->fetch();
  if(!$u || !password_verify($_POST['password']??'',$u['password'])) $err='Sai tên đăng nhập hoặc mật khẩu.';
  elseif($u['status']!=='active') $err='Tài khoản đã bị khóa.';
  else { session_regenerate_id(true); $_SESSION['uid']=$u['id'];
    header('Location: '.($u['role']==='admin'?'admin/dashboard.php':'index.php')); exit; }
}
?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng nhập - Sân Bóng</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth"><form class="auth-box" method="post"><h2>⚽ Đăng nhập</h2>
<?php if(!empty($_SESSION["flash"])){ echo "<div class=\"alert ok\">".e($_SESSION["flash"][1])."</div>"; unset($_SESSION["flash"]); } if($err): ?><div class="alert err"><?=e($err)?></div><?php endif; ?>
<?=csrf_field()?>
<label>Tên đăng nhập</label><input name="username" required value="<?=e($_POST['username']??'')?>">
<label>Mật khẩu</label><input type="password" name="password" required>
<button class="btn" type="submit">Đăng nhập</button>
<p><a href="register.php">Tạo tài khoản</a> &nbsp;|&nbsp; <a href="index.php">← Trang chủ</a></p><small>Demo: admin / password &nbsp;|&nbsp; nguyenvana / password</small></form></body></html>
