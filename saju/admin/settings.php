<?php
include_once('../../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php'); include_once('../_config.php'); include_once('../_lib.php');
if ($is_admin!='super') alert('관리자만 접근');
$g5['title']='사주 설정'; include_once(G5_PATH.'/head.php');

if($_SERVER['REQUEST_METHOD']=='POST'){
  foreach($_POST as $k=>$v){
    if(strpos($k,'CFG_')===0){
      $mk = sql_real_escape_string($k);
      $mv = sql_real_escape_string(trim($v));
      sql_query("INSERT INTO g5_saju_meta (meta_key, meta_value, updated_at) VALUES ('{$mk}','{$mv}',NOW())
                 ON DUPLICATE KEY UPDATE meta_value='{$mv}', updated_at=NOW()");
    }
  }
  alert('저장되었습니다.','./settings.php');
}

$meta = array();
$r = sql_query("SELECT meta_key, meta_value FROM g5_saju_meta WHERE meta_key LIKE 'CFG_%'");
while($row=sql_fetch_array($r)){ $meta[$row['meta_key']]=$row['meta_value']; }

function cfg($k,$def=''){ global $meta; return isset($meta[$k])?$meta[$k]:$def; }
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<div class="sj-wrap sj-card">
  <h3 class="sj-h3">사주 설정</h3>
  <form method="post">
    <div class="sj-grid">
      <div class="sj-card">
        <h3 class="sj-h3">기본</h3>
        <label>비회원 주문 허용</label>
        <select name="CFG_ALLOW_GUEST"><option value="1" <?php echo cfg('CFG_ALLOW_GUEST','1')=='1'?'selected':'';?>>허용</option><option value="0" <?php echo cfg('CFG_ALLOW_GUEST','1')=='0'?'selected':'';?>>비허용</option></select>
        <div class="sj-sep"></div>
        <label>기본 가격(참고)</label><input name="CFG_PRICE" value="<?php echo get_text(cfg('CFG_PRICE','55000'));?>">
        <div class="sj-sep"></div>
        <label>PG 모드</label>
        <select name="CFG_PG_MODE">
          <option value="DUMMY" <?php echo cfg('CFG_PG_MODE','DUMMY')=='DUMMY'?'selected':'';?>>DUMMY</option>
          <option value="YOUNGCART" <?php echo cfg('CFG_PG_MODE','DUMMY')=='YOUNGCART'?'selected':'';?>>YOUNGCART</option>
        </select>
      </div>
      <div class="sj-card">
        <h3 class="sj-h3">Youngcart</h3>
        <label>영카트 결제 사용</label>
        <select name="CFG_USE_YOUNGCART">
          <option value="1" <?php echo cfg('CFG_USE_YOUNGCART','0')=='1'?'selected':'';?>>사용</option>
          <option value="0" <?php echo cfg('CFG_USE_YOUNGCART','0')=='0'?'selected':'';?>>미사용</option>
        </select>
        <div class="sj-sep"></div>
        <label>상품 선택 모드</label>
        <select name="CFG_SELECT_MODE">
          <?php foreach(['AUTO','CATEGORY','PRICE','MAP_ONLY','FIXED'] as $m) echo '<option '.(cfg('CFG_SELECT_MODE','AUTO')==$m?'selected':'').' value="'.$m.'">'.$m.'</option>'; ?>
        </select>
        <div class="sj-sep"></div>
        <label>분류코드(콤마구분)</label><input name="CFG_CATEGORY_IDS" value="<?php echo get_text(cfg('CFG_CATEGORY_IDS','saju_basic,saju_premium'));?>">
        <div class="sj-sep"></div>
        <label>기본 it_id(Fallback)</label><input name="CFG_FALLBACK_IT_ID" value="<?php echo get_text(cfg('CFG_FALLBACK_IT_ID','SAJU001'));?>">
        <div class="sj-sep"></div>
        <label>가격 허용 오차</label><input name="CFG_PRICE_TOLERANCE" value="<?php echo get_text(cfg('CFG_PRICE_TOLERANCE','100'));?>">
      </div>
      <div class="sj-card">
        <h3 class="sj-h3">SMS</h3>
        <label>벤더</label>
        <select name="CFG_SMS_VENDOR">
          <option value="" <?php echo cfg('CFG_SMS_VENDOR','')==''?'selected':'';?>>사용안함</option>
          <option value="ICODE" <?php echo cfg('CFG_SMS_VENDOR','')=='ICODE'?'selected':'';?>>ICODE</option>
          <option value="ALIGO" <?php echo cfg('CFG_SMS_VENDOR','')=='ALIGO'?'selected':'';?>>ALIGO</option>
        </select>
        <div class="sj-sep"></div>
        <label>ICODE_ID / ICODE_PW</label>
        <input name="CFG_ICODE_ID" placeholder="id" value="<?php echo get_text(cfg('CFG_ICODE_ID',''));?>">
        <input name="CFG_ICODE_PW" placeholder="pw (텍스트 저장 주의)" value="<?php echo get_text(cfg('CFG_ICODE_PW',''));?>">
        <div class="sj-sep"></div>
        <label>ALIGO_KEY / ALIGO_ID / ALIGO_SENDER</label>
        <input name="CFG_ALIGO_KEY" placeholder="key" value="<?php echo get_text(cfg('CFG_ALIGO_KEY',''));?>">
        <input name="CFG_ALIGO_ID" placeholder="userid" value="<?php echo get_text(cfg('CFG_ALIGO_ID',''));?>">
        <input name="CFG_ALIGO_SENDER" placeholder="발신번호" value="<?php echo get_text(cfg('CFG_ALIGO_SENDER',''));?>">
        <div class="sj-sep"></div>
        <label>결제 완료시 SMS 발송</label>
        <select name="CFG_PAY_SMS">
          <option value="1" <?php echo cfg('CFG_PAY_SMS','0')=='1'?'selected':'';?>>발송</option>
          <option value="0" <?php echo cfg('CFG_PAY_SMS','0')=='0'?'selected':'';?>>발송안함</option>
        </select>
      </div>
      <div class="sj-card">
        <h3 class="sj-h3">기타</h3>
        <label>결제 후 이동 URL prefix</label><input name="CFG_AFTER_PAY_URL" value="<?php echo get_text(cfg('CFG_AFTER_PAY_URL',G5_URL.'/saju/view.php?od='));?>">
        <div class="sj-sep"></div>
        <label>WebRTC 제공자</label>
        <select name="CFG_WEBRTC_PROVIDER">
          <option value="JITSI" <?php echo cfg('CFG_WEBRTC_PROVIDER','JITSI')=='JITSI'?'selected':'';?>>JITSI</option>
        </select>
      </div>
    </div>
    <div class="sj-sep"></div>
    <button class="sj-btn">저장</button>
  </form>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
