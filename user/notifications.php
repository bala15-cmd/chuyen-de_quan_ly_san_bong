<?php
require_once '../includes/auth.php';
$u=require_login(); if($u['role']==='admin'){ header('Location: ../admin/dashboard.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_check(); $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$u['id']]); flash('ok','Đã đánh dấu tất cả là đã đọc.'); header('Location: notifications.php'); exit; }
$s=$pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 50"); $s->execute([$u['id']]); $rows=$s->fetchAll();
$active='noti'; $title='Thông báo'; include '../includes/header.php'; ?>
<div class="bh"><h3>Thông báo</h3><?php if(array_filter($rows,fn($r)=>!$r['is_read'])): ?><form method="post"><?=csrf_field()?><button class="btn sm">Đánh dấu đã đọc</button></form><?php endif; ?></div>
<?php if(!$rows): ?><div class="empty">Bạn chưa có thông báo nào.</div><?php endif; foreach($rows as $r): ?>
<div class="bk" style="<?=$r['is_read']?'':'background:#f0fdf4'?>"><div>🔔 <?=e($r['message'])?><small><?=date('d/m/Y H:i',strtotime($r['created_at']))?></small></div></div><?php endforeach; include '../includes/footer.php';
