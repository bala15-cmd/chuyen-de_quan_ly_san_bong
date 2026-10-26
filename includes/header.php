<?php // Dùng cho trang User con. Cần: $u (người dùng), $active, $title
$base='/quan-ly-san-bong/'; ?><!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title??'Sân Bóng')?></title><link rel="stylesheet" href="<?=$base?>assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="<?=$base?>index.php" style="color:#fff">⚽ <div><b>Sân Bóng</b></div></a><div class="tb-right"><?php if($u): ?><span class="avatar"><?=e(mb_substr($u['full_name'],0,1))?></span><span><?=e($u['full_name'])?></span>
<a class="lnk" href="<?=$base?>logout.php" onclick="return confirm('Đăng xuất?')">Đăng xuất</a><?php else: ?><a class="lnk" href="<?=$base?>login.php">Đăng nhập</a><?php endif; ?></div></header>
<div class="layout one"><nav class="side"><?php foreach(['home'=>['index.php','🏠 Trang chủ'],'book'=>['user/booking.php','🕘 Đặt sân'],'mine'=>['user/my-bookings.php','📅 Lịch đặt của tôi'],'noti'=>['user/notifications.php','🔔 Thông báo'],'prof'=>['user/profile.php','👤 Tài khoản']] as $k=>$v): ?>
<a class="<?=($active??'')===$k?'on':''?>" href="<?=$base.$v[0]?>"><?=$v[1]?></a><?php endforeach; ?></nav><main>
<?php if(!empty($_SESSION['flash'])): [$t,$m]=$_SESSION['flash']; unset($_SESSION['flash']); ?><div class="alert <?=$t?>"><?=e($m)?></div><?php endif; ?>
