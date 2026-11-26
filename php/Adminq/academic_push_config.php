<?php
$mod = 'admin';
$title='学术推送配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$config = $DB->getRow("SELECT * FROM nteam_academic_push_config WHERE id = 1");
if (!$config) {
    $config = [
        'schedule_reminder_enabled' => 1,
        'grade_notify_enabled' => 1,
        'wechat_push_enabled' => 1,
        'email_push_enabled' => 1,
        'schedule_reminder_time' => '20:00:00',
        'quiet_hours_start' => '22:00:00',
        'quiet_hours_end' => '08:00:00',
        'grade_semester' => '',
        'schedule_template_id' => null,
        'grade_template_id' => null,
        'default_schedule_mode' => ''
    ];
}

$scheduleTemplates = $DB->getAll("SELECT id, template_id, name FROM nteam_wx_templates WHERE scene_type = 'schedule_reminder' AND status = 1 ORDER BY id DESC");
$gradeTemplates = $DB->getAll("SELECT id, template_id, name FROM nteam_wx_templates WHERE scene_type = 'grade_notify' AND status = 1 ORDER BY id DESC");

$checkColumn = $DB->getRow("SHOW COLUMNS FROM nteam_academic_push_config LIKE 'schedule_template_id'");
$hasTemplateColumns = $checkColumn ? true : false;

// 学期列表与课程模式只读文件缓存，页面渲染阶段不再同步外呼教务系统。
// 缓存缺失时由 Adminq/foot.php 异步请求 Ajax.php?act=academic_meta_refresh 回填。
require_once('../funsion/AcademicSystem.php');
$semesterList  = AcademicSystem::getMeta('semesters');
$scheduleModes = AcademicSystem::getMeta('modes');
$semesterList  = is_array($semesterList) ? $semesterList : [];
$scheduleModes = is_array($scheduleModes) ? $scheduleModes : [];

// 自动迁移：确保 default_schedule_mode 字段存在（无需手动执行 SQL）
$checkDefaultCol = $DB->getRow("SHOW COLUMNS FROM nteam_academic_push_config LIKE 'default_schedule_mode'");
if (!$checkDefaultCol) {
    $DB->exec("ALTER TABLE nteam_academic_push_config ADD COLUMN default_schedule_mode VARCHAR(50) NULL DEFAULT NULL COMMENT '系统默认课程表类型(kbjcmsid)'");
}

$stats = [
    'total_users' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_settings WHERE is_enabled = 1") ?: 0,
    'schedule_users' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_settings WHERE is_enabled = 1 AND schedule_reminder_enabled = 1") ?: 0,
    'grade_users' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_settings WHERE is_enabled = 1 AND grade_notify_enabled = 1") ?: 0,
    'today_logs' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE DATE(created_at) = CURDATE()") ?: 0,
    'success_logs' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 1") ?: 0,
    'failed_logs' => $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 2") ?: 0,
];

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 15;
$offset = ($page - 1) * $pageSize;

$where = '1=1';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
if ($filter === 'schedule') {
    $where .= " AND pl.notify_type = 'schedule_reminder'";
} elseif ($filter === 'grade') {
    $where .= " AND pl.notify_type = 'grade_notify'";
}

