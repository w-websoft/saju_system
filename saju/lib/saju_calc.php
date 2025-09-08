<?php
if (!defined('_GNUBOARD_')) exit;
include_once(__DIR__.'/saju_const.php');

/** 60갑자 테이블 */
function saju_60_table(){
  global $saju_const;
  $G=$saju_const['gan']; $Z=$saju_const['zhi'];
  $out=[];
  for($i=0;$i<60;$i++){
    $gi=$i%10; $zi=$i%12;
    $out[$i]=['gan'=>$G[$gi],'zhi'=>$Z[$zi],'name'=>$G[$gi]['han'].$Z[$zi]['han']];
  }
  return $out;
}
$saju_60 = saju_60_table();

/** 율리우스 적일수 (PHP용: floor 캐스팅 사용) */
function saju_jdn($y, $m, $d){
  if ($m <= 2) { $y -= 1; $m += 12; }
  $A = intdiv($y, 100);
  $B = 2 - $A + intdiv($A, 4);
  // PHP에는 int()가 없음. floor로 정수화 후 (int) 캐스팅
  $part1 = (int) floor(365.25 * ($y + 4716));
  $part2 = (int) floor(30.6001 * ($m + 1));
  return $part1 + $part2 + $d + $B - 1524;
}


/** 일주 인덱스 (1984-02-02 戊子 기준) */
function saju_day_index($y,$m,$d){
  $jdn=saju_jdn($y,$m,$d);
  $base=11; $ref=saju_jdn(1984,2,2);
  $idx=($jdn-$ref+$base)%60;
  if($idx<0)$idx+=60;
  return $idx;
}

/** 시지 인덱스 */
function saju_hour_zhi_index($h){
  if($h==23) return 0;
  return intdiv(($h+1),2)%12;
}

/** 연간지 인덱스 */
function saju_year_gz_index($y){
  $o=($y-1984);
  $gi=($o%10+10)%10; $zi=($o%12+12)%12;
  return [$gi,$zi];
}

/** 월간지 인덱스 (단순 근사) */
function saju_month_gz_index($y,$m){
  $start=13;
  $months=($y-1984)*12+($m-1);
  $idx=($start+$months)%60;
  if($idx<0)$idx+=60;
  return [$idx%10,$idx%12];
}

/** 신살 계산 */
function saju_calc_shensha($d_g,$d_z){
  $tianyi=[0=>[1,11],1=>[0,2],2=>[11,9],3=>[8,10],4=>[3,5],5=>[2,4],6=>[5,7],7=>[6,8],8=>[1,3],9=>[0,2]];
  $wenchang=[2=>4,3=>7,4=>9,5=>2,6=>5,7=>8,8=>11,9=>1,0=>3,1=>6];
  $hongyan=[2=>11,3=>10,4=>9,5=>8,6=>7,7=>6,8=>5,9=>4,0=>3,1=>2];
  $baihu=function($dz){ return ($dz+6)%12; };
  $guaigang=in_array($d_g,[0,6,2,8])?[2,8,6,0]:[];

  return [
    '天乙貴人'=>isset($tianyi[$d_g])?array_map('intval',$tianyi[$d_g]):[],
    '文昌貴人'=>isset($wenchang[$d_z])?[$wenchang[$d_z]]:[],
    '紅艶殺'=>isset($hongyan[$d_z])?[$hongyan[$d_z]]:[],
    '白虎'=>[$baihu($d_z)],
    '魁罡'=>$guaigang
  ];
}

/** 원국 생성 */
function saju_build($Y,$M,$D,$H=12){
  global $saju_const,$saju_12life,$saju_zhanggan;
  list($y_g,$y_z)=saju_year_gz_index($Y);
  list($m_g,$m_z)=saju_month_gz_index($Y,$M);
  $d_idx=saju_day_index($Y,$M,$D); $d_g=$d_idx%10; $d_z=$d_idx%12;
  $h_z=saju_hour_zhi_index($H); $h_g=($d_g*2+$h_z)%10;

  $G=$saju_const['gan']; $Z=$saju_const['zhi'];
  $pillars=[
    'year'=>['g'=>$G[$y_g],'z'=>$Z[$y_z]],
    'month'=>['g'=>$G[$m_g],'z'=>$Z[$m_z]],
    'day'=>['g'=>$G[$d_g],'z'=>$Z[$d_z]],
    'hour'=>['g'=>$G[$h_g],'z'=>$Z[$h_z]],
  ];

  foreach($pillars as &$p){
    $p['tengod']=saju_tengod($d_g,$p['g']['idx']);
    $p['life12']=$saju_12life[$d_g][$p['z']['idx']];
    $zg=isset($saju_zhanggan[$p['z']['idx']])?$saju_zhanggan[$p['z']['idx']]:[];
    $p['hidden']=array_map(function($gi) use($G,$d_g){
      return ['g'=>$G[$gi],'tengod'=>saju_tengod($d_g,$gi)];
    }, $zg);
  } unset($p);

  $conf=[]; $br=[ $y_z,$m_z,$d_z,$h_z ]; $nm=['연','월','일','시'];
  for($i=0;$i<4;$i++){
    for($j=$i+1;$j<4;$j++){
      if($saju_const['zhi'][$br[$i]]['clash']==$br[$j]) $conf[]="{$nm[$i]}↔{$nm[$j]} 沖";
    }
  }

  return [
    'pillars'=>$pillars,
    'day_index'=>$d_idx,
    'conflicts'=>$conf,
    'shensha'=>saju_calc_shensha($d_g,$d_z),
    'meta'=>['day_master'=>$G[$d_g],'note'=>'절기 보정 없는 근사']
  ];
}

/** 간지 이름 */
function ganji_name($gi,$zi){
  global $saju_const;
  return $saju_const['gan'][$gi]['han'].$saju_const['zhi'][$zi]['han'];
}

/** 기도 길일 추천 */
function saju_suggest_prayer_days($bY,$bM,$bD,$bH,$sY,$sM,$sD,$days=60){
  global $saju_dayofficer,$saju_dayofficer_score,$saju_const;
  $natal = saju_day_index($bY,$bM,$bD)%12;
  $out=[];
  $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d',$sY,$sM,$sD));

  for($i=0;$i<$days;$i++){
    if($i>0) $dt->modify('+1 day');
    $y=(int)$dt->format('Y'); $m=(int)$dt->format('n'); $d=(int)$dt->format('j');

    $didx = saju_day_index($y,$m,$d); $di = $didx%12; $gi = $didx%10;
    list(,$mz) = saju_month_gz_index($y,$m);

    $off = $saju_dayofficer[$mz][$di];
    $score = $saju_dayofficer_score[$off] ?? 0;
    if ($saju_const['zhi'][$di]['clash'] == $natal) $score -= 3;
    $tianyi = saju_calc_shensha($gi,$di)['天乙貴人'];
    if (in_array($di,$tianyi)) $score += 2;

    $out[] = [
      'date'=>$dt->format('Y-m-d'),
      'ganji'=>ganji_name($gi,$di),
      'officer'=>$off,
      'score'=>$score
    ];
  }

  usort($out,function($a,$b){ return $b['score'] <=> $a['score']; });
  return array_slice($out,0,12);
}
