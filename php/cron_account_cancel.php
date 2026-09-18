<?php
$notLogin = true;
include('Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */
require_once __DIR__ . '/funsion/AccountCancellation.php';

define('CRON_LOG_FILE', __DIR__ . '/logs/account_cancel_cron.log');

function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    @file_put_contents(CRON_LOG_FILE, $logMessage, FILE_APPEND);
    echo $logMessage;
}

cronLog("========== 账号注销到期处理开始 ==========");

$cfg = cancelGetConfig();

// 冷静期关闭时不会存在冷静期账号，直接跳过
if ($cfg['cooling_enable'] !== 1) {
    cronLog("冷静期已关闭，无需处理");
    cronLog("========== 处理结束 ==========");
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '冷静期已关闭，无需处理'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$users = $DB->getAll("SELECT * FROM nteam_users WHERE is_cancelling=1 AND cancel_expire_time IS NOT NULL AND cancel_expire_time <= NOW()");

if (!$users) {
    cronLog("没有到期需要注销的账号");
    cronLog("========== 处理结束 ==========");
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'msg' => '没有到期需要注销的账号'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$successCount = 0;
$failedCount = 0;

foreach ($users as $user) {
    $userId = intval($user['id']);
    cronLog("处理用户 {$userId} ({$user['username']}) ...");

    // 正式删除前发送"账号已注销"邮件（按配置）
    if ($cfg['mail_deleted'] === 1) {
        $mailRes = cancelSendDeleteMail($user);
        if ($mailRes !== true) {
            cronLog("  邮件发送失败（不阻断）：" . $mailRes);
        }
    }

    $result = deleteUserAccount($DB, $userId, $user);
    if (!$result['success']) {
        $failedCount++;
        cronLog("  注销失败：{$result['error']}");
        continue;
    }

    $DB->exec("UPDATE nteam_account_deletions SET status='deleted', delete_time=NOW() WHERE user_id='{$userId}' AND status='pending'");
    $successCount++;
    cronLog("  已注销");
}

cronLog("处理完成：成功 {$successCount} 个，失败 {$failedCount} 个");
cronLog("========== 处理结束 ==========");

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 0,
        'msg' => '执行完成',
        'data' => ['deleted' => $successCount, 'failed' => $failedCount]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
