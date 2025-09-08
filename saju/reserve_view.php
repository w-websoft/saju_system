<?php
/* =========================================================
   상담 예약 상세 (reserve_view.php)
   - /saju/reserve_view.php?rv_id=123
   - 예약 정보 확인 및 바로 입장(call.php) 버튼
   - 권한: 주문 당사자 또는 관리자
   ========================================================= */

// ---------- robust bootstrap: 그누보드 루트 ----------
$__ROOT = realpath(__DIR__ . '/..');
if (!$__ROOT || !is_file($__ROOT.'/common.php')) {
    $__ROOT = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'],'/') : dirname(__DIR__);
}
include_once($__ROOT.'/common.php'); if(!defined('_GNUBOARD_')) exit;

// ---------- 프로젝트 부트스트랩 ----------
@include_once(__DIR__.'/_bootstrap.php');
@include_once(__DIR__.'/_config.php');
@include_once(__DIR__.'/_lib.php');

// ---------- 라이브러리 ----------
include_once(__DIR__.'/lib/saju_db.php');

// ---------- 유틸(중복 방지 가드) ----------
if (!function_exists('saju_e')) {
  function saju_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('saju_own_or_admin')) {
  // 퍼미션 헬퍼가 없을 때의 보수적 기본값(주문자=mb_id 일치 또는 최고관리자)
  function saju_own_or_admin($od){
    global $is_admin, $member;
    if ($is_admin === 'super') return true;
    if (!$od) return false;
    if (!empty($member['mb_id']) && $od['mb_id'] === $member['mb_id']) return true;
    return false;
  }
}

// ---------- 입력 ----------
$rv_id = isset($_GET['rv_id']) ? (int)$_GET['rv_id'] : 0;
if ($rv_id < 1) alert('예약 번호가 없습니다.');

// ---------- 데이터 로드 ----------
$sql = "SELECT r.*,
               o.target_name, o.gender, o.birth_y, o.birth_m, o.birth_d, o.birth_h,
               o.buyer_name, o.buyer_phone, o.buyer_email, o.mb_id as od_mb_id,
               o.mb_id, o.od_uid
        FROM g5_saju_reserve r
        LEFT JOIN g5_saju_order o ON o.od_uid = r.od_uid
        WHERE r.rv_id = {$rv_id}";
$rv = sql_fetch($sql);
if (!$rv) alert('예약을 찾을 수 없습니다.');

// 주문 레코드(권한 확인용)
$od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($rv['od_uid'])."'");

// 권한: 주문자 또는 관리자
$can_view = saju_own_or_admin($od);
if (!$can_view) alert('열람 권한이 없습니다.');

// ---------- 표시 값 ----------
$g5['title'] = '상담 예약 상세';
include_once(G5_PATH.'/head.php');

$genderKR  = (($rv['gender'] ?? 'M')==='M') ? '남' : '여';
$birthY    = (int)($rv['birth_y'] ?? 0);
$birthM    = (int)($rv['birth_m'] ?? 0);
$birthD    = (int)($rv['birth_d'] ?? 0);
$birthKR   = ($birthY>0 && $birthM>0 && $birthD>0) ? sprintf('%04d-%02d-%02d', $birthY, $birthM, $birthD) : '미상';
$hourKR    = is_numeric($rv['birth_h']) ? sprintf('%02d시',(int)$rv['birth_h']) : '미상';
$rv_dt_kr  = trim(trim((string)($rv['rv_date'] ?? '')) . ' ' . trim((string)($rv['rv_time'] ?? '')));
$method    = strtoupper((string)($rv['method'] ?? ''));
$methodKR  = ($method==='VOICE' ? '음성' : ($method==='VIDEO' ? '영상' : '미정'));
$status    = strtoupper((string)($rv['status'] ?? ''));
$statusKR  = ($status==='PAID' ? '확정' : ($status==='DONE' ? '완료' : ($status==='CANCEL' ? '취소' : '요청')));
$room      = 'saju_'.$rv_id;

$link_call = G5_URL.'/saju/call.php?rv_id='.$rv_id;         // 통화실(임베드)
$link_copy = $link_call;                                    // 문자 발송 등에서 쓸 링크
$meet_url  = trim((string)($rv['meet_url'] ?? '')) !== '' ? $rv['meet_url'] : $link_call;

// 관리자 여부
$is_super  = (isset($is_admin) && $is_admin==='super');

