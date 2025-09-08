<?php
include_once('../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('./_bootstrap.php'); include_once('./_config.php'); include_once('./_lib.php');

$od = saju_get_order($_GET['od']); if(!$od) alert('주문 없음','./index.php');
if(!saju_own_or_admin($od)) alert('권한 없음');
if(!in_array($od['status'], ['PAID','DONE'])) alert('결제 후 예약 가능','./pay.php?od='.$od['od_uid']);

$g5['title'] = '상담 예약'; include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<link rel="stylesheet" href="<?php echo G5_URL?>/js/jquery-ui/jquery-ui.min.css">
<script src="<?php echo G5_URL?>/js/jquery-ui/jquery-ui.min.js"></script>

<div class="sj-wrap">
  <div class="sj-grid">
    <div class="sj-card">
      <h3 class="sj-h3">상담 예약</h3>
      <p class="sj-p">방법을 고르고 날짜/시간을 선택하세요. (기본 30분)</p>
      <form action="./reserve_submit.php" method="post" onsubmit="return rv_ok(this);">
        <input type="hidden" name="token" value="<?php echo get_token(); ?>">
        <input type="hidden" name="od_uid" value="<?php echo $od['od_uid'];?>">
        <div class="sj-kv"><b>방식</b>
          <label><input type="radio" name="method" value="VOICE" checked> 음성통화</label>
          <label><input type="radio" name="method" value="VIDEO"> 영상통화(WebRTC)</label>
        </div>
        <div class="sj-sep"></div>
        <label>원하는 날짜</label><input type="text" id="rv_date" name="rv_date" placeholder="YYYY-MM-DD" required>
        <div class="sj-sep"></div>
        <label>시간 선택</label>
        <select name="rv_time" required>
          <?php $slots = ['10:00','11:00','14:00','15:00','16:00','20:00','21:00']; foreach($slots as $t) echo '<option value="'.$t.'">'.$t.'</option>'; ?>
        </select>
        <div class="sj-sep"></div>
        <label>메모(선택)</label><textarea name="memo" rows="3" placeholder="원하는 포인트(금전/관계/사업/건강 등)"></textarea>
        <div class="sj-sep"></div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div class="sj-p">상담비: <b><?php echo saju_format_amount(30000);?></b></div>
          <button class="sj-btn" type="submit">상담 결제(테스트)</button>
        </div>
      </form>
    </div>

    <div class="sj-card">
      <h3 class="sj-h3">안내</h3>
      <div class="sj-list">
        <div class="item">결제 확정 후, 상담 시간에 접속할 링크가 발송됩니다.</div>
        <div class="item">WebRTC(브라우저 통화)로 모바일/PC 모두 지원됩니다.</div>
      </div>
    </div>
  </div>
</div>

<script>
$('#rv_date').datepicker({ dateFormat:"yy-mm-dd", minDate:+1, changeMonth:true, changeYear:true });
function rv_ok(f){ if(!f.rv_date.value){ alert('날짜 선택'); return false; } return true; }
</script>
<?php include_once(G5_PATH.'/tail.php'); ?>
