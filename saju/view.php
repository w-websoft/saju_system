<?php
/* =========================================================
   SAJU RESULT VIEW (통합 안정판)
   - 영카트 연동/단독 모두 동작
   - od / od_uid / od_id 파라미터 지원
   - 출생정보 URL 보정(y,m,d,h) 허용
   - 자동 해설 + 전문가 해설 + 기도길일
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

// ---------- 라이브러리 ----------
include_once(__DIR__.'/lib/saju_db.php');
include_once(__DIR__.'/lib/saju_calc.php');

// ---------- 유틸 ----------
if (!function_exists('saju_e')) {
  function saju_e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
}

// ---------- 주문 키 취득(od_uid / od / od_id) ----------
$od_key = '';
if (isset($_GET['od_uid']))      $od_key = trim($_GET['od_uid']);
else if (isset($_GET['od']))     $od_key = trim($_GET['od']);
else if (isset($_GET['od_id']))  $od_key = trim($_GET['od_id']);

$od = null;
if ($od_key !== '') {
    // 1) 자체 주문
    $od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($od_key)."'");
    // 2) 영카트 주문번호만 온 경우 매핑 테이블로 od_uid 획득
    if (!$od) {
        $map = sql_fetch("SELECT od_uid FROM g5_saju_pgmap WHERE yc_od_id='".sql_real_escape_string($od_key)."'");
        if ($map && $map['od_uid']) {
            $od_key = $map['od_uid'];
            $od = sql_fetch("SELECT * FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($od_key)."'");
        }
    }
}
if (!$od) alert('주문 없음');

// ---------- 출생정보(DB 우선, URL 보정 허용) ----------
$birthY = (int)$od['birth_y'];
$birthM = (int)$od['birth_m'];
$birthD = (int)$od['birth_d'];
$birthH = is_numeric($od['birth_h']) ? (int)$od['birth_h'] : 12; // 미입력 시 정오

if (isset($_GET['y'])) $birthY = (int)$_GET['y'];
if (isset($_GET['m'])) $birthM = (int)$_GET['m'];
if (isset($_GET['d'])) $birthD = (int)$_GET['d'];
if (isset($_GET['h'])) $birthH = (int)$_GET['h'];

if ($birthY < 1 || $birthM < 1 || $birthD < 1) {
    alert('출생정보가 없습니다. (연/월/일 필수)');
}

// ---------- 엔진 계산 + 캐시 ----------
$mode   = saju_cfg('ENGINE_MODE','ROUGH');
$key    = saju_calc_key($birthY,$birthM,$birthD,$birthH,$mode);
$cached = saju_cache_get($key);
$S      = $cached ? json_decode($cached,true) : saju_build($birthY,$birthM,$birthD,$birthH);
if (!$cached) saju_cache_put($key, json_encode($S, JSON_UNESCAPED_UNICODE), null);

// ---------- 전문가 해설 로딩 ----------
if (!function_exists('saju_get_expert_reading')) {
  function saju_get_expert_reading($od_uid){
    $row = sql_fetch("SELECT * FROM g5_saju_reading WHERE od_uid='".sql_real_escape_string($od_uid)."' ORDER BY id DESC LIMIT 1");
    if ($row && trim((string)$row['content'])!=='') {
      return [
        'exists' => true,
        'title'  => $row['title'] ?? '전문가 해설',
        'content'=> $row['content'],
        'updated'=> $row['updated_at'] ?? $row['created_at']
      ];
    }
    return ['exists'=>false];
  }
}

// ---------- 오행관계 유틸 ----------
if (!function_exists('saju_rel_elem')) {
  function saju_rel_elem($elem, $type){
    // 생: 木→火→土→金→水→木
    $sheng = ['木'=>'火','火'=>'土','土'=>'金','金'=>'水','水'=>'木'];
    // 극: 木克土, 土克水, 水克火, 火克金, 金克木
    $ke    = ['木'=>'土','土'=>'水','水'=>'火','火'=>'金','金'=>'木'];
    // 나를 생하는 것(모체)
    $mother = ['木'=>'水','火'=>'木','土'=>'火','金'=>'土','水'=>'金'];

    switch($type){
      case 'wealth':   return $ke[$elem]        ?? null;                 // 재성(내가 극함)
      case 'officer':  return array_search($elem, $ke, true) ?: null;    // 관성(나를 극함)
      case 'output':   return $sheng[$elem]     ?? null;                 // 식상(내가 생함)
      case 'resource': return $mother[$elem]    ?? null;                 // 인성(나를 생함)
    }
    return null;
  }
}

// ---------- 자동 해설 생성 ----------
if (!function_exists('saju_auto_explain')) {
  function saju_auto_explain(array $S, int $y=0, int $m=0, int $d=0, int $h=12){
    $pillars = $S['pillars'] ?? [];
    $day     = $pillars['day']   ?? [];
    $month   = $pillars['month'] ?? [];

    $dayElem = (string)($day['g']['elem'] ?? '');
    $dayGan  = (string)($day['g']['han']  ?? '');
    $yy      = (string)($day['g']['yy']   ?? '');
    $mlife   = (string)($month['life12']  ?? '');

    $ec = $S['elem_count'] ?? $S['elem_counts'] ?? [];
    foreach (['木','火','土','金','水'] as $k) if (!isset($ec[$k])) $ec[$k] = 0;
    $sum = max(1, array_sum($ec));
    $maxE=''; $maxV=-1; $minE=''; $minV=99;
    foreach (['木','火','土','金','水'] as $e) {
      $v = (int)$ec[$e];
      if ($v>$maxV){ $maxV=$v;$maxE=$e; }
      if ($v<$minV){ $minV=$v;$minE=$e; }
    }
    $balance = ($maxV - $minV >= 2)
      ? "오행 편중이 있습니다(강: {$maxE}, 약: {$minE}). 약한 {$minE} 기운 보완 권장."
      : "오행 균형이 비교적 안정적입니다. 무리한 변화보다 현재 루틴 유지가 유리합니다.";

    $wealth   = saju_rel_elem($dayElem,'wealth');    $cntWealth   = (int)($ec[$wealth]   ?? 0);
    $officer  = saju_rel_elem($dayElem,'officer');   $cntOfficer  = (int)($ec[$officer]  ?? 0);
    $resource = saju_rel_elem($dayElem,'resource');  $cntResource = (int)($ec[$resource] ?? 0);
    $output   = saju_rel_elem($dayElem,'output');    $cntOutput   = (int)($ec[$output]   ?? 0);

    $traitMap = [
      '木'=>['kw'=>'성장·원칙·정직','good'=>'성장이 빠르고 곧은 성격. 정의감/원칙 중시, 추진력 양호.','weak'=>'융통/협상력이 부족해 보일 수 있어 완급조절과 기록·합의 습관 권장.'],
      '火'=>['kw'=>'열정·표현·속도','good'=>'에너지/표현력 우수, 리딩에 강점.','weak'=>'과열/과속 경향. 수면·심박 리듬 관리 필수.'],
      '土'=>['kw'=>'안정·신뢰·현실','good'=>'신중/책임감, 운영/관리/조율 강점.','weak'=>'보수성으로 기회 손실 우려. 의사결정 속도 관리.'],
      '金'=>['kw'=>'분석·규율·정밀','good'=>'분석/판단/품질·리스크 관리 탁월.','weak'=>'완벽주의/비판성으로 관계 피로 주의.'],
      '水'=>['kw'=>'지혜·유연·연결','good'=>'유연/정보감각/연결력이 강함.','weak'=>'결단 지연 우려. 마감/체크리스트 필수.'],
    ];
    $t = $traitMap[$dayElem] ?? ['kw'=>'','good'=>'','weak'=>''];

    $moneyMsg = ($cntWealth>=2)
      ? "재성({$wealth}) 비중이 있어 실수입 창출/계약 유리. 다만 과투자·차입은 금물."
      : "재성({$wealth}) 약세. 지출/리스크 통제를 먼저 확립하고 수익 다변화는 단계적으로.";
    $workMsg  = ($cntOutput>=2)
      ? "식상({$output}) 활성: 콘텐츠·영업·전문성 발휘에 유리."
      : "식상({$output}) 약세: 백오피스/운영 안정형이 적합.";
    $ruleMsg  = ($cntOfficer>=2)
      ? "관성({$officer}) 강세: 규율·법·품질·공공영역과 인연. 책임/평판 중시."
      : "관성({$officer}) 약세: 자유도 높은 프로젝트형이 적합할 수 있음.";
    $studyMsg = ($cntResource>=2)
      ? "인성({$resource}) 받침: 공부/자격/멘토 도움 얻기 좋음."
      : "인성({$resource}) 약세: 기본기·휴식·회복 루틴 우선.";

    $relMsg = ($cntOutput>=2)
      ? "표현/소통 활발 → 네트워크 확장 유리. 말보다 계약/기록 우선."
      : "과묵·집중형 → 신뢰 구축 강점. 피드백 타이밍을 의식적으로.";

    $healthMap = [
      '木'=>'간·근육·눈 / 스트레칭, 녹채소, 아침 햇빛',
      '火'=>'심혈관·혈압 / 수면·열 관리, 과음 주의',
      '土'=>'비위·소화 / 규칙식·곡물, 장건강 루틴',
      '金'=>'폐·피부·호흡 / 공기질·습도 관리',
      '水'=>'신장·수분대사 / 수분 섭취, 하체 보온',
    ];
    $healthMsg = '약한 오행 보완: '.($healthMap[$minE] ?? '생활 리듬 고른 관리');

    $shen = $S['shensha'] ?? [];
    $shenTop = [];
    foreach($shen as $name=>$arr){ if(!$arr) continue; $shenTop[]=$name; if(count($shenTop)>=8) break; }
    $shenLine = $shenTop ? '주요 신살: '.implode(' · ',$shenTop) : '신살 특이점: 뚜렷하지 않음';

    $cfl  = $S['conflicts'] ?? [];
    $cflLine = $cfl ? ('형충/파: '.implode(' , ',$cfl)) : '';

    ob_start(); ?>
      <div style="line-height:1.75">
        <p><b>일간/오행:</b> <?=saju_e($dayGan.'('.$dayElem.')');?> · <?=saju_e($yy);?>
           · <b>월령 12운성:</b> <?=saju_e($mlife)?></p>

        <p><b>오행 분포:</b>
          <?php foreach(['木','火','土','金','水'] as $e):
            $v=(int)$ec[$e]; $w=min(100, round($v/$sum*100));
          ?>
          <span style="display:inline-flex;gap:6px;align-items:center;margin-right:10px">
            <span><?=$e?></span>
            <span style="display:inline-block;width:90px;height:8px;background:#f1f5f9;border-radius:8px;overflow:hidden">
              <span style="display:block;width:<?=$w?>%;height:100%;background:#111827"></span>
            </span>
            <span><?=$v?></span>
          </span>
          <?php endforeach;?>
        </p>
        <p class="sub" style="color:#64748b"><?=saju_e($balance)?></p>

        <h4 style="margin:10px 0 6px;font-weight:700">성향/성격</h4>
        <ul style="margin:0 0 8px 18px">
          <li>키워드: <?=saju_e($t['kw'])?></li>
          <li><?=saju_e($t['good'])?></li>
          <li>약점/보완: <?=saju_e($t['weak'])?></li>
        </ul>

        <h4 style="margin:10px 0 6px;font-weight:700">금전/일(커리어)</h4>
        <ul style="margin:0 0 8px 18px">
          <li><?=saju_e($moneyMsg)?></li>
          <li><?=saju_e($workMsg)?></li>
          <li><?=saju_e($ruleMsg)?></li>
          <li><?=saju_e($studyMsg)?></li>
        </ul>

        <h4 style="margin:10px 0 6px;font-weight:700">관계/커뮤니케이션</h4>
        <p><?=saju_e($relMsg)?></p>

        <h4 style="margin:10px 0 6px;font-weight:700">건강/생활 루틴</h4>
        <p><?=saju_e($healthMsg)?></p>

        <p class="sub" style="color:#64748b;margin-top:8px">
          <?=saju_e($shenLine)?><?php if($cflLine){ echo ' · '.saju_e($cflLine); } ?>
        </p>
        <p class="sub" style="color:#9aa2af">※ 자동 해설은 원국 중심의 일반 가이드입니다. 실제 상담에서 세운/대운/택일을 반영해 구체화합니다.</p>
      </div>
    <?php
    return ob_get_clean();
  }
}

// ---------- 기도 길일(없어도 안전) ----------
$reco = null;
if (function_exists('saju_prayer_get') && function_exists('saju_suggest_prayer_days') && function_exists('saju_prayer_save')) {
  $today = new DateTime('today');
  $ref   = $today->format('Y-m-d');
  $pf_id = null;
  $cached_days = saju_prayer_get((int)$pf_id, $ref);
  if ($cached_days){
    $reco = array_map(function($row){
      return ['date'=>$row['date'], 'ganji'=>$row['ganji'], 'officer'=>$row['officer'], 'score'=>(int)$row['score']];
    }, $cached_days);
  } else {
    $reco = saju_suggest_prayer_days($birthY,$birthM,$birthD,$birthH, (int)$today->format('Y'),(int)$today->format('n'),(int)$today->format('j'), 60);
    saju_prayer_save((int)$pf_id, $ref, $reco);
  }
}
if (!is_array($reco)) $reco = [];

// ---------- 페이지 타이틀 ----------
$g5['title']='사주 결과';
include_once(G5_PATH.'/head.php');

// ---------- 표시용 값 ----------
$od_uid   = $od['od_uid'];
$genderKR = ($od['gender'] ?? 'M')==='M' ? '남' : '여';
$birthKR  = sprintf('%04d-%02d-%02d', $birthY, $birthM, $birthD);
$hourKR   = ($birthH===12 ? '미상(정오적용)' : sprintf('%02d시', $birthH));
$birthTypeKR = (($od['birth_type']??'SOLAR')==='LUNAR')?'음력':'양력';

$conflicts = $S['conflicts'] ?? [];
$expert    = saju_get_expert_reading($od_uid);
$autoExplainHtml = saju_auto_explain($S, $birthY,$birthM,$birthD,$birthH);

$rv_apply_url = G5_URL.'/saju/reserve_apply.php?od_uid='.urlencode($od_uid);
$again_url    = G5_URL.'/saju/apply.php';
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<style>
  :root{ --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --card:#ffffff; --soft:#f8fafc; --pri:#111827; --green:#16a34a; --red:#ef4444; }
  .wrap{max-width:1100px;margin:0 auto;padding:10px;}
  .hero{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px 20px;display:flex;gap:18px;align-items:center}
  .avatar{width:64px;height:64px;border-radius:14px;background:linear-gradient(135deg,#eee,#ddd);}
  .title{font-size:20px;font-weight:800;color:var(--ink)}
  .sub{color:var(--muted);margin-top:4px}
  .toolbar{margin-left:auto; display:flex; gap:8px;flex-wrap:wrap}
  .btn{padding:10px 14px;border-radius:10px;border:1px solid var(--line);background:#fff;font-weight:600}
  .btn.primary{background:#111827;color:#fff;border-color:#111827}
  .grid{display:grid;grid-template-columns:1fr;gap:14px;margin-top:14px}
  @media(min-width:980px){ .grid{grid-template-columns:2fr 1fr;} }
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px}
  .h3{font-weight:800;margin:0 0 10px 0;color:var(--ink)}
  .kv{display:grid;grid-template-columns:120px 1fr;row-gap:8px}
  .kv .k{color:var(--muted)}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{border-top:1px solid var(--line);padding:8px 10px;text-align:left}
  .pill{display:inline-flex;align-items:center;border:1px solid var(--line);border-radius:30px;padding:4px 10px;margin:2px;background:var(--soft);font-size:12px}
  .four{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
  .cell{border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff}
  .cell .top{padding:10px 12px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;background:var(--soft)}
  .cell .body{padding:12px}
  .big{font-size:28px;font-weight:900}
  .meta{color:var(--muted);font-size:12px}
  .list{margin:0;padding-left:18px;color:var(--ink)}
  .cta{position:sticky;bottom:12px;z-index:5;display:flex;justify-content:flex-end}
  .cta .btn{box-shadow:0 10px 20px rgba(0,0,0,0.08)}
</style>

<div class="wrap">
  <!-- 상단 헤더 -->
  <div class="hero">
    <div class="avatar"></div>
    <div>
      <div class="title"><?php echo get_text($od['target_name']);?> (<?php echo $genderKR;?>)</div>
      <div class="sub"><?php echo $birthKR;?> · <?php echo $birthTypeKR;?> · <?php echo $hourKR;?> · 주문번호 <?php echo saju_e($od_uid);?></div>
    </div>
    <div class="toolbar">
      <a class="btn" href="<?php echo $again_url;?>">새 신청</a>
      <a class="btn primary" href="<?php echo $rv_apply_url;?>">영상/음성 상담 신청</a>
    </div>
  </div>

  <div class="grid">
    <!-- 좌측: 결과 -->
    <div class="card">

      <!-- 자동 해설: 맨 먼저 -->
      <?php echo $autoExplainHtml; ?>

      <!-- 원국 -->
      <h3 class="h3" style="margin-top:6px">원국 (四柱)</h3>
      <div class="four" style="margin-bottom:10px">
        <?php
          $map = ['year'=>'년주','month'=>'월주','day'=>'일주','hour'=>'시주'];
          foreach($map as $k=>$label){
            $p = $S['pillars'][$k] ?? null;
            $g = $p['g'] ?? []; $z = $p['z'] ?? [];
            $tg = $p['tengod'] ?? '';
            $lv = $p['life12'] ?? '';
            $hidden = '-';
            if (!empty($p['hidden'])) {
              $harr = array_map(function($x){
                return ($x['g']['han']??'').'<span class="meta">('.($x['tengod']??'').')</span>';
              }, $p['hidden']);
              $hidden = implode(' , ', $harr);
            }
            echo '<div class="cell">';
            echo '  <div class="top"><div>'.$label.'</div><div class="meta">'.($lv? $lv : '').'</div></div>';
            echo '  <div class="body">';
            echo '    <div class="big" style="color:'.($g['color']??'#111').'">'.($g['han']??'').' / '.($z['han']??'').'</div>';
            echo '    <div class="meta" style="margin-top:6px">'.($g['kr']??'').' · '.($g['elem']??'').' / '.($z['elem']??'').'</div>';
            echo '    <div style="margin-top:8px" class="meta">십신: '.$tg.'</div>';
            echo '    <div style="margin-top:8px" class="meta">지장간: '.$hidden.'</div>';
            echo '  </div>';
            echo '</div>';
          }
        ?>
      </div>

      <?php if(!empty($conflicts)){ ?>
        <div style="margin:10px 0">
          <?php foreach($conflicts as $c) echo '<span class="pill">형충: '.get_text($c).'</span>'; ?>
        </div>
      <?php } ?>

      <!-- 빠른 요약 (항상 노출) -->
      <div class="card" style="margin-top:6px">
        <h3 class="h3">빠른 요약</h3>
        <div class="kv">
          <div class="k">일주</div>
          <div><?php echo saju_e(($S['pillars']['day']['g']['han']??'').($S['pillars']['day']['z']['han']??''));?></div>

          <div class="k">강/약 포인트</div>
          <div>
            <?php
              $elemCnt = $S['elem_count'] ?? $S['elem_counts'] ?? [];
              foreach(['木','火','土','金','水'] as $e){ if(!isset($elemCnt[$e])) $elemCnt[$e]=0; }
              arsort($elemCnt); $keys=array_keys($elemCnt);
              echo '강: <b>'.($keys[0]??'-').'</b> / 약: <b>'.($keys[4]??'-').'</b>';
            ?>
          </div>

          <div class="k">월령 12운성</div>
          <div><?php echo saju_e($S['pillars']['month']['life12'] ?? ''); ?></div>

          <div class="k">신살 하이라이트</div>
          <div>
            <?php
              $sh = $S['shensha'] ?? [];
              $names=[];
              foreach($sh as $n=>$arr){ if(!$arr) continue; $names[]=$n; if(count($names)>=6) break; }
              echo $names ? implode(' · ', array_map('saju_e',$names)) : '<span class="meta">-</span>';
            ?>
          </div>
        </div>
      </div>

      <!-- 전문가 해설 -->
      <h3 class="h3" style="margin-top:18px">전문가 해설</h3>
      <?php if ($expert['exists']) { ?>
        <?php if(!empty($expert['title'])) { ?>
          <div class="sub" style="margin-bottom:6px"><?php echo saju_e($expert['title']); ?></div>
        <?php } ?>
        <div style="white-space:pre-line;line-height:1.7;color:#111;border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff">
          <?php echo nl2br(get_text($expert['content'], 1)); ?>
        </div>
        <?php if(!empty($expert['updated'])) { ?>
          <div class="sub" style="margin-top:6px;color:#94a3b8">업데이트: <?php echo saju_e($expert['updated']); ?></div>
        <?php } ?>
      <?php } else { ?>
        <div style="color:#999;border:1px dashed #e5e7eb;border-radius:12px;padding:12px;background:#fff">
          전문가 풀이가 <b>준비중입니다.</b> 상담 예약 후 상세 풀이를 받아보세요.
        </div>
      <?php } ?>

      <!-- 기도 길일 -->
      <h3 class="h3" style="margin-top:18px">기도 가면 좋은 날짜</h3>
      <table class="table">
        <tr><th style="width:120px">날짜</th><th style="width:120px">간지</th><th>일진(建除)</th><th style="width:100px">추천</th></tr>
        <?php
        if (is_array($reco) && count($reco)>0){
          foreach($reco as $r){
            $score = (int)($r['score'] ?? 0);
            $color = $score>=3 ? '#16a34a' : ($score>=1 ? '#22c55e' : ($score<=-2 ? '#ef4444' : '#64748b'));
            echo '<tr>';
            echo '<td>'.saju_e($r['date']??'').'</td><td>'.saju_e($r['ganji']??'').'</td><td>'.saju_e($r['officer']??'').'</td><td style="color:'.$color.';font-weight:700">'.$score.'</td>';
            echo '</tr>';
          }
        } else {
          echo '<tr><td colspan="4" style="text-align:center;color:#999">추천 날짜 데이터가 없습니다.</td></tr>';
        }
        ?>
      </table>

    </div>

    <!-- 우측: 상담/고객정보 -->
    <div class="card">
      <h3 class="h3">상담 신청</h3>
      <p class="sub" style="margin-bottom:8px">영상/음성 중 선택하여 원하는 시간대를 예약하세요.</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?php echo $rv_apply_url; ?>" class="btn primary" style="flex:1;text-align:center">영상/음성 상담 예약</a>
      </div>

      <h3 class="h3" style="margin-top:18px">대상자 정보</h3>
      <div class="kv">
        <div class="k">이름</div><div><?php echo get_text($od['target_name']);?></div>
        <div class="k">성별</div><div><?php echo $genderKR;?></div>
        <div class="k">생년월일</div><div><?php echo $birthKR;?> (<?php echo $birthTypeKR;?>)</div>
        <div class="k">태어난 시</div><div><?php echo $hourKR;?></div>
        <div class="k">주문번호</div><div><?php echo saju_e($od_uid);?></div>
      </div>

      <h3 class="h3" style="margin-top:18px">알림</h3>
      <ul class="list">
        <li>상담 예약 완료 시 문자로 접속 링크가 발송됩니다.</li>
        <li>브라우저는 크롬/크로미움 계열을 권장합니다.</li>
        <li>모바일에서는 카메라/마이크 권한을 허용해 주세요.</li>
      </ul>
    </div>
  </div>

  <!-- 하단 고정 CTA -->
  <div class="cta">
    <a class="btn primary" href="<?php echo $rv_apply_url;?>">상담 예약 계속하기</a>
  </div>
</div>

<?php include_once(G5_PATH.'/tail.php'); ?>
