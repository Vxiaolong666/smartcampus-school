<?php
$notLogin = true;
include('Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */

define('CRON_LOG_FILE', __DIR__ . '/logs/subscription_alert_cron.log');

$stats = [
    'users_checked' => 0,
    'alerts_sent' => 0,
    'wechat_sent' => 0,
    'email_sent' => 0,
    'errors' => 0
];

function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents(CRON_LOG_FILE, $logMessage, FILE_APPEND);
    echo $logMessage;
}

function getAccessToken($DB) {
    $wxConfig = $DB->getRow("SELECT * FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
    if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
        return null;
    }
    
    $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wxConfig['appid']}&secret={$wxConfig['secret']}";
    $tokenResult = @file_get_contents($tokenUrl);
    $tokenData = json_decode($tokenResult, true);
    
    if (!isset($tokenData['access_token'])) {
        return null;
    }
    
    return $tokenData['access_token'];
}

function sendWechatSubscribeMessage($accessToken, $openid, $templateId, $data) {
    $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
    
    $postData = [
        'touser' => $openid,
        'template_id' => $templateId,
        'page' => 'pages/subscription/index',
        'data' => $data
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sendUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    $responseData = json_decode($result, true);
    if (isset($responseData['errcode']) && $responseData['errcode'] != 0) {
        return ['success' => false, 'error' => $responseData['errmsg'] ?? '发送失败'];
    }
    
    return ['success' => true];
}

function sendEmailNotification($email, $subject, $content) {
    if (!function_exists('send_mail')) {
        require_once __DIR__ . '/Common/Core_Functions.php';
    }
    
    $result = send_mail($email, $subject, $content, true);
    
    if ($result === true) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => $result];
    }
}

cronLog("========== 订阅额度提醒定时任务开始 ==========");

$alertTemplate = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE scene_type = 'subscription_alert' AND status = 1 LIMIT 1");

$lowSubscriptionUsers = $DB->getAll("SELECT 
    s.user_id, 
    s.template_id, 
    s.subscribe_count,
    s.openid,
    u.username, 
    u.email,
    u.wx_openid,
    t.name as template_name,
    t.scene_type
FROM nteam_subscriptions s 
JOIN nteam_users u ON s.user_id = u.id 
JOIN nteam_wx_templates t ON s.template_id = t.template_id 
WHERE s.status = 1 
    AND s.subscribe_count < 2 
    AND t.status = 1
    AND t.scene_type != 'subscription_alert'
    AND u.is_cancelling = 0
ORDER BY s.user_id, s.subscribe_count ASC");

if (empty($lowSubscriptionUsers)) {
    cronLog("没有需要提醒的用户");
    cronLog("========== 订阅额度提醒定时任务结束 ==========");
    exit;
}

$userAlerts = [];
foreach ($lowSubscriptionUsers as $sub) {
    $userId = $sub['user_id'];
    if (!isset($userAlerts[$userId])) {
        $userAlerts[$userId] = [
            'user_id' => $userId,
            'username' => $sub['username'],
            'email' => $sub['email'],
            'wx_openid' => $sub['wx_openid'],
            'low_templates' => []
        ];
    }
    $userAlerts[$userId]['low_templates'][] = [
        'name' => $sub['template_name'],
        'scene_type' => $sub['scene_type'],
        'count' => $sub['subscribe_count']
    ];
    $stats['users_checked']++;
}

cronLog("发现 " . count($userAlerts) . " 个用户需要提醒");

$accessToken = null;
if ($alertTemplate) {
    $accessToken = getAccessToken($DB);
    if ($accessToken) {
        cronLog("获取微信access_token成功");
    } else {
        cronLog("获取微信access_token失败，将仅使用邮件通知");
    }
} else {
    cronLog("未配置额度提醒模板，将仅使用邮件通知");
}

$siteName = conf('Name') ?: '校园服务系统';
$siteUrl = conf('Mail_Site_Url') ?: conf('Url') ?: 'http://127.0.0.4';

foreach ($userAlerts as $userId => $alert) {
    $stats['alerts_sent']++;
    
    $templateList = [];
    foreach ($alert['low_templates'] as $tpl) {
        $templateList[] = "- {$tpl['name']}：剩余 {$tpl['count']} 次";
    }
    $templateListStr = implode("\n", $templateList);
    
    $emailSubject = "【{$siteName}】订阅消息额度不足提醒";
    $emailContent = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
            <h2 style='color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;'>订阅消息额度不足提醒 - {$siteName}</h2>
            <p>您好，<strong>{$alert['username']}</strong>：</p>
            <p>您的以下订阅消息类型额度不足，请及时在小程序中增加订阅，以免错过重要通知：</p>
            <div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 15px 0;'>
                <pre style='margin: 0; white-space: pre-wrap;'>{$templateListStr}</pre>
            </div>
            <p><strong>如何增加订阅次数？</strong></p>
            <ol>
                <li>打开微信小程序</li>
                <li>进入「我的」-「订阅管理」页面</li>
                <li>点击对应模板的「立即订阅」按钮</li>
                <li>在弹出的授权窗口中点击「允许」</li>
            </ol>
            <p style='color: #6c757d; font-size: 12px;'>此邮件为系统自动发送，请勿回复。</p>
        </div>
    </body>
    </html>
    ";
    
    $emailResult = sendEmailNotification($alert['email'], $emailSubject, $emailContent);
    if ($emailResult['success']) {
        $stats['email_sent']++;
        cronLog("用户 {$alert['username']}({$alert['email']}) 邮件发送成功");
    } else {
        cronLog("用户 {$alert['username']}({$alert['email']}) 邮件发送失败: " . $emailResult['error']);
    }
    
    if ($alertTemplate && $accessToken && !empty($alert['wx_openid'])) {
        $wechatData = [
            'thing3' => ['value' => '订阅消息额度不足提醒'],
            'short_thing9' => ['value' => '系统通知'],
            'thing4' => ['value' => '您有' . count($alert['low_templates']) . '个订阅类型额度不足，请及时增加订阅']
        ];
        
        $wechatResult = sendWechatSubscribeMessage($accessToken, $alert['wx_openid'], $alertTemplate['template_id'], $wechatData);
        if ($wechatResult['success']) {
            $stats['wechat_sent']++;
            cronLog("用户 {$alert['username']} 微信订阅消息发送成功");
            
            $DB->exec("UPDATE nteam_subscriptions SET subscribe_count = subscribe_count - 1 WHERE user_id = {$userId} AND template_id = '{$alertTemplate['template_id']}' AND subscribe_count > 0");
        } else {
            cronLog("用户 {$alert['username']} 微信订阅消息发送失败: " . $wechatResult['error']);
        }
    }
}

cronLog("========== 统计信息 ==========");
cronLog("检查用户数: {$stats['users_checked']}");
cronLog("发送提醒数: {$stats['alerts_sent']}");
cronLog("邮件发送成功: {$stats['email_sent']}");
cronLog("微信发送成功: {$stats['wechat_sent']}");
cronLog("========== 订阅额度提醒定时任务结束 ==========");
