<?php
$notLogin = true;
include('Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */

require_once 'funsion/AcademicSystem.php';

define('ACADEMIC_CRON_LOG_FILE', __DIR__ . '/logs/academic_push_cron.log');

$stats = [
    'success' => 0,
    'failed' => 0,
    'skipped' => 0
];

$pushConfig = $DB->getRow("SELECT * FROM nteam_academic_push_config WHERE id = 1");
if (!$pushConfig) {
    $pushConfig = [
        'schedule_reminder_enabled' => 1,
        'grade_notify_enabled' => 1,
        'wechat_push_enabled' => 1,
        'email_push_enabled' => 1,
        'schedule_reminder_time' => '20:00:00',
        'quiet_hours_start' => '22:00:00',
        'quiet_hours_end' => '08:00:00',
        'grade_semester' => ''
    ];
}

function isInQuietHours($config) {
    $now = time();
    $currentTime = date('H:i:s');
    
    $quietStart = $config['quiet_hours_start'] ?? '22:00:00';
    $quietEnd = $config['quiet_hours_end'] ?? '08:00:00';
    
    if ($quietStart < $quietEnd) {
        return ($currentTime >= $quietStart && $currentTime < $quietEnd);
    } else {
        return ($currentTime >= $quietStart || $currentTime < $quietEnd);
    }
}

function academicCronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents(ACADEMIC_CRON_LOG_FILE, $logMessage, FILE_APPEND);
    echo $logMessage;
}

