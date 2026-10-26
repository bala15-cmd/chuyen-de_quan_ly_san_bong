<?php
require_once '../includes/auth.php';
$adm=require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check(); $a=$_POST['action']??'';
  if($a==='profile'){
    $n=trim($_POST['full_name']??''); $p=trim($_POST['phone']??''); $m=trim($_POST['email']??'');
    $s=$pdo->prepare("SELECT COUNT(*) FROM users WHERE email=? AND id<>?"); $s->execute([$m,$adm['id']]);
    if(mb_strlen($n)<2||!preg_match('/^0\d{9}$/',$p)||!filter_var($m,FILTER_VALIDATE_EMAIL)) flash('err','Thông tin không hợp lệ (SĐT 10 số bắt đầu bằng 0, email đúng định dạng).');
    elseif($s->fetchColumn()) flash('err','Email đã được sử dụng.');
    else { $pdo->prepare("UPDATE users SET full_name=?,phone=?,email=? WHERE id=?")->execute([$n,$p,$m,$adm['id']]); flash('ok','Đã cập nhật thông tin.'); }
  } elseif($a==='password'){
    $new=$_POST['new']??'';
    if(!password_verify($_POST['old']??'',$adm['password'])) flash('err','Mật khẩu hiện tại không đúng.');
    elseif(strlen($new)<6) flash('err','Mật khẩu mới tối thiểu 6 ký tự.');
    elseif($new!==($_POST['new2']??'')) flash('err','Mật khẩu nhập lại không khớp.');
    else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$adm['id']]); flash('ok','Đã đổi mật khẩu.'); }
  }
  header('Location: settings.php'); exit;
}
$page='settings'; $title='Cài đặt'; include '../includes/admin_header.php'; ?>
<div class="two"><div class="box"><b>Thông tin tài khoản</b><form class="form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="profile">
<label>Họ tên</label><input name="full_name" required value="<?=e($adm['full_name'])?>"><label>Số điện thoại</label><input name="phone" required value="<?=e($adm['phone'])?>"><label>Email</label><input type="email" name="email" required value="<?=e($adm['email'])?>"><br><br><button class="btn">Lưu thay đổi</button></form></div>
<div class="box"><b>Đổi mật khẩu</b><form class="form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="password">
<label>Mật khẩu hiện tại</label><input type="password" name="old" required><label>Mật khẩu mới</label><input type="password" name="new" required><label>Nhập lại mật khẩu mới</label><input type="password" name="new2" required><br><br><button class="btn">Đổi mật khẩu</button></form></div></div>
<?php include '../includes/admin_footer.php';
