<?php
$mod = 'admin';
$title = '管理员管理';
include('./head.php');

$adminList = $DB->query("SELECT * FROM nteam_admin ORDER BY id ASC")->fetchAll();
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-users me-2"></i>管理员列表
                </h3>
                <div class="block-options">
                    <button type="button" class="btn btn-alt-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fa fa-plus me-1"></i>添加管理员
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>用户名</th>
                                <th>QQ</th>
                                <th class="text-center">权限等级</th>
                                <th class="text-center" style="width: 160px; white-space: nowrap;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($adminList)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-users-slash"></i>
                                        <span>暂无管理员数据</span>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($adminList as $admin): ?>
                            <?php 
                            $rankText = '';
                            $rankBadge = '';
                            switch($admin['adminRank']) {
                                case 1: 
                                    $rankText = '超级管理员'; 
                                    $rankBadge = 'bg-danger';
                                    break;
                                case 2: 
                                    $rankText = '普通管理员'; 
                                    $rankBadge = 'bg-primary';
                                    break;
                                default: 
                                    $rankText = '未知'; 
                                    $rankBadge = 'bg-secondary';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['adminUser']); ?></td>
                                <td><?php echo htmlspecialchars($admin['adminQq']); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $rankBadge; ?>"><?php echo $rankText; ?></span>
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-alt-primary me-1" onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                        <i class="fa fa-edit"></i> 编辑
                                    </button>
                                    <?php if($admin['id'] != 1): ?>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="delAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['adminUser']); ?>')">
                                        <i class="fa fa-trash"></i> 删除
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mobile-cards">
                    <?php if(empty($adminList)): ?>
                    <div class="info-empty-overlay">
                        <i class="fa fa-users-slash"></i>
                        <span>暂无管理员数据</span>
                    </div>
                    <?php else: ?>
                        <?php foreach($adminList as $admin): ?>
                        <?php 
                        $rankText = '';
                        $rankBadge = '';
                        switch($admin['adminRank']) {
                            case 1: 
                                $rankText = '超级管理员'; 
                                $rankBadge = 'bg-danger';
                                break;
                            case 2: 
                                $rankText = '普通管理员'; 
                                $rankBadge = 'bg-primary';
                                break;
                            default: 
                                $rankText = '未知'; 
                                $rankBadge = 'bg-secondary';
                        }
                        ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($admin['adminUser']); ?></strong>
                                    <?php if($admin['adminQq']): ?>
                                    <small class="text-muted d-block">QQ: <?php echo htmlspecialchars($admin['adminQq']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?php echo $rankBadge; ?>"><?php echo $rankText; ?></span>
                            </div>
                            <div class="mobile-card-actions">
                                <button type="button" class="btn btn-sm btn-alt-primary flex-fill" onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                    <i class="fa fa-edit"></i> 编辑
                                </button>
                                <?php if($admin['id'] != 1): ?>
                                <button type="button" class="btn btn-sm btn-alt-danger flex-fill" onclick="delAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['adminUser']); ?>')">
                                    <i class="fa fa-trash"></i> 删除
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">添加管理员</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm">
                    <div class="mb-3">
                        <label class="form-label">用户名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="adminUser" required placeholder="请输入用户名">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密码 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="adminPwd" required placeholder="请输入密码">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">QQ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="adminQq" required placeholder="请输入QQ号">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">权限等级 <span class="text-danger">*</span></label>
                        <select class="form-select" name="adminRank" required>
                            <option value="1">超级管理员</option>
                            <option value="2">普通管理员</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-alt-primary" onclick="submitAddAdmin()">添加</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">编辑管理员</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAdminForm">
                    <input type="hidden" name="id" id="editAdminId">
                    <div class="mb-3">
                        <label class="form-label">用户名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="adminUser" id="editAdminUser" required placeholder="请输入用户名">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密码 <span class="text-muted">(留空则不修改)</span></label>
                        <input type="password" class="form-control" name="adminPwd" placeholder="留空则不修改">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">QQ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="adminQq" id="editAdminQq" required placeholder="请输入QQ号">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">权限等级 <span class="text-danger">*</span></label>
                        <select class="form-select" name="adminRank" id="editAdminRank" required>
                            <option value="1">超级管理员</option>
                            <option value="2">普通管理员</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-alt-primary" onclick="submitEditAdmin()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitAddAdmin() {
    var formData = $('#addAdminForm').serialize();
    $.ajax({
        url: 'Ajax.php?act=AddAdmin',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if(res.code == 0) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.msg('请求失败', {icon: 2});
        }
    });
}

function editAdmin(admin) {
    $('#editAdminId').val(admin.id);
    $('#editAdminUser').val(admin.adminUser);
    $('#editAdminQq').val(admin.adminQq);
    $('#editAdminRank').val(admin.adminRank);
    $('#editAdminModal').modal('show');
}

function submitEditAdmin() {
    var formData = $('#editAdminForm').serialize();
    $.ajax({
        url: 'Ajax.php?act=EditAdmin',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if(res.code == 0) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.msg('请求失败', {icon: 2});
        }
    });
}

function delAdmin(id, name) {
    layer.confirm('确定要删除管理员 "' + name + '" 吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'Ajax.php?act=DelAdmin',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function() {
                layer.msg('请求失败', {icon: 2});
            }
        });
    });
}
</script>

<style>
.info-empty-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #9ca3af;
    background: #f9fafb;
    border-radius: 0.375rem;
    border: 2px dashed #e5e7eb;
}

.info-empty-overlay i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.info-empty-overlay span {
    font-size: 0.95rem;
}

#page-container.dark-mode .info-empty-overlay {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}

@media (max-width: 768px) {
    .table-responsive {
        display: none !important;
    }
    .mobile-cards {
        display: block !important;
    }
    .mobile-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
    }
    .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .mobile-card-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }
}

@media (min-width: 769px) {
    .mobile-cards {
        display: none !important;
    }
}

#page-container.dark-mode .mobile-card {
    background: #1e293b !important;
    border-color: #334155 !important;
}

#page-container.dark-mode .mobile-card-header,
#page-container.dark-mode .mobile-card-actions {
    border-color: #334155 !important;
}
</style>

<?php include 'foot.php'; ?>
