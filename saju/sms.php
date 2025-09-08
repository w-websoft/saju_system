<?php
// saju/sms.php
if (!defined('_GNUBOARD_')) exit;

/**
 * 아이코드 전용 안정화 래퍼
 * - 함수(API) 방식: UTF-8 그대로
 * - 클래스(소켓) 방식: EUC-KR 변환
 * - 발신번호 필수 및 call_back/caller 모두 지정
 * 사용: $ok = saju_send_sms('01012345678,01098765432','내용',$why);
 */
function saju_send_sms($to, $msg, &$why = null) {
    global $config, $default;

    $to  = preg_replace('/[^0-9,]/','',(string)$to);
    $msg = trim((string)$msg);
    if (!$to || !$msg) { $why='invalid_to_or_msg'; return false; }

    // ▶ 발신번호: 관리자 설정에서 가져와 숫자만
    $sender = '';
    if (!empty($config['cf_sms_from']))         $sender = preg_replace('/[^0-9]/','',$config['cf_sms_from']);
    elseif (!empty($default['de_sms_hp']))      $sender = preg_replace('/[^0-9]/','',$default['de_sms_hp']);
    elseif (!empty($config['cf_icode_phone']))  $sender = preg_replace('/[^0-9]/','',$config['cf_icode_phone']);

    // ▶ 발신번호 필수 (아이코드 정책상 미등록/미일치 시 미발송)
    if (!$sender || strlen($sender) < 10) { $why='sender_not_set'; return false; }

    // 라이브러리 로드(여러 경로 시도)
    $cands = [];
    if (defined('G5_LIB_PATH')) { $cands[] = G5_LIB_PATH.'/icode.sms.lib.php'; $cands[] = G5_LIB_PATH.'/sms.lib.php'; }
    if (defined('G5_PATH'))      { $cands[] = G5_PATH.'/lib/icode.sms.lib.php'; $cands[] = G5_PATH.'/lib/sms.lib.php'; }
    $root_guess = dirname(__DIR__);
    $cands[] = $root_guess.'/lib/icode.sms.lib.php';
    $cands[] = $root_guess.'/lib/sms.lib.php';
    foreach (array_unique($cands) as $p) if (is_file($p)) include_once($p);

    // 단건 발송 루프
    $receivers = array_filter(array_map('trim', explode(',', $to)));
    if (!count($receivers)) { $why='no_valid_receivers'; return false; }

    // 결과 해석
    $ok = function($r, &$why) {
        if ($r === true) return true;
        if ($r === false || $r === 0 || $r === '0') { $why='provider_return_false'; return false; }
        if (is_array($r)) {
            if (isset($r['result'])) {
                $res = strtolower((string)$r['result']);
                if (in_array($res, ['success','ok','y','1'])) return true;
                $why = 'provider_result: '.$res; return false;
            }
            if (isset($r['error'])) { $why='provider_error: '.$r['error']; return false; }
            if (isset($r['desc']))  { $why='provider_desc: '.$r['desc'];  return false; }
            $why = 'provider_array_unknown'; return false;
        }
        if (is_string($r)) {
            $s = strtolower(trim($r));
            if (in_array($s, ['ok','success','y','1'])) return true;
            $why = 'provider_msg: '.$r; return false;
        }
        $why = 'provider_unknown_type'; return false;
    };

    // ① 함수(API) 방식 (UTF-8)
    if (function_exists('icode_send_sms') || function_exists('icode_send_lms') || function_exists('icode_sms_send')) {
        $all_ok = true; $last_err = null;
        foreach ($receivers as $rcv) {
            if (function_exists('icode_send_lms') && strlen($msg) > 90) {
                $r = @icode_send_lms($rcv, $sender, '알림', $msg, '');
            } elseif (function_exists('icode_send_sms')) {
                $r = @icode_send_sms($rcv, $sender, $msg, '');
            } else { // icode_sms_send(구버전)
                $r = @icode_sms_send($rcv, $msg); // 일부 구버전은 내부 설정의 발신번호 사용
            }
            if (!$ok($r, $err)) { $all_ok=false; $last_err=$err; }
        }
        if ($all_ok) return true;
        $why = 'icode_func_send_partial_or_fail: '.$last_err;
        return false;
    }

    // ② 클래스(소켓) 방식 (EUC-KR)
    if (class_exists('SMS')) {
        $svr_ip   = (string)($config['cf_icode_server_ip'] ?? '211.172.232.124');
        $svr_port = (int)($config['cf_icode_server_port'] ?? 7295);
        $icode_id = (string)($config['cf_icode_id'] ?? '');
        $icode_pw = (string)($config['cf_icode_pw'] ?? '');
        if (!$icode_id || !$icode_pw) { $why='icode_credentials_empty'; return false; }

        $msg_euckr = function_exists('iconv') ? @iconv('UTF-8','EUC-KR//IGNORE',$msg) : $msg;

        try {
            $SMS = new SMS;
            $SMS->SMS_con($svr_ip, $icode_id, $icode_pw, $svr_port);
            foreach ($receivers as $rcv) {
                // Add(수신, call_back, caller, 내용, 예약일자, 예약시간) → 두 파라미터 모두 발신번호 지정
                $SMS->Add($rcv, $sender, $sender, $msg_euckr, '', '');
            }
            $send = $SMS->Send();
            $SMS->Init();
            if ($ok($send, $err)) return true;
            $why = 'icode_class_send_failed: '.$err; return false;
        } catch (\Throwable $e) {
            $why = 'icode_class_exception: '.$e->getMessage(); return false;
        }
    }

    $why = 'no_sms_module_found(icode)';
    return false;
}

/* 하위호환 */
if (!function_exists('send_sms')) {
    function send_sms($to, $msg){ $w=null; return saju_send_sms($to, $msg, $w); }
}
