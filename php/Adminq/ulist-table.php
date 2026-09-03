<?php
$notLogin = true;
include('../Common/Core_brain.php');

if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$search = isset($_GET['search']) ? addslashes($_GET['search']) : '';
$offset = ($page - 1) * $limit;

$where = "1=1";
if($search != '') {
    $where .= " AND (username LIKE '%$search%' OR email LIKE '%$search%' OR qq LIKE '%$search%')";
}

$total = $DB->query("SELECT COUNT(*) as total FROM nteam_users WHERE $where")->fetch()['total'];
$sql = "SELECT * FROM nteam_users WHERE $where ORDER BY id DESC LIMIT $offset, $limit";
$result = $DB->query($sql);

$data = [];
while($row = $result->fetch()) {
    $data[] = $row;
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'total' => $total,
    'rows' => $data
]);
