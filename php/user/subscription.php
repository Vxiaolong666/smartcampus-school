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
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <select id="sub-action-filter" class="form-select form-select-sm">
                            <option value="">全部操作</option>
                            <option value="subscribe">订阅</option>
                            <option value="send">发送</option>
                            <option value="admin_add">管理员增加</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="sub-sort" class="form-select form-select-sm">
                            <option value="time_desc">时间倒序</option>
                            <option value="time_asc">时间正序</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="sub-pagesize" class="form-select form-select-sm">
                            <option value="10">每页 10 条</option>
                            <option value="20">每页 20 条</option>
                            <option value="50">每页 50 条</option>
                        </select>
                    </div>
                </div>
                <div id="subscription-logs">
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-spinner fa-spin me-2"></i>加载中...
                    </div>
                </div>
                <div id="subscription-pager" class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2"></div>
            </div>
        </div>

        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bell me-2"></i>消息推送日志
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <select id="push-type-filter" class="form-select form-select-sm">
                            <option value="">全部类型</option>
                            <option value="wechat">微信</option>
                            <option value="email">邮件</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="push-status-filter" class="form-select form-select-sm">
                            <option value="">全部状态</option>
                            <option value="1">已发送</option>
                            <option value="2">发送失败</option>
                            <option value="0">待发送</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="push-sort" class="form-select form-select-sm">
                            <option value="time_desc">时间倒序</option>
                            <option value="time_asc">时间正序</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="push-pagesize" class="form-select form-select-sm">
                            <option value="10">每页 10 条</option>
                            <option value="20">每页 20 条</option>
                            <option value="50">每页 50 条</option>
                        </select>
                    </div>
                </div>
                <div id="push-logs">
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-spinner fa-spin me-2"></i>加载中...
                    </div>
                </div>
                <div id="push-pager" class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2"></div>
            </div>
        </div>
    </div>
</div>

<script>
var subState = { page: 1, pageSize: 10, action: '', sort: 'time_desc' };
var pushState = { page: 1, pageSize: 10, push_type: '', status: '', sort: 'time_desc' };

$(function() {
    $('#sub-action-filter').on('change', function() { subState.action = $(this).val(); subState.page = 1; loadSubscriptionLogs(); });
    $('#sub-sort').on('change', function() { subState.sort = $(this).val(); loadSubscriptionLogs(); });
    $('#sub-pagesize').on('change', function() { subState.pageSize = intVal($(this).val()); subState.page = 1; loadSubscriptionLogs(); });

    $('#push-type-filter').on('change', function() { pushState.push_type = $(this).val(); pushState.page = 1; loadPushLogs(); });
    $('#push-status-filter').on('change', function() { pushState.status = $(this).val(); pushState.page = 1; loadPushLogs(); });
    $('#push-sort').on('change', function() { pushState.sort = $(this).val(); loadPushLogs(); });
    $('#push-pagesize').on('change', function() { pushState.pageSize = intVal($(this).val()); pushState.page = 1; loadPushLogs(); });

    loadSubscriptionLogs();
    loadPushLogs();
});

