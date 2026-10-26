<?php // Cần: $adm, $page, $title
$pages = [
  'dashboard' => ['🏠', 'Tổng quan'],
  'calendar'  => ['📅', 'Xem lịch'],
  'fields'    => ['🏟', 'Quản lý sân'],
  'bookings'  => ['📋', 'Đặt sân'],
  'vouchers'  => ['🎟', 'Voucher'],
  'users'     => ['👥', 'Người dùng'],
  'revenue'   => ['📈', 'Doanh thu'],
  'reports'   => ['📊', 'Báo cáo'],
  'settings'  => ['⚙', 'Cài đặt']
];
<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - <?=e($title)?></title>
<link rel="stylesheet" href="/quan-ly-san-bong/assets/css/style.css"></head><body class="adm"><aside class="asb" id="asb"><div class="abrand">⚽ Admin Sân Bóng</div>
<?php foreach($pages as $k=>$v): ?><a class="<?=$page===$k?'on':''?>" href="/quan-ly-san-bong/admin/<?=$k?>.php"><?=$v[0]?> <?=$v[1]?></a><?php endforeach; ?>
<a class="out" href="/quan-ly-san-bong/logout.php" onclick="return confirm('Đăng xuất?')">↪ Đăng xuất</a></aside>
<div class="amain"><div class="atop"><button onclick="document.getElementById('asb').classList.toggle('open')">☰</button><span>👤 <?=e($adm['username'])?></span></div><h2><?=e($title)?></h2>
<?php if(!empty($_SESSION['flash'])): [$t,$m]=$_SESSION['flash']; unset($_SESSION['flash']); ?><div class="alert <?=$t?>"><?=e($m)?></div><?php endif; ?>
