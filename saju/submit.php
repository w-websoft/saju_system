<?php
// ===== Robust bootstrap =====
$__ROOT = realpath(__DIR__ . '/..');
if (!$__ROOT || !is_file($__ROOT.'/common.php')) {
    $__ROOT = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'],'/') : dirname(__DIR__);
}
include_once($__ROOT.'/common.php'); if(!defined('_GNUBOARD_')) exit;

@include_once(__DIR__.'/_bootstrap.php');
@include_once(__DIR__.'/_config.php');
@include_once(__DIR__.'/_lib.php');

// ===== Fallback helpers =====
if (!function_exists('saju_cfg')) {
    function saju_cfg($key, $default=null){
        // g5_saju_config 사용(없으면 기본값)
        $row = @sql_fetch("SELECT cf_value FROM g5_saju_config WHERE cf_key='".sql_real_escape_string($key)."'");
        return ($row && isset($row['cf_value'])) ? $row['cf_value'] : $default;
    }
}
if (!function_exists('saju_table_has_col')) {
    function saju_table_has_col($table, $col){
        $table = preg_replace('/[^a-zA-Z0-9_]/','', $table);
        $col   = preg_replace('/[^a-zA-Z0-9_]/','', $col);
        $row = @sql_fetch("SHOW COLUMNS FROM {$table} LIKE '".sql_real_escape_string($col)."'");
        return $row ? true : false;
    }
}
if (!function_exists('saju_ensure_order_table')) {
    function saju_ensure_order_table(){
        // 테이블 존재 확인
        $tbl = sql_fetch("SHOW TABLES LIKE 'g5_saju_order'");
        if (!$tbl) {
            // 최소 스키마 생성
            $sql = "
            CREATE TABLE IF NOT EXISTS g5_saju_order (
              od_uid VARCHAR(64) NOT NULL,
              mb_id VARCHAR(50) DEFAULT '',
              buyer_name VARCHAR(100) DEFAULT '',
              buyer_phone VARCHAR(30) DEFAULT '',
              buyer_email VARCHAR(120) DEFAULT '',
              target_name VARCHAR(100) DEFAULT '',
              gender ENUM('M','F','X') DEFAULT 'X',
              birth_y INT DEFAULT NULL,
              birth_m INT DEFAULT NULL,
              birth_d INT DEFAULT NULL,
              birth_h INT DEFAULT NULL,
              birth_type ENUM('SOLAR','LUNAR') DEFAULT 'SOLAR',
              tz VARCHAR(64) DEFAULT 'Asia/Seoul',
              item_code VARCHAR(50) DEFAULT '',
              amount INT DEFAULT 0,
              pg_code VARCHAR(30) DEFAULT 'YOUNGCART',
              order_status ENUM('REQUEST','PAID','CANCEL','REFUND') DEFAULT 'REQUEST',
              status ENUM('REQUEST','PAID','DONE','CANCEL') NULL,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (od_uid),
              KEY idx_mb (mb_id),
              KEY idx_status (order_status),
              KEY idx_item (item_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            @sql_query($sql, false);
        } else {
            // 부족 컬럼 보강(있으면 무시)
            $adds = [
                "ALTER TABLE g5_saju_order ADD COLUMN gender ENUM('M','F','X') DEFAULT 'X'",
                "ALTER TABLE g5_saju_order ADD COLUMN birth_y INT DEFAULT NULL",
                "ALTER TABLE g5_saju_order ADD COLUMN birth_m INT DEFAULT NULL",
                "ALTER TABLE g5_saju_order ADD COLUMN birth_d INT DEFAULT NULL",
                "ALTER TABLE g5_saju_order ADD COLUMN birth_h INT DEFAULT NULL",
                "ALTER TABLE g5_saju_order ADD COLUMN birth_type ENUM('SOLAR','LUNAR') DEFAULT 'SOLAR'",
                "ALTER TABLE g5_saju_order ADD COLUMN tz VARCHAR(64) DEFAULT 'Asia/Seoul'",
                "ALTER TABLE g5_saju_order ADD COLUMN item_code VARCHAR(50) DEFAULT ''",
                "ALTER TABLE g5_saju_order ADD COLUMN amount INT DEFAULT 0",
                "ALTER TABLE g5_saju_order ADD COLUMN pg_code VARCHAR(30) DEFAULT 'YOUNGCART'",
                "ALTER TABLE g5_saju_order ADD COLUMN order_status ENUM('REQUEST','PAID','CANCEL','REFUND') DEFAULT 'REQUEST'",
                "ALTER TABLE g5_saju_order ADD COLUMN status ENUM('REQUEST','PAID','DONE','CANCEL') NULL",
                "ALTER TABLE g5_saju_order ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
                "ALTER TABLE g5_saju_order ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                "ALTER TABLE g5_saju_order ADD KEY idx_mb (mb_id)",
                "ALTER TABLE g5_saju_order ADD KEY idx_status (order_status)",
                "ALTER TABLE g5_saju_order ADD KEY idx_item (item_code)",
            ];
            foreach($adds as $q){ @sql_query($q, false); } // 존재하면 무시되고 넘어감
        }
    }
}

check_token(); // CSRF

// ===== 입력값 수집 =====
$buyer_name   = trim($_POST['buyer_name']  ?? '');
$buyer_phone  = trim($_POST['buyer_phone'] ?? '');
$buyer_email  = trim($_POST['buyer_email'] ?? ''); // 선택
$target_name  = trim($_POST['target_name'] ?? '');
$gender       = in_array($_POST['gender'] ?? '', ['M','F']) ? $_POST['gender'] : 'M';

$birth_y = (int)($_POST['birth_y'] ?? 0);
$birth_m = (int)($_POST['birth_m'] ?? 0);
$birth_d = (int)($_POST['birth_d'] ?? 0);
$birth_h = (int)($_POST['birth_h'] ?? 12);
$birth_type = ($_POST['birth_type'] ?? 'SOLAR') === 'LUNAR' ? 'LUNAR' : 'SOLAR';

$memo       = trim($_POST['memo'] ?? '');
$tz         = 'Asia/Seoul';
$item_code  = 'BASIC';

// ===== 필수 검증 =====
if ($buyer_name === '' || $target_name === '' || $buyer_phone === '') {
    alert('필수값이 누락되었습니다. (이름/연락처/대상자 이름)');
}
if ($birth_y < 1 || $birth_m < 1 || $birth_d < 1) {
    alert('출생정보가 없습니다. (연/월/일)');
}
// 시간 보정
if ($birth_h < 0 || $birth_h > 23) $birth_h = 12;

// 금액: 설정값(PRICE) 없으면 55,000 기본
$amount = (int) saju_cfg('PRICE', 55000);
if ($amount <= 0) $amount = 55000;

// ===== 주문 UID 발급 =====
try {
    $od_uid = bin2hex(random_bytes(16)); // 32자 UID
} catch(Throwable $e) {
    $od_uid = md5(uniqid('saju', true));
}

// ===== 컬럼 존재에 따라 상태 컬럼 결정 =====
saju_ensure_order_table(); // 먼저 테이블/컬럼 확보
$status_col = saju_table_has_col('g5_saju_order','order_status') ? 'order_status'
            : (saju_table_has_col('g5_saju_order','status') ? 'status' : null);

// ===== INSERT 구성 =====
$cols = [
    'od_uid'      => $od_uid,
    'mb_id'       => $member['mb_id'] ?? '',
    'buyer_name'  => $buyer_name,
    'buyer_phone' => $buyer_phone,
    'buyer_email' => $buyer_email,
    'target_name' => $target_name,
    'gender'      => $gender,
    'birth_y'     => (string)$birth_y,
    'birth_m'     => (string)$birth_m,
    'birth_d'     => (string)$birth_d,
    'birth_h'     => (string)$birth_h,
    'birth_type'  => $birth_type,
    'tz'          => $tz,
    'item_code'   => $item_code,
    'amount'      => (string)$amount,
    'pg_code'     => 'YOUNGCART',
    'created_at'  => date('Y-m-d H:i:s'),
];
if ($status_col) $cols[$status_col] = 'REQUEST';

$col_names = [];
$val_items = [];
foreach ($cols as $k=>$v){
    $col_names[] = $k;
    $val_items[] = "'".sql_real_escape_string((string)$v)."'";
}
$sql = "INSERT INTO g5_saju_order (".implode(',', $col_names).") VALUES (".implode(',', $val_items).")";
$ok = @sql_query($sql, false);

// 실패 시 한 번 더: 스키마 보강 후 재시도
if (!$ok) {
    saju_ensure_order_table();
    $ok = @sql_query($sql, false);
}

if (!$ok) {
    // 슈퍼관리자면 실제 에러 메시지 노출
    if (isset($is_admin) && $is_admin === 'super') {
        // 그누보드의 최근 에러를 가져오는 헬퍼가 없다면 mysql_error 계열이 막혀있을 수 있으므로
        // 간단한 진단 정보만 출력
        echo "<pre>INSERT 실패: g5_saju_order\nSQL:\n{$sql}\n\n컬럼 목록:\n";
        $cols = sql_query("SHOW COLUMNS FROM g5_saju_order");
        while($c = sql_fetch_array($cols)) { echo $c['Field'].' '.$c['Type']."\n"; }
        echo "\n권한/스키마를 확인하세요.</pre>";
        exit;
    }
    alert('주문 저장 중 문제가 발생했습니다. (스키마 확인 필요)');
}

// 최근 주문 UID 세션 보관
$_SESSION['saju_last_od'] = $od_uid;

// 결제 페이지로 이동 (항상 od_uid 사용)
goto_url(G5_URL.'/saju/pay.php?od_uid='.$od_uid);
