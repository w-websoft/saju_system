<?php
// ===== bootstrap =====
$__ROOT = realpath(__DIR__ . '/..');
if (!$__ROOT || !is_file($__ROOT.'/common.php')) {
  $__ROOT = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'],'/') : dirname(__DIR__);
}
include_once($__ROOT.'/common.php'); if(!defined('_GNUBOARD_')) exit;

@include_once(__DIR__.'/_bootstrap.php');
@include_once(__DIR__.'/_config.php');
@include_once(__DIR__.'/_lib.php');

$od_uid = trim($_GET['od_uid'] ?? $_POST['od_uid'] ?? '');
$od = $od_uid ? sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($od_uid)."'") : null;
if (!$od) alert('주문이 없습니다.','./index.php');
if (!saju_own_or_admin($od)) alert('권한 없음');

// 간단한 SMS 함수(아이코드 사용 환경에서)
if (!function_exists('saju_send_sms')){
  function saju_send_sms($to, $msg){
    @include_once(G5_LIB_PATH.'/icode.sms.lib.php'); // 그누보드 내장
    if (function_exists('icode_send_sms')) {
      return @icode_send_sms($to, $msg);
    }
    // 사용자 정의가 있다면 아래로 대체
    if (function_exists('send_sms')) {
      return @send_sms($to, $msg);
    }
    return false;
  }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_token();

  $method = in_array($_POST['method']??'', ['VIDEO','AUDIO']) ? $_POST['method'] : 'VIDEO';
  $dt     = trim($_POST['rv_dt'] ?? '');
  // datetime-local → 2025-09-06T11:00 → 분리
  if (!preg_match('/^\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}$/', $dt)) alert('예약 일시 형식이 올바르지 않습니다.');
  list($d,$t) = explode('T',$dt);
  $rv_date = $d; $rv_time = $t;

  // 레코드 생성
  $sql = "INSERT INTO g5_saju_reserve (od_uid, method, rv_date, rv_time, status, created_at)
          VALUES ('".sql_real_escape_string($od_uid)."',
                  '".sql_real_escape_string($method)."',
                  '".sql_real_escape_string($rv_date)."',
                  '".sql_real_escape_string($rv_time)."',
                  'REQUEST', NOW())";
  $ok = sql_query($sql,false);
  if(!$ok) alert('예약 저장 실패(테이블 확인)');

  $rv_id = sql_insert_id();

  // 상담 접속링크
  $call_link = G5_URL.'/saju/call.php?rv_id='.$rv_id;

  // SMS 안내(선택)
  $to = preg_replace('/[^0-9]/','', $od['buyer_phone'] ?? '');
  if ($to) {
    $msg = "[사주상담] 예약 접수\n시간: {$rv_date} {$rv_time}\n접속: {$call_link}";
    @saju_send_sms($to, $msg);
  }

  alert('예약이 접수되었습니다. 문자로 접속 링크가 안내됩니다.', G5_URL.'/saju/reserve_view.php?rv_id='.$rv_id);
  exit;
}

$g5['title'] = '상담 예약 신청';
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<style>.wrap{max-width:720px;margin:0 auto;padding:10px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px}</style>

<div class="wrap">
  <div class="card">
    <h3 class="sj-h3">상담 예약 신청</h3>
    <form method="post" onsubmit="return confirm('예약을 접수할까요?');">
      <input type="hidden" name="token" value="<?php echo get_token(); ?>">
      <input type="hidden" name="od_uid" value="<?php echo get_text($od_uid); ?>">

      <div class="sj-kv">
        <div><b>대상자</b></div><div><?php echo get_text($od['target_name']);?> (<?php echo ($od['gender']??'M')==='M'?'남':'여';?>)</div>
        <div><b>연락처</b></div><div><?php echo get_text($od['buyer_phone']);?></div>
      </div>
      <div class="sj-sep"></div>

      <label>상담 방식</label>
      <select name="method" required>
        <option value="VIDEO">영상</option>
        <option value="AUDIO">음성</option>
      </select>

      <div style="height:8px"></div>
      <label>예약 일시</label>
      <input type="datetime-local" name="rv_dt" required>

      <div class="sj-sep"></div>
      <button class="sj-btn" type="submit">예약 접수</button>
      <a class="sj-btn" href="<?php echo G5_URL.'/saju/view.php?od_uid='.urlencode($od_uid);?>" style="margin-left:6px">결과로 돌아가기</a>
    </form>
  </div>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
