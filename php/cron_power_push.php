<?php
$notLogin = true;
include('Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */

define('CRON_LOG_FILE', __DIR__ . '/logs/power_push_cron.log');

$stats = [
    'success' => 0,
    'failed' => 0,
    'skipped' => 0
];

function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents(CRON_LOG_FILE, $logMessage, FILE_APPEND);
    echo $logMessage;
}

function sendWechatSubscribeMessage($openid, $templateId, $data) {
    global $DB;
    
    $wxConfig = $DB->getRow("SELECT * FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
    if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
        return ['success' => false, 'error' => '微信配置不完整'];
    }
    
    $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wxConfig['appid']}&secret={$wxConfig['secret']}";
    $tokenResult = file_get_contents($tokenUrl);
    $tokenData = json_decode($tokenResult, true);
    
    if (!isset($tokenData['access_token'])) {
        return ['success' => false, 'error' => '获取access_token失败: ' . ($tokenData['errmsg'] ?? '未知错误')];
    }
    
    $accessToken = $tokenData['access_token'];
    
    $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
    
    $postData = [
        'touser' => $openid,
        'template_id' => $templateId,
        'page' => 'pages/power-fee/index',
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

function calculateEstimatedDays($DB, $roomNum, $currentBalance, $pricePerKwh = 0.61) {
    $startTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    $records = $DB->getAll("SELECT * FROM nteam_power_daily_records 
        WHERE room_num = '$roomNum' AND record_time >= '$startTime' 
        ORDER BY record_time ASC");
    
    if (count($records) < 5) {
        return null;
    }
    
    $firstRecord = $records[0];
    $lastRecord = $records[count($records) - 1];
    
    $firstBalance = floatval($firstRecord['balance']);
    $lastBalance = floatval($lastRecord['balance']);
    
    if ($firstBalance <= $lastBalance) {
        return null;
    }
    
    $balanceDiff = $firstBalance - $lastBalance;
    
    $firstTime = strtotime($firstRecord['record_time']);
    $lastTime = strtotime($lastRecord['record_time']);
    $hoursDiff = ($lastTime - $firstTime) / 3600;
    
    if ($hoursDiff <= 0) {
        return null;
    }
    
    $avgUsagePerHour = $balanceDiff / $hoursDiff;
    
    if ($avgUsagePerHour <= 0) {
        return null;
    }
    
    $estimatedHours = $currentBalance / $avgUsagePerHour;
    $estimatedDays = $estimatedHours / 24;
    
    return [
        'days' => round($estimatedDays, 1),
        'avg_usage_per_hour' => round($avgUsagePerHour, 4),
        'avg_kwh_per_hour' => round($avgUsagePerHour / $pricePerKwh, 4),
        'data_count' => count($records)
    ];
}

function sendEmailNotification($email, $subject, $content, $extraData = [], $pricePerKwh = 0.61, $estimatedDays = null) {
    if (!function_exists('send_mail')) {
        require_once __DIR__ . '/Common/Core_Functions.php';
    }
    
    $siteName = conf('Name');
    $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
    $username = $extraData['username'] ?? '用户';
    $alias = $extraData['alias'] ?? '';
    $balance = $extraData['balance'] ?? null;
    $notifyType = $extraData['notify_type'] ?? '';
    
    $typeLabel = '';
    $typeIcon = '';
    $typeColor = '#007bff';
    switch ($notifyType) {
        case 'arrears':
            $typeLabel = '欠费提醒';
            $typeIcon = '';
            $typeColor = '#dc3545';
            break;
        case 'balance_critical':
            $typeLabel = '紧急提醒';
            $typeIcon = '';
            $typeColor = '#fd7e14';
            break;
        case 'balance_low':
            $typeLabel = '余额提醒';
            $typeIcon = '';
            $typeColor = '#ffc107';
            break;
        case 'daily_summary':
            $typeLabel = '每日用电汇总';
            $typeIcon = '';
            $typeColor = '#17a2b8';
            break;
        default:
            $typeLabel = '电费通知';
            $typeIcon = '';
            $typeColor = '#007bff';
    }
    
    $balanceDisplay = $balance !== null ? number_format($balance, 2) . ' 元' : '-';
    $remainingKwh = null;
    if ($balance !== null && $pricePerKwh > 0) {
        $remainingKwh = round($balance / $pricePerKwh, 1);
    }
    
    $htmlContent = '<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($subject) . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">' . htmlspecialchars($typeLabel) . ' - ' . htmlspecialchars($siteName) . '</h2>
        <p>您好，<strong>' . htmlspecialchars($username) . '</strong>：</p>
        <p>' . nl2br(htmlspecialchars($content)) . '</p>';
    
    if ($alias) {
        $htmlContent .= '
        <div style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 15px 0;">
            <p style="margin: 0;"><strong>宿舍：</strong>' . htmlspecialchars($alias) . '</p>
            <p style="margin: 8px 0 0 0;"><strong>当前余额：</strong>' . $balanceDisplay . '</p>';
        if ($remainingKwh !== null) {
            $htmlContent .= '
            <p style="margin: 8px 0 0 0;"><strong>剩余电量：</strong>' . $remainingKwh . ' kWh</p>';
        }
        if ($estimatedDays !== null) {
            $htmlContent .= '
            <p style="margin: 8px 0 0 0;"><strong>预计可用：</strong>' . $estimatedDays['days'] . ' 天</p>';
        }
        $htmlContent .= '
        </div>';
    }
    
    $htmlContent .= '
        <br><p><a href="' . htmlspecialchars($siteUrl) . 'user/power_fee.php" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">查看详情</a></p>
        <p style="color: #6c757d; font-size: 12px;">此邮件为系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';
    
    $result = send_mail($email, $subject, $htmlContent, true);
    
    if ($result === true) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => $result];
    }
}

