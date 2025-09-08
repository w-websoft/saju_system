<?php
include_once('../common.php'); if (!defined('_GNUBOARD_')) exit;
include_once('./_bootstrap.php'); include_once('./_lib.php');
$mode = $_REQUEST['mode'] ?? '';
$od_uid = preg_replace('/[^a-f0-9]/','', $_REQUEST['od_uid'] ?? '');
$od = saju_get_order($od_uid); if(!$od) die('NO');
if(!saju_own_or_admin($od)) die('NO AUTH');

if($mode=='send'){
    $msg = trim($_POST['message']);
    if($msg){
        $msg = sql_real_escape_string($msg);
        global $is_admin, $member;
        $writer_type = ($is_admin=='super') ? 'ADMIN' : 'USER';
        $mbid = $member['mb_id'] ?: '';
        sql_query("INSERT INTO g5_saju_consult (od_uid, writer_type, mb_id, message, created_at)
                   VALUES ('{$od_uid}', '{$writer_type}', '{$mbid}', '{$msg}', NOW())");
    }
    echo 'OK'; exit;
}
if($mode=='list'){
    $rs = sql_query("SELECT * FROM g5_saju_consult WHERE od_uid='{$od_uid}' ORDER BY cs_id ASC");
    $items = [];
    while($row = sql_fetch_array($rs)){
        $items[] = ['who' => $row['writer_type']=='ADMIN'?'관리자':'사용자', 'message' => get_text($row['message']), 'date' => $row['created_at']];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['items'=>$items]); exit;
}
echo 'NO';
