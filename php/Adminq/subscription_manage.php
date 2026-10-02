<?php
/**
 * 订阅与消息模板（合并页面）
 * ------------------------------------------------------------------
 * 两个 Tab（与 power_config.php / home_config.php 的写法一致，通过 ?tab= 切换）：
 *   - 消息模板（?tab=templates，默认）     ：微信订阅消息模板的增删改与使用说明；
 *   - 用户订阅（?tab=subscriptions）       ：搜索筛选、用户订阅列表（增加 / 重置次数）、订阅操作日志。
 *
 * 说明：原先「用户订阅管理」(subscription_manage.php) 与「消息模板管理」(wx_templates.php)
 *       是两个独立页面，现已合并到本页；原 wx_templates.php 已移除，
 *       其它页面（wx_config.php、power_config.php、academic_push_config.php）的入口
 *       统一指向 subscription_manage.php?tab=templates。
 */
$mod = 'admin';
$title='订阅与消息模板';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

// ===== 当前 Tab：消息模板（默认）/ 用户订阅 =====
$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'subscriptions') ? 'subscriptions' : 'templates';

// ===== 消息模板：管理列表（含禁用项，供「消息模板」Tab 的表格使用）=====
$templates = $DB->getAll("SELECT * FROM nteam_wx_templates ORDER BY id DESC");

// ===== 用户订阅：筛选下拉可选模板（仅启用项，与原订阅页一致）=====
$filterTemplates = $DB->getAll("SELECT id, template_id, name, scene_type FROM nteam_wx_templates WHERE status = 1 ORDER BY id DESC");

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
<style>
    /* 表格表头不换行（各 Tab 的表格共用） */
    .table-responsive table th {
        white-space: nowrap;
    }
