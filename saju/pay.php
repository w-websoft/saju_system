<?php
// 공통 로드 (경로 안전)
$__ROOT = realpath(__DIR__ . '/..');
if (!$__ROOT || !is_file($__ROOT.'/common.php')) {
    $__ROOT = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'],'/') : dirname(__DIR__);
}
include_once($__ROOT.'/common.php'); if (!defined('_GNUBOARD_')) exit;

@include_once(__DIR__.'/_bootstrap.php');
@include_once(__DIR__.'/_config.php');
@include_once(__DIR__.'/_lib.php');
@include_once(__DIR__.'/yc/_yc_config.php');
@include_once(__DIR__.'/yc/_yc_lib.php');

// 주문 키 (od_uid 우선, od도 허용)
$od_key = $_GET['od_uid'] ?? $_GET['od'] ?? '';
$od = null;

if ($od_key !== '') {
    // 1) 우리 주문 테이블
    $od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($od_key)."'");
    // 2) 영카트 주문번호만 온 경우 매핑테이블로 od_uid 찾기
    if (!$od) {
        $map = sql_fetch("SELECT od_uid FROM g5_saju_pgmap WHERE yc_od_id='".sql_real_escape_string($od_key)."'");
        if ($map && $map['od_uid']) {
            $od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($map['od_uid'])."'");
        }
    }
}

if (!$od) alert('주문이 없습니다.','./index.php');
if (!saju_own_or_admin($od)) alert('권한 없음');

// 표시용 값 정리
$od_uid   = $od['od_uid'];
$status   = $od['order_status'] ?: $od['status']; // 둘 중 하나 사용
$is_paid  = in_array($status, ['PAID','DONE']);
$gender   = ($od['gender'] ?? 'M') === 'M' ? '남' : '여';

$birth_type = ($od['birth_type'] ?? 'SOLAR') === 'LUNAR' ? '음력' : '양력';

// 생년월일 포맷 (신규 필드 우선, 없으면 구필드 대응)
$birth_ymd = '';
if ((int)$od['birth_y'] > 0 && (int)$od['birth_m'] > 0 && (int)$od['birth_d'] > 0) {
    $birth_ymd = sprintf('%04d-%02d-%02d', (int)$od['birth_y'], (int)$od['birth_m'], (int)$od['birth_d']);
} elseif (!empty($od['birth_ymd'])) {
    $digits = preg_replace('/\D/','', $od['birth_ymd']);
    if (strlen($digits) === 8) {
        $birth_ymd = substr($digits,0,4).'-'.substr($digits,4,2).'-'.substr($digits,6,2);
    }
}

$g5['title'] = '결제 - '.get_text($od['target_name']);
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">

<div class="sj-wrap">
  <div class="sj-card">
    <h3 class="sj-h3">결제</h3>
    <div class="sj-list">
      <div class="item">주문번호: <b><?php echo get_text($od_uid);?></b></div>
      <div class="item">대상자: <?php echo get_text($od['target_name']);?> (<?php echo $gender;?>)</div>
      <div class="item">생년월일: <?php echo $birth_ymd ? $birth_ymd : '-';?> (<?php echo $birth_type;?>)</div>
      <div class="item">상태: <?php echo get_text($status);?></div>
    </div>

    <?php if($is_paid){ ?>
      <a class="sj-btn" href="./view.php?od_uid=<?php echo urlencode($od_uid);?>">결제완료됨 → 결과/상담</a>
    <?php } else { ?>
      <div class="sj-sep"></div>
      <?php if((int)saju_cfg('USE_YOUNGCART',0)===1){ ?>
        <h3 class="sj-h3">영카트 실결제</h3>
        <a class="sj-btn" href="./yc/yc_start.php?od_uid=<?php echo urlencode($od_uid);?>">영카트 결제 진행</a>
        <p class="sj-p" style="margin-top:8px">여러 사주상품 보유 시 자동으로 적합한 상품을 선택합니다.</p>
        <div class="sj-sep"></div>
      <?php } ?>
      <h3 class="sj-h3">테스트 결제(DUMMY)</h3>
      <form action="./pay_notify.php" method="post" onsubmit="return confirm('결제(테스트) 진행할까요?');">
        <input type="hidden" name="od_uid" value="<?php echo get_text($od_uid);?>">
        <input type="hidden" name="mode" value="DUMMY_SUCCESS">
        <button class="sj-btn" type="submit">온라인 결제(테스트 즉시 성공)</button>
      </form>
    <?php } ?>
  </div>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
