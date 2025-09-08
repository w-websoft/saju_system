<?php
include_once('../../common.php');
include_once('../_lib.php');
if(!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php');
include_once('../sms.php');
if ($is_admin!='super') alert('관리자만');
$rv_id = (int)$_POST['rv_id'];
$r = sql_fetch("SELECT * FROM g5_saju_reserve WHERE rv_id={$rv_id}");
if(!$r) alert('예약 없음','./reserve_list.php');
$meet_url = sql_real_escape_string($_POST['meet_url']);
$status = in_array($_POST['status'],['REQUEST','PAID','DONE','CANCEL'])?$_POST['status']:'REQUEST';
sql_query("UPDATE g5_saju_reserve SET meet_url='{$meet_url}', status='{$status}' WHERE rv_id={$rv_id}");
if($status=='PAID' && $meet_url){
  $od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($r['od_uid'])."'");
  if($od && $od['buyer_phone']){
    $msg = "[사주상담] 예약 확정\n시간: {$r['rv_date']} {$r['rv_time']}\n접속: {$meet_url}";
    @saju_send_sms($od['buyer_phone'], $msg);
  }
}
alert('저장되었습니다','./reserve_list.php');
