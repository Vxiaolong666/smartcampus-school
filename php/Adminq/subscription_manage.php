<?php
$mod = 'admin';
$title='用户订阅管理';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$templates = $DB->getAll("SELECT id, template_id, name, scene_type FROM nteam_wx_templates WHERE status = 1 ORDER BY id DESC");

$searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$searchTemplate = isset($_GET['template']) ? trim($_GET['template']) : '';

$whereClause = "1=1";
if (!empty($searchKeyword)) {
    $whereClause .= " AND (u.username LIKE '%".addslashes($searchKeyword)."%' OR u.nickname LIKE '%".addslashes($searchKeyword)."%' OR s.openid LIKE '%".addslashes($searchKeyword)."%')";
}
if (!empty($searchTemplate)) {
    $whereClause .= " AND s.template_id = '".addslashes($searchTemplate)."'";
}

$subscriptions = $DB->getAll("SELECT s.*, u.username, u.nickname, t.name as template_name 
    FROM nteam_subscriptions s 
    LEFT JOIN nteam_users u ON s.user_id = u.id 
    LEFT JOIN nteam_wx_templates t ON s.template_id = t.template_id 
    WHERE {$whereClause}
    ORDER BY s.id DESC 
    LIMIT 200");

$sceneTypeNames = [
    'schedule_reminder' => '课程提醒',
    'grade_notify' => '成绩通知',
    'power_fee' => '电费提醒',
    'power_report' => '电费报告',
    'subscription_alert' => '额度提醒'
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
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="wx_config.php">小程序配置</a>
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
        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">搜索筛选</h3>
            </div>
            <div class="block-content block-content-full">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="keyword" placeholder="用户名/昵称/OpenID" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="template">
                            <option value="">全部模板</option>
                            <?php foreach($templates as $tpl): ?>
                            <option value="<?php echo $tpl['template_id']; ?>" <?php echo $searchTemplate == $tpl['template_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tpl['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa fa-search me-1"></i>搜索
                        </button>
                        <a href="?" class="btn btn-alt-secondary">重置</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">用户订阅列表</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 50px;">ID</th>
                                <th style="min-width: 120px;">用户信息</th>
                                <th style="min-width: 120px;">模板名称</th>
                                <th class="text-center" style="min-width: 80px;">剩余次数</th>
                                <th class="text-center" style="min-width: 140px;">最后订阅时间</th>
                                <th class="text-center" style="min-width: 60px;">状态</th>
                                <th class="text-center" style="min-width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($subscriptions): ?>
                                <?php foreach($subscriptions as $sub): ?>
                                <tr>
                                    <td class="text-center"><?php echo $sub['id']; ?></td>
                                    <td>
                                        <div><b><?php echo htmlspecialchars($sub['username'] ?: '未知'); ?></b></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($sub['nickname'] ?: ''); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($sub['template_name'] ?: $sub['template_id']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($sub['subscribe_count'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $sub['subscribe_count']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $sub['subscribe_time'] ?: '-'; ?></td>
                                    <td class="text-center">
                                        <?php if($sub['status'] == 1): ?>
                                            <span class="badge bg-success">有效</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">无效</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-alt-primary me-1" onclick="addSubscription(<?php echo $sub['id']; ?>, '<?php echo htmlspecialchars($sub['username'] ?: '用户'); ?>', <?php echo $sub['subscribe_count']; ?>)">
                                            <i class="fa fa-plus"></i> 增加
                                        </button>
                                        <button class="btn btn-sm btn-alt-danger" onclick="resetSubscription(<?php echo $sub['id']; ?>, '<?php echo htmlspecialchars($sub['username'] ?: '用户'); ?>')">
                                            <i class="fa fa-refresh"></i> 重置
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">暂无订阅数据</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">订阅操作日志</h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 50px;">ID</th>
                                <th style="min-width: 100px;">用户</th>
                                <th style="min-width: 120px;">模板</th>
                                <th class="text-center" style="min-width: 80px;">操作类型</th>
                                <th class="text-center" style="min-width: 80px;">次数变化</th>
                                <th class="text-center" style="min-width: 140px;">时间</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin me-2"></i>加载中...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">增加订阅次数</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <input type="hidden" name="id" id="add_id" value="">
                    <div class="mb-3">
                        <label class="form-label">用户</label>
                        <input type="text" class="form-control" id="add_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">当前剩余次数</label>
                        <input type="text" class="form-control" id="add_current_count" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><b>增加次数</b></label>
                        <input type="number" class="form-control" name="count" id="add_count" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">备注</label>
                        <input type="text" class="form-control" name="remark" id="add_remark" placeholder="可选">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitAddSubscription()">确认增加</button>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'?>
<script>
var addModal = new bootstrap.Modal(document.getElementById('addModal'));

$(function() {
    loadSubscriptionLogs();
});

function loadSubscriptionLogs() {
    $.get('Ajax.php?act=get_subscription_logs', function(res) {
        if (res.code == 0 && res.data && res.data.length > 0) {
            var html = '';
            res.data.forEach(function(log) {
                var actionBadge = '';
                if (log.action == 'subscribe') {
                    actionBadge = '<span class="badge bg-success">' + log.action_name + '</span>';
                } else if (log.action == 'admin_add') {
                    actionBadge = '<span class="badge bg-primary">' + log.action_name + '</span>';
                } else if (log.action == 'send') {
                    actionBadge = '<span class="badge bg-info">' + log.action_name + '</span>';
                } else {
                    actionBadge = '<span class="badge bg-secondary">' + log.action_name + '</span>';
                }
                
                var countChange = log.count_change > 0 ? '+' + log.count_change : log.count_change;
                var countClass = log.count_change > 0 ? 'text-success' : 'text-danger';
                
                html += '<tr>';
                html += '<td class="text-center">' + log.id + '</td>';
                html += '<td><b>' + log.username + '</b>' + (log.nickname ? ' <small class="text-muted">' + log.nickname + '</small>' : '') + '</td>';
                html += '<td>' + log.template_name + '</td>';
                html += '<td class="text-center">' + actionBadge + '</td>';
                html += '<td class="text-center ' + countClass + '"><b>' + countChange + '</b></td>';
                html += '<td class="text-center">' + log.created_at + '</td>';
                html += '</tr>';
            });
            $('#logs-tbody').html(html);
        } else {
            $('#logs-tbody').html('<tr><td colspan="6" class="text-center text-muted py-4">暂无日志记录</td></tr>');
        }
    }, 'json').fail(function() {
        $('#logs-tbody').html('<tr><td colspan="6" class="text-center text-muted py-4">暂无日志记录</td></tr>');
    });
}

function addSubscription(id, username, currentCount) {
    document.getElementById('add_id').value = id;
    document.getElementById('add_username').value = username;
    document.getElementById('add_current_count').value = currentCount;
    document.getElementById('add_count').value = 1;
    document.getElementById('add_remark').value = '';
    addModal.show();
}

function submitAddSubscription() {
    var id = document.getElementById('add_id').value;
    var count = document.getElementById('add_count').value;
    var remark = document.getElementById('add_remark').value;
    
    if (!count || count < 1) {
        layer.alert('请输入有效的次数', {icon: 2});
        return;
    }
    
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=add_subscription_count',
        data: {id: id, count: count, remark: remark},
        dataType: 'json',
        success: function(data) {
            layer.close(ii);
            if(data.code == 0){
                addModal.hide();
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
}

function resetSubscription(id, username) {
    layer.confirm('确定要重置用户 "' + username + '" 的订阅次数为0吗？', {
        btn: ['确定', '取消']
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=reset_subscription_count',
            data: {id: id},
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
