<?php
include_once('../../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php');
include_once('../_config.php');
include_once('../sms.php'); // saju_send_sms()

if ($is_admin!='super') alert('관리자만 접근');
$g5['title']='SMS 테스트'; include_once(G5_PATH.'/head.php');

if($_SERVER['REQUEST_METHOD']=='POST'){
  $to = trim($_POST['to']);
  $msg = trim($_POST['msg']);

$why = null;
$ok  = saju_send_sms($to, $msg, $why);
echo '<pre>'.($ok ? 'SUCCESS' : 'FAILED').'</pre>';
if(!$ok) echo '<div>reason: <b>'.htmlspecialchars($why,ENT_QUOTES).'</b></div>';

  echo '<pre style="padding:10px;background:#fde68a">'.($ok ? 'SUCCESS' : 'FAILED')."</pre>";
  if(!$ok){
    echo '<div style="padding:8px 10px;background:#e0f2fe;border-left:4px solid #0284c7;margin:8px 0">';
    echo 'reason: <b>'.htmlspecialchars($why,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</b>';
    echo '</div>';
    echo '<p style="color:#64748b">※ g5_saju_logs 테이블에도 실패 로그가 적재됩니다.</p>';
  }
  echo '<p><a href="./sms_test.php">뒤로</a></p>';
  include_once(G5_PATH.'/tail.php'); exit;
}
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<div class="sj-wrap sj-card">
  <h3 class="sj-h3">SMS 테스트</h3>
  <form method="post">
    <label>받는 번호</label><input name="to" placeholder="01012345678">
    <label>메시지</label><textarea name="msg" rows="4">[사주상담] 테스트 메시지</textarea>
    <div class="sj-sep"></div><button class="sj-btn">발송</button>
  </form>
  <div class="sj-sep"></div>
  <div class="sj-p">환경설정 확인 경로: 관리자 &gt; 기본환경설정 &gt; SMS 설정</div>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
