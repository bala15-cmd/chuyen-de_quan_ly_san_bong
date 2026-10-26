<?php
require_once '../includes/auth.php';
$u=require_login();
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $s=$pdo->prepare("SELECT b.*,f.name FROM bookings b JOIN fields f ON f.id=b.field_id WHERE b.id=? AND b.user_id=?"); $s->execute([(int)$_POST['id'],$u['id']]); $b=$s->fetch();
  // Quy định: chỉ hủy đơn chờ/đã xác nhận và còn ít nhất 2 giờ trước giờ chơi
  if(!$b||!in_array($b['status'],['pending','confirmed'])) flash('err','Không thể hủy đơn này.');
  elseif(strtotime($b['booking_date'].' '.$b['start_time'])-time()<7200) flash('err','Chỉ được hủy trước giờ chơi ít nhất 2 giờ.');
  else { $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$b['id']]);
    $pdo->prepare("UPDATE payments SET status=IF(status='paid','refunded',status) WHERE booking_id=?")->execute([$b['id']]);
    notify($u['id'],"Bạn đã hủy đặt {$b['name']} ngày ".date('d/m/Y',strtotime($b['booking_date'])).'.'); flash('ok','Đã hủy lịch đặt.'); }
  header("Location: my-bookings.php?pay_bid=$bid");
}
$s=$pdo->prepare("SELECT b.*,f.name FROM bookings b JOIN fields f ON f.id=b.field_id WHERE b.user_id=? ORDER BY b.booking_date DESC,b.start_time DESC"); $s->execute([$u['id']]); $rows=$s->fetchAll();
$st=['pending'=>['Chờ xác nhận','blue'],'confirmed'=>['Đã xác nhận','green'],'cancelled'=>['Đã hủy','gray'],'completed'=>['Hoàn thành','green']];
$active='mine'; $title='Lịch đặt của tôi'; include '../includes/header.php'; ?>
<h3>Lịch đặt của tôi</h3><?php if(!$rows): ?><div class="empty">Bạn chưa có lịch đặt nào. <a href="booking.php">Đặt sân ngay</a></div><?php else: ?>
<div class="tw"><table><tr><th>Sân</th><th>Ngày</th><th>Giờ</th><th>Giá</th><th>Trạng thái</th><th></th></tr>
<?php foreach($rows as $r): [$lb,$cl]=$st[$r['status']]; ?><tr><td><?=e($r['name'])?></td><td><?=date('d/m/Y',strtotime($r['booking_date']))?></td><td><?=substr($r['start_time'],0,5)?>-<?=substr($r['end_time'],0,5)?></td><td><?=money($r['total_price'])?></td><td><span class="tag <?=$cl?>"><?=$lb?></span></td>
<td><?php if(in_array($r['status'],['pending','confirmed'])): ?><form class="inl" method="post" onsubmit="return confirm('Bạn chắc chắn muốn hủy?')"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn sm red">Hủy</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div><?php endif; include '../includes/footer.php';
<?php
// Kiểm tra hiển thị Modal VietQR/MoMo nếu có tham số pay_bid
$pay_bid = (int)($_GET['pay_bid'] ?? 0);
if($pay_bid > 0):
  $stmt_p = $pdo->prepare("SELECT b.*, p.method, f.name fname FROM bookings b JOIN payments p ON p.booking_id=b.id JOIN fields f ON f.id=b.field_id WHERE b.id=? AND b.user_id=?");
  $stmt_p->execute([$pay_bid, $u['id']]);
  $pay_info = $stmt_p->fetch();
  if($pay_info && in_array($pay_info['method'], ['bank', 'momo'])):
    $bank_id = "MB"; // Mã ngân hàng (ví dụ MBBank, VCB, ACB...)
    $account_no = "0900000000"; // Số tài khoản ngân hàng admin
    $account_name = "QUAN LY SAN BONG";
    $amount = $pay_info['total_price'];
    $memo = "Datsan".$pay_info['id'];
    
    // Tạo link QR VietQR tự động
    $qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$amount}&addInfo={$memo}&accountName=".urlencode($account_name);
?>
<div class="box" style="margin-top:16px;background:#f0fdf4;border:1px solid #86efac;padding:16px;border-radius:8px;text-align:center">
  <h3 style="color:#15803d;margin-top:0">Quét mã QR để Thanh toán đơn #<?=$pay_info['id']?></h3>
  <p>Phương thức: <b><?=$pay_info['method']==='momo'?'Ví MoMo':'Chuyển khoản Ngân hàng (VietQR)'?></b></p>
  <img src="<?=$qr_url?>" alt="VietQR" style="max-width:240px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15)">
  <p>Số tiền: <b style="color:#dc2626;font-size:18px"><?=money($amount)?></b></p>
  <p><small>Nội dung chuyển khoản: <b><?=$memo?></b></small></p>
  <a href="my-bookings.php" class="btn sm">Đã hoàn thành chuyển khoản</a>
</div>
<?php endif; endif; ?>