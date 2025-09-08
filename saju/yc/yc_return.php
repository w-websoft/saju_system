<?php
include_once('../../common.php'); 
if (!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php'); 
include_once('../_config.php'); 
include_once('../_lib.php');
include_once('./_yc_lib.php'); 
include_once('../sms.php');

$yc_od_id = preg_replace('/[^0-9\-]/','', $_GET['od_id'] ?? '');
if(!$yc_od_id) alert('od_id가 필요합니다.');

$od = sql_fetch("SELECT * FROM g5_shop_order WHERE od_id='".sql_real_escape_string($yc_od_id)."'");
if(!$od) alert('영카트 주문을 찾을 수 없습니다.');

$paid = in_array($od['od_status'], array('입금','결제완료','배송','완료'));
if(!$paid) alert('아직 결제가 완료되지 않았습니다.');

$ct = sql_query("SELECT ct_memo FROM g5_shop_cart WHERE od_id='".sql_real_escape_string($yc_od_id)."'");
$od_uid = null;
for($i=0;$row=sql_fetch_array($ct);$i++){
  if(!$row['ct_memo']) continue;
  $payload = json_decode($row['ct_memo'], true);
  if(isset($payload['od_uid'])){ $od_uid = $payload['od_uid']; break; }
}
if(!$od_uid){
  $od_uid = saju_pgmap_get_oduid_by_yc($yc_od_id);
}
if(!$od_uid) alert('사주 주문 식별자(od_uid)를 찾을 수 없습니다.');

$so = saju_get_order($od_uid);
if(!$so) alert('사주 주문이 존재하지 않습니다.');

saju_pgmap_set($od_uid, $yc_od_id);
sql_query("UPDATE g5_saju_order SET status='PAID', paid_at=NOW(), pg_code='YOUNGCART' WHERE od_uid='".sql_real_escape_string($od_uid)."' AND status='PENDING'");

if((int)saju_cfg('PAY_SMS',0)===1){
  $msg = "[사주상담] 결제가 확인되었습니다. 결과/상담 페이지에서 자동풀이를 확인하고 예약을 진행하세요.\n".saju_cfg('AFTER_PAY_URL', G5_URL.'/saju/view.php?od=').$od_uid;
  if($so['buyer_phone']) @saju_send_sms($so['buyer_phone'], $msg);
}

goto_url(saju_cfg('AFTER_PAY_URL', G5_URL.'/saju/view.php?od=').$od_uid);