$totalLogs = $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs pl WHERE $where") ?: 0;
$logs = $DB->getAll("SELECT pl.*, u.username, aa.student_id
    FROM nteam_academic_push_logs pl 
    LEFT JOIN nteam_users u ON pl.user_id = u.id 
    LEFT JOIN nteam_academic_accounts aa ON pl.user_id = aa.user_id
    WHERE $where
    ORDER BY pl.id DESC 
    LIMIT $offset, $pageSize") ?: [];

$totalGradeRecords = $DB->getColumn("SELECT COUNT(*) FROM nteam_grade_push_records") ?: 0;
$gradeRecords = $DB->getAll("SELECT gpr.*, u.username, aa.student_id
    FROM nteam_grade_push_records gpr 
    LEFT JOIN nteam_users u ON gpr.user_id = u.id 
    LEFT JOIN nteam_academic_accounts aa ON gpr.user_id = aa.user_id
    ORDER BY gpr.id DESC 
    LIMIT $offset, $pageSize") ?: [];

$totalPages = max($totalLogs, $totalGradeRecords) > 0 ? ceil(max($totalLogs, $totalGradeRecords) / $pageSize) : 1;

$notifyTypeMap = [
    'schedule_reminder' => '课程提醒',
    'grade_notify' => '成绩通知'
];

$pushTypeMap = [
    'wechat' => '微信',
    'email' => '邮件'
];
?>
<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <?php echo $title ?>
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            <?php echo $title ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 bg-primary">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white"><?php echo $stats['total_users']; ?></div>
                        <div class="text-white opacity-75">开启推送用户</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 bg-info">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white"><?php echo $stats['schedule_users']; ?></div>
                        <div class="text-white opacity-75">课程提醒订阅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 bg-success">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white"><?php echo $stats['grade_users']; ?></div>
                        <div class="text-white opacity-75">成绩通知订阅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded mb-0 bg-warning">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white"><?php echo $stats['today_logs']; ?></div>
                        <div class="text-white opacity-75">今日推送次数</div>
                    </div>
                </div>
            </div>
        </div>
        
        <ul class="nav nav-tabs mt-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'config' ? 'active' : ''; ?>" href="?tab=config">推送配置</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'logs' ? 'active' : ''; ?>" href="?tab=logs">推送日志</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'grades' ? 'active' : ''; ?>" href="?tab=grades">成绩记录</a>
            </li>
        </ul>
        
        <?php if ($activeTab === 'config'): ?>
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">推送功能设置</h3>
            </div>
            <div class="block-content">
                <form onsubmit="return saveAcademicPushConfig(this)" method="post" name="academic-push-config-form">
                    <h4 class="mb-3">推送功能开关</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>明日课程提醒</b></label>
                                <select class="form-select" name="schedule_reminder_enabled">
                                    <option value="1" <?php echo ($config['schedule_reminder_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['schedule_reminder_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>成绩发布通知</b></label>
                                <select class="form-select" name="grade_notify_enabled">
                                    <option value="1" <?php echo ($config['grade_notify_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['grade_notify_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">推送方式开关</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>微信推送</b></label>
                                <select class="form-select" name="wechat_push_enabled">
                                    <option value="1" <?php echo ($config['wechat_push_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['wechat_push_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>邮件推送</b></label>
                                <select class="form-select" name="email_push_enabled">
                                    <option value="1" <?php echo ($config['email_push_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['email_push_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">默认提醒时间</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>课程提醒时间</b></label>
                                <input type="time" class="form-control" name="schedule_reminder_time" 
                                       value="<?php echo substr($config['schedule_reminder_time'] ?? '20:00:00', 0, 5); ?>">
                                <div class="form-text">新用户默认的提醒时间</div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">成绩推送学期</h4>
                    <div class="alert alert-info mb-3">
                        <strong>说明：</strong>设置成绩推送的学期，留空则自动获取当前学期。手动指定学期可用于推送历史学期成绩。
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>推送学期</b></label>
                                <select class="form-select" name="grade_semester" id="grade_semester">
                                    <option value="">自动获取当前学期</option>
                                    <?php if (!empty($semesterList)): ?>
                                        <?php foreach ($semesterList as $sem): ?>
                                            <option value="<?php echo htmlspecialchars($sem['semesterId'] ?? ''); ?>" 
                                                    <?php echo ($config['grade_semester'] ?? '') == ($sem['semesterId'] ?? '') ? 'selected' : ''; ?>
                                                    <?php echo isset($sem['isdqxq']) && $sem['isdqxq'] == '1' ? 'data-current="true"' : ''; ?>>
                                                <?php echo htmlspecialchars($sem['semesterName'] ?? $sem['semesterId'] ?? ''); ?>
                                                <?php echo isset($sem['isdqxq']) && $sem['isdqxq'] == '1' ? ' (当前学期)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($semesterList)): ?>
                                <div class="form-text text-warning">学期列表正在后台异步加载，稍后会自动刷新；若长时间为空请检查教务账号绑定。</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">默认课程表类型</h4>
                    <div class="alert alert-info mb-3">
                        <strong>说明：</strong>设置系统默认课程表类型（课程表类别），课程表页与首页「今日课程」将优先使用该类型展示。留空则自动使用第一个有课的类型。
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>课程表类型</b></label>
                                <select class="form-select" name="default_schedule_mode">
                                    <option value="">自动（使用第一个有课的类型）</option>
                                    <?php if (!empty($scheduleModes)): ?>
                                        <?php foreach ($scheduleModes as $mode): ?>
                                            <option value="<?php echo htmlspecialchars($mode['kbjcmsid'] ?? ''); ?>"
                                                    <?php echo ($config['default_schedule_mode'] ?? '') == ($mode['kbjcmsid'] ?? '') ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($mode['kbjcmsmc'] ?? $mode['kbjcmsid'] ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($scheduleModes)): ?>
                                <div class="form-text text-warning">课程表类型正在后台异步加载，稍后会自动刷新；若长时间为空请检查教务账号绑定。</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($hasTemplateColumns): ?>
                    <h4 class="mb-3 mt-4">微信订阅消息模板</h4>
                    <div class="alert alert-info mb-3">
                        <strong>说明：</strong>请先在<a href="subscription_manage.php?tab=templates">消息模板管理</a>中添加对应场景的模板，然后在此选择。
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>课程提醒模板</b></label>
                                <select class="form-select" name="schedule_template_id">
                                    <option value="">请选择模板</option>
                                    <?php if ($scheduleTemplates): ?>
                                        <?php foreach ($scheduleTemplates as $tpl): ?>
                                            <option value="<?php echo $tpl['id']; ?>" <?php echo ($config['schedule_template_id'] ?? '') == $tpl['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tpl['name']); ?> (<?php echo htmlspecialchars($tpl['template_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($scheduleTemplates)): ?>
                                <div class="form-text text-warning">暂无可用模板，请先添加场景类型为"课程提醒"的模板</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>成绩通知模板</b></label>
                                <select class="form-select" name="grade_template_id">
                                    <option value="">请选择模板</option>
                                    <?php if ($gradeTemplates): ?>
                                        <?php foreach ($gradeTemplates as $tpl): ?>
                                            <option value="<?php echo $tpl['id']; ?>" <?php echo ($config['grade_template_id'] ?? '') == $tpl['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tpl['name']); ?> (<?php echo htmlspecialchars($tpl['template_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($gradeTemplates)): ?>
                                <div class="form-text text-warning">暂无可用模板，请先添加场景类型为"成绩通知"的模板</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <h4 class="mb-3 mt-4">服务停止时段</h4>
                    <div class="alert alert-warning mb-3">
                        <strong>说明：</strong>在服务停止时段内，定时任务将暂停执行推送，避免在夜间等时段打扰用户。
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>开始时间</b></label>
                                <input type="time" class="form-control" name="quiet_hours_start" 
                                       value="<?php echo substr($config['quiet_hours_start'] ?? '22:00:00', 0, 5); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>结束时间</b></label>
                                <input type="time" class="form-control" name="quiet_hours_end" 
                                       value="<?php echo substr($config['quiet_hours_end'] ?? '08:00:00', 0, 5); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存配置</b>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif ($activeTab === 'logs'): ?>
        <div class="block block-rounded mt-4 mb-3">
            <div class="block-header block-header-default">
                <h3 class="block-title">推送统计</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-success fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 1") ?: 0; ?></div>
                                <div class="text-muted">成功发送</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-danger fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 2") ?: 0; ?></div>
                                <div class="text-muted">发送失败</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-primary fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE notify_type = 'schedule_reminder'") ?: 0; ?></div>
                                <div class="text-muted">课程提醒</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-info fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE notify_type = 'grade_notify'") ?: 0; ?></div>
                                <div class="text-muted">成绩通知</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3 mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center py-3">
                                <?php 
                                $successCount = $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 1") ?: 0;
                                $failedCount = $DB->getColumn("SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status = 2") ?: 0;
                                $total = $successCount + $failedCount;
                                $successRate = $total > 0 ? round($successCount / $total * 100, 1) : 0;
                                ?>
                                <div class="fs-4 text-primary fw-bold"><?php echo $successRate; ?>%</div>
                                <div class="text-muted">推送成功率 (共 <?php echo $total; ?> 次推送)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">学术推送日志</h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-alt-danger" onclick="clearPushLogs()"><i class="fa fa-trash me-1"></i>一键清空</button>
                    <span class="text-muted ms-2">共 <?php echo $totalLogs; ?> 条记录</span>
                </div>
            </div>
            <div class="block-content">
                <div class="mb-3">
                    <a href="?tab=logs&filter=" class="btn btn-sm <?php echo $filter === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">全部</a>
                    <a href="?tab=logs&filter=schedule" class="btn btn-sm <?php echo $filter === 'schedule' ? 'btn-primary' : 'btn-outline-primary'; ?>">课程提醒</a>
                    <a href="?tab=logs&filter=grade" class="btn btn-sm <?php echo $filter === 'grade' ? 'btn-primary' : 'btn-outline-primary'; ?>">成绩通知</a>
                </div>
                
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="min-width: 50px;">ID</th>
                                <th style="min-width: 80px;">用户</th>
                                <th style="min-width: 100px;">学工号</th>
                                <th style="min-width: 80px;">推送类型</th>
                                <th style="min-width: 80px;">通知类型</th>
                                <th style="min-width: 120px;">标题</th>
                                <th class="text-center" style="min-width: 60px;">状态</th>
                                <th style="min-width: 140px;">发送时间</th>
                                <th style="min-width: 80px;">错误信息</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo htmlspecialchars($log['username'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['student_id'] ?: '-'); ?></td>
                                    <td><?php echo $pushTypeMap[$log['push_type']] ?? $log['push_type']; ?></td>
                                    <td><?php echo $notifyTypeMap[$log['notify_type']] ?? $log['notify_type']; ?></td>
                                    <td><?php echo htmlspecialchars($log['title'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <?php if ($log['status'] == 1): ?>
                                            <span class="badge bg-success">已发送</span>
                                        <?php elseif ($log['status'] == 2): ?>
                                            <span class="badge bg-danger">发送失败</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">待发送</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['sent_at'] ?: '-'; ?></td>
                                    <td>
                                        <?php if ($log['error_msg']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#errorModal<?php echo $log['id']; ?>">
                                            查看
                                        </button>
                                        <div class="modal fade" id="errorModal<?php echo $log['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">错误信息</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <pre class="text-danger"><?php echo htmlspecialchars($log['error_msg']); ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">暂无记录</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=logs&page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>">上一页</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?tab=logs&page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=logs&page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>">下一页</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($activeTab === 'grades'): ?>
        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">成绩推送记录</h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-alt-danger" onclick="clearGradeRecords()"><i class="fa fa-trash me-1"></i>一键清空</button>
                    <span class="text-muted ms-2">共 <?php echo $totalGradeRecords; ?> 条记录</span>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="min-width: 50px;">ID</th>
                                <th style="min-width: 80px;">用户</th>
                                <th style="min-width: 100px;">学工号</th>
                                <th style="min-width: 120px;">课程名称</th>
                                <th style="min-width: 60px;">成绩</th>
                                <th style="min-width: 50px;">学分</th>
                                <th style="min-width: 100px;">学期</th>
                                <th style="min-width: 60px;">推送类型</th>
                                <th class="text-center" style="min-width: 60px;">状态</th>
                                <th style="min-width: 140px;">推送时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($gradeRecords): ?>
                                <?php foreach ($gradeRecords as $record): ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><?php echo htmlspecialchars($record['username'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($record['student_id'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($record['course_name'] ?: '-'); ?></td>
                                    <td>
                                        <?php 
                                        $score = $record['score'];
                                        $scoreClass = '';
                                        if (is_numeric($score)) {
                                            if ($score >= 90) $scoreClass = 'text-success fw-bold';
                                            elseif ($score >= 80) $scoreClass = 'text-primary';
                                            elseif ($score >= 60) $scoreClass = 'text-warning';
                                            else $scoreClass = 'text-danger fw-bold';
                                        }
                                        ?>
                                        <span class="<?php echo $scoreClass; ?>"><?php echo htmlspecialchars($score); ?></span>
                                    </td>
                                    <td><?php echo $record['credit'] ?: '-'; ?></td>
                                    <td><?php echo htmlspecialchars($record['semester'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($record['push_type'] == 'wechat'): ?>
                                        <span class="badge bg-success">微信</span>
                                        <?php else: ?>
                                        <span class="badge bg-primary">邮件</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($record['status'] == 1): ?>
                                            <span class="badge bg-success">已推送</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">推送失败</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $record['pushed_at'] ?: '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">暂无记录</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=grades&page=<?php echo $page - 1; ?>">上一页</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?tab=grades&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=grades&page=<?php echo $page + 1; ?>">下一页</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function saveAcademicPushConfig(form) {
    var formData = new FormData(form);
    var data = {
        act: 'save_academic_push_config',
        schedule_reminder_enabled: formData.get('schedule_reminder_enabled'),
        grade_notify_enabled: formData.get('grade_notify_enabled'),
        wechat_push_enabled: formData.get('wechat_push_enabled'),
        email_push_enabled: formData.get('email_push_enabled'),
        schedule_reminder_time: formData.get('schedule_reminder_time'),
        quiet_hours_start: formData.get('quiet_hours_start'),
        quiet_hours_end: formData.get('quiet_hours_end'),
        grade_semester: formData.get('grade_semester'),
        schedule_template_id: formData.get('schedule_template_id'),
        grade_template_id: formData.get('grade_template_id'),
        default_schedule_mode: formData.get('default_schedule_mode')
    };
    
    $.post('Ajax.php', data, function(res) {
        if (res.code == 0) {
            layer.msg('保存成功', {icon: 1});
        } else {
            layer.msg(res.msg || '保存失败', {icon: 2});
        }
    }, 'json');
    
    return false;
}

function clearPushLogs() {
    layer.confirm('确定要清空所有推送日志吗？此操作不可恢复！', {
        btn: ['确定清空', '取消'],
        icon: 3
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clear_push_logs',
            dataType: 'json',
            success: function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.alert(data.msg, {icon: 1}, function(){
                        window.location.reload();
                    });
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function() {
                layer.close(ii);
                layer.alert('请求失败', {icon: 2});
            }
        });
    });
}

function clearGradeRecords() {
    layer.confirm('确定要清空所有成绩推送记录吗？此操作不可恢复！', {
        btn: ['确定清空', '取消'],
        icon: 3
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clear_grade_records',
            dataType: 'json',
            success: function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.alert(data.msg, {icon: 1}, function(){
                        window.location.reload();
                    });
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function() {
                layer.close(ii);
                layer.alert('请求失败', {icon: 2});
            }
        });
    });
}
</script>

<?php include './foot.php'; ?>
