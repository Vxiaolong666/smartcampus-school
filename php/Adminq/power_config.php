<?php
$mod = 'admin';
$title='电费配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

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
        'threshold_amount' => 5,
        'quiet_hours_start' => '22:00:00',
        'quiet_hours_end' => '08:00:00',
        'power_fee_template_id' => null,
        'power_report_template_id' => null
    ];
}

$powerFeeTemplates = $DB->getAll("SELECT id, template_id, name FROM nteam_wx_templates WHERE scene_type = 'power_fee' AND status = 1 ORDER BY id DESC");
$powerReportTemplates = $DB->getAll("SELECT id, template_id, name FROM nteam_wx_templates WHERE scene_type = 'power_report' AND status = 1 ORDER BY id DESC");

$checkColumn = $DB->getRow("SHOW COLUMNS FROM nteam_power_config LIKE 'power_fee_template_id'");
$hasTemplateColumns = $checkColumn ? true : false;

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 15;
$offset = ($page - 1) * $pageSize;

$total = $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs") ?: 0;

$logs = $DB->getAll("SELECT pl.*, u.username, db.room_num, db.building, db.room 
    FROM nteam_push_logs pl 
    LEFT JOIN nteam_users u ON pl.user_id = u.id 
    LEFT JOIN nteam_dormitory_bindings db ON pl.dormitory_binding_id = db.id 
    ORDER BY pl.id DESC 
    LIMIT $offset, $pageSize") ?: [];

$totalPages = $total > 0 ? ceil($total / $pageSize) : 1;

$notifyTypeMap = [
    'daily_summary' => '每日汇总',
    'balance_low' => '余额不足',
    'balance_critical' => '即将耗尽',
    'arrears' => '欠费提醒'
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
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'config' ? 'active' : ''; ?>" href="?tab=config">推送配置</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'logs' ? 'active' : ''; ?>" href="?tab=logs">推送日志</a>
            </li>
        </ul>
        
        <?php if ($activeTab === 'config'): ?>
        <div class="block block-rounded mt-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">电费价格设置</h3>
            </div>
            <div class="block-content">
                <form onsubmit="return savePowerConfig(this)" method="post" name="power-config-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label"><b>每度电价格（元）</b></label>
                                <input type="number" step="0.0001" class="form-control" name="price_per_kwh" value="<?php echo $config['price_per_kwh'] ?? 0.61; ?>">
                                <div class="form-text">用于计算电量剩余度数</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label"><b>余额不足阈值（元）</b></label>
                                <input type="number" step="0.01" class="form-control" name="low_balance_threshold" value="<?php echo $config['low_balance_threshold'] ?? 30; ?>">
                                <div class="form-text">低于此金额触发余额不足提醒</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label"><b>余额即将耗尽阈值（元）</b></label>
                                <input type="number" step="0.01" class="form-control" name="critical_balance_threshold" value="<?php echo $config['critical_balance_threshold'] ?? 10; ?>">
                                <div class="form-text">低于此金额触发紧急提醒</div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">推送功能开关</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>每日用电汇总</b></label>
                                <select class="form-select" name="daily_summary_enabled">
                                    <option value="1" <?php echo ($config['daily_summary_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['daily_summary_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>余额不足提醒</b></label>
                                <select class="form-select" name="low_balance_enabled">
                                    <option value="1" <?php echo ($config['low_balance_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['low_balance_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>余额即将耗尽提醒</b></label>
                                <select class="form-select" name="critical_balance_enabled">
                                    <option value="1" <?php echo ($config['critical_balance_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['critical_balance_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>欠费提醒</b></label>
                                <select class="form-select" name="arrears_enabled">
                                    <option value="1" <?php echo ($config['arrears_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo ($config['arrears_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
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
                    
                    <h4 class="mb-3 mt-4">各通知类型推送频率</h4>
                    <div class="alert alert-info mb-3">
                        <strong>频率说明：</strong><br>
                        • <strong>每次都提醒</strong>：每次定时任务执行时，只要满足条件就发送提醒<br>
                        • <strong>每天提醒一次</strong>：每天最多发送一次提醒，避免频繁打扰<br>
                        • <strong>余额下降时提醒</strong>：当余额相比上次提醒下降超过指定额度时才发送提醒
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>每日用电汇总频率</b></label>
                                <select class="form-select" name="daily_summary_frequency">
                                    <option value="daily" <?php echo ($config['daily_summary_frequency'] ?? 'daily') == 'daily' ? 'selected' : ''; ?>>每天提醒一次</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>余额不足提醒频率</b></label>
                                <select class="form-select" name="low_balance_frequency">
                                    <option value="always" <?php echo ($config['low_balance_frequency'] ?? 'daily') == 'always' ? 'selected' : ''; ?>>每次都提醒</option>
                                    <option value="daily" <?php echo ($config['low_balance_frequency'] ?? 'daily') == 'daily' ? 'selected' : ''; ?>>每天提醒一次</option>
                                    <option value="threshold" <?php echo ($config['low_balance_frequency'] ?? 'daily') == 'threshold' ? 'selected' : ''; ?>>余额下降时提醒</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>余额即将耗尽提醒频率</b></label>
                                <select class="form-select" name="critical_balance_frequency">
                                    <option value="always" <?php echo ($config['critical_balance_frequency'] ?? 'always') == 'always' ? 'selected' : ''; ?>>每次都提醒</option>
                                    <option value="daily" <?php echo ($config['critical_balance_frequency'] ?? 'always') == 'daily' ? 'selected' : ''; ?>>每天提醒一次</option>
                                    <option value="threshold" <?php echo ($config['critical_balance_frequency'] ?? 'always') == 'threshold' ? 'selected' : ''; ?>>余额下降时提醒</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>欠费提醒频率</b></label>
                                <select class="form-select" name="arrears_frequency">
                                    <option value="always" <?php echo ($config['arrears_frequency'] ?? 'always') == 'always' ? 'selected' : ''; ?>>每次都提醒</option>
                                    <option value="daily" <?php echo ($config['arrears_frequency'] ?? 'always') == 'daily' ? 'selected' : ''; ?>>每天提醒一次</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>余额下降提醒阈值（元）</b></label>
                                <input type="number" step="0.01" class="form-control" name="threshold_amount" value="<?php echo $config['threshold_amount'] ?? 5; ?>">
                                <div class="form-text">当选择"余额下降时提醒"时，余额相比上次提醒下降超过此金额才发送提醒</div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3 mt-4">用电健康度阈值设置</h4>
                    <div class="alert alert-info mb-3">
                        <strong>健康度说明：</strong><br>
                        • <strong>节能</strong>：平均每小时用电量 ≤ 节能阈值<br>
                        • <strong>正常</strong>：节能阈值 < 平均每小时用电量 ≤ 正常阈值<br>
                        • <strong>偏高</strong>：平均每小时用电量 > 正常阈值
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>节能阈值（度/小时）</b></label>
                                <input type="number" step="0.0001" class="form-control" name="usage_saving_threshold" value="<?php echo $config['usage_saving_threshold'] ?? 0.5; ?>">
                                <div class="form-text">平均每小时用电量低于此值为"节能"状态</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>正常阈值（度/小时）</b></label>
                                <input type="number" step="0.0001" class="form-control" name="usage_normal_threshold" value="<?php echo $config['usage_normal_threshold'] ?? 1.0; ?>">
                                <div class="form-text">平均每小时用电量高于此值为"偏高"状态</div>
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
                                <label class="form-label"><b>电费提醒模板</b></label>
                                <select class="form-select" name="power_fee_template_id">
                                    <option value="">请选择模板</option>
                                    <?php if ($powerFeeTemplates): ?>
                                        <?php foreach ($powerFeeTemplates as $tpl): ?>
                                            <option value="<?php echo $tpl['id']; ?>" <?php echo ($config['power_fee_template_id'] ?? '') == $tpl['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tpl['name']); ?> (<?php echo htmlspecialchars($tpl['template_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($powerFeeTemplates)): ?>
                                <div class="form-text text-warning">暂无可用模板，请先添加场景类型为"电费提醒"的模板</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>电费报告模板</b></label>
                                <select class="form-select" name="power_report_template_id">
                                    <option value="">请选择模板</option>
                                    <?php if ($powerReportTemplates): ?>
                                        <?php foreach ($powerReportTemplates as $tpl): ?>
                                            <option value="<?php echo $tpl['id']; ?>" <?php echo ($config['power_report_template_id'] ?? '') == $tpl['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tpl['name']); ?> (<?php echo htmlspecialchars($tpl['template_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($powerReportTemplates)): ?>
                                <div class="form-text text-warning">暂无可用模板，请先添加场景类型为"电费报告"的模板</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <h4 class="mb-3 mt-4">服务停止时段</h4>
                    <div class="alert alert-warning mb-3">
                        <strong>说明：</strong>在服务停止时段内，<b>电费推送定时任务</b>将暂停执行电费查询与推送，避免在夜间等时段打扰用户。
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>开始时间</b></label>
                                <input type="time" class="form-control" name="quiet_hours_start" value="<?php echo substr($config['quiet_hours_start'] ?? '22:00:00', 0, 5); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label"><b>结束时间</b></label>
                                <input type="time" class="form-control" name="quiet_hours_end" value="<?php echo substr($config['quiet_hours_end'] ?? '08:00:00', 0, 5); ?>">
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
                                <div class="fs-3 text-success fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE status = 1") ?: 0; ?></div>
                                <div class="text-muted">成功发送</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-danger fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE status = 2") ?: 0; ?></div>
                                <div class="text-muted">发送失败</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-info fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE notify_type = 'daily_summary'") ?: 0; ?></div>
                                <div class="text-muted">每日汇总</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="fs-3 text-warning fw-bold"><?php echo $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE notify_type IN ('balance_low', 'balance_critical', 'arrears')") ?: 0; ?></div>
                                <div class="text-muted">余额提醒</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3 mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center py-3">
                                <?php 
                                $successCount = $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE status = 1") ?: 0;
                                $failedCount = $DB->getColumn("SELECT COUNT(*) FROM nteam_push_logs WHERE status = 2") ?: 0;
                                $totalPush = $successCount + $failedCount;
                                $successRate = $totalPush > 0 ? round($successCount / $totalPush * 100, 1) : 0;
                                ?>
                                <div class="fs-4 text-primary fw-bold"><?php echo $successRate; ?>%</div>
                                <div class="text-muted">推送成功率 (共 <?php echo $totalPush; ?> 次推送)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">推送记录列表</h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-alt-danger" onclick="clearPowerPushLogs()"><i class="fa fa-trash me-1"></i>一键清空</button>
                    <span class="text-muted ms-2">共 <?php echo $total; ?> 条记录</span>
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
                                <th style="min-width: 100px;">宿舍</th>
                                <th style="min-width: 80px;">推送类型</th>
                                <th style="min-width: 80px;">通知类型</th>
                                <th style="min-width: 80px;">余额</th>
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
                                    <td><?php echo htmlspecialchars(($log['building'] ?? '') . ' ' . ($log['room'] ?? $log['room_num'] ?? '-')); ?></td>
                                    <td><?php echo $pushTypeMap[$log['push_type']] ?? $log['push_type']; ?></td>
                                    <td><?php echo $notifyTypeMap[$log['notify_type']] ?? $log['notify_type']; ?></td>
                                    <td><?php echo $log['balance'] !== null ? number_format($log['balance'], 2) . '元' : '-'; ?></td>
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
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="showError('<?php echo htmlspecialchars(addslashes($log['error_msg'])); ?>')">
                                            查看
                                        </button>
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
                            <a class="page-link" href="?tab=logs&page=<?php echo $page - 1; ?>">上一页</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?tab=logs&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=logs&page=<?php echo $page + 1; ?>">下一页</a>
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
function showError(msg) {
    var areaWidth = $(window).width() > 540 ? '500px' : '90%';
    layer.alert(msg, {
        title: '错误信息',
        icon: 2,
        area: [areaWidth, 'auto']
    });
}

function savePowerConfig(form) {
    var formData = new FormData(form);
    var data = {
        act: 'save_power_config',
        price_per_kwh: formData.get('price_per_kwh'),
        low_balance_threshold: formData.get('low_balance_threshold'),
        critical_balance_threshold: formData.get('critical_balance_threshold'),
        daily_summary_enabled: formData.get('daily_summary_enabled'),
        low_balance_enabled: formData.get('low_balance_enabled'),
        critical_balance_enabled: formData.get('critical_balance_enabled'),
        arrears_enabled: formData.get('arrears_enabled'),
        email_push_enabled: formData.get('email_push_enabled'),
        wechat_push_enabled: formData.get('wechat_push_enabled'),
        daily_summary_frequency: formData.get('daily_summary_frequency'),
        low_balance_frequency: formData.get('low_balance_frequency'),
        critical_balance_frequency: formData.get('critical_balance_frequency'),
        arrears_frequency: formData.get('arrears_frequency'),
        threshold_amount: formData.get('threshold_amount'),
        usage_saving_threshold: formData.get('usage_saving_threshold'),
        usage_normal_threshold: formData.get('usage_normal_threshold'),
        quiet_hours_start: formData.get('quiet_hours_start'),
        quiet_hours_end: formData.get('quiet_hours_end'),
        power_fee_template_id: formData.get('power_fee_template_id'),
        power_report_template_id: formData.get('power_report_template_id')
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

function clearPowerPushLogs() {
    layer.confirm('确定要清空所有电费推送日志吗？此操作不可恢复！', {
        btn: ['确定清空', '取消'],
        icon: 3
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clear_power_push_logs',
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