function addPushLog($DB, $userId, $dormitoryBindingId, $pushType, $notifyType, $balance, $title, $content, $status, $errorMsg = null) {
    $now = date('Y-m-d H:i:s');
    $balance = $balance !== null ? "'$balance'" : 'NULL';
    $title = addslashes($title);
    $content = addslashes($content);
    $errorMsg = $errorMsg ? "'" . addslashes($errorMsg) . "'" : 'NULL';
    
    $sql = "INSERT INTO nteam_push_logs (user_id, dormitory_binding_id, push_type, notify_type, balance, title, content, status, error_msg, sent_at, created_at)
            VALUES ('$userId', '$dormitoryBindingId', '$pushType', '$notifyType', $balance, '$title', '$content', '$status', $errorMsg, '$now', '$now')";
    
    $DB->exec($sql);
}

function checkFrequencyMatch($frequency, $lastReminderTime, $lastReminderBalance, $currentBalance, $thresholdAmount = 5) {
    switch ($frequency) {
        case 'always':
            return true;
        case 'daily':
            return (!$lastReminderTime || date('Y-m-d') > substr($lastReminderTime, 0, 10));
        case 'threshold':
            return ($lastReminderBalance === null || $currentBalance < $lastReminderBalance - $thresholdAmount);
        default:
            return true;
    }
}

