<?php
require_once '../includes/auth.php';
$u = require_login(); 
if($u['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit; }

$fields = $pdo->query("SELECT * FROM fields WHERE status='active' ORDER BY name")->fetchAll();
$services = $pdo->query("SELECT * FROM services WHERE status='active'")->fetchAll();
$fid = (int)($_POST['field_id'] ?? $_GET['field_id'] ?? 0);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  csrf_check();
  $date = $_POST['date'] ?? ''; 
  $st = $_POST['start'] ?? ''; 
  $en = $_POST['end'] ?? '';
  $voucher_code = trim($_POST['voucher_code'] ?? '');
  $req_services = $_POST['services'] ?? []; // mảng service_id => quantity

  $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) && preg_match('/^\d{2}:00$/',$st) && preg_match('/^\d{2}:00$/',$en);
  if(!$ok || $en <= $st) flash('err','Khung giờ không hợp lệ.');
  elseif($date < date('Y-m-d') || ($date == date('Y-m-d') && $st <= date('H:i'))) flash('err','Không thể đặt thời gian đã qua.');
  else {
    $pdo->beginTransaction();
    $s = $pdo->prepare("SELECT * FROM fields WHERE id=? AND status='active' FOR UPDATE"); 
    $s->execute([$fid]); 
    $f = $s->fetch();

    if(!$f){ $pdo->rollBack(); flash('err','Sân không tồn tại hoặc bảo trì.'); }
    else {
      $c = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE field_id=? AND booking_date=? AND status IN('pending','confirmed','completed') AND start_time<? AND end_time>?");
      $c->execute([$fid,$date,$en,$st]);
      if($c->fetchColumn()){ 
        $pdo->rollBack(); 
        flash('err','Khung giờ này đã có người đặt.'); 
      } else {
        $hours = (int)$en - (int)$st;
        $subtotal = $hours * $f['price_per_hour'];

        // Tính tiền dịch vụ
        $service_total = 0;
        $svc_to_insert = [];
        if(!empty($req_services) && is_array($req_services)){
          $svc_ids = array_keys($req_services);
          $in  = str_repeat('?,', count($svc_ids) - 1) . '?';
          $stmt_svc = $pdo->prepare("SELECT * FROM services WHERE id IN ($in)");
          $stmt_svc->execute($svc_ids);
          $svc_db = $stmt_svc->fetchAll();

          foreach($svc_db as $s_item){
            $qty = max(0, (int)($req_services[$s_item['id']] ?? 0));
            if($qty > 0){
              $item_cost = $qty * $s_item['price'];
              $service_total += $item_cost;
              $svc_to_insert[] = ['id' => $s_item['id'], 'qty' => $qty, 'price' => $s_item['price']];
            }
          }
        }

        $total = $subtotal + $service_total;
        $vid = null;

        // Xử lý Voucher giảm giá
        if($voucher_code !== ''){
          $vstmt = $pdo->prepare("SELECT * FROM vouchers WHERE code=? AND status='active' AND expired_at >= CURDATE() FOR UPDATE");
          $vstmt->execute([$voucher_code]);
          $v = $vstmt->fetch();
          if($v && $v['used_count'] < $v['usage_limit'] && $total >= $v['min_order']){
            $vid = $v['id'];
            if($v['discount_type'] === 'percent'){
              $discount = ($total * $v['discount_value']) / 100;
              if($v['max_discount'] > 0 && $discount > $v['max_discount']) $discount = $v['max_discount'];
            } else {
              $discount = $v['discount_value'];
            }
            $total = max(0, $total - $discount);
            $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id=?")->execute([$vid]);
          }
        }

        // Tạo Đơn đặt
        $pdo->prepare("INSERT INTO bookings(user_id,field_id,booking_date,start_time,end_time,total_price,voucher_id) VALUES(?,?,?,?,?,?,?)")
            ->execute([$u['id'],$fid,$date,$st,$en,$total,$vid]);
        $bid = $pdo->lastInsertId();

        // Lưu dịch vụ đi kèm
        foreach($svc_to_insert as $s_ins){
          $pdo->prepare("INSERT INTO booking_services(booking_id,service_id,quantity,price) VALUES(?,?,?,?)")
              ->execute([$bid, $s_ins['id'], $s_ins['qty'], $s_ins['price']]);
        }

        $method = $_POST['payment_method'] ?? 'cash';
        $pdo->prepare("INSERT INTO payments(booking_id,method,amount) VALUES(?,?,?)")->execute([$bid,$method,$total]);

        notify($u['id'], "Bạn đã đặt {$f['name']} ngày ".date('d/m/Y',strtotime($date))." ($st-$en). Tổng tiền: ".money($total));
        $pdo->commit(); 

        flash('ok', 'Đặt sân thành công!');
        header("Location: my-bookings.php?pay_bid=$bid"); 
        exit;
      }
    }
  }
}

