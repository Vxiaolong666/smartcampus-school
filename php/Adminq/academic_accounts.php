<?php
$title = '教务账号管理';
include('./head.php');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$totalCount = $DB->query("SELECT COUNT(*) as count FROM nteam_academic_accounts")->fetch()['count'];
$totalPages = ceil($totalCount / $pageSize);

$academicAccounts = $DB->query("SELECT a.*, u.username, u.email, u.qq as user_qq 
    FROM nteam_academic_accounts a 
    LEFT JOIN nteam_users u ON a.user_id = u.id 
    ORDER BY a.bind_time DESC 
    LIMIT $offset, $pageSize")->fetchAll();
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
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-graduation-cap me-2"></i>教务账号管理
                </h3>
                <div class="block-options">
                    <span class="badge bg-primary">共 <?php echo $totalCount; ?> 条记录</span>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>用户信息</th>
                                <th>学工号</th>
                                <th>教务密码</th>
                                <th>真实姓名</th>
                                <th>绑定时间</th>
                                <th class="text-center" style="width: 160px; white-space: nowrap;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($academicAccounts)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-graduation-cap"></i>
                                        <span>暂无教务账号绑定数据</span>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($academicAccounts as $account): ?>
                                <tr>
                                    <td class="text-center"><?php echo $account['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                                        </div>
                                        <?php if($account['user_qq']): ?>
                                        <div>
                                            <img src="https://q2.qlogo.cn/g?b=qq&nk=<?php echo $account['user_qq']; ?>&s=100" 
                                                 alt="QQ头像" class="rounded-circle me-1" style="width: 20px; height: 20px;">
                                            <small class="text-muted">QQ: <?php echo htmlspecialchars($account['user_qq']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($account['student_id']); ?></code>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="password" class="form-control form-control-sm" 
                                                   value="<?php echo htmlspecialchars($account['password']); ?>" 
                                                   id="pwd_<?php echo $account['id']; ?>" readonly>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="togglePwd(<?php echo $account['id']; ?>)">
                                                <i class="fa fa-eye" id="pwd_icon_<?php echo $account['id']; ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($account['real_name'] ?: '-'); ?></td>
                                    <td>
                                        <small><?php echo $account['bind_time']; ?></small>
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button type="button" class="btn btn-sm btn-alt-primary me-1" 
                                                onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)">
                                            <i class="fa fa-edit"></i> 编辑
                                        </button>
                                        <button type="button" class="btn btn-sm btn-alt-danger" 
                                                onclick="delAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['student_id']); ?>')">
                                            <i class="fa fa-trash"></i> 删除
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mobile-cards">
                    <?php if(empty($academicAccounts)): ?>
                    <div class="info-empty-overlay">
                        <i class="fa fa-graduation-cap"></i>
                        <span>暂无教务账号绑定数据</span>
                    </div>
                    <?php else: ?>
                        <?php foreach($academicAccounts as $account): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                                </div>
                                <span class="badge bg-secondary">#<?php echo $account['id']; ?></span>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">学工号</span>
                                    <span><code><?php echo htmlspecialchars($account['student_id']); ?></code></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">真实姓名</span>
                                    <span><?php echo htmlspecialchars($account['real_name'] ?: '-'); ?></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">绑定时间</span>
                                    <span><small><?php echo $account['bind_time']; ?></small></span>
                                </div>
                            </div>
                            <div class="mobile-card-actions">
                                <button type="button" class="btn btn-sm btn-alt-primary flex-fill" 
                                        onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)">
                                    <i class="fa fa-edit"></i> 编辑
                                </button>
                                <button type="button" class="btn btn-sm btn-alt-danger flex-fill" 
                                        onclick="delAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['student_id']); ?>')">
                                    <i class="fa fa-trash"></i> 删除
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">上一页</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php if($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">1</a>
                            </li>
                            <?php if($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($endPage < $totalPages): ?>
                            <?php if($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">下一页</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">编辑教务账号</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">学工号</label>
                        <input type="text" class="form-control" name="student_id" id="edit_student_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">教务密码</label>
                        <input type="text" class="form-control" name="password" id="edit_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">真实姓名</label>
                        <input type="text" class="form-control" name="real_name" id="edit_real_name">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-alt-primary" onclick="saveEdit()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePwd(id) {
    var pwdInput = document.getElementById('pwd_' + id);
    var pwdIcon = document.getElementById('pwd_icon_' + id);
    
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        pwdIcon.classList.remove('fa-eye');
        pwdIcon.classList.add('fa-eye-slash');
    } else {
        pwdInput.type = 'password';
        pwdIcon.classList.remove('fa-eye-slash');
        pwdIcon.classList.add('fa-eye');
    }
}

function editAccount(account) {
    document.getElementById('edit_id').value = account.id;
    document.getElementById('edit_student_id').value = account.student_id;
    document.getElementById('edit_password').value = account.password;
    document.getElementById('edit_real_name').value = account.real_name || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function saveEdit() {
    var formData = $('#editForm').serialize();
    
    $.ajax({
        url: 'Ajax.php?act=update_academic_account',
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

function delAccount(id, studentId) {
    layer.confirm('确定要删除学工号 <strong>' + studentId + '</strong> 的绑定记录吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'Ajax.php?act=delete_academic_account',
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
    .mobile-card-body {
        font-size: 0.875rem;
    }
    .mobile-card-row {
        display: flex;
        justify-content: space-between;
        padding: 0.375rem 0;
        border-bottom: 1px dashed #f0f0f0;
    }
    .mobile-card-row:last-child {
        border-bottom: none;
    }
    .mobile-card-label {
        color: #6b7280;
        font-weight: 500;
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
#page-container.dark-mode .mobile-card-row,
#page-container.dark-mode .mobile-card-actions {
    border-color: #334155 !important;
}

#page-container.dark-mode .mobile-card-label {
    color: #94a3b8 !important;
}
</style>

<?php include 'foot.php'; ?>
