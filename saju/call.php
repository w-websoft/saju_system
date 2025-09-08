<?php
include_once('../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('./_bootstrap.php'); include_once('./_config.php');

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], true, 301); exit;
}

$rv_id = (int)($_GET['rv_id'] ?? 0);
$r = sql_fetch("SELECT * FROM g5_saju_reserve WHERE rv_id={$rv_id}");

$room     = 'saju_'.$rv_id;
$nickname = $member['mb_nick'] ?: 'Guest';

$redirect_url = G5_URL.'/saju/';
if ($r && !empty($r['od_uid'])) {
    $od = sql_fetch("SELECT od_uid FROM g5_saju_order WHERE od_uid='".sql_real_escape_string($r['od_uid'])."'");
    $redirect_url = $od ? G5_URL.'/saju/view.php?od_uid='.urlencode($r['od_uid']) : G5_URL.'/saju/reserve.php?rv_id='.$rv_id;
} elseif ($r) {
    $redirect_url = G5_URL.'/saju/reserve.php?rv_id='.$rv_id;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_inapp = preg_match('~KAKAOTALK|NAVER|Instagram|FBAN|FBAV|DaumApps|Line|Whale~i', $ua);

$g5['title']='상담실 (WebRTC)'; include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<style>
  .sj-alert{padding:12px 16px;margin:10px 0;border:1px solid #fca5a5;background:#fee2e2;border-radius:8px}
  .sj-alert a{color:#b91c1c;font-weight:700;text-decoration:underline}
  #meet{width:100%;height:78vh;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
</style>

<div class="sj-wrap">
  <div class="sj-card">
    <h3 class="sj-h3">상담실 (WebRTC)</h3>

    <?php if($is_inapp): ?>
    <div class="sj-alert">
      ⚠ 인앱 브라우저(카톡/네이버 등)에서는 카메라·마이크가 제한될 수 있어요.
      <a href="<?php echo G5_URL ?>/saju/call.php?rv_id=<?php echo $rv_id; ?>" target="_system">Chrome/Safari로 열기</a>를 권장합니다.
    </div>
    <?php endif; ?>

    <div id="meet" allow="camera; microphone; fullscreen; display-capture; autoplay; clipboard-read; clipboard-write"></div>
  </div>
</div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
(function(){
  const domain  = 'meet.jit.si';
  const options = {
    roomName: '<?php echo $room; ?>',
    parentNode: document.querySelector('#meet'),
    userInfo: { displayName: <?php echo json_encode($nickname, JSON_UNESCAPED_UNICODE); ?> },
    configOverwrite: {
      prejoinPageEnabled: false,
      disableDeepLinking: true,
      enableClosePage: false,
      startWithAudioMuted: false,
      startWithVideoMuted: false
    },
    interfaceConfigOverwrite: {
      MOBILE_APP_PROMO: false,
      SHOW_JITSI_WATERMARK: false,
      SHOW_WATERMARK_FOR_GUESTS: false,
      HIDE_INVITE_MORE_HEADER: true,
      TOOLBAR_BUTTONS: ['microphone','camera','hangup','chat','tileview','fullscreen','settings']
    }
  };
  const api = new JitsiMeetExternalAPI(domain, options);
  const goResult = () => { window.location.replace('<?php echo $redirect_url; ?>'); };
  api.addEventListener('readyToClose', goResult);
  api.addEventListener('videoConferenceLeft', goResult);
  api.addEventListener('videoConferenceEnded', goResult);
  api.executeCommand('displayName', <?php echo json_encode($nickname, JSON_UNESCAPED_UNICODE); ?>);
})();
</script>

<?php include_once(G5_PATH.'/tail.php'); ?>
