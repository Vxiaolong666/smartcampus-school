<?php
/**
 * 协议中心：一次性更新脚本
 * ------------------------------------------------------------------
 * 作用：执行同目录的 agreements_update.sql ——
 *   1) 创建协议表 nteam_agreements
 *   2) 写入内置预设协议（用户服务协议 / 隐私政策 / 用户注销协议 / 免责声明）
 *   3) 迁移旧《用户注销服务协议》（nteam_config.Cancel_Agreement）并删除该配置列
 *
 * 用法（任选其一）：
 *   - 浏览器访问：/agreements_update.php（需先登录后台管理员账号）
 *   - 命令行执行：php agreements_update.php
 *
 * 注意：执行成功后建议删除本文件。脚本可重复执行（幂等）。
 */

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'localhost';
    if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD'] = 'GET';
}

$notLogin = true;
require_once __DIR__ . '/Common/Core_brain.php';
/** @var \lib\PdoHelper $DB */
/** @var array|null $adminData */

// ===== 权限校验 =====
if (!$isCli) {
    if (empty($_SESSION['adminUser']) || empty($adminData) || intval($adminData['adminRank']) !== 1) {
        header('Content-Type: text/html; charset=UTF-8');
        exit('<div style="font:14px/1.8 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;padding:24px">'
            . '请先以超级管理员身份登录后台，再执行本脚本。</div>');
    }
}

// ===== 读取并解析 SQL =====
$sqlFile = __DIR__ . '/agreements_update.sql';
if (!is_file($sqlFile)) {
    $msg = '未找到更新脚本：agreements_update.sql';
    echo $isCli ? $msg . PHP_EOL : '<div style="padding:24px">' . $msg . '</div>';
    exit;
}

$raw = (string) file_get_contents($sqlFile);
$lines = preg_split('/\r\n|\r|\n/', $raw);
$kept = [];
foreach ($lines as $line) {
    // 跳过注释行（本文件内容中不含 ASCII 分号，故可按“行尾分号”安全切分语句）
    if (preg_match('/^\s*--/', $line)) {
        continue;
    }
    $kept[] = $line;
}
$sql = implode("\n", $kept);
$statements = preg_split('/;\s*\n/', $sql);

$results = [];
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }
    $label = preg_replace('/\s+/', ' ', substr($stmt, 0, 90));
    try {
        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $stmt)) {
            $r = $DB->query($stmt);
            $ok = ($r !== false);
            $err = $ok ? '' : $DB->error();
        } else {
            $r = $DB->exec($stmt);
            $ok = ($r !== false);
            $err = $ok ? '' : $DB->error();
        }
    } catch (\Throwable $e) {
        $ok = false;
        $err = $e->getMessage();
    }
    $results[] = ['label' => $label, 'ok' => $ok, 'err' => $err];
}

$failed = 0;
foreach ($results as $r) {
    if (!$r['ok']) $failed++;
}

$tableOk = false;
try {
    $chk = $DB->query("SELECT 1 FROM nteam_agreements LIMIT 1");
    $tableOk = (bool) $chk;
} catch (\Throwable $e) {
    $tableOk = false;
}
$count = 0;
if ($tableOk) {
    try {
        $count = intval($DB->getColumn("SELECT COUNT(*) FROM nteam_agreements"));
    } catch (\Throwable $e) {
        $count = 0;
    }
}

// ===== 输出结果 =====
if ($isCli) {
    echo "协议中心更新脚本执行完成\n";
    foreach ($results as $r) {
        echo ($r['ok'] ? '[OK] ' : '[FAIL] ') . $r['label'] . ($r['ok'] ? '' : ' -> ' . $r['err']) . "\n";
    }
    echo "协议表：" . ($tableOk ? '已就绪' : '不可用') . "，协议数量：" . $count . "，失败语句：" . $failed . "\n";
    exit($failed > 0 ? 1 : 0);
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>协议中心 - 一次性更新脚本</title>
<style>
body { margin: 0; padding: 28px; background: #f5f6fa; color: #303133; font: 14px/1.7 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.card { max-width: 860px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px 28px; box-shadow: 0 6px 24px rgba(0,0,0,.06); }
h1 { font-size: 20px; margin: 0 0 6px; }
.sub { color: #909399; font-size: 13px; margin-bottom: 18px; }
.ok { color: #67c23a; }
.fail { color: #f56c6c; }
table { width: 100%; border-collapse: collapse; margin: 14px 0; }
th, td { border-bottom: 1px solid #ebeef5; padding: 8px 10px; text-align: left; font-size: 13px; vertical-align: top; }
th { background: #fafafa; }
code { background: #f4f4f5; padding: 1px 5px; border-radius: 3px; font-size: 12px; }
.tip { background: #fdf6ec; color: #b88230; border-radius: 8px; padding: 12px 16px; margin-top: 16px; font-size: 13px; }
.btn { display: inline-block; margin-top: 14px; padding: 8px 18px; background: #409eff; color: #fff; border-radius: 6px; text-decoration: none; font-size: 14px; }
</style>
</head>
<body>
<div class="card">
    <h1>协议中心 - 一次性更新脚本</h1>
    <div class="sub">已执行 <code>agreements_update.sql</code>：建表 + 写入内置预设协议 + 迁移并移除旧的注销协议配置列。</div>

    <div>协议表：<b class="<?php echo $tableOk ? 'ok' : 'fail'; ?>"><?php echo $tableOk ? '已就绪' : '不可用'; ?></b>　协议数量：<b><?php echo $count; ?></b>　失败语句：<b class="<?php echo $failed > 0 ? 'fail' : 'ok'; ?>"><?php echo $failed; ?></b></div>

    <table>
        <thead><tr><th style="width:70px">结果</th><th>语句</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr>
                <td class="<?php echo $r['ok'] ? 'ok' : 'fail'; ?>"><?php echo $r['ok'] ? '成功' : '失败'; ?></td>
                <td><code><?php echo htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8'); ?></code>
                    <?php if (!$r['ok'] && $r['err'] !== ''): ?><div class="fail"><?php echo htmlspecialchars($r['err'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="tip">
        执行成功后请删除本文件 <code>php/agreements_update.php</code>，并前往后台「协议管理」核对协议内容。
        <?php if ($failed > 0): ?>如存在失败语句，请根据上方错误信息排查（如数据库账号缺少 ALTER 权限等）。<?php endif; ?>
    </div>
    <a class="btn" href="/adminq/agreement_manage.php">前往「协议管理」</a>
</div>
</body>
</html>
