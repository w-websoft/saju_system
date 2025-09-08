<?php
include_once('../../common.php');

header('Content-Type: text/plain; charset=utf-8');

$info = [
  'G5_PATH'      => defined('G5_PATH') ? G5_PATH : '(undefined)',
  'G5_URL'       => defined('G5_URL') ? G5_URL : '(undefined)',
  'G5_LIB_PATH'  => defined('G5_LIB_PATH') ? G5_LIB_PATH : '(undefined)',
  'G5_PLUGIN_PATH'=> defined('G5_PLUGIN_PATH') ? G5_PLUGIN_PATH : '(undefined)',
  '__DIR__'      => __DIR__,
  'saju_dir'     => dirname(__DIR__),
];

echo "=== CONSTS ===\n";
foreach ($info as $k=>$v) echo $k.": ".$v."\n";

$cands = [];
if (defined('G5_LIB_PATH'))   $cands[] = G5_LIB_PATH . '/icode.sms.lib.php';
if (defined('G5_PATH'))       $cands[] = G5_PATH     . '/lib/icode.sms.lib.php';
$cands[] = dirname(__DIR__)   . '/lib/icode.sms.lib.php'; // 루트 추정
if (defined('G5_PLUGIN_PATH')) $cands[] = G5_PLUGIN_PATH . '/aligo_sms/aligo_sms.lib.php';
$cands[] = dirname(__DIR__)   . '/plugin/aligo_sms/aligo_sms.lib.php';

echo "\n=== CHECK FILES ===\n";
foreach ($cands as $p) {
  echo $p.' : '.(is_file($p) ? 'FOUND' : 'MISSING')."\n";
}
