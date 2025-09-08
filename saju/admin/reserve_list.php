<?php
include_once('../../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php'); if ($is_admin!='super') alert('관리자만 접근');
$g5['title']='상담 예약 관리'; include_once(G5_PATH.'/head.php');

function saju_abs_url($url, $rv_id){
    $url = trim((string)$url);
    if ($url === '') return G5_URL . '/saju/call.php?rv_id=' . (int)$rv_id;
    if (preg_match('~^https?://~i', $url)) return $url;
    if ($url[0] !== '/') $url = '/'.ltrim($url, '/');
    return G5_URL . $url;
}

$rs = sql_query("SELECT r.*, o.buyer_email, o.buyer_phone, o.target_name FROM g5_saju_reserve r
LEFT JOIN g5_saju_order o ON o.od_uid=r.od_uid ORDER BY rv_id DESC LIMIT 500");
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<style>
  .sj-table{width:100%;border-collapse:collapse}
  .sj-table th,.sj-table td{padding:8px 10px;border-top:1px solid #e5e7eb;vertical-align:middle}
  .sj-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
  .sj-linkcell{display:flex;gap:8px;align-items:center}
  .sj-link{white-space:nowrap;max-width:380px;overflow:hidden;text-overflow:ellipsis;display:inline-block;vertical-align:middle}
  .sj-badge{padding:2px 8px;border-radius:9999px;background:#eef2ff;color:#3730a3;font-size:12px}
</style>

<h2 class="sj-h3">상담 예약 관리</h2>
<table class="sj-table">
<tr><th>rv_id</th><th>od_uid</th><th>대상</th><th>방법</th><th>일시</th><th>상태</th><th>링크</th><th>액션</th></tr>
<?php while($r=sql_fetch_array($rs)){ 
  $abs_url = saju_abs_url($r['meet_url'], $r['rv_id']);
  $rv_dt   = trim($r['rv_date'].' '.$r['rv_time']);
?>
<tr>
  <td><?php echo (int)$r['rv_id']?></td>
  <td><?php echo get_text($r['od_uid'])?></td>
  <td><?php echo get_text($r['target_name'])?></td>
  <td><span class="sj-badge"><?php echo get_text($r['method'])?></span></td>
  <td><?php echo get_text($rv_dt)?></td>
  <td><?php echo get_text($r['status'])?></td>
  <td class="sj-linkcell">
    <?php if($abs_url){ ?>
      <a class="sj-link" href="<?php echo $abs_url?>" target="_blank" rel="noopener">열기</a>
      <button type="button" class="sj-btn sj-btn-sm" onclick="copyToClipboard('<?php echo $abs_url?>')">복사</button>
    <?php } else { echo '-'; } ?>
  </td>
  <td>
    <form method="post" action="./reserve_op.php" class="sj-actions">
      <input type="hidden" name="rv_id" value="<?php echo (int)$r['rv_id']?>">
      <input type="text" name="meet_url" placeholder="통화 링크" value="<?php echo get_text($r['meet_url'])?>" style="width:260px">
      <select name="status">
        <?php foreach(['REQUEST','PAID','DONE','CANCEL'] as $s) echo '<option '.($r['status']==$s?'selected':'').' value="'.$s.'">'.$s.'</option>'; ?>
      </select>
      <button class="sj-btn" type="submit">저장</button>
    </form>
  </td>
</tr>
<?php } ?>
</table>
<script>
function copyToClipboard(text){
  if (!navigator.clipboard) {
    const ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); alert('복사되었습니다'); }
    catch(e){ alert('복사 실패: '+e.message); }
    finally { document.body.removeChild(ta); }
    return;
  }
  navigator.clipboard.writeText(text).then(()=>alert('복사되었습니다')).catch(err=>alert('복사 실패: '+err));
}
</script>
<?php include_once(G5_PATH.'/tail.php'); ?>
