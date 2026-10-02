<?php
$mod = 'admin';
$title='功能设置';
include('../Common/Core_brain.php');
/** @var array|null $adminData */
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$features = $DB->query("SELECT * FROM nteam_feature_settings ORDER BY id ASC")->fetchAll();
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
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">功能开关与公告设置</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openAddModal()">
                        <i class="fa fa-plus me-1"></i>添加功能标识
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                        .status-toggle {
                            cursor: pointer;
                            transition: opacity .15s ease;
                        }
                        .status-toggle:hover {
                            opacity: .75;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 60px;">ID</th>
                                <th style="min-width: 120px;">功能名称</th>
                                <th style="min-width: 100px;">功能标识</th>
                                <th class="text-center" style="min-width: 80px;">状态</th>
                                <th style="min-width: 200px;">公告内容</th>
                                <th style="min-width: 80px;">公告类型</th>
                                <th class="text-center" style="min-width: 140px;">更新时间</th>
                                <th class="text-center" style="min-width: 80px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($features as $feature): ?>
                            <tr>
                                <td class="text-center"><?php echo $feature['id']; ?></td>
                                <td><b><?php echo htmlspecialchars($feature['feature_name']); ?></b></td>
                                <td><code><?php echo htmlspecialchars($feature['feature_key']); ?></code></td>
                                <td class="text-center">
                                    <?php if($feature['is_enabled'] == 1): ?>
                                    <span id="status-badge-<?php echo $feature['id']; ?>" class="badge bg-success status-toggle" onclick="toggleFeature(<?php echo $feature['id']; ?>, 1)" title="点击禁用">已启用</span>
                                    <?php else: ?>
                                    <span id="status-badge-<?php echo $feature['id']; ?>" class="badge bg-danger status-toggle" onclick="toggleFeature(<?php echo $feature['id']; ?>, 0)" title="点击启用">已禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($feature['announcement']): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars(mb_substr($feature['announcement'], 0, 50)); ?><?php echo mb_strlen($feature['announcement']) > 50 ? '...' : ''; ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $typeLabels = [
                                        'info' => '<span class="badge bg-info">信息</span>',
                                        'success' => '<span class="badge bg-success">成功</span>',
                                        'warning' => '<span class="badge bg-warning">警告</span>',
                                        'danger' => '<span class="badge bg-danger">危险</span>'
                                    ];
                                    echo $typeLabels[$feature['announcement_type']] ?? $typeLabels['info'];
                                    ?>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted fs-sm"><?php echo $feature['updated_at']; ?></span>
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-alt-primary" onclick="editFeature(<?php echo $feature['id']; ?>)">
                                        <i class="fa fa-edit me-1"></i>编辑
                                    </button>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="deleteFeature(<?php echo $feature['id']; ?>)">
                                        <i class="fa fa-trash me-1"></i>删除
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <!-- 移动端适配参考 alist.php：保持对话框形态（非全屏），居中显示 + 内容超出时可滚动 -->
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">编辑功能设置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="id" id="feature_id">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><b>功能名称</b></label>
                            <input type="text" class="form-control" id="feature_name" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><b>功能标识</b></label>
                            <input type="text" class="form-control" id="feature_key" readonly>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><b>功能状态</b></label>
                        <div class="space-x-2">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="is_enabled" id="enabled_1" value="1"> 启用
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="is_enabled" id="enabled_0" value="0"> 禁用
                                </label>
                            </div>
                        </div>
                        <div class="form-text">禁用后用户将无法访问该功能</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><b>公告内容</b></label>
                        <textarea class="form-control" name="announcement" id="announcement" rows="3" placeholder="留空则不显示公告"></textarea>
                        <div class="form-text">设置后将在功能页面顶部显示公告信息</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><b>公告类型</b></label>
                        <select class="form-select" name="announcement_type" id="announcement_type">
                            <option value="info">信息（蓝色）</option>
                            <option value="success">成功（绿色）</option>
                            <option value="warning">警告（黄色）</option>
                            <option value="danger">危险（红色）</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-alt-primary" onclick="saveFeature()">
                    <i class="fa fa-check me-1"></i>保存设置
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 手动添加功能标识（移动端适配参考 alist.php：非全屏，居中显示 + 内容超出时可滚动） ===== -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">添加功能标识</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="add_name"><b>功能名称</b> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" maxlength="100" placeholder="如：网页端 AI 对话" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="add_key"><b>功能标识</b> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_key" maxlength="50" placeholder="如：web_ai_chat" autocomplete="off">
                            <div class="form-text">仅字母、数字、下划线、短横线，且不可与已有标识重复；客户端按该标识读取开关。</div>
                        </div>
                    </div>
                    <div class="mt-4 mb-4">
                        <label class="form-label"><b>初始状态</b></label>
                        <div class="space-x-2">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="add_is_enabled" id="add_enabled_1" value="1" checked> 启用
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="add_is_enabled" id="add_enabled_0" value="0"> 禁用
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="add_announcement"><b>公告内容</b></label>
                        <textarea class="form-control" id="add_announcement" rows="3" placeholder="留空则不显示公告；功能被禁用时该内容会作为提示文案展示给用户"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="add_announcement_type"><b>公告类型</b></label>
                        <select class="form-select" id="add_announcement_type">
                            <option value="info">信息（蓝色）</option>
                            <option value="success">成功（绿色）</option>
                            <option value="warning">警告（黄色）</option>
                            <option value="danger">危险（红色）</option>
                        </select>
                    </div>
                    <div class="alert alert-info mb-0 fs-sm">
                        <i class="fa fa-info-circle me-1"></i>
                        功能标识需与前端代码约定一致才能在页面上生效（例如 <code>web_ai_chat</code> 对应网页端 / App 端「AI 对话」）。
                        添加后可在左侧「操作」列点击「编辑」补充公告或调整状态。
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-alt-primary" onclick="saveNewFeature()">
                    <i class="fa fa-plus me-1"></i>确定添加
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'?>
<script>
var featureData = <?php echo json_encode($features); ?>;

function toggleFeature(id, current) {
    var badge = document.getElementById('status-badge-' + id);
    if (!badge) return;
    var newStatus = current == 1 ? 0 : 1;

    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=toggle_feature_status',
        data: {id: id, is_enabled: newStatus},
        dataType: 'json',
        success: function(data) {
            layer.close(ii);
            if (data.code == 0) {
                if (newStatus == 1) {
                    badge.className = 'badge bg-success status-toggle';
                    badge.title = '点击禁用';
                    badge.textContent = '已启用';
                } else {
                    badge.className = 'badge bg-danger status-toggle';
                    badge.title = '点击启用';
                    badge.textContent = '已禁用';
                }
                badge.setAttribute('onclick', 'toggleFeature(' + id + ', ' + newStatus + ')');
                layer.msg(data.msg, {icon: 1});
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

function editFeature(id) {
    var feature = featureData.find(function(f) { return f.id == id; });
    if (!feature) return;
    
    document.getElementById('feature_id').value = feature.id;
    document.getElementById('feature_name').value = feature.feature_name;
    document.getElementById('feature_key').value = feature.feature_key;
    document.getElementById('announcement').value = feature.announcement || '';
    document.getElementById('announcement_type').value = feature.announcement_type || 'info';
    
    if (feature.is_enabled == 1) {
        document.getElementById('enabled_1').checked = true;
    } else {
        document.getElementById('enabled_0').checked = true;
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function saveFeature() {
    var id = document.getElementById('feature_id').value;
    var is_enabled = document.querySelector('input[name="is_enabled"]:checked').value;
    var announcement = document.getElementById('announcement').value;
    var announcement_type = document.getElementById('announcement_type').value;
    
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=update_feature_setting',
        data: {
            id: id,
            is_enabled: is_enabled,
            announcement: announcement,
            announcement_type: announcement_type
        },
        dataType: 'json',
        success: function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.alert(data.msg, {
                    icon: 1,
                    closeBtn: false
                }, function(){
                    window.location.reload();
                });
            } else {
                layer.alert(data.msg, {icon: 2});
            }
        },
        error: function(){
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
}

// ===== 删除功能标识 =====
function deleteFeature(id) {
    var feature = (featureData || []).find(function (f) { return f.id == id; });
    if (!feature) {
        layer.msg('未找到该功能设置', {icon: 0});
        return false;
    }

    var tip = '确认删除功能标识「<b>' + feature.feature_name + '</b>」(<code>' + feature.feature_key + '</code>)？'
        + '<br><span class="text-muted fs-sm">删除后列表不再显示该标识；客户端读取不到该标识时会按「未限制」处理，'
        + '对应功能将恢复可用。如只是暂时停用，建议改用「禁用」。</span>';

    layer.confirm(tip, {icon: 3, title: '删除功能标识'}, function (index) {
        layer.close(index);

        var ii = layer.load(2, {shade: [0.1, '#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=delete_feature_setting',
            data: {id: id},
            dataType: 'json',
            success: function (data) {
                layer.close(ii);
                if (data.code == 0) {
                    layer.alert(data.msg, {
                        icon: 1,
                        closeBtn: false
                    }, function () {
                        window.location.reload();
                    });
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function () {
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
    });
    return false;
}

// ===== 手动添加功能标识 =====
function openAddModal() {
    document.getElementById('add_name').value = '';
    document.getElementById('add_key').value = '';
    document.getElementById('add_enabled_1').checked = true;
    document.getElementById('add_announcement').value = '';
    document.getElementById('add_announcement_type').value = 'info';
    new bootstrap.Modal(document.getElementById('addModal')).show();
}

function saveNewFeature() {
    var name = (document.getElementById('add_name').value || '').trim();
    var key = (document.getElementById('add_key').value || '').trim();
    var enabledInput = document.querySelector('input[name="add_is_enabled"]:checked');
    var announcement = document.getElementById('add_announcement').value;
    var announcementType = document.getElementById('add_announcement_type').value;

    if (!name) {
        layer.msg('请填写功能名称', {icon: 0});
        return false;
    }
    if (!key) {
        layer.msg('请填写功能标识', {icon: 0});
        return false;
    }
    if (!/^[A-Za-z0-9_-]{1,50}$/.test(key)) {
        layer.msg('功能标识只能包含字母、数字、下划线或短横线（不超过 50 个字符）', {icon: 0});
        return false;
    }
    // 前端先查重，避免明显重复也发一次请求（后端仍会再校验一次）
    var duplicated = (featureData || []).some(function (f) {
        return String(f.feature_key).toLowerCase() === key.toLowerCase();
    });
    if (duplicated) {
        layer.msg('功能标识「' + key + '」已存在', {icon: 0});
        return false;
    }

    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=add_feature_setting',
        data: {
            feature_name: name,
            feature_key: key,
            is_enabled: enabledInput ? enabledInput.value : 1,
            announcement: announcement,
            announcement_type: announcementType
        },
        dataType: 'json',
        success: function (data) {
            layer.close(ii);
            if (data.code == 0) {
                layer.alert(data.msg, {
                    icon: 1,
                    closeBtn: false
                }, function () {
                    window.location.reload();
                });
            } else {
                layer.alert(data.msg, {icon: 2});
            }
        },
        error: function () {
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
    return false;
}
</script>
</body>
</html>
