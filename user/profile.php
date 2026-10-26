<?php
require_once '../includes/auth.php';
$u=require_login(); if($u['role']==='admin'){ header('Location: ../admin/settings.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $a=$_POST['action']??'';
  if($a==='profile'){
    $n=trim($_POST['full_name']??''); $p=trim($_POST['phone']??''); $m=trim($_POST['email']??'');
    $s=$pdo->prepare("SELECT COUNT(*) FROM users WHERE email=? AND id<>?"); $s->execute([$m,$u['id']]);
    if(mb_strlen($n)<2||!preg_match('/^0\d{9}$/',$p)||!filter_var($m,FILTER_VALIDATE_EMAIL)) flash('err','Thông tin không hợp lệ (SĐT 10 số bắt đầu bằng 0, email đúng định dạng).');
    elseif($s->fetchColumn()) flash('err','Email đã được sử dụng.');
    else { $pdo->prepare("UPDATE users SET full_name=?,phone=?,email=? WHERE id=?")->execute([$n,$p,$m,$u['id']]); flash('ok','Đã cập nhật thông tin cá nhân.'); }
  } elseif($a==='password'){
    $new=$_POST['new']??'';
    if(!password_verify($_POST['old']??'',$u['password'])) flash('err','Mật khẩu hiện tại không đúng.');
    elseif(strlen($new)<6) flash('err','Mật khẩu mới tối thiểu 6 ký tự.');
    elseif($new!==($_POST['new2']??'')) flash('err','Mật khẩu nhập lại không khớp.');
    else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]); flash('ok','Đã đổi mật khẩu.'); }
  }
  header('Location: profile.php'); exit;
}
$active='prof'; $title='Tài khoản'; include '../includes/header.php'; ?>
<h3>Tài khoản</h3><div class="form"><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="profile"><label>Tên đăng nhập</label><input value="<?=e($u['username'])?>" disabled>
<label>Họ tên</label><input name="full_name" required value="<?=e($u['full_name'])?>"><label>Số điện thoại</label><input name="phone" required value="<?=e($u['phone'])?>"><label>Email</label><input type="email" name="email" required value="<?=e($u['email'])?>"><br><br><button class="btn">Lưu thay đổi</button></form>
<h3 style="margin-top:28px">Đổi mật khẩu</h3><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="password"><label>Mật khẩu hiện tại</label><input type="password" name="old" required>
<label>Mật khẩu mới</label><input type="password" name="new" required><label>Nhập lại mật khẩu mới</label><input type="password" name="new2" required><br><br><button class="btn">Đổi mật khẩu</button></form></div><?php include '../includes/footer.php';