function loadSubscriptionLogs() {
    var qs = $.param({
        act: 'get_user_subscription_logs',
        page: subState.page,
        pageSize: subState.pageSize,
        action: subState.action,
        sort: subState.sort
    });
    $.get('../api.php?' + qs, function(res) {
        if (res.code == 0 && res.data && res.data.list && res.data.list.length > 0) {
            var html = '<div class="table-responsive"><table class="table table-bordered table-striped table-vcenter"><thead><tr><th>模板名称</th><th class="text-center">操作</th><th class="text-center d-none d-sm-table-cell">时间</th></tr></thead><tbody>';
            res.data.list.forEach(function(log) {
                html += '<tr>';
                html += '<td>' + escapeHtml(log.template_name || '未知模板') + '<div class="d-sm-none mt-1 small text-muted">' + escapeHtml(log.created_at) + '</div></td>';
                var actionBadge = '';
                if (log.action == 'subscribe') {
                    actionBadge = '<span class="badge bg-success">订阅</span>';
                } else if (log.action == 'send') {
                    actionBadge = '<span class="badge bg-info">发送</span>';
                } else if (log.action == 'admin_add') {
                    actionBadge = '<span class="badge bg-primary">管理员增加</span>';
                } else {
                    actionBadge = '<span class="badge bg-secondary">' + escapeHtml(log.action) + '</span>';
                }
                html += '<td class="text-center">' + actionBadge + '</td>';
                html += '<td class="text-center d-none d-sm-table-cell">' + escapeHtml(log.created_at) + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#subscription-logs').html(html);
            renderPager('#subscription-pager', res.data);
        } else {
            $('#subscription-logs').html('<div class="text-center text-muted py-4">暂无订阅记录</div>');
            $('#subscription-pager').html('');
        }
    }, 'json').fail(function() {
        $('#subscription-logs').html('<div class="text-center text-muted py-4">加载失败，请刷新重试</div>');
        $('#subscription-pager').html('');
    });
}

function loadPushLogs() {
    var qs = $.param({
        act: 'get_user_push_logs',
        page: pushState.page,
        pageSize: pushState.pageSize,
        push_type: pushState.push_type,
        status: pushState.status,
        sort: pushState.sort
    });
    $.get('../api.php?' + qs, function(res) {
        if (res.code == 0 && res.data && res.data.list && res.data.list.length > 0) {
            var cards = '';
            var tableRows = '';
            res.data.list.forEach(function(log) {
                var balanceBadge = (log.balance !== null && log.balance !== '') ? ' <span class="badge bg-secondary">余额 ' + escapeHtml(String(log.balance)) + '</span>' : '';

                var typeBadge = log.push_type == 'email'
                    ? '<span class="badge bg-info">邮件</span>'
                    : '<span class="badge bg-success">微信</span>';

                var statusBadge = '';
                if (log.status == 1) {
                    statusBadge = '<span class="badge bg-success">已发送</span>';
                } else if (log.status == 2) {
                    statusBadge = '<span class="badge bg-danger">发送失败</span>';
                } else {
                    statusBadge = '<span class="badge bg-warning">待发送</span>';
                }

                var contentHtml = log.content
                    ? '<div class="small text-muted mt-1" style="white-space:pre-wrap;word-break:break-word;">' + escapeHtml(log.content) + '</div>'
                    : '';
                var errorHtml = log.error_msg
                    ? '<div class="small text-danger mt-1" style="word-break:break-word;">失败原因：' + escapeHtml(log.error_msg) + '</div>'
                    : '';
                var timeText = escapeHtml(log.sent_at || log.created_at || '-');

                // 移动端卡片（< md）
                cards += '<div class="border rounded p-2 mb-2">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<div class="fw-semibold" style="word-break:break-word;">' + escapeHtml(log.title || '推送消息') + balanceBadge + '</div>' +
                    '<div class="flex-shrink-0 text-end">' + statusBadge + '</div>' +
                    '</div>' +
                    contentHtml + errorHtml +
                    '<div class="d-flex justify-content-between align-items-center mt-2 small text-muted gap-2">' +
                    '<span class="text-nowrap">' + typeBadge + ' <span class="ms-1">' + escapeHtml(log.notify_type || '') + '</span></span>' +
                    '<span class="text-nowrap">' + timeText + '</span>' +
                    '</div>' +
                    '</div>';

                // 桌面端表格行（>= md）
                tableRows += '<tr>' +
                    '<td style="word-break:break-word;"><strong>' + escapeHtml(log.title || '推送消息') + '</strong>' + balanceBadge + contentHtml + errorHtml + '</td>' +
                    '<td class="text-center">' + typeBadge + ' <div class="small text-muted mt-1">' + escapeHtml(log.notify_type || '') + '</div></td>' +
                    '<td class="text-center">' + statusBadge + '</td>' +
                    '<td class="text-center">' + timeText + '</td>' +
                    '</tr>';
            });

            var html =
                '<div class="d-md-none">' + cards + '</div>' +
                '<div class="d-none d-md-block table-responsive"><table class="table table-bordered table-striped table-vcenter"><thead><tr>' +
                '<th>标题</th><th class="text-center">类型</th><th class="text-center">状态</th><th class="text-center">时间</th></tr></thead><tbody>' +
                tableRows +
                '</tbody></table></div>';
            $('#push-logs').html(html);
            renderPager('#push-pager', res.data);
        } else {
            $('#push-logs').html('<div class="text-center text-muted py-4">暂无消息推送记录</div>');
            $('#push-pager').html('');
        }
    }, 'json').fail(function() {
        $('#push-logs').html('<div class="text-center text-muted py-4">加载失败，请刷新重试</div>');
        $('#push-pager').html('');
    });
}

function renderPager(container, data) {
    var total = intVal(data.total);
    var page = intVal(data.page);
    var pageSize = intVal(data.pageSize);
    var totalPages = Math.ceil(total / pageSize) || 1;
    if (page > totalPages) {
        page = totalPages;
    }

    var info = '共 ' + total + ' 条，第 ' + page + ' / ' + totalPages + ' 页';
    var prevDisabled = page <= 1 ? 'disabled' : '';
    var nextDisabled = page >= totalPages ? 'disabled' : '';
    var html = '<div class="text-muted small">' + info + '</div>' +
        '<div class="btn-group btn-group-sm">' +
        '<button type="button" class="btn btn-outline-secondary" data-pager="prev" ' + prevDisabled + '><i class="fa fa-chevron-left"></i> 上一页</button>' +
        '<button type="button" class="btn btn-outline-secondary" data-pager="next" ' + nextDisabled + '>下一页 <i class="fa fa-chevron-right"></i></button>' +
        '</div>';
    $(container).html(html);

    if (page > 1) {
        $(container + ' [data-pager="prev"]').on('click', function() { goPage(container, page - 1); });
    }
    if (page < totalPages) {
        $(container + ' [data-pager="next"]').on('click', function() { goPage(container, page + 1); });
    }
}

function goPage(container, target) {
    if (container === '#subscription-pager') {
        subState.page = target;
        loadSubscriptionLogs();
    } else if (container === '#push-pager') {
        pushState.page = target;
        loadPushLogs();
    }
}

function intVal(v) {
    var n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function(c) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
    });
}
</script>

<?php include('./foot.php'); ?>
