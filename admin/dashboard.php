<?php
require_once '../includes/auth.php';
$admin=require_admin();
$c=fn($sql)=>$pdo->query($sql)->fetchColumn();
$totalFields=$c("SELECT COUNT(*) FROM fields");
$todayBk=$c("SELECT COUNT(*) FROM bookings WHERE booking_date=CURDATE()");
$totalUsers=$c("SELECT COUNT(*) FROM users WHERE role='user'");
// Quy tắc doanh thu: chỉ tính đơn Đã xác nhận + Hoàn thành, không tính Hủy/Chờ
$todayRev=$c("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE booking_date=CURDATE() AND status IN('confirmed','completed')");
$rev=$pdo->query("SELECT booking_date d,SUM(total_price) t FROM bookings WHERE status IN('confirmed','completed') AND booking_date>=CURDATE()-INTERVAL 6 DAY GROUP BY booking_date")->fetchAll(PDO::FETCH_KEY_PAIR);
$labels=[];$vals=[]; for($i=6;$i>=0;$i--){ $d=date('Y-m-d',strtotime("-$i day")); $labels[]=date('d/m',strtotime($d)); $vals[]=(int)($rev[$d]??0); }
$recent=$pdo->query("SELECT b.*,f.name fname,u.full_name FROM bookings b JOIN fields f ON f.id=b.field_id JOIN users u ON u.id=b.user_id ORDER BY b.created_at DESC,b.id DESC LIMIT 5")->fetchAll();
$st=['pending'=>['Chờ xác nhận','blue'],'confirmed'=>['Đã xác nhận','green'],'cancelled'=>['Đã hủy','gray'],'completed'=>['Hoàn thành','green']];
$cards=[['⚽','Tổng số sân',$totalFields],['📅','Tổng đặt sân hôm nay',$todayBk],['👤','Tổng người dùng',$totalUsers],['💲','Doanh thu hôm nay',money($todayRev)]];
?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - Tổng quan</title><link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script></head><body class="adm">
<aside class="asb" id="asb"><div class="abrand">⚽ Admin Sân Bóng</div>
<a class="on" href="dashboard.php">🏠 Tổng quan</a><a href="fields.php">🏟 Quản lý sân</a><a href="bookings.php">📅 Đặt sân</a><a href="users.php">👥 Người dùng</a><a href="revenue.php">📈 Doanh thu</a><a href="reports.php">📊 Báo cáo</a><a href="settings.php">⚙ Cài đặt</a>
<a class="out" href="../logout.php" onclick="return confirm('Đăng xuất?')">↪ Đăng xuất</a></aside>
<div class="amain"><div class="atop"><button onclick="document.getElementById('asb').classList.toggle('open')">☰</button><span>👤 <?=e($admin['username'])?></span></div>
<h2>Tổng quan</h2><div class="stats"><?php foreach($cards as $k): ?><div class="stat"><span class="ic"><?=$k[0]?></span><div><small><?=$k[1]?></small><b><?=e($k[2])?></b></div></div><?php endforeach; ?></div>
<div class="two"><div class="box"><b>Doanh thu (7 ngày gần nhất)</b><div style="height:260px"><canvas id="ch"></canvas></div></div>
<div class="box"><div class="bh"><b>Đặt sân gần đây</b><a href="#">Xem tất cả →</a></div>
<?php if(!$recent): ?><div class="empty">Chưa có lượt đặt.</div><?php else: ?><div class="tw"><table><tr><th>Thời gian</th><th>Sân</th><th>Người đặt</th><th>Trạng thái</th></tr>
<?php foreach($recent as $r): [$lb,$cl]=$st[$r['status']]; ?><tr><td><?=date('d/m',strtotime($r['booking_date']))?> - <?=substr($r['start_time'],0,5)?></td><td><?=e($r['fname'])?></td><td><?=e($r['full_name'])?></td><td><span class="tag <?=$cl?>"><?=$lb?></span></td></tr><?php endforeach; ?></table></div><?php endif; ?></div></div>
<div class="quick"><div class="box"><b>Quản lý sân</b><small>Thông tin sân, hình ảnh, giá cả...</small><a class="btn" href="fields.php">Quản lý sân →</a></div>
<div class="box"><b>Quản lý người dùng</b><small>Danh sách người dùng, phân quyền...</small><a class="btn bl" href="users.php">Xem người dùng →</a></div>
<div class="box"><b>Báo cáo doanh thu</b><small>Thống kê doanh thu, lượt đặt sân...</small><a class="btn pu" href="reports.php">Xem báo cáo →</a></div></div></div>
<script>new Chart(document.getElementById('ch'),{type:'line',data:{labels:<?=json_encode($labels)?>,datasets:[{data:<?=json_encode($vals)?>,borderColor:'#15803d',backgroundColor:'rgba(21,128,61,.12)',fill:true,tension:.35}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}}}});</script></body></html>
