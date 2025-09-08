<?php
include_once('../common.php'); if(!defined('_GNUBOARD_')) exit;
include_once('./_bootstrap.php'); include_once('./_config.php'); include_once('./_lib.php'); check_token();

$od = saju_get_order($_POST['od_uid']); if(!$od) alert('주문 없음','./index.php');
if(!saju_own_or_admin($od)) alert('권한 없음');

$mb_id = $member['mb_id'] ?: '';
$method = in_array($_POST['method'],['VOICE','VIDEO']) ? $_POST['method'] : 'VOICE';
$rv_date = preg_replace('/[^0-9\-]/','', $_POST['rv_date']);
$rv_time = preg_replace('/[^0-9:]/','', $_POST['rv_time']);
$memo    = sql_real_escape_string($_POST['memo']);

sql_query("INSERT INTO g5_saju_reserve (od_uid, mb_id, method, rv_date, rv_time, price, status, memo, created_at)
VALUES ('{$od['od_uid']}', '{$mb_id}', '{$method}', '{$rv_date}', '{$rv_time}', 30000, 'REQUEST', '{$memo}', NOW())");
$rv_id = sql_insert_id();

/* 테스트 결제 즉시 성공 + 링크 */
sql_query("UPDATE g5_saju_reserve SET status='PAID', meet_url='/saju/call.php?rv_id={$rv_id}' WHERE rv_id={$rv_id}");

alert('예약요청이 접수되었습니다. 상담시간에 맞춰 접속 링크로 입장하세요.','./view.php?od='.$od['od_uid']);
