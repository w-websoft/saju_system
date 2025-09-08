<?php
include_once('../common.php'); if (!defined('_GNUBOARD_')) exit;
include_once('./_bootstrap.php'); include_once('./_config.php'); include_once('./_lib.php');

$mode = $_POST['mode'] ?? '';
$od_uid = preg_replace('/[^a-f0-9]/','', $_POST['od_uid'] ?? '');
$od = saju_get_order($od_uid); if(!$od) die('NO ORDER');

if ($mode==='DUMMY_SUCCESS') {
    sql_query("UPDATE g5_saju_order SET status='PAID', paid_at=NOW() WHERE od_uid='{$od_uid}' AND status='PENDING'");
    goto_url('./view.php?od='.$od_uid);
}
if ($mode==='BANK_CONFIRM') {
    alert('입금확인 요청 접수. 관리자 확인 후 안내드립니다.','./pay.php?od='.$od_uid);
}
alert('처리 불가한 요청입니다.');
