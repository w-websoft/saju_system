<?php
if (!defined('_GNUBOARD_')) exit;

/** 메타 설정 읽기 (_lib.php 와 중복 방지) */
if (!function_exists('saju_cfg')) {
  function saju_cfg($key, $default=null){
    $row = sql_fetch("SELECT meta_value FROM g5_saju_meta WHERE meta_key = '".sql_real_escape_string($key)."'");
    if ($row && isset($row['meta_value'])) return $row['meta_value'];
    return $default;
  }
}

/** 캐시 키 */
if (!function_exists('saju_calc_key')) {
  function saju_calc_key($y,$m,$d,$h,$mode='ROUGH'){
    $raw = json_encode([$y,$m,$d,$h,$mode], JSON_UNESCAPED_UNICODE);
    return sha1($raw);
  }
}

/** 캐시 조회 */
if (!function_exists('saju_cache_get')) {
  function saju_cache_get($key_hash){
    $row = sql_fetch("SELECT payload FROM g5_saju_calc_cache WHERE key_hash='".sql_real_escape_string($key_hash)."'");
    return ($row && isset($row['payload'])) ? $row['payload'] : null;
  }
}

/** 캐시 저장 */
if (!function_exists('saju_cache_put')) {
  function saju_cache_put($key_hash, $payload_json, $pf_id=null){
    $pf_sql = is_null($pf_id) ? "NULL" : intval($pf_id);
    $k = sql_real_escape_string($key_hash);
    $p = sql_real_escape_string($payload_json);
    sql_query("INSERT INTO g5_saju_calc_cache (pf_id, key_hash, payload)
               VALUES ($pf_sql, '$k', '$p')
               ON DUPLICATE KEY UPDATE payload=VALUES(payload), created_at=NOW()");
  }
}

/** 기도 길일 저장 */
if (!function_exists('saju_prayer_save')) {
  function saju_prayer_save($pf_id, $ref_date, $rows){
    $pf = intval($pf_id);
    $ref = sql_real_escape_string($ref_date);
    foreach ((array)$rows as $r){
      $date  = sql_real_escape_string($r['date']   ?? '');
      $ganji = sql_real_escape_string($r['ganji']  ?? '');
      $offi  = sql_real_escape_string($r['officer']?? '');
      $score = intval($r['score'] ?? 0);
      if ($date === '') continue;
      sql_query("INSERT INTO g5_saju_prayer_day (pf_id, ref_date, date, ganji, officer, score)
                 VALUES ($pf, '$ref', '$date', '$ganji', '$offi', $score)");
    }
  }
}

/** 기도 길일 조회(캐시) */
if (!function_exists('saju_prayer_get')) {
  function saju_prayer_get($pf_id, $ref_date){
    $pf = intval($pf_id);
    $ref = sql_real_escape_string($ref_date);
    $res = sql_query("SELECT * FROM g5_saju_prayer_day
                      WHERE pf_id=$pf AND ref_date='$ref'
                      ORDER BY score DESC, date ASC LIMIT 12");
    $out=[]; while($row=sql_fetch_array($res)){ $out[] = $row; }
    return $out;
  }
}
