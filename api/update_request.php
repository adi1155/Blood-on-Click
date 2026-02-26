<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';
requireLogin();

$db     = getDB();
$rid    = (int)($_POST['request_id']??0);
$status = in_array($_POST['status']??'',['open','fulfilled','cancelled'])?$_POST['status']:'cancelled';
$uid    = $_SESSION['user_id'];

// Only requester or admin can update
$req = $db->prepare("SELECT * FROM blood_requests WHERE id=?"); $req->execute([$rid]); $req=$req->fetch();
if ($req && ($req['requester_id']===$uid || $_SESSION['role']==='admin')) {
    $db->prepare("UPDATE blood_requests SET status=? WHERE id=?")->execute([$status,$rid]);
}
header('Location: '.($_SERVER['HTTP_REFERER']??'/dashboard.php'));
exit;
