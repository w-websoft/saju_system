<?php
include_once('../../common.php'); if (!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php'); include_once('../_lib.php');
if ($is_admin!='super') alert('관리자만 접근');

$od_uid = preg_replace('/[^a-f0-9]/','', $_GET['od'] ?? $_POST['od_uid'] ?? '');
$od = saju_get_order($od_uid); if(!$od) alert('주문 없음', './list.php');

if($_SERVER['REQUEST_METHOD']=='POST'){
  $title = sql_real_escape_string($_POST['title']);
  $content = sql_real_escape_string($_POST['content']);
  $w = sql_fetch("SELECT rd_id FROM g5_saju_reading WHERE od_uid='{$od_uid}'");
  if($w){ sql_query("UPDATE g5_saju_reading SET title='{$title}', content='{$content}', updated_at=NOW() WHERE od_uid='{$od_uid}'"); }
  else { global $member; sql_query("INSERT INTO g5_saju_reading (od_uid, mb_id_writer, title, content, created_at)
               VALUES ('{$od_uid}', '".sql_real_escape_string($member['mb_id'])."', '{$title}', '{$content}', NOW())"); }
  sql_query("UPDATE g5_saju_order SET status='DONE' WHERE od_uid='{$od_uid}' AND status IN('PAID','DONE')");
  alert('저장되었습니다.', './reading_edit.php?od='.$od_uid);
}

$rd = sql_fetch("SELECT * FROM g5_saju_reading WHERE od_uid='{$od_uid}'");
$g5['title'] = '풀이 작성'; include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<h2 class="sj-h3">풀이 작성 - <?php echo $od['target_name'];?></h2>
<form method="post">
  <input type="hidden" name="od_uid" value="<?php echo $od_uid;?>">
  <p><input type="text" name="title" value="<?php echo get_text($rd['title']);?>" placeholder="제목" style="width:100%;padding:10px"></p>
  <p><textarea name="content" rows="18" style="width:100%;padding:10px" placeholder="보스님 스타일대로 자유롭게 작성"></textarea></p>
  <?php if($rd){ ?><script>document.querySelector('[name=content]').value = <?php echo json_encode($rd['content']);?>;</script><?php } ?>
  <p><button class="sj-btn" type="submit">저장</button></p>
</form>
<?php include_once(G5_PATH.'/tail.php'); ?>
