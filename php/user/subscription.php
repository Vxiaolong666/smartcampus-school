<?php
$title = '订阅消息管理';
$requireLogin = true;
include('./head.php');

$userId = $_SESSION['userId'];

$templates = $DB->getAll("SELECT id, template_id, name, description, scene_type FROM nteam_wx_templates WHERE status = 1 ORDER BY id DESC");

$subscriptions = $DB->getAll("SELECT template_id, subscribe_count, subscribe_time FROM nteam_subscriptions WHERE user_id = '$userId' AND status = 1");

$subscriptionMap = [];
foreach ($subscriptions as $sub) {
    $subscriptionMap[$sub['template_id']] = $sub;
}

$sceneTypeNames = [
    'schedule_reminder' => '课程提醒',
    'grade_notify' => '成绩通知',
    'power_fee' => '电费提醒',
    'power_report' => '电费报告',
    'subscription_alert' => '额度提醒'
];
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fab fa-weixin me-2"></i>订阅消息管理
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>说明：</strong>订阅消息需要您在小程序中主动授权，每次授权可增加一次订阅次数。当订阅次数用完后，将无法收到对应类型的推送通知。
                    </div>
                </div>
                
                <?php if (empty($templates)): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    暂无可用的订阅消息模板
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>模板名称</th>
                                <th class="d-none d-md-table-cell">场景类型</th>
                                <th class="d-none d-lg-table-cell">描述</th>
                                <th class="text-center">剩余次数</th>
                                <th class="text-center d-none d-sm-table-cell">最后订阅时间</th>
                                <th class="text-center">状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $tpl): 
                                $sub = isset($subscriptionMap[$tpl['template_id']]) ? $subscriptionMap[$tpl['template_id']] : null;
                                $count = $sub ? intval($sub['subscribe_count']) : 0;
                                $sceneName = isset($sceneTypeNames[$tpl['scene_type']]) ? $sceneTypeNames[$tpl['scene_type']] : '未分类';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($tpl['name']); ?></strong>
                                    <div class="d-md-none mt-1">
                                        <span class="badge bg-info"><?php echo $sceneName; ?></span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge bg-info"><?php echo $sceneName; ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($tpl['description'] ?? ''); ?></td>
                                <td class="text-center">
                                    <?php if ($count > 0): ?>
                                        <span class="badge bg-success"><?php echo $count; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center d-none d-sm-table-cell">
                                    <?php echo $sub ? $sub['subscribe_time'] : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($count > 0): ?>
                                        <span class="badge bg-success">可接收</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">需订阅</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5 class="mb-3">如何增加订阅次数？</h5>
                    <ol class="mb-0">
                        <li>打开微信小程序</li>
                        <li>进入"推送测试"或相关功能页面</li>
                        <li>点击"请求订阅消息授权"按钮</li>
                        <li>在弹出的授权窗口中点击"允许"</li>
                        <li>每次授权可增加一次订阅次数</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history me-2"></i>订阅记录
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div id="subscription-logs">
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-spinner fa-spin me-2"></i>加载中...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    loadSubscriptionLogs();
});

function loadSubscriptionLogs() {
    $.get('../api.php?act=get_user_subscription_logs', function(res) {
        if (res.code == 0 && res.data && res.data.length > 0) {
            var html = '<div class="table-responsive"><table class="table table-bordered table-striped table-vcenter"><thead><tr><th>模板名称</th><th class="text-center">操作</th><th class="text-center d-none d-sm-table-cell">时间</th></tr></thead><tbody>';
            res.data.forEach(function(log) {
                html += '<tr>';
                html += '<td>' + (log.template_name || '未知模板') + '<div class="d-sm-none mt-1 small text-muted">' + log.created_at + '</div></td>';
                var actionBadge = '';
                if (log.action == 'subscribe') {
                    actionBadge = '<span class="badge bg-success">订阅</span>';
                } else if (log.action == 'send') {
                    actionBadge = '<span class="badge bg-info">发送</span>';
                } else if (log.action == 'admin_add') {
                    actionBadge = '<span class="badge bg-primary">管理员增加</span>';
                } else {
                    actionBadge = '<span class="badge bg-secondary">' + log.action + '</span>';
                }
                html += '<td class="text-center">' + actionBadge + '</td>';
                html += '<td class="text-center d-none d-sm-table-cell">' + log.created_at + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#subscription-logs').html(html);
        } else {
            $('#subscription-logs').html('<div class="text-center text-muted py-4">暂无订阅记录</div>');
        }
    }, 'json').fail(function() {
        $('#subscription-logs').html('<div class="text-center text-muted py-4">加载失败，请刷新重试</div>');
    });
}
</script>

<?php include('./foot.php'); ?>
