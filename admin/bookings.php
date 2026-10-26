<?php
require_once '../includes/auth.php';
$admin=require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $map=['confirm'=>['confirmed','đã được xác nhận'],'cancel'=>['cancelled','đã bị hủy/từ chối'],'complete'=>['completed','đã hoàn thành']];
  $a=$_POST['action']??''; $s=$pdo->prepare("SELECT b.*,f.name FROM bookings b JOIN fields f ON f.id=b.field_id WHERE b.id=?"); $s->execute([(int)$_POST['id']]); $b=$s->fetch();
  if($b&&isset($map[$a])){
    $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$map[$a][0],$b['id']]);
    if($a==='complete') $pdo->prepare("UPDATE payments SET status='paid',paid_at=NOW() WHERE booking_id=?")->execute([$b['id']]);
    notify($b['user_id'],"Lịch đặt {$b['name']} ngày ".date('d/m/Y',strtotime($b['booking_date'])).' '.$map[$a][1].'.');
    flash('ok','Đã cập nhật đơn #'.$b['id']);
  } else flash('err','Thao tác không hợp lệ.');
  header('Location: bookings.php'); exit;
}
$fs=$_GET['status']??''; $q=trim($_GET['q']??''); $date=$_GET['date']??'';
$sql="SELECT b.*,f.name fname,u.full_name,u.phone FROM bookings b JOIN fields f ON f.id=b.field_id JOIN users u ON u.id=b.user_id WHERE (f.name LIKE ? OR u.full_name LIKE ?)"; $p=["%$q%","%$q%"];
if($fs){ $sql.=" AND b.status=?"; $p[]=$fs; } if($date){ $sql.=" AND b.booking_date=?"; $p[]=$date; }
$s=$pdo->prepare($sql." ORDER BY b.booking_date DESC,b.start_time DESC"); $s->execute($p); $rows=$s->fetchAll();
$st=['pending'=>['Chờ xác nhận','blue'],'confirmed'=>['Đã xác nhận','green'],'cancelled'=>['Đã hủy','gray'],'completed'=>['Hoàn thành','green']];
?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - Đặt sân</title><link rel="stylesheet" href="../assets/css/style.css"></head><body class="adm">
<aside class="asb" id="asb"><div class="abrand">⚽ Admin Sân Bóng</div><a href="dashboard.php">🏠 Tổng quan</a><a href="fields.php">🏟 Quản lý sân</a><a class="on" href="bookings.php">📅 Đặt sân</a><a href="users.php">👥 Người dùng</a><a href="revenue.php">📈 Doanh thu</a><a href="reports.php">📊 Báo cáo</a><a href="settings.php">⚙ Cài đặt</a><a class="out" href="../logout.php" onclick="return confirm('Đăng xuất?')">↪ Đăng xuất</a></aside>
<div class="amain"><div class="atop"><button onclick="document.getElementById('asb').classList.toggle('open')">☰</button><span>👤 <?=e($admin['username'])?></span></div><h2>Quản lý đặt sân</h2>
<?php if(!empty($_SESSION['flash'])): [$t,$m]=$_SESSION['flash']; unset($_SESSION['flash']); ?><div class="alert <?=$t?>"><?=e($m)?></div><?php endif; ?>
<form class="sbar" method="get" style="background:#fff;margin:0 0 14px"><input name="q" placeholder="Tên sân / người đặt" value="<?=e($q)?>"><input type="date" name="date" value="<?=e($date)?>">
<select name="status"><option value="">Tất cả trạng thái</option><?php foreach($st as $k=>$v): ?><option value="<?=$k?>" <?=$fs===$k?'selected':''?>><?=$v[0]?></option><?php endforeach; ?></select><button class="btn">Lọc</button></form>
<div class="box"><?php if(!$rows): ?><div class="empty">Không có lịch đặt phù hợp.</div><?php else: ?><div class="tw"><table><tr><th>#</th><th>Sân</th><th>Khách</th><th>Ngày</th><th>Giờ</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr>
<?php foreach($rows as $r): [$lb,$cl]=$st[$r['status']]; ?><tr><td><?=$r['id']?></td><td><?=e($r['fname'])?></td><td><?=e($r['full_name'])?><small><?=e($r['phone'])?></small></td><td><?=date('d/m/Y',strtotime($r['booking_date']))?></td><td><?=substr($r['start_time'],0,5)?>-<?=substr($r['end_time'],0,5)?></td><td><?=money($r['total_price'])?></td><td><span class="tag <?=$cl?>"><?=$lb?></span></td><td>
<?php foreach(['pending'=>[['confirm','Xác nhận',''],['cancel','Từ chối','red']],'confirmed'=>[['complete','Hoàn thành','bl'],['cancel','Hủy','red']]][$r['status']]??[] as $a): ?>
<form class="inl" method="post" onsubmit="return <?=$a[0]==='cancel'?"confirm('Xác nhận hủy đơn?')":'true'?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="<?=$a[0]?>"><button class="btn sm <?=$a[2]?>"><?=$a[1]?></button></form> <?php endforeach; ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></div></div></body></html>
