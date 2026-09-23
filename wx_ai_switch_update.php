<?php
/**
 * 微信小程序端 AI 功能开关：一次性更新脚本
 * ------------------------------------------------------------------
 * 作用：向功能开关表 nteam_feature_settings 增加一行
 *   feature_key  = wx_ai_chat
 *   feature_name = 微信小程序端AI功能
 *
 * 小程序 AI 助手页（pages/ai-chat/index）读取该开关：
 *   关闭时整页遮罩提示「微信小程序不支持该功能，请前往网页或APP端使用」，且不发起任何 AI 请求。
 *   网页端 / APP 端不使用该开关，不受影响。
 *
 * 用法（任选其一）：
 *   - 浏览器访问：/wx_ai_switch_update.php（需先登录后台超级管理员账号）
 *   - 命令行执行：php wx_ai_switch_update.php
 *
 * 脚本可重复执行（幂等）。
 */

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    // 命令行执行：直接读取数据库配置建立连接（框架在 CLI 下会提前退出）
    require __DIR__ . '/Common/Database_Config.php';
    /** @var array $dbconfig */
    $DB = new PDO(
        "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4",
        $dbconfig['user'],
        $dbconfig['pwd'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} else {
    if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'localhost';
    $notLogin = true;
    require_once __DIR__ . '/Common/Core_brain.php';
    /** @var \lib\PdoHelper $DB */
    /** @var array|null $adminData */

    // 权限校验：浏览器执行时要求超级管理员登录
    if (empty($_SESSION['adminUser']) || empty($adminData) || intval($adminData['adminRank']) !== 1) {
        header('Content-Type: text/html; charset=UTF-8');
        exit('<div style="font:14px/1.8 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;padding:24px">'
            . '请先以超级管理员身份登录后台，再执行本脚本。</div>');
    }
}

$lines = array();

try {
    $exists = $DB->query("SELECT id, is_enabled FROM nteam_feature_settings WHERE feature_key='wx_ai_chat'")->fetch(PDO::FETCH_ASSOC);
    if ($exists) {
        $lines[] = '开关已存在：wx_ai_chat（id=' . intval($exists['id'])
            . '，当前状态：' . (intval($exists['is_enabled']) === 1 ? '启用' : '禁用') . '），无需重复添加。';
    } else {
        $ok = $DB->exec("INSERT INTO nteam_feature_settings (feature_key, feature_name, is_enabled, announcement, announcement_type) "
            . "VALUES ('wx_ai_chat', '微信小程序端AI功能', 1, '', 'info')");
        $lines[] = ($ok !== false)
            ? '已添加开关：wx_ai_chat（微信小程序端AI功能），默认启用。'
            : '添加失败，请检查数据库。';
    }
    $lines[] = '后续可在后台「功能设置」（feature_settings.php）里启用 / 禁用或填写公告内容。';
} catch (\Throwable $e) {
    $lines[] = '执行异常：' . $e->getMessage();
    $lines[] = '请确认功能开关表 nteam_feature_settings 已存在。';
}

// ===== 输出 =====
if ($isCli) {
    echo implode(PHP_EOL, $lines) . PHP_EOL;
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo '<div style="font:14px/1.9 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;padding:24px;max-width:720px">';
echo '<h3 style="margin:0 0 12px">微信小程序端 AI 功能开关</h3>';
foreach ($lines as $line) {
    echo '<p style="margin:0 0 8px">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
}
echo '<p style="margin:16px 0 0"><a href="Adminq/feature_settings.php">前往后台「功能设置」</a></p>';
echo '</div>';
