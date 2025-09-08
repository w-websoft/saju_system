<?php
if (!defined('_GNUBOARD_')) exit;

function saju_log($type, $msg, $i1=null, $i2=null){
  $type = sql_real_escape_string($type);
  $msg = sql_real_escape_string($msg);
  $i1 = sql_real_escape_string($i1);
  $i2 = sql_real_escape_string($i2);
  @sql_query("INSERT INTO g5_saju_logs (log_type, message, idx1, idx2, created_at) VALUES ('{$type}','{$msg}','{$i1}','{$i2}',NOW())");
}

function saju_bootstrap(){
  // 1) 테이블 생성
  $sql = file_get_contents(__DIR__.'/_schema.sql');
  $chunks = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
  foreach($chunks as $q){
    @sql_query($q);
  }

  // 2) 스키마 버전 기록
  @sql_query("INSERT IGNORE INTO g5_saju_meta (meta_key, meta_value, updated_at) VALUES ('CFG_VERSION','1.0',NOW())");

  // 3) 기본 카테고리/설정 값 프리셋
  @sql_query("INSERT IGNORE INTO g5_saju_meta (meta_key, meta_value, updated_at) VALUES
    ('CFG_ALLOW_GUEST','1',NOW()),
    ('CFG_PRICE','55000',NOW()),
    ('CFG_PG_MODE','DUMMY',NOW()),
    ('CFG_SMS_VENDOR','',NOW()),
    ('CFG_USE_YOUNGCART','0',NOW()),
    ('CFG_SELECT_MODE','AUTO',NOW()),
    ('CFG_CATEGORY_IDS','saju_basic,saju_premium',NOW()),
    ('CFG_FALLBACK_IT_ID','SAJU001',NOW()),
    ('CFG_PRICE_TOLERANCE','100',NOW()),
    ('CFG_AFTER_PAY_URL','".G5_URL."/saju/view.php?od=',NOW()),
    ('CFG_PAY_SMS','0',NOW()),
    ('CFG_WEBRTC_PROVIDER','JITSI',NOW())
  " );

  // 4) 마이그레이션 예시(컬럼 존재 확인 후 추가 등) - 안전하게 IF NOT EXISTS
  // (여기서는 스키마에 이미 반영되어 있으므로 생략)

  saju_log('BOOT', 'bootstrap completed', G5_TIME_YMDHIS, $_SERVER['REMOTE_ADDR']);
}

$__chk = sql_fetch("SHOW TABLES LIKE 'g5_saju_order'");
if(!$__chk){ saju_bootstrap(); }
