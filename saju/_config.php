<?php
if (!defined('_GNUBOARD_')) exit;

/* 기본값 (관리자 설정 UI에서 덮어씌움) */
$config['saju'] = array(
  'ALLOW_GUEST' => 1,              // 비회원 주문 허용
  'PRICE' => 55000,                // 기본 참고가
  'PG_MODE' => 'DUMMY',            // DUMMY or YOUNGCART
  'SMS_VENDOR' => '',              // '', ICODE, ALIGO
  'ICODE_ID' => '', 'ICODE_PW' => '',
  'ALIGO_KEY' => '', 'ALIGO_ID' => '', 'ALIGO_SENDER' => '',
  'USE_YOUNGCART' => 0,            // 0/1
  'SELECT_MODE' => 'AUTO',         // AUTO/CATEGORY/PRICE/MAP_ONLY/FIXED
  'CATEGORY_IDS' => 'saju_basic,saju_premium',
  'FALLBACK_IT_ID' => 'SAJU001',
  'PRICE_TOLERANCE' => 100,
  'AFTER_PAY_URL' => G5_URL.'/saju/view.php?od=',
  'PAY_SMS' => 0,                  // 결제 완료시 SMS 발송
  'WEBRTC_PROVIDER' => 'JITSI',    // JITSI (iframe)
);

/* 관리자 설정 덮어쓰기 (meta) */
$r = sql_query("SELECT meta_key, meta_value FROM g5_saju_meta", false);
while($row = sql_fetch_array($r)){
  if(strpos($row['meta_key'], 'CFG_') === 0){
    $k = substr($row['meta_key'], 4);
    $config['saju'][$k] = $row['meta_value'];
  }
}