function checkAndSendPush() {
    global $DB, $stats;
    
    require_once 'funsion/DormitorySystem.php';
    
    $config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
    if (!$config) {
        $config = [
            'price_per_kwh' => 0.61,
            'low_balance_threshold' => 30,
            'critical_balance_threshold' => 10,
            'daily_summary_enabled' => 1,
            'low_balance_enabled' => 1,
            'critical_balance_enabled' => 1,
            'arrears_enabled' => 1,
            'email_push_enabled' => 1,
            'wechat_push_enabled' => 1,
            'daily_summary_frequency' => 'daily',
            'low_balance_frequency' => 'daily',
            'critical_balance_frequency' => 'always',
            'arrears_frequency' => 'always',
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '08:00:00'
        ];
    }
    
    $now = date('Y-m-d H:i:s');
    $currentTime = date('H:i:s');
    
    $quietStart = $config['quiet_hours_start'] ?? '22:00:00';
    $quietEnd = $config['quiet_hours_end'] ?? '08:00:00';
    
    $isQuietHours = false;
    if ($quietStart < $quietEnd) {
        $isQuietHours = ($currentTime >= $quietStart && $currentTime < $quietEnd);
    } else {
        $isQuietHours = ($currentTime >= $quietStart || $currentTime < $quietEnd);
    }
    
    if ($isQuietHours) {
        cronLog("当前处于服务停止时段 ({$quietStart} - {$quietEnd})，跳过推送");
        return ['status' => 'skipped', 'reason' => '服务停止时段'];
    }
    
    $pushSettings = $DB->getAll("SELECT ps.*, db.room_num, db.school_area, db.building, db.alias, u.email, u.wx_openid, u.username
        FROM nteam_push_settings ps
        LEFT JOIN nteam_dormitory_bindings db ON ps.dormitory_binding_id = db.id
        LEFT JOIN nteam_users u ON ps.user_id = u.id
        WHERE ps.is_enabled = 1 AND db.status = 1 AND (u.is_cancelling IS NULL OR u.is_cancelling = 0)");

    if (!$pushSettings) {
        cronLog("没有需要处理的推送设置");
        return ['status' => 'skipped', 'reason' => '没有推送设置'];
    }
    
    cronLog("开始处理 " . count($pushSettings) . " 个推送设置");
    
    $localStats = ['success' => 0, 'failed' => 0, 'skipped' => 0];
    
    foreach ($pushSettings as $setting) {
        $userId = $setting['user_id'];
        $dormitoryBindingId = $setting['dormitory_binding_id'];
        $roomNum = $setting['room_num'];
        $alias = $setting['alias'] ?: $roomNum;
        
        $result = DormitorySystem::getPowerFee($roomNum);
        
        if (!isset($result['code']) || $result['code'] != 1) {
            cronLog("查询宿舍 {$roomNum} 电费失败: " . ($result['msg'] ?? '未知错误'));
            continue;
        }
        
        $balance = floatval($result['data']['balance']);
        
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        
        $DB->exec("INSERT INTO nteam_power_daily_records (room_num, record_time, record_date, balance) VALUES ('$roomNum', '$now', '$today', '$balance')");
        
        $notifyTypes = explode(',', $setting['notify_types']);
        $pushTypes = $setting['push_types'];
        $lastReminderBalance = $setting['last_reminder_balance'];
        $lastReminderTime = $setting['last_reminder_time'];
        
        $shouldPush = false;
        $notifyType = '';
        $title = '';
        $content = '';
        
        $balanceLowThreshold = floatval($setting['balance_low_threshold'] ?: $config['low_balance_threshold']);
        $balanceCriticalThreshold = floatval($setting['balance_critical_threshold'] ?: $config['critical_balance_threshold']);
        
        $arrearsFrequency = $config['arrears_frequency'] ?? 'always';
        $criticalFrequency = $config['critical_balance_frequency'] ?? 'always';
        $lowFrequency = $config['low_balance_frequency'] ?? 'daily';
        $thresholdAmount = $config['threshold_amount'] ?? 5;
        
        if ($balance <= 0 && in_array('arrears', $notifyTypes) && $config['arrears_enabled']) {
            if (checkFrequencyMatch($arrearsFrequency, $lastReminderTime, $lastReminderBalance, $balance, $thresholdAmount)) {
                $shouldPush = true;
                $notifyType = 'arrears';
                $title = "【欠费提醒】{$alias}";
                $content = "您的宿舍 {$alias} 已欠费 {$balance} 元，请及时充值！";
            }
        } elseif ($balance < $balanceCriticalThreshold && in_array('balance_critical', $notifyTypes) && $config['critical_balance_enabled']) {
            if (checkFrequencyMatch($criticalFrequency, $lastReminderTime, $lastReminderBalance, $balance, $thresholdAmount)) {
                $shouldPush = true;
                $notifyType = 'balance_critical';
                $title = "【紧急提醒】{$alias} 电费即将耗尽";
                $content = "您的宿舍 {$alias} 电费余额仅剩 {$balance} 元，请尽快充值！";
            }
        } elseif ($balance < $balanceLowThreshold && in_array('balance_low', $notifyTypes) && $config['low_balance_enabled']) {
            if (checkFrequencyMatch($lowFrequency, $lastReminderTime, $lastReminderBalance, $balance, $thresholdAmount)) {
                $shouldPush = true;
                $notifyType = 'balance_low';
                $title = "【余额提醒】{$alias} 电费不足";
                $content = "您的宿舍 {$alias} 电费余额为 {$balance} 元，建议及时充值。";
            }
        }
        
        if ($shouldPush) {
            cronLog("宿舍 {$roomNum} 触发推送: {$notifyType}, 余额: {$balance}");
            
            $pushSuccess = false;
            
            if (strpos($pushTypes, 'wechat') !== false && $config['wechat_push_enabled'] && !empty($setting['wx_openid'])) {
                $templateId = $config['power_fee_template_id'] ?? null;
                if ($templateId) {
                    $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id = '$templateId' AND status = 1");
                } else {
                    $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE scene_type = 'power_fee' AND status = 1 ORDER BY id DESC LIMIT 1");
                }
                
                if ($template) {
                    $templateData = json_decode($template['default_data'], true) ?: [];
                    $fields = json_decode($template['fields'], true) ?: [];
                    
                    $fieldMap = [];
                    foreach ($fields as $field) {
                        $fieldMap[$field['name']] = $field['field'];
                    }
                    
                    if (isset($fieldMap['账户ID'])) {
                        $templateData[$fieldMap['账户ID']] = ['value' => mb_substr($alias, 0, 20)];
                    }
                    if (isset($fieldMap['当前余额'])) {
                        $templateData[$fieldMap['当前余额']] = ['value' => number_format($balance, 2)];
                    }
                    if (isset($fieldMap['余量'])) {
                        $remainingKwh = round($balance / ($config['price_per_kwh'] ?? 0.61), 1);
                        $templateData[$fieldMap['余量']] = ['value' => $remainingKwh];
                    }
                    if (isset($fieldMap['温馨提醒'])) {
                        $templateData[$fieldMap['温馨提醒']] = ['value' => mb_substr($content, 0, 20)];
                    }
                    
                    $result = sendWechatSubscribeMessage($setting['wx_openid'], $template['template_id'], $templateData);
                    
                    if ($result['success']) {
                        $pushSuccess = true;
                        $localStats['success']++;
                        addPushLog($DB, $userId, $dormitoryBindingId, 'wechat', $notifyType, $balance, $title, $content, 1);
                        cronLog("微信推送成功: {$roomNum}");
                    } else {
                        $localStats['failed']++;
                        addPushLog($DB, $userId, $dormitoryBindingId, 'wechat', $notifyType, $balance, $title, $content, 2, $result['error']);
                        cronLog("微信推送失败: {$roomNum} - " . $result['error']);
                    }
                }
            }
            
            if (strpos($pushTypes, 'email') !== false && $config['email_push_enabled'] && !empty($setting['email'])) {
                $estimatedDays = calculateEstimatedDays($DB, $roomNum, $balance, $config['price_per_kwh'] ?? 0.61);
                $result = sendEmailNotification($setting['email'], $title, $content, [
                    'username' => $setting['username'] ?? '',
                    'alias' => $alias,
                    'balance' => $balance,
                    'notify_type' => $notifyType
                ], $config['price_per_kwh'] ?? 0.61, $estimatedDays);
                
                if ($result['success']) {
                    $pushSuccess = true;
                    $localStats['success']++;
                    addPushLog($DB, $userId, $dormitoryBindingId, 'email', $notifyType, $balance, $title, $content, 1);
                    cronLog("邮件推送成功: {$roomNum}");
                } else {
                    $localStats['failed']++;
                    addPushLog($DB, $userId, $dormitoryBindingId, 'email', $notifyType, $balance, $title, $content, 2, $result['error']);
                    cronLog("邮件推送失败: {$roomNum} - " . $result['error']);
                }
            }
            
            if ($pushSuccess) {
                $DB->exec("UPDATE nteam_push_settings SET last_reminder_balance = '{$balance}', last_reminder_time = NOW() WHERE id = '{$setting['id']}'");
            }
        } else {
            $localStats['skipped']++;
        }
    }
    
    $stats['success'] += $localStats['success'];
    $stats['failed'] += $localStats['failed'];
    $stats['skipped'] += $localStats['skipped'];
    
    cronLog("推送任务执行完成: 成功 {$localStats['success']} 个, 失败 {$localStats['failed']} 个, 跳过 {$localStats['skipped']} 个");
    
    return [
        'status' => 'completed',
        'success' => $localStats['success'],
        'failed' => $localStats['failed'],
        'skipped' => $localStats['skipped']
    ];
}

function sendDailySummary() {
    global $DB, $stats;
    
    $config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
    if (!$config || !$config['daily_summary_enabled']) {
        cronLog("每日用电汇总功能未启用");
        return ['status' => 'skipped', 'reason' => '功能未启用'];
    }
    
    $pushSettings = $DB->getAll("SELECT ps.*, db.room_num, db.alias, u.email, u.wx_openid, u.username
        FROM nteam_push_settings ps
        LEFT JOIN nteam_dormitory_bindings db ON ps.dormitory_binding_id = db.id
        LEFT JOIN nteam_users u ON ps.user_id = u.id
        WHERE ps.is_enabled = 1 AND db.status = 1 AND FIND_IN_SET('daily_summary', ps.notify_types) AND (u.is_cancelling IS NULL OR u.is_cancelling = 0)");
    
    if (!$pushSettings) {
        cronLog("没有需要发送每日汇总的用户");
        return ['status' => 'skipped', 'reason' => '没有订阅用户'];
    }
    
    $localStats = ['success' => 0, 'failed' => 0, 'skipped' => 0];
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    foreach ($pushSettings as $setting) {
        $roomNum = $setting['room_num'];
        $alias = $setting['alias'] ?: $roomNum;
        
        $record = $DB->getRow("SELECT * FROM nteam_power_daily_records WHERE room_num = '$roomNum' AND record_date = '$yesterday'");
        
        if (!$record) {
            $localStats['skipped']++;
            continue;
        }
        
        $balance = $record['balance'];
        $usageKwh = $record['usage_kwh'];
        $usageCost = $record['usage_cost'];
        
        $title = "【每日用电】{$alias} 用电汇总";
        $content = "昨日用电: " . ($usageKwh ? round($usageKwh, 2) . " 度" : "暂无数据");
        $content .= "\n昨日费用: " . ($usageCost ? round($usageCost, 2) . " 元" : "暂无数据");
        $content .= "\n当前余额: {$balance} 元";
        
        $pushSuccess = false;
        
        if (strpos($setting['push_types'], 'wechat') !== false && $config['wechat_push_enabled'] && !empty($setting['wx_openid'])) {
            $templateId = $config['power_report_template_id'] ?? null;
            if ($templateId) {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id = '$templateId' AND status = 1");
            } else {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE scene_type = 'power_report' AND status = 1 ORDER BY id DESC LIMIT 1");
            }
            
            if ($template) {
                $templateData = json_decode($template['default_data'], true) ?: [];
                $fields = json_decode($template['fields'], true) ?: [];
                
                $fieldMap = [];
                foreach ($fields as $field) {
                    $fieldMap[$field['name']] = $field['field'];
                }
                
                if (isset($fieldMap['项目名称'])) {
                    $templateData[$fieldMap['项目名称']] = ['value' => mb_substr("{$alias}每日用电汇总", 0, 20)];
                }
                if (isset($fieldMap['生成时间'])) {
                    $templateData[$fieldMap['生成时间']] = ['value' => date('Y-m-d H:i')];
                }
                if (isset($fieldMap['报告状态'])) {
                    $templateData[$fieldMap['报告状态']] = ['value' => '已完成'];
                }
                if (isset($fieldMap['备注'])) {
                    $remark = "用电" . round($usageKwh, 2) . "度，余额" . number_format($balance, 2) . "元";
                    $templateData[$fieldMap['备注']] = ['value' => mb_substr($remark, 0, 20)];
                }
                
                $result = sendWechatSubscribeMessage($setting['wx_openid'], $template['template_id'], $templateData);
                
                if ($result['success']) {
                    $pushSuccess = true;
                    $localStats['success']++;
                    addPushLog($DB, $setting['user_id'], $setting['dormitory_binding_id'], 'wechat', 'daily_summary', $balance, $title, $content, 1);
                    cronLog("每日汇总微信推送成功: {$roomNum}");
                } else {
                    $localStats['failed']++;
                    addPushLog($DB, $setting['user_id'], $setting['dormitory_binding_id'], 'wechat', 'daily_summary', $balance, $title, $content, 2, $result['error']);
                }
            }
        }
        
        if (strpos($setting['push_types'], 'email') !== false && $config['email_push_enabled'] && !empty($setting['email'])) {
            $estimatedDays = calculateEstimatedDays($DB, $roomNum, $balance, $config['price_per_kwh'] ?? 0.61);
            $result = sendEmailNotification($setting['email'], $title, $content, [
                'username' => $setting['username'] ?? '',
                'alias' => $alias,
                'balance' => $balance,
                'notify_type' => 'daily_summary'
            ], $config['price_per_kwh'] ?? 0.61, $estimatedDays);
            
            if ($result['success']) {
                $pushSuccess = true;
                $localStats['success']++;
                addPushLog($DB, $setting['user_id'], $setting['dormitory_binding_id'], 'email', 'daily_summary', $balance, $title, $content, 1);
                cronLog("每日汇总邮件推送成功: {$roomNum}");
            } else {
                $localStats['failed']++;
            }
        }
        
        if (!$pushSuccess && strpos($setting['push_types'], 'wechat') === false && strpos($setting['push_types'], 'email') === false) {
            $localStats['skipped']++;
        }
    }
    
    $stats['success'] += $localStats['success'];
    $stats['failed'] += $localStats['failed'];
    $stats['skipped'] += $localStats['skipped'];
    
    cronLog("每日用电汇总推送完成: 成功 {$localStats['success']} 个, 失败 {$localStats['failed']} 个, 跳过 {$localStats['skipped']} 个");
    
    return [
        'status' => 'completed',
        'success' => $localStats['success'],
        'failed' => $localStats['failed'],
        'skipped' => $localStats['skipped']
    ];
}

$mode = isset($argv[1]) ? $argv[1] : (isset($_GET['act']) ? $_GET['act'] : (isset($_GET['mode']) ? $_GET['mode'] : 'check'));

cronLog("========== 开始执行电费推送任务 (模式: {$mode}) ==========");

$result = null;

switch ($mode) {
    case 'check':
        $result = checkAndSendPush();
        break;
    case 'daily':
        $result = sendDailySummary();
        break;
    case 'all':
        $r1 = checkAndSendPush();
        $r2 = sendDailySummary();
        $result = [
            'status' => 'completed',
            'success' => $stats['success'],
            'failed' => $stats['failed'],
            'skipped' => $stats['skipped'],
            'details' => [
                'check' => $r1,
                'daily' => $r2
            ]
        ];
        break;
    default:
        cronLog("未知模式: {$mode}");
        $result = ['status' => 'error', 'reason' => '未知模式'];
}

cronLog("========== 任务执行结束 ==========");
cronLog("汇总: 成功 {$stats['success']} 个, 失败 {$stats['failed']} 个, 跳过 {$stats['skipped']} 个");

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 0,
        'msg' => '执行完成',
        'data' => $result,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