</style>
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
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'templates' ? 'active' : ''; ?>" href="?tab=templates">消息模板</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'subscriptions' ? 'active' : ''; ?>" href="?tab=subscriptions">用户订阅</a>
            </li>
        </ul>

        <?php if ($activeTab === 'templates'): ?>
        <!-- ============ 消息模板 ============ -->
        <div class="block block-rounded mt-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">消息模板列表</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="showAddModal()">
                        <i class="fa fa-plus opacity-50 me-1"></i>添加模板
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 50px;">ID</th>
                                <th style="min-width: 120px;">模板名称</th>
                                <th style="min-width: 150px;">模板ID</th>
                                <th style="min-width: 100px;">场景类型</th>
                                <th style="min-width: 150px;">描述</th>
                                <th class="text-center" style="min-width: 60px;">状态</th>
                                <th class="text-center" style="min-width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($templates): ?>
                                <?php foreach($templates as $tpl): ?>
                                <tr>
                                    <td class="text-center"><?php echo $tpl['id']; ?></td>
                                    <td><?php echo htmlspecialchars($tpl['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($tpl['template_id']); ?></code></td>
                                    <td>
                                        <?php
                                        $sceneType = $tpl['scene_type'] ?? '';
                                        echo isset($sceneTypeNames[$sceneType])
                                            ? '<span class="badge bg-info">'.$sceneTypeNames[$sceneType].'</span>'
                                            : '<span class="badge bg-secondary">未分类</span>';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($tpl['description'] ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php if($tpl['status'] == 1): ?>
                                            <span class="badge bg-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-alt-primary" onclick="editTemplate(<?php echo $tpl['id']; ?>)">
                                            <i class="fa fa-edit"></i> 编辑
                                        </button>
                                        <button class="btn btn-sm btn-alt-danger" onclick="deleteTemplate(<?php echo $tpl['id']; ?>)">
                                            <i class="fa fa-trash"></i> 删除
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">暂无模板数据</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">使用说明</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5 class="alert-heading">如何获取模板ID？</h5>
                    <hr>
                    <ol class="mb-0">
                        <li>登录 <a href="https://mp.weixin.qq.com" target="_blank">微信公众平台</a></li>
                        <li>进入「订阅消息」-「公共模板库」或「我的模板」</li>
                        <li>选择合适的模板，复制模板ID</li>
                        <li>在此页面添加模板配置</li>
                    </ol>
                </div>
                <div class="alert alert-warning">
                    <h5 class="alert-heading">字段配置说明</h5>
                    <hr>
                    <p class="mb-2">字段配置为JSON数组格式，每个字段包含：</p>
                    <ul class="mb-0">
                        <li><code>field</code>: 字段ID（如 thing8、time15）</li>
                        <li><code>name</code>: 字段名称（如 课程名称、开始时间）</li>
                        <li><code>type</code>: 字段类型（thing、time、number等）</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ============ 用户订阅 ============ -->
        <div class="block block-rounded mt-4 mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">搜索筛选</h3>
            </div>
            <div class="block-content block-content-full">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="tab" value="subscriptions">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="keyword" placeholder="用户名/昵称/OpenID" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="template">
                            <option value="">全部模板</option>
                            <?php foreach($filterTemplates as $tpl): ?>
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
                        <a href="?tab=subscriptions" class="btn btn-alt-secondary">重置</a>
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
        <?php endif; ?>
    </div>
</div>

<!-- ============ 用户订阅：增加订阅次数 ============ -->
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

<!-- ============ 消息模板：添加 / 编辑 ============ -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">添加模板</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="id" id="tpl_id" value="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>模板ID *</b></label>
                                <input type="text" class="form-control" name="template_id" id="tpl_template_id" required placeholder="请输入模板ID">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>模板名称 *</b></label>
                                <input type="text" class="form-control" name="name" id="tpl_name" required placeholder="请输入模板名称">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><b>场景类型 *</b></label>
                                <select class="form-select" name="scene_type" id="tpl_scene_type" required>
                                    <option value="">请选择场景类型</option>
                                    <option value="schedule_reminder">课程提醒</option>
                                    <option value="grade_notify">成绩通知</option>
                                    <option value="power_fee">电费提醒</option>
                                    <option value="power_report">电费报告</option>
                                    <option value="subscription_alert">额度提醒</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><b>模板描述</b></label>
                        <input type="text" class="form-control" name="description" id="tpl_description" placeholder="请输入模板描述（可选）">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><b>字段配置 (JSON格式)</b></label>
                        <textarea class="form-control" name="fields" id="tpl_fields" rows="4" placeholder='[{"field":"thing8","name":"课程名称","type":"thing"}]'></textarea>
                        <div class="form-text">JSON数组格式，例如：[{"field":"thing8","name":"课程名称","type":"thing"}]</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><b>默认消息数据 (JSON格式)</b></label>
                        <textarea class="form-control" name="default_data" id="tpl_default_data" rows="4" placeholder='{"thing8":{"value":"测试内容"}}'></textarea>
                        <div class="form-text">JSON对象格式，例如：{"thing8":{"value":"测试内容"}}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><b>状态</b></label>
                        <select class="form-select" name="status" id="tpl_status">
                            <option value="1">启用</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">保存</button>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'?>
<script>
// 当前 Tab（用于按需初始化：订阅日志只在「用户订阅」Tab 拉取）
var ACTIVE_TAB = <?php echo json_encode($activeTab); ?>;

/* ==================== 用户订阅 ==================== */
var addModal = new bootstrap.Modal(document.getElementById('addModal'));

$(function() {
    if (ACTIVE_TAB === 'subscriptions') {
        loadSubscriptionLogs();
    }
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

/* ==================== 消息模板 ==================== */
var templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
var templateData = <?php echo json_encode($templates ?: []); ?>;

function showAddModal() {
    document.getElementById('modalTitle').innerText = '添加模板';
    document.getElementById('templateForm').reset();
    document.getElementById('tpl_id').value = '';
    templateModal.show();
}

function editTemplate(id) {
    document.getElementById('modalTitle').innerText = '编辑模板';
    var tpl = templateData.find(t => t.id == id);
    if (tpl) {
        document.getElementById('tpl_id').value = tpl.id;
        document.getElementById('tpl_template_id').value = tpl.template_id;
        document.getElementById('tpl_name').value = tpl.name;
        document.getElementById('tpl_scene_type').value = tpl.scene_type || '';
        document.getElementById('tpl_description').value = tpl.description || '';
        document.getElementById('tpl_fields').value = tpl.fields || '';
        document.getElementById('tpl_default_data').value = tpl.default_data || '';
        document.getElementById('tpl_status').value = tpl.status;
        templateModal.show();
    }
}

function saveTemplate() {
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    var formData = $('#templateForm').serialize();
    
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=save_wx_template',
        data: formData,
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
            layer.msg('服务器错误');
        }
    });
}

function deleteTemplate(id) {
    layer.confirm('确定要删除该模板吗？', {icon: 3, title: '提示'}, function(index){
        layer.close(index);
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=delete_wx_template',
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
                layer.msg('服务器错误');
            }
        });
    });
}
</script>
</body>
</html>
