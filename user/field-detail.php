<?php
require_once '../includes/auth.php';
$u=current_user(); if($u&&$u['role']==='admin'){ header('Location: ../admin/fields.php'); exit; }
$s=$pdo->prepare("SELECT * FROM fields WHERE id=?"); $s->execute([(int)($_GET['id']??0)]); $f=$s->fetch();
if(!$f){ http_response_code(404); flash('err','Không tìm thấy sân.'); header('Location: ../index.php'); exit; }
$s=$pdo->prepare("SELECT start_time,end_time FROM bookings WHERE field_id=? AND booking_date=CURDATE() AND status IN('pending','confirmed','completed') ORDER BY start_time"); $s->execute([$f['id']]); $busy=$s->fetchAll();
$active='book'; $title=$f['name']; include '../includes/header.php'; ?>
<div class="box" style="max-width:720px"><img src="<?=e(img_src($f['image']))?>" alt="" style="width:100%;max-height:320px;object-fit:cover;border-radius:8px"><h2><?=e($f['name'])?></h2>
<small>📍 <?=e($f['address'])?> &nbsp; ⚽ Sân <?=e($f['type'])?> &nbsp; ⭐ <?=e($f['rating'])?> (<?=e($f['review_count'])?> đánh giá)</small>
<p class="price" style="font-size:20px"><?=money($f['price_per_hour'])?> / giờ <span class="tag <?=$f['status']==='active'?'green':'gray'?>"><?=$f['status']==='active'?'Đang hoạt động':'Bảo trì'?></span></p>
<p><?=nl2br(e($f['description']?:'Chưa có mô tả.'))?></p><p><b>Khung giờ đã đặt hôm nay:</b> <?php if(!$busy) echo 'Còn trống cả ngày.'; foreach($busy as $b) echo '<span class="tag gray">'.substr($b['start_time'],0,5).'-'.substr($b['end_time'],0,5).'</span> '; ?></p>
<?php if($f['status']==='active'): ?><a class="btn" style="max-width:200px" href="booking.php?field_id=<?=$f['id']?>">Đặt sân</a><?php endif; ?></div><?php include '../includes/footer.php';
