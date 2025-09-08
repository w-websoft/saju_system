<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('saju_cfg')) {
    function saju_cfg($key, $default=null){
        global $g5;
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $rows = sql_query("SELECT cf_key, cf_value FROM g5_saju_config");
            while($row = sql_fetch_array($rows)){
                $cache[$row['cf_key']] = $row['cf_value'];
            }
        }
        return isset($cache[$key]) ? $cache[$key] : $default;
    }
}


function saju_make_uid() { return substr(md5(uniqid('', true)), 0, 32); }

function saju_allow_guest(){ return (int)saju_cfg('ALLOW_GUEST',1) ? true:false; }

function saju_require_login() {
    global $is_member;
    if (!$is_member && !saju_allow_guest()) {
        alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php');
    }
}

function saju_format_amount($n) { return number_format((int)$n).'원'; }

function saju_get_order($od_uid) {
    $od_uid = sql_real_escape_string($od_uid);
    return sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='{$od_uid}'");
}

function saju_own_or_admin($order) {
    global $member, $is_admin;
    if ($is_admin == 'super') return true;
    if ($order['mb_id'] && $member['mb_id'] === $order['mb_id']) return true;
    // 게스트 세션
    if (!$order['mb_id']) {
        $sess = get_session('saju_view_'.$order['od_uid']);
        if ($sess === 'Y') return true;
    }
    return false;
}

