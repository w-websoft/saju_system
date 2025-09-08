<?php
if (!defined('_GNUBOARD_')) exit;

function saju_pgmap_set($od_uid, $yc_od_id){
  $od_uid = sql_real_escape_string($od_uid);
  $yc_od_id = sql_real_escape_string($yc_od_id);
  $has = sql_fetch("SELECT map_id FROM g5_saju_pgmap WHERE od_uid='{$od_uid}'");
  if($has){ sql_query("UPDATE g5_saju_pgmap SET yc_od_id='{$yc_od_id}' WHERE od_uid='{$od_uid}'"); }
  else { sql_query("INSERT INTO g5_saju_pgmap (od_uid, yc_od_id, created_at) VALUES ('{$od_uid}','{$yc_od_id}',NOW())"); }
}

function saju_pgmap_get_oduid_by_yc($yc_od_id){
  $yc_od_id = sql_real_escape_string($yc_od_id);
  $r = sql_fetch("SELECT od_uid FROM g5_saju_pgmap WHERE yc_od_id='{$yc_od_id}'");
  return $r ? $r['od_uid'] : null;
}

function saju_pick_item_by_map($order){
  if(isset($order['service_code']) && $order['service_code']!==''){
    $svc = sql_real_escape_string($order['service_code']);
    $r = sql_fetch("SELECT it_id FROM g5_saju_itemmap WHERE key_type='SERVICE' AND key_value='{$svc}'");
    if($r && $r['it_id']) return $r['it_id'];
  }
  $amt = (int)$order['amount'];
  $r = sql_fetch("SELECT it_id FROM g5_saju_itemmap WHERE key_type='PRICE' AND key_value='{$amt}'");
  if($r && $r['it_id']) return $r['it_id'];
  return null;
}

function saju_pick_item_by_category($categories){
  if(!$categories) return null;
  $arr = explode(',', $categories);
  foreach($arr as $ca){
    $ca = sql_real_escape_string(trim($ca));
    if(!$ca) continue;
    $r = sql_fetch("SELECT it_id FROM g5_shop_item WHERE (ca_id='{$ca}' OR ca_id2='{$ca}' OR ca_id3='{$ca}') AND it_use=1 ORDER BY it_order, it_id LIMIT 1");
    if($r && $r['it_id']) return $r['it_id'];
  }
  return null;
}

function saju_pick_item_by_price($amount, $tol){
  $min = max(0, $amount - $tol); $max = $amount + $tol;
  $r = sql_fetch("SELECT it_id FROM g5_shop_item WHERE it_use=1 AND it_price BETWEEN {$min} AND {$max} ORDER BY it_order, it_id LIMIT 1");
  return $r ? $r['it_id'] : null;
}

function saju_resolve_it_id($order){
  if(isset($_GET['it_id']) && $_GET['it_id']) return preg_replace('/[^A-Za-z0-9_\-]/','', $_GET['it_id']);

  $mode = saju_cfg('SELECT_MODE','AUTO');
  if($mode==='MAP_ONLY'){
    $it = saju_pick_item_by_map($order);
    return $it ?: saju_cfg('FALLBACK_IT_ID','SAJU001');
  }
  if($mode==='CATEGORY'){
    $it = saju_pick_item_by_category(saju_cfg('CATEGORY_IDS',''));
    return $it ?: saju_cfg('FALLBACK_IT_ID','SAJU001');
  }
  if($mode==='PRICE'){
    $it = saju_pick_item_by_price((int)$order['amount'], (int)saju_cfg('PRICE_TOLERANCE',100));
    return $it ?: saju_cfg('FALLBACK_IT_ID','SAJU001');
  }
  if($mode==='FIXED'){
    return saju_cfg('FALLBACK_IT_ID','SAJU001');
  }

  // AUTO
  $it = saju_pick_item_by_map($order); if($it) return $it;
  $it = saju_pick_item_by_category(saju_cfg('CATEGORY_IDS','')); if($it) return $it;
  $it = saju_pick_item_by_price((int)$order['amount'], (int)saju_cfg('PRICE_TOLERANCE',100)); if($it) return $it;
  return saju_cfg('FALLBACK_IT_ID','SAJU001');
}