$sel = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d'); 
$busy = [];
if($fid){ 
  $s = $pdo->prepare("SELECT start_time,end_time FROM bookings WHERE field_id=? AND booking_date=? AND status IN('pending','confirmed','completed') ORDER BY start_time"); 
  $s->execute([$fid,$sel]); 
  $busy = $s->fetchAll(); 
}

$active = 'book'; $title = 'Đặt sân'; include '../includes/header.php'; ?>

<h3>Đặt sân bóng</h3>
<form class="form" method="get">
  <label>Sân</label>
  <select name="field_id" onchange="this.form.submit()">
    <option value="">-- Chọn sân --</option>
    <?php foreach($fields as $f): ?>
      <option value="<?=$f['id']?>" <?=$f['id']==$fid?'selected':''?>><?=e($f['name'])?> - <?=money($f['price_per_hour'])?>/giờ</option>
    <?php endforeach; ?>
  </select>
  <label>Ngày</label>
  <input type="date" name="date" min="<?=date('Y-m-d')?>" value="<?=e($sel)?>" onchange="this.form.submit()">
</form>

<?php if($fid): ?>
  <p style="margin-top:14px"><b>Khung giờ đã đặt:</b> 
    <?php if(!$busy) echo 'Còn trống cả ngày.'; 
    foreach($busy as $b) echo '<span class="tag gray">'.substr($b['start_time'],0,5).'-'.substr($b['end_time'],0,5).'</span> '; ?>
  </p>

  <form class="form" method="post" style="max-width:600px;margin-top:10px">
    <?=csrf_field()?>
    <input type="hidden" name="field_id" value="<?=$fid?>">
    <input type="hidden" name="date" value="<?=e($sel)?>">

    <label>Giờ bắt đầu</label>
    <select name="start"><?php for($h=6;$h<=22;$h++) printf('<option>%02d:00</option>',$h); ?></select>

    <label>Giờ kết thúc</label>
    <select name="end"><?php for($h=7;$h<=23;$h++) printf('<option>%02d:00</option>',$h); ?></select>

    <h4 style="margin-top:18px;margin-bottom:6px">Dịch vụ đi kèm</h4>
    <?php foreach($services as $svc): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <span><?=e($svc['name'])?> (<?=money($svc['price'])?>/<?=$svc['unit']?>)</span>
        <input type="number" min="0" name="services[<?=$svc['id']?>]" value="0" style="width:70px;padding:4px">
      </div>
    <?php endforeach; ?>

    <label>Mã giảm giá (Voucher)</label>
    <input name="voucher_code" placeholder="Ví dụ: SANBONG20">

    <label>Phương thức thanh toán</label>
    <select name="payment_method">
      <option value="cash">Tiền mặt tại sân</option>
      <option value="bank">Chuyển khoản Ngân hàng (VietQR)</option>
      <option value="momo">Ví điện tử MoMo</option>
    </select>

    <br><br>
    <button class="btn" onclick="return confirm('Xác nhận đặt sân?')">Xác nhận đặt sân</button>
  </form>
<?php else: ?>
  <div class="empty">Hãy chọn sân để xem khung giờ trống.</div>
<?php endif; 

include '../includes/footer.php';