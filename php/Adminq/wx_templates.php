<?php
$mod = 'admin';
$title='消息模板管理';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$templates = $DB->getAll("SELECT * FROM nteam_wx_templates ORDER BY id DESC");
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
        <div class="block block-rounded">
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
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
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
                                        $sceneTypes = [
                                            'schedule_reminder' => '课程提醒',
                                            'grade_notify' => '成绩通知',
                                            'power_fee' => '电费提醒',
                                            'power_report' => '电费报告',
                                            'subscription_alert' => '额度提醒'
                                        ];
                                        $sceneType = $tpl['scene_type'] ?? '';
                                        echo isset($sceneTypes[$sceneType]) ? '<span class="badge bg-info">'.$sceneTypes[$sceneType].'</span>' : '<span class="badge bg-secondary">未分类</span>';
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
    </div>
</div>

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
