<?php
require_once 'includes/auth.php';
$u=current_user();
if($u && $u['role']==='admin'){ header('Location: admin/dashboard.php'); exit; }
$q=trim($_GET['q']??'');
$type=in_array($_GET['type']??'',['5','7','11'])?$_GET['type']:''; $max=(int)($_GET['max']??0);
$date=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['date']??'')?$_GET['date']:''; $start=preg_match('/^\d{2}:00$/',$_GET['start']??'')?$_GET['start']:'';
$sql="SELECT * FROM fields f WHERE f.status='active' AND (f.name LIKE ? OR f.address LIKE ?)"; $p=["%$q%","%$q%"];
if($type){ $sql.=" AND f.type=?"; $p[]=$type; } if($max){ $sql.=" AND f.price_per_hour<=?"; $p[]=$max; }
if($date&&$start){ $end=sprintf('%02d:00',(int)$start+1); $sql.=" AND NOT EXISTS(SELECT 1 FROM bookings b WHERE b.field_id=f.id AND b.booking_date=? AND b.status IN('pending','confirmed','completed') AND b.start_time<? AND b.end_time>?)"; array_push($p,$date,$end,$start); }
$s=$pdo->prepare($sql." ORDER BY f.rating DESC LIMIT 12"); $s->execute($p); $fields=$s->fetchAll();
$my=[]; $unread=0;
if($u){
  $s=$pdo->prepare("SELECT b.*,f.name,f.image FROM bookings b JOIN fields f ON f.id=b.field_id WHERE b.user_id=? ORDER BY b.booking_date DESC,b.start_time DESC LIMIT 3");
  $s->execute([$u['id']]); $my=$s->fetchAll();
  $s=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $s->execute([$u['id']]); $unread=$s->fetchColumn();
}
$st=['pending'=>['Chờ xác nhận','blue'],'confirmed'=>['Đã xác nhận','green'],'cancelled'=>['Đã hủy','gray'],'completed'=>['Hoàn thành','green']];
?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sân Bóng - Đặt sân nhanh chóng</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="topbar"><div class="brand">⚽ <div><b>Sân Bóng</b><small>Đặt sân dễ dàng - Chơi bóng thỏa thích</small></div></div>
<form class="search" method="get"><input name="q" placeholder="Tìm sân, địa điểm..." value="<?=e($q)?>"><button>🔍</button></form>
<div class="tb-right"><a href="user/notifications.php" style="color:#fff">🔔<?php if($unread):?><i class="dot"><?=$unread?></i><?php endif;?></a>
<?php if($u): ?><span class="avatar"><?=e(mb_substr($u['full_name'],0,1))?></span><span class="uname"><?=e($u['full_name'])?></span><a class="lnk" href="logout.php" onclick="return confirm('Đăng xuất?')">Đăng xuất</a>
<?php else: ?><a class="lnk" href="login.php">Đăng nhập</a><?php endif; ?></div></header>
<div class="layout">
<nav class="side"><a class="on" href="index.php">🏠 Trang chủ</a><a href="user/booking.php">🕘 Đặt sân</a><a href="user/my-bookings.php">📅 Lịch đặt của tôi</a>
<a href="user/notifications.php">🔔 Thông báo <?php if($unread):?><i class="dot"><?=$unread?></i><?php endif;?></a><a href="user/profile.php">👤 Tài khoản</a></nav>
<main><section class="banner"><h1>Đặt sân bóng nhanh chóng<br>Trải nghiệm thể thao tuyệt vời</h1>
<p>Tìm và đặt sân bóng gần bạn chỉ với vài thao tác đơn giản!</p>
<form class="sbar" method="get"><input type="hidden" name="q" value="<?=e($q)?>">
<select name="type"><option value="">Mọi loại sân</option><?php foreach(['5','7','11'] as $tp): ?><option value="<?=$tp?>" <?=$type===$tp?'selected':''?>>Sân <?=$tp?></option><?php endforeach; ?></select>
<select name="max"><option value="">Mọi mức giá</option><?php foreach([100000,120000,150000] as $m): ?><option value="<?=$m?>" <?=$max==$m?'selected':''?>>Đến <?=money($m)?>/giờ</option><?php endforeach; ?></select>
<input type="date" name="date" min="<?=date('Y-m-d')?>" value="<?=e($date)?>"><select name="start"><option value="">Giờ</option><?php for($h=6;$h<=22;$h++): $v=sprintf('%02d:00',$h); ?><option <?=$start===$v?'selected':''?>><?=$v?></option><?php endfor; ?></select>
<button class="btn">Tìm sân</button></form></section>
<h3 id="fields">Danh sách sân gần bạn</h3>
<?php if(!$fields): ?><div class="empty">Không tìm thấy sân phù hợp.</div><?php endif; ?>
<div class="grid"><?php foreach($fields as $f): ?><div class="card"><img src="<?=e($f['image'])?>" alt="<?=e($f['name'])?>">
<h4><?=e($f['name'])?></h4><small>📍 <?=e($f['address'])?></small><small>⭐ <b><?=e($f['rating'])?></b> (<?=e($f['review_count'])?> đánh giá)</small>
<p class="price"><?=money($f['price_per_hour'])?> / giờ</p><a href="user/field-detail.php?id=<?=$f['id']?>">Xem chi tiết</a><a class="btn" href="user/booking.php?field_id=<?=$f['id']?>">Đặt sân</a></div><?php endforeach; ?></div></main>
<aside id="bookings"><div class="box"><div class="bh"><b>Lịch đặt của tôi</b><a href="user/my-bookings.php">Xem tất cả</a></div>
<?php if(!$u): ?><div class="empty"><a href="login.php">Đăng nhập</a> để xem lịch đặt.</div>
<?php elseif(!$my): ?><div class="empty">Bạn chưa có lịch đặt nào.</div><?php endif; ?>
<?php foreach($my as $b): [$lb,$cl]=$st[$b['status']]; ?><div class="bk"><img src="<?=e($b['image'])?>" alt=""><div><b><?=e($b['name'])?></b>
<small>📅 <?=date('d/m/Y',strtotime($b['booking_date']))?> - <?=substr($b['start_time'],0,5)?> - <?=substr($b['end_time'],0,5)?></small><span class="tag <?=$cl?>"><?=$lb?></span></div></div><?php endforeach; ?></div>
<div class="promo"><b>Chơi bóng cùng bạn bè</b><small>Sân bóng chất lượng - Dịch vụ uy tín - Giá tốt nhất</small><a class="btn" href="#fields">Đặt sân ngay →</a></div></aside>
</div></body></html>