?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<style>
  :root{ --ink:#0F172A; --muted:#64748B; --line:#E5E7EB; --card:#FFFFFF; --soft:#F8FAFC; }
  .wrap{max-width:860px;margin:0 auto;padding:12px}
  .card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px}
  .h3{font-weight:800;margin:0 0 10px 0;color:var(--ink)}
  .kv{display:grid;grid-template-columns:120px 1fr;row-gap:8px}
  .kv .k{color:var(--muted)}
  .btn{padding:10px 14px;border-radius:10px;border:1px solid var(--line);background:#fff;font-weight:600;display:inline-block}
  .btn.primary{background:#111827;color:#fff;border-color:#111827}
  .badge{display:inline-block;border:1px solid var(--line);border-radius:999px;padding:4px 10px;background:#f8fafc;margin-left:6px}
  .row{display:flex;gap:8px;flex-wrap:wrap}
  .sep{height:1px;background:#e5e7eb;margin:14px 0}
  .copy{display:flex;gap:6px}
  .copy input{flex:1;border:1px solid var(--line);border-radius:10px;padding:10px 12px}
</style>

<div class="wrap">
  <div class="card">
    <h3 class="h3">상담 예약 상세
      <span class="badge"><?php echo $statusKR; ?></span>
      <span class="badge"><?php echo $methodKR; ?></span>
    </h3>

    <div class="kv">
      <div class="k">예약번호</div><div><?php echo (int)$rv['rv_id']; ?></div>
      <div class="k">주문번호</div><div><?php echo saju_e($rv['od_uid']); ?></div>
      <div class="k">대상자</div><div><?php echo get_text(($rv['target_name'] ?: $rv['buyer_name']) ?? ''); ?> (<?php echo $genderKR;?>)</div>
      <div class="k">출생</div><div><?php echo saju_e($birthKR.' '.$hourKR); ?></div>
      <div class="k">예약 일시</div><div><?php echo saju_e($rv_dt_kr ?: '미정'); ?></div>
      <div class="k">상담 방식</div><div><?php echo saju_e($methodKR); ?></div>
      <div class="k">상태</div><div><?php echo saju_e($statusKR); ?></div>
      <div class="k">메모</div><div><?php echo nl2br(get_text($rv['memo'] ?? '',1)); ?></div>
    </div>

    <div class="sep"></div>

    <h3 class="h3">입장/링크</h3>
    <div class="row">
      <a class="btn primary" href="<?php echo $link_call; ?>" target="_blank" rel="noopener">상담실 입장(새창)</a>
      <?php if($meet_url && $meet_url !== $link_call){ ?>
        <a class="btn" href="<?php echo saju_e($meet_url); ?>" target="_blank" rel="noopener">외부링크 열기</a>
      <?php } ?>
    </div>
    <div class="copy" style="margin-top:10px">
      <input type="text" readonly value="<?php echo saju_e($link_copy); ?>" id="copyLink">
      <button class="btn" type="button" onclick="copyLink()">링크 복사</button>
    </div>

    <?php if ($is_super) { ?>
      <div class="sep"></div>
      <h3 class="h3">관리자 액션</h3>
      <form action="<?php echo G5_URL;?>/saju/admin/reserve_op.php" method="post" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="hidden" name="rv_id" value="<?php echo (int)$rv['rv_id']; ?>">
        <input type="text" name="meet_url" placeholder="통화 링크(Jitsi/Zoom 등)" value="<?php echo saju_e($rv['meet_url'] ?? ''); ?>" style="min-width:320px;padding:10px;border:1px solid var(--line);border-radius:10px">
        <select name="status" style="padding:10px;border:1px solid var(--line);border-radius:10px">
          <?php
            $opts = ['REQUEST'=>'REQUEST','PAID'=>'PAID','DONE'=>'DONE','CANCEL'=>'CANCEL'];
            foreach($opts as $v=>$t){ $sel = ($status===$v ? 'selected' : ''); echo "<option value='{$v}' {$sel}>{$t}</option>"; }
          ?>
        </select>
        <button class="btn primary" type="submit">저장</button>
        <a class="btn" href="<?php echo G5_URL;?>/saju/admin/reserve_list.php">목록</a>
      </form>
    <?php } else { ?>
      <div class="sep"></div>
      <div class="row">
        <a class="btn" href="<?php echo G5_URL;?>/saju/view.php?od=<?php echo urlencode($rv['od_uid']);?>">사주 결과로</a>
      </div>
    <?php } ?>
  </div>
</div>

<script>
function copyLink(){
  var el = document.getElementById('copyLink');
  el.select(); el.setSelectionRange(0, 99999);
  try {
    document.execCommand('copy');
    alert('링크가 복사되었습니다.');
  } catch(e){
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(el.value).then(function(){ alert('링크가 복사되었습니다.'); });
    } else {
      alert('복사 실패. 링크를 직접 선택해 복사하세요.');
    }
  }
}
</script>

<?php include_once(G5_PATH.'/tail.php'); ?>