function sendWechatSubscribeMessage($openid, $templateId, $data, $page = 'pages/white-home/index') {
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
        'page' => $page,
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

function sendAcademicEmail($email, $subject, $content, $extraData = []) {
    if (!function_exists('send_mail')) {
        require_once __DIR__ . '/Common/Core_Functions.php';
    }
    
    $siteName = conf('Name');
    $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
    $username = $extraData['username'] ?? '用户';
    $notifyType = $extraData['notify_type'] ?? '';
    
    $typeLabel = '';
    $typeIcon = '';
    $typeColor = '#007bff';
    
    switch ($notifyType) {
        case 'schedule_reminder':
            $typeLabel = '明日课程提醒';
            $typeIcon = '';
            $typeColor = '#17a2b8';
            break;
        case 'grade_notify':
            $typeLabel = '成绩发布通知';
            $typeIcon = '';
            $typeColor = '#28a745';
            break;
        default:
            $typeLabel = '学术通知';
            $typeIcon = '';
            $typeColor = '#007bff';
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
        ' . $content . '
        <br><p><a href="' . htmlspecialchars($siteUrl) . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">查看详情</a></p>
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

function addAcademicPushLog($DB, $userId, $pushType, $notifyType, $title, $content, $status, $errorMsg = null) {
    $now = date('Y-m-d H:i:s');
    $title = addslashes($title);
    $content = addslashes($content);
    $errorMsg = $errorMsg ? "'" . addslashes($errorMsg) . "'" : 'NULL';
    
    $sql = "INSERT INTO nteam_academic_push_logs (user_id, push_type, notify_type, title, content, status, error_msg, sent_at, created_at)
            VALUES ('$userId', '$pushType', '$notifyType', '$title', '$content', '$status', $errorMsg, '$now', '$now')";
    
    $DB->exec($sql);
}

function getTomorrowSchedule($token) {
    $tomorrow = strtotime('+1 day');
    $tomorrowWeekDay = date('N', $tomorrow);
    $weekDayMap = [
        1 => '星期一',
        2 => '星期二',
        3 => '星期三',
        4 => '星期四',
        5 => '星期五',
        6 => '星期六',
        7 => '星期日'
    ];
    
    $scheduleResult = AcademicSystem::getSchedule($token);
    
    if (!isset($scheduleResult['code']) || $scheduleResult['code'] != 1) {
        return ['success' => false, 'msg' => $scheduleResult['msg'] ?? '获取课程表失败'];
    }
    
    $scheduleData = $scheduleResult['data'] ?? [];
    $tomorrowCourses = [];
    $currentWeek = 1;
    
    if (is_array($scheduleData) && !empty($scheduleData)) {
        $schedule = $scheduleData[0] ?? [];
        
        if (isset($schedule['week'])) {
            $currentWeek = intval($schedule['week']);
        }
        
        $courses = $schedule['courses'] ?? [];
        
        foreach ($courses as $course) {
            $courseDay = intval($course['weekDay'] ?? 0);
            
            if ($courseDay == $tomorrowWeekDay) {
                $weeks = $course['classWeek'] ?? $course['weeks'] ?? '';
                $classWeekDetails = $course['classWeekDetails'] ?? '';
                
                $inWeek = false;
                if (!empty($classWeekDetails)) {
                    $inWeek = strpos($classWeekDetails, ',' . $currentWeek . ',') !== false;
                } elseif (!empty($weeks)) {
                    if (preg_match('/^(\d+)-(\d+)$/', $weeks, $matches)) {
                        $startWeek = intval($matches[1]);
                        $endWeek = intval($matches[2]);
                        $inWeek = ($currentWeek >= $startWeek && $currentWeek <= $endWeek);
                    } else {
                        $inWeek = strpos($weeks, (string)$currentWeek) !== false;
                    }
                } else {
                    $inWeek = true;
                }
                
                if ($inWeek) {
                    $tomorrowCourses[] = [
                        'name' => $course['courseName'] ?? '',
                        'teacher' => $course['teacherName'] ?? $course['teacher'] ?? '',
                        'room' => $course['location'] ?? $course['classroomName'] ?? '',
                        'time' => $course['classTime'] ?? '',
                        'weekDay' => $courseDay
                    ];
                }
            }
        }
    }
    
    return [
        'success' => true,
        'date' => date('Y-m-d', $tomorrow),
        'weekDay' => $tomorrowWeekDay,
        'weekDayName' => $weekDayMap[$tomorrowWeekDay],
        'currentWeek' => $currentWeek,
        'courses' => $tomorrowCourses,
        'total' => count($tomorrowCourses)
    ];
}

function checkNewGrades($DB, $userId, $token, $semester = '') {
    if (empty($semester)) {
        $semesterResult = AcademicSystem::getSemesterList($token);
        if (isset($semesterResult['code']) && $semesterResult['code'] == 1 && !empty($semesterResult['data'])) {
            foreach ($semesterResult['data'] as $s) {
                if (isset($s['isdqxq']) && $s['isdqxq'] == '1') {
                    $semester = $s['semesterId'];
                    break;
                }
            }
            if (empty($semester)) {
                $semester = $semesterResult['data'][0]['semesterId'] ?? '';
            }
        }
    }
    
    if (empty($semester)) {
        return ['success' => false, 'msg' => '无法获取当前学期'];
    }
    
    $gradeResult = AcademicSystem::getGrades($token, $semester);
    
    if (!isset($gradeResult['code']) || $gradeResult['code'] != 1) {
        return ['success' => false, 'msg' => $gradeResult['msg'] ?? '获取成绩失败'];
    }
    
    $grades = [];
    if (isset($gradeResult['data']) && is_array($gradeResult['data']) && !empty($gradeResult['data'])) {
        $achievement = $gradeResult['data'][0]['achievement'] ?? [];
        foreach ($achievement as $course) {
            $courseId = $course['id'] ?? $course['kch'] ?? md5($course['courseName'] ?? '');
            $grades[] = [
                'course_id' => $courseId,
                'course_name' => $course['courseName'] ?? '',
                'score' => $course['fraction'] ?? '',
                'credit' => $course['credit'] ?? 0,
                'semester' => $semester
            ];
        }
    }
    
    $newGrades = [];
    foreach ($grades as $grade) {
        $courseId = addslashes($grade['course_id']);
        $semesterEsc = addslashes($grade['semester']);
        
        $exists = $DB->getRow("SELECT id FROM nteam_grade_push_records WHERE user_id='$userId' AND course_id='$courseId' AND semester='$semesterEsc'");
        
        if (!$exists) {
            $newGrades[] = $grade;
        }
    }
    
    return [
        'success' => true,
        'semester' => $semester,
        'grades' => $grades,
        'new_grades' => $newGrades
    ];
}

function sendScheduleReminder() {
    global $DB, $stats, $pushConfig;
    
    academicCronLog("开始执行明日课程提醒任务");
    
    if ($pushConfig['schedule_reminder_enabled'] != 1) {
        academicCronLog("课程提醒功能已禁用");
        return ['status' => 'skipped', 'reason' => '课程提醒功能已禁用'];
    }
    
    if (isInQuietHours($pushConfig)) {
        academicCronLog("当前处于服务停止时段，跳过课程提醒");
        return ['status' => 'skipped', 'reason' => '服务停止时段'];
    }
    
    $pushSettings = $DB->getAll("SELECT aps.*, aa.student_id, aa.password, u.email, u.wx_openid, u.username
        FROM nteam_academic_push_settings aps
        LEFT JOIN nteam_academic_accounts aa ON aps.academic_account_id = aa.id
        LEFT JOIN nteam_users u ON aps.user_id = u.id
        WHERE aps.is_enabled = 1 AND aps.schedule_reminder_enabled = 1 AND aa.status = 1");
    
    if (!$pushSettings) {
        academicCronLog("没有需要发送课程提醒的用户");
        return ['status' => 'skipped', 'reason' => '没有订阅用户'];
    }
    
    $localStats = ['success' => 0, 'failed' => 0, 'skipped' => 0];
    
    foreach ($pushSettings as $setting) {
        $userId = $setting['user_id'];
        $username = $setting['username'] ?? '';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $userId, $setting['student_id'], $setting['password']);
        
        if (!$tokenResult['success']) {
            academicCronLog("用户 {$userId} 获取token失败: " . $tokenResult['msg']);
            $localStats['skipped']++;
            continue;
        }
        
        $token = $tokenResult['token'];
        $scheduleResult = getTomorrowSchedule($token);
        
        if (!$scheduleResult['success']) {
            academicCronLog("用户 {$userId} 获取课程表失败: " . ($scheduleResult['msg'] ?? '未知错误'));
            $localStats['skipped']++;
            continue;
        }
        
        $tomorrowCourses = $scheduleResult['courses'];
        $totalCourses = $scheduleResult['total'];
        $tomorrowDate = $scheduleResult['date'];
        $weekDayName = $scheduleResult['weekDayName'];
        
        if ($totalCourses == 0) {
            academicCronLog("用户 {$userId} 明天没有课程");
            $localStats['skipped']++;
            continue;
        }
        
        $pushTypes = $setting['push_types'];
        $title = "【课程提醒】明天{$weekDayName}有{$totalCourses}节课";
        $wechatContent = "明天{$weekDayName}共有{$totalCourses}节课，请提前做好准备";
        
        $emailContent = "<p>明天（{$tomorrowDate} {$weekDayName}）共有 <strong>{$totalCourses}</strong> 节课：</p>";
        $emailContent .= '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        $emailContent .= '<tr style="background-color: #f8f9fa;"><th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">课程名称</th><th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">教师</th><th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">教室</th><th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">节次</th></tr>';
        
        foreach ($tomorrowCourses as $course) {
            $emailContent .= '<tr>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($course['name']) . '</td>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($course['teacher']) . '</td>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($course['room']) . '</td>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($course['time']) . '</td>';
            $emailContent .= '</tr>';
        }
        $emailContent .= '</table>';
        
        $pushSuccess = false;
        
        if (strpos($pushTypes, 'wechat') !== false && !empty($setting['wx_openid']) && $pushConfig['wechat_push_enabled'] == 1) {
            $templateId = $pushConfig['schedule_template_id'] ?? null;
            if ($templateId) {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id = '$templateId' AND status = 1");
            } else {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE scene_type = 'schedule_reminder' AND status = 1 ORDER BY id DESC LIMIT 1");
            }
            
            if ($template) {
                $templateData = json_decode($template['default_data'], true) ?: [];
                $fields = json_decode($template['fields'], true) ?: [];
                
                $fieldMap = [];
                foreach ($fields as $field) {
                    $fieldMap[$field['name']] = $field['field'];
                }
                
                if (isset($fieldMap['日程主题'])) {
                    $templateData[$fieldMap['日程主题']] = ['value' => '明日课程提醒'];
                }
                if (isset($fieldMap['日程描述'])) {
                    $templateData[$fieldMap['日程描述']] = ['value' => mb_substr("明天{$weekDayName}共有{$totalCourses}节课", 0, 20)];
                }
                if (isset($fieldMap['温馨提示'])) {
                    $templateData[$fieldMap['温馨提示']] = ['value' => '请提前做好准备'];
                }
                if (isset($fieldMap['当前状态'])) {
                    $templateData[$fieldMap['当前状态']] = ['value' => '待处理'];
                }
                
                $result = sendWechatSubscribeMessage($setting['wx_openid'], $template['template_id'], $templateData, 'pages/today-schedule/index');
                
                if ($result['success']) {
                    $pushSuccess = true;
                    $localStats['success']++;
                    addAcademicPushLog($DB, $userId, 'wechat', 'schedule_reminder', $title, $wechatContent, 1);
                    academicCronLog("用户 {$userId} 课程提醒微信推送成功");
                } else {
                    $localStats['failed']++;
                    addAcademicPushLog($DB, $userId, 'wechat', 'schedule_reminder', $title, $wechatContent, 2, $result['error']);
                    academicCronLog("用户 {$userId} 课程提醒微信推送失败: " . $result['error']);
                }
            } else {
                academicCronLog("用户 {$userId} 课程提醒微信推送失败: 未找到可用的模板");
            }
        }
        
        if (strpos($pushTypes, 'email') !== false && !empty($setting['email']) && $pushConfig['email_push_enabled'] == 1) {
            $result = sendAcademicEmail($setting['email'], $title, $emailContent, [
                'username' => $username,
                'notify_type' => 'schedule_reminder'
            ]);
            
            if ($result['success']) {
                $pushSuccess = true;
                $localStats['success']++;
                addAcademicPushLog($DB, $userId, 'email', 'schedule_reminder', $title, strip_tags($emailContent), 1);
                academicCronLog("用户 {$userId} 课程提醒邮件推送成功");
            } else {
                $localStats['failed']++;
                addAcademicPushLog($DB, $userId, 'email', 'schedule_reminder', $title, strip_tags($emailContent), 2, $result['error']);
                academicCronLog("用户 {$userId} 课程提醒邮件推送失败: " . $result['error']);
            }
        }
        
        if (!$pushSuccess && strpos($pushTypes, 'wechat') === false && strpos($pushTypes, 'email') === false) {
            $localStats['skipped']++;
        }
    }
    
    $stats['success'] += $localStats['success'];
    $stats['failed'] += $localStats['failed'];
    $stats['skipped'] += $localStats['skipped'];
    
    academicCronLog("课程提醒任务完成: 成功 {$localStats['success']}, 失败 {$localStats['failed']}, 跳过 {$localStats['skipped']}");
    
    return [
        'status' => 'completed',
        'success' => $localStats['success'],
        'failed' => $localStats['failed'],
        'skipped' => $localStats['skipped']
    ];
}

function sendGradeNotification() {
    global $DB, $stats, $pushConfig;
    
    academicCronLog("开始执行成绩发布通知任务");
    
    if ($pushConfig['grade_notify_enabled'] != 1) {
        academicCronLog("成绩通知功能已禁用");
        return ['status' => 'skipped', 'reason' => '成绩通知功能已禁用'];
    }
    
    if (isInQuietHours($pushConfig)) {
        academicCronLog("当前处于服务停止时段，跳过成绩通知");
        return ['status' => 'skipped', 'reason' => '服务停止时段'];
    }
    
    $pushSettings = $DB->getAll("SELECT aps.*, aa.student_id, aa.password, u.email, u.wx_openid, u.username
        FROM nteam_academic_push_settings aps
        LEFT JOIN nteam_academic_accounts aa ON aps.academic_account_id = aa.id
        LEFT JOIN nteam_users u ON aps.user_id = u.id
        WHERE aps.is_enabled = 1 AND aps.grade_notify_enabled = 1 AND aa.status = 1");
    
    if (!$pushSettings) {
        academicCronLog("没有需要发送成绩通知的用户");
        return ['status' => 'skipped', 'reason' => '没有订阅用户'];
    }
    
    $localStats = ['success' => 0, 'failed' => 0, 'skipped' => 0];
    
    foreach ($pushSettings as $setting) {
        $userId = $setting['user_id'];
        $username = $setting['username'] ?? '';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $userId, $setting['student_id'], $setting['password']);
        
        if (!$tokenResult['success']) {
            academicCronLog("用户 {$userId} 获取token失败: " . $tokenResult['msg']);
            $localStats['skipped']++;
            continue;
        }
        
        $token = $tokenResult['token'];
        $gradeSemester = $pushConfig['grade_semester'] ?? '';
        if (!empty($gradeSemester)) {
            academicCronLog("使用管理员设置的学期: {$gradeSemester}");
        }
        $gradeResult = checkNewGrades($DB, $userId, $token, $gradeSemester);
        
        if (!$gradeResult['success']) {
            academicCronLog("用户 {$userId} 获取成绩失败: " . ($gradeResult['msg'] ?? '未知错误'));
            $localStats['skipped']++;
            continue;
        }
        
        $newGrades = $gradeResult['new_grades'];
        
        if (empty($newGrades)) {
            academicCronLog("用户 {$userId} 没有新成绩");
            $localStats['skipped']++;
            continue;
        }
        
        $pushTypes = $setting['push_types'];
        $semester = $gradeResult['semester'];
        $totalNewGrades = count($newGrades);
        
        $title = "【成绩通知】您有{$totalNewGrades}条新成绩";
        $wechatContent = "共有{$totalNewGrades}门课程成绩已发布";
        
        $emailContent = "<p>您有 <strong>{$totalNewGrades}</strong> 门课程成绩已发布：</p>";
        $emailContent .= '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        $emailContent .= '<tr style="background-color: #f8f9fa;"><th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">课程名称</th><th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">成绩</th><th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">学分</th></tr>';
        foreach ($newGrades as $grade) {
            $emailContent .= '<tr>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($grade['course_name']) . '</td>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;">' . htmlspecialchars($grade['score']) . '</td>';
            $emailContent .= '<td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">' . htmlspecialchars($grade['credit']) . '</td>';
            $emailContent .= '</tr>';
        }
        $emailContent .= '</table>';
        
        $pushSuccess = false;
        $now = date('Y-m-d H:i:s');
        
        if (strpos($pushTypes, 'wechat') !== false && !empty($setting['wx_openid']) && $pushConfig['wechat_push_enabled'] == 1) {
            $templateId = $pushConfig['grade_template_id'] ?? null;
            if ($templateId) {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id = '$templateId' AND status = 1");
            } else {
                $template = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE scene_type = 'grade_notify' AND status = 1 ORDER BY id DESC LIMIT 1");
            }
            
            if ($template) {
                $templateData = json_decode($template['default_data'], true) ?: [];
                $fields = json_decode($template['fields'], true) ?: [];
                
                $fieldMap = [];
                foreach ($fields as $field) {
                    $fieldMap[$field['name']] = $field['field'];
                }
                
                $firstGrade = $newGrades[0];
                if (isset($fieldMap['考试科目'])) {
                    $templateData[$fieldMap['考试科目']] = ['value' => mb_substr($firstGrade['course_name'], 0, 20)];
                }
                if (isset($fieldMap['考试分数'])) {
                    $templateData[$fieldMap['考试分数']] = ['value' => $firstGrade['score']];
                }
                if (isset($fieldMap['学生ID'])) {
                    $templateData[$fieldMap['学生ID']] = ['value' => $setting['student_id']];
                }
                if (isset($fieldMap['课程名称'])) {
                    $templateData[$fieldMap['课程名称']] = ['value' => mb_substr($firstGrade['course_name'], 0, 20)];
                }
                if (isset($fieldMap['考试结果'])) {
                    $passStatus = (is_numeric($firstGrade['score']) && $firstGrade['score'] >= 60) ? '通过' : '未通过';
                    $templateData[$fieldMap['考试结果']] = ['value' => $passStatus];
                }
                
                $result = sendWechatSubscribeMessage($setting['wx_openid'], $template['template_id'], $templateData, 'pages/academic-grade/index');
                
                if ($result['success']) {
                    $pushSuccess = true;
                    $localStats['success']++;
                    addAcademicPushLog($DB, $userId, 'wechat', 'grade_notify', $title, $wechatContent, 1);
                    foreach ($newGrades as $grade) {
                        $courseId = addslashes($grade['course_id']);
                        $courseName = addslashes($grade['course_name']);
                        $score = $grade['score'];
                        $credit = $grade['credit'];
                        $semesterEsc = addslashes($semester);
                        $DB->exec("INSERT INTO nteam_grade_push_records (user_id, course_id, course_name, score, credit, semester, push_type, status, pushed_at) VALUES ('$userId', '$courseId', '$courseName', '$score', '$credit', '$semesterEsc', 'wechat', 1, '$now')");
                    }
                    academicCronLog("用户 {$userId} 成绩通知微信推送成功: {$totalNewGrades}门课程");
                } else {
                    $localStats['failed']++;
                    addAcademicPushLog($DB, $userId, 'wechat', 'grade_notify', $title, $wechatContent, 2, $result['error']);
                    foreach ($newGrades as $grade) {
                        $courseId = addslashes($grade['course_id']);
                        $courseName = addslashes($grade['course_name']);
                        $score = $grade['score'];
                        $credit = $grade['credit'];
                        $semesterEsc = addslashes($semester);
                        $DB->exec("INSERT INTO nteam_grade_push_records (user_id, course_id, course_name, score, credit, semester, push_type, status, error_msg, pushed_at) VALUES ('$userId', '$courseId', '$courseName', '$score', '$credit', '$semesterEsc', 'wechat', 2, '" . addslashes($result['error']) . "', '$now')");
                    }
                    academicCronLog("用户 {$userId} 成绩通知微信推送失败: " . $result['error']);
                }
            }
        }
        
        if (strpos($pushTypes, 'email') !== false && !empty($setting['email']) && $pushConfig['email_push_enabled'] == 1) {
            $result = sendAcademicEmail($setting['email'], $title, $emailContent, [
                'username' => $username,
                'notify_type' => 'grade_notify'
            ]);
            
            if ($result['success']) {
                $pushSuccess = true;
                $localStats['success']++;
                addAcademicPushLog($DB, $userId, 'email', 'grade_notify', $title, "共{$totalNewGrades}门课程成绩", 1);
                foreach ($newGrades as $grade) {
                    $courseId = addslashes($grade['course_id']);
                    $courseName = addslashes($grade['course_name']);
                    $score = $grade['score'];
                    $credit = $grade['credit'];
                    $semesterEsc = addslashes($semester);
                    $DB->exec("INSERT INTO nteam_grade_push_records (user_id, course_id, course_name, score, credit, semester, push_type, status, pushed_at) VALUES ('$userId', '$courseId', '$courseName', '$score', '$credit', '$semesterEsc', 'email', 1, '$now')");
                }
                academicCronLog("用户 {$userId} 成绩通知邮件推送成功: {$totalNewGrades}门课程");
            } else {
                $localStats['failed']++;
                addAcademicPushLog($DB, $userId, 'email', 'grade_notify', $title, "共{$totalNewGrades}门课程成绩", 2, $result['error']);
                foreach ($newGrades as $grade) {
                    $courseId = addslashes($grade['course_id']);
                    $courseName = addslashes($grade['course_name']);
                    $score = $grade['score'];
                    $credit = $grade['credit'];
                    $semesterEsc = addslashes($semester);
                    $DB->exec("INSERT INTO nteam_grade_push_records (user_id, course_id, course_name, score, credit, semester, push_type, status, error_msg, pushed_at) VALUES ('$userId', '$courseId', '$courseName', '$score', '$credit', '$semesterEsc', 'email', 2, '" . addslashes($result['error']) . "', '$now')");
                }
                academicCronLog("用户 {$userId} 成绩通知邮件推送失败: " . $result['error']);
            }
        }
    }
    
    $stats['success'] += $localStats['success'];
    $stats['failed'] += $localStats['failed'];
    $stats['skipped'] += $localStats['skipped'];
    
    academicCronLog("成绩通知任务完成: 成功 {$localStats['success']}, 失败 {$localStats['failed']}, 跳过 {$localStats['skipped']}");
    
    return [
        'status' => 'completed',
        'success' => $localStats['success'],
        'failed' => $localStats['failed'],
        'skipped' => $localStats['skipped']
    ];
}

$mode = $_GET['mode'] ?? '';

academicCronLog("=== 学术推送任务开始 === mode: {$mode}");

switch ($mode) {
    case 'schedule':
        $result = sendScheduleReminder();
        break;
    case 'grade':
        $result = sendGradeNotification();
        break;
    case 'all':
        $scheduleResult = sendScheduleReminder();
        $gradeResult = sendGradeNotification();
        $result = [
            'status' => 'completed',
            'schedule' => $scheduleResult,
            'grade' => $gradeResult
        ];
        break;
    default:
        $result = ['status' => 'error', 'msg' => '无效的mode参数，可选: schedule, grade, all'];
        academicCronLog("错误: " . $result['msg']);
}

academicCronLog("=== 学术推送任务结束 ===");
academicCronLog("统计: 成功 {$stats['success']}, 失败 {$stats['failed']}, 跳过 {$stats['skipped']}");

echo json_encode($result, JSON_UNESCAPED_UNICODE);
