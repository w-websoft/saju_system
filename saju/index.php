<?php
// GNUBOARD 루트 common.php 확실히 로드
$__ROOT = realpath(__DIR__ . '/..');
if (!$__ROOT || !is_file($__ROOT.'/common.php')) {
    $__ROOT = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'],'/') : dirname(__DIR__);
}
include_once($__ROOT . '/common.php'); if (!defined('_GNUBOARD_')) exit;

@include_once(__DIR__.'/_bootstrap.php');
@include_once(__DIR__.'/_config.php');
@include_once(__DIR__.'/_lib.php');

$g5['title'] = '맞춤 사주 신청';
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<link rel="stylesheet" href="<?php echo G5_URL?>/js/jquery-ui/jquery-ui.min.css">
<script src="<?php echo G5_URL?>/js/jquery-ui/jquery-ui.min.js"></script>

<div class="sj-wrap">
  <div class="sj-card sj-hero">
    <h2>사주 상담 신청</h2>
    <p>결제 후 자동풀이가 즉시 제공되며, 영상/음성 상담을 예약할 수 있습니다. (비회원도 가능)</p>
  </div>

  <div class="sj-card" style="margin-top:14px">
    <form action="./submit.php" method="post" onsubmit="return f_submit(this);">
      <input type="hidden" name="token" value="<?php echo get_token(); ?>">

      <!-- 서버 저장에 직접 쓰는 hidden -->
      <input type="hidden" name="birth_y" id="birth_y">
      <input type="hidden" name="birth_m" id="birth_m">
      <input type="hidden" name="birth_d" id="birth_d">
      <input type="hidden" name="birth_h" id="birth_h">

      <h3 class="sj-h3">신청자</h3>
      <div class="sj-kv">
        <div style="flex:1">
          <label>이름</label>
          <input type="text" name="buyer_name" required>
        </div>
        <div style="flex:1">
          <label>연락처</label>
          <input type="text" name="buyer_phone" placeholder="010-0000-0000" pattern="^0\d{1,2}-?\d{3,4}-?\d{4}$" title="전화번호 형식: 010-1234-5678" required>
        </div>
        <div style="flex:1">
          <label>이메일(선택)</label>
          <input type="email" name="buyer_email" placeholder="선택 입력">
        </div>
      </div>

      <div class="sj-sep"></div>

      <h3 class="sj-h3">대상자</h3>
      <div class="sj-kv">
        <div style="flex:1">
          <label>이름</label>
          <input type="text" name="target_name" required>
        </div>
        <div style="flex:1">
          <label>성별</label>
          <select name="gender" required>
            <option value="M">남</option>
            <option value="F">여</option>
          </select>
        </div>
      </div>

      <div class="sj-kv" style="margin-top:10px">
        <div style="flex:1">
          <label>생년월일</label>
          <input type="text" name="birth_ymd" id="birth_ymd" placeholder="YYYY-MM-DD 또는 YYYYMMDD" autocomplete="off" required>
          <div class="sj-p" style="margin-top:4px;color:#64748b">예) 19970522 또는 1997-05-22</div>
        </div>
        <div style="flex:1">
          <label>양력/음력</label>
          <select name="birth_type" id="birth_type">
            <option value="SOLAR">양력</option>
            <option value="LUNAR">음력</option>
          </select>
        </div>
        <div style="flex:1">
          <label>태어난 시(지지)</label>
          <select name="birth_time" id="birth_time">
            <option value="">모름</option>
            <option value="23">子시(23~01)</option>
            <option value="1">丑시(01~03)</option>
            <option value="3">寅시(03~05)</option>
            <option value="5">卯시(05~07)</option>
            <option value="7">辰시(07~09)</option>
            <option value="9">巳시(09~11)</option>
            <option value="11">午시(11~13)</option>
            <option value="13">未시(13~15)</option>
            <option value="15">申시(15~17)</option>
            <option value="17">酉시(17~19)</option>
            <option value="19">戌시(19~21)</option>
            <option value="21">亥시(21~23)</option>
          </select>
          <div class="sj-p" style="margin-top:4px;color:#64748b">* 모르면 비워두세요(기본 12시로 계산)</div>
        </div>
      </div>

      <div style="margin-top:10px">
        <label>추가요청</label>
        <textarea name="memo" rows="3" placeholder="중점 상담 분야(금전/관계/사업/건강 등)"></textarea>
      </div>

      <div class="sj-sep"></div>

      <div style="display:flex;justify-content:space-between;align-items:center">
        <div class="sj-p">금액(참고): <b><?php echo saju_format_amount(saju_cfg('PRICE',55000));?></b></div>
        <button class="sj-btn" type="submit">다음(결제)</button>
      </div>
    </form>
  </div>
</div>

<script>
$(function(){
  $("#birth_ymd").datepicker({
    dateFormat:"yy-mm-dd",
    changeMonth:true,
    changeYear:true,
    yearRange:"-100:+0"
  });
});

/**
 * YYYYMMDD 또는 YYYY-MM-DD 모두 허용 → YYYY-MM-DD로 정규화
 * hidden(birth_y/m/d/h)에 값 주입, 시간 모르면 12시
 */
function f_submit(f){
  if(!f.buyer_name.value.trim()){ alert('신청자 이름을 입력해 주세요.'); f.buyer_name.focus(); return false; }
  if(!f.target_name.value.trim()){ alert('대상자 이름을 입력해 주세요.'); f.target_name.focus(); return false; }

  var raw = (f.birth_ymd.value || '').trim();
  raw = raw.replace(/\./g,'-');         // 1997.05.22 → 1997-05-22
  var digits = raw.replace(/\D/g,'');   // 숫자만

  var y, m, d;
  if (digits.length === 8) {
    y = parseInt(digits.substr(0,4),10);
    m = parseInt(digits.substr(4,2),10);
    d = parseInt(digits.substr(6,2),10);
  } else if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    var sp = raw.split('-'); y=parseInt(sp[0],10); m=parseInt(sp[1],10); d=parseInt(sp[2],10);
  } else {
    alert('생년월일 형식이 올바르지 않습니다. (YYYY-MM-DD 또는 YYYYMMDD)');
    f.birth_ymd.focus(); return false;
  }

  // 유효범위 대략 체크
  if (!(y>=1900 && y<=2100 && m>=1 && m<=12 && d>=1 && d<=31)) {
    alert('생년월일 범위를 확인해 주세요.'); return false;
  }

  // 시간: 지지 선택 → 엔진이 이해하는 정시로 전송(기본 12)
  var h = f.birth_time.value === '' ? 12 : parseInt(f.birth_time.value,10);

  // hidden 주입
  document.getElementById('birth_y').value = y;
  document.getElementById('birth_m').value = m;
  document.getElementById('birth_d').value = d;
  document.getElementById('birth_h').value = h;

  // 화면 입력창은 보기 좋게 YYYY-MM-DD로 통일
  f.birth_ymd.value = (y+'-'+('0'+m).slice(-2)+'-'+('0'+d).slice(-2));

  return true;
}
</script>
<?php include_once(G5_PATH.'/tail.php'); ?>
