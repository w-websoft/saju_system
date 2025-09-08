<?php
include_once('../../common.php'); if (!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php'); include_once('../_config.php'); include_once('../_lib.php');
include_once('./_yc_lib.php');

if(!function_exists('set_cart_id')) include_once(G5_SHOP_PATH.'/cart.lib.php');
if(!defined('G5_SHOP_URL')) alert('Youngcart가 설치되어야 합니다.');
if((int)saju_cfg('USE_YOUNGCART',0)!==1) alert('관리자 설정에서 영카트 결제를 활성화하세요.');

$od_uid = preg_replace('/[^a-f0-9]/','', $_GET['od'] ?? '');
$od = saju_get_order($od_uid);
if(!$od) alert('사주 주문이 없습니다.','../index.php');
if(!saju_own_or_admin($od)) alert('권한이 없습니다.');

/* it_id 자동선택 */
$it_id = saju_resolve_it_id($od);
if(!$it_id) alert('선택할 상품(it_id)을 찾을 수 없습니다. 설정을 확인하세요.');

/* 장바구니 준비 */
$s_cart_id = set_cart_id(get_session('ss_cart_id'));
set_session('ss_cart_id', $s_cart_id);
sql_query("DELETE FROM g5_shop_cart WHERE od_id='{$s_cart_id}'");

/* 옵션 JSON */
$opt_payload = json_encode([
  'od_uid'=>$od['od_uid'],
  'target_name'=>$od['target_name'],
  'birth_ymd'=>$od['birth_ymd'],
  'birth_time'=>$od['birth_time'],
  'gender'=>$od['gender'],
  'is_solar'=>$od['is_solar'],
], JSON_UNESCAPED_UNICODE);

/* 담기 */
$io_id=''; $io_value='사주상담'; $ct_qty=1;
$sql = " INSERT INTO g5_shop_cart
          SET od_id='{$s_cart_id}', it_id='".sql_real_escape_string($it_id)."',
              ct_status='쇼핑', ct_price=0, ct_point=0, ct_qty='{$ct_qty}',
              ct_option='".sql_real_escape_string($io_value)."', io_id='{$io_id}', io_type='0',
              ct_time=NOW(), ct_ip='{$_SERVER['REMOTE_ADDR']}', ct_send_cost='0', ct_select='1',
              it_name=(SELECT it_name FROM g5_shop_item WHERE it_id='".sql_real_escape_string($it_id)."' LIMIT 1),
              ct_history='', ct_memo='".sql_real_escape_string($opt_payload)."' ";
sql_query($sql,1);

goto_url(G5_SHOP_URL.'/orderform.php');
