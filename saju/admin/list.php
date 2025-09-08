<?php
include_once('../../common.php');
if (!defined('_GNUBOARD_')) exit;
include_once('../_bootstrap.php');
include_once('../_config.php');
include_once('../_lib.php');
if ($is_admin!='super') alert('관리자만 접근');
$g5['title'] = '사주 주문 관리'; include_once(G5_PATH.'/head.php');
$where = '1'; $status = $_GET['status'] ?? ''; if($status) $where .= " AND status='".sql_real_escape_string($status)."'";
$rs = sql_query("SELECT * FROM g5_saju_order WHERE {$where} ORDER BY od_id DESC LIMIT 500");
?>
<link rel="stylesheet" href="<?php echo G5_URL?>/saju/_2025.css">
<h2 class="sj-h3">사주 주문 관리</h2>
<p><a href="?">전체</a> | <a href="?status=PENDING">PENDING</a> | <a href="?status=PAID">PAID</a> | <a href="?status=DONE">DONE</a></p>
<table style="width:100%;border-collapse:collapse">
<tr><th>주문ID</th><th>대상자</th><th>금액</th><th>상태</th><th>결제시각</th><th>관리</th></tr>
<?php while($r=sql_fetch_array($rs)){ ?>
<tr style="border-top:1px solid #e5e7eb">
  <td><?php echo $r['od_uid'];?></td>
  <td><?php echo get_text($r['target_name']);?></td>
  <td style="text-align:right"><?php echo number_format($r['amount']);?></td>
  <td><?php echo $r['status'];?></td>
  <td><?php echo $r['paid_at'];?></td>
  <td>
    <?php if($r['status']=='PENDING'){ ?><a href="./list.php?op=mark_paid&od=<?php echo $r['od_uid'];?>">입금확인(수동)</a> | <?php } ?>
    <a href="./reading_edit.php?od=<?php echo $r['od_uid'];?>">풀이작성/수정</a> |
    <a href="../view.php?od=<?php echo $r['od_uid'];?>" target="_blank">사용자화면</a>
  </td>
</tr>
<?php } ?>
</table>
<?php
if($_GET['op']=='mark_paid' && $_GET['od']){
  $od = saju_get_order($_GET['od']);
  if($od && $od['status']=='PENDING'){
    sql_query("UPDATE g5_saju_order SET status='PAID', paid_at=NOW() WHERE od_uid='".sql_real_escape_string($od['od_uid'])."'");
    goto_url('./list.php?status=PAID');
  }
}
include_once(G5_PATH.'/tail.php');
