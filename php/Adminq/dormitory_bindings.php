<?php
$title = '宿舍绑定管理';
include('./head.php');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$searchUser = isset($_GET['user']) ? trim($_GET['user']) : '';
$searchRoom = isset($_GET['room']) ? trim($_GET['room']) : '';

$whereClause = "WHERE d.status=1";
if ($searchUser) {
    $searchUserEsc = addslashes($searchUser);
    $whereClause .= " AND (u.username LIKE '%{$searchUserEsc}%' OR u.email LIKE '%{$searchUserEsc}%')";
}
if ($searchRoom) {
    $searchRoomEsc = addslashes($searchRoom);
    $whereClause .= " AND (d.room_num LIKE '%{$searchRoomEsc}%' OR d.room LIKE '%{$searchRoomEsc}%' OR d.building LIKE '%{$searchRoomEsc}%')";
}

$totalCount = $DB->getRow("SELECT COUNT(*) as count FROM nteam_dormitory_bindings d LEFT JOIN nteam_users u ON d.user_id = u.id $whereClause")['count'];
$totalPages = ceil($totalCount / $pageSize);

$dormitories = $DB->getAll("SELECT d.*, u.username, u.email, u.qq as user_qq 
    FROM nteam_dormitory_bindings d 
    LEFT JOIN nteam_users u ON d.user_id = u.id 
    $whereClause
    ORDER BY d.bind_time DESC 
    LIMIT $offset, $pageSize");
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-home me-2"></i>宿舍绑定管理
                </h3>
                <div class="block-options">
                    <span class="badge bg-primary">共 <?php echo $totalCount; ?> 条记录</span>
                </div>
            </div>
            <div class="block-content block-content-full">
                <form method="GET" action="" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="user" placeholder="搜索用户名/邮箱" value="<?php echo htmlspecialchars($searchUser); ?>">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="room" placeholder="搜索宿舍号/楼栋" value="<?php echo htmlspecialchars($searchRoom); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-alt-primary me-2">
                            <i class="fa fa-search me-1"></i>搜索
                        </button>
                        <a href="?" class="btn btn-alt-secondary">
                            <i class="fa fa-redo me-1"></i>重置
                        </a>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>用户信息</th>
                                <th>校区</th>
                                <th>楼栋</th>
                                <th>房间号</th>
                                <th>宿舍编号</th>
                                <th>别名</th>
                                <th class="text-center">默认</th>
                                <th>绑定时间</th>
                                <th class="text-center" style="width: 100px; white-space: nowrap;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($dormitories)): ?>
                            <tr>
                                <td colspan="10">
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-home"></i>
                                        <span>暂无宿舍绑定数据</span>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($dormitories as $dorm): ?>
                                <tr>
                                    <td class="text-center"><?php echo $dorm['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($dorm['username']); ?></strong>
                                        </div>
                                        <?php if($dorm['user_qq']): ?>
                                        <div>
                                            <img src="https://q2.qlogo.cn/g?b=qq&nk=<?php echo $dorm['user_qq']; ?>&s=100" 
                                                 alt="QQ头像" class="rounded-circle me-1" style="width: 20px; height: 20px;">
                                            <small class="text-muted">QQ: <?php echo htmlspecialchars($dorm['user_qq']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($dorm['school_area']); ?></td>
                                    <td><?php echo htmlspecialchars($dorm['building']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($dorm['room']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($dorm['room_num']); ?></code></td>
                                    <td><?php echo htmlspecialchars($dorm['alias'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <?php if($dorm['is_default'] == 1): ?>
                                        <span class="badge bg-success">默认</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo $dorm['bind_time']; ?></small></td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <button type="button" class="btn btn-sm btn-alt-primary me-1" 
                                                onclick="editDorm(<?php echo htmlspecialchars(json_encode($dorm)); ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-alt-danger" 
                                                onclick="delDorm(<?php echo $dorm['id']; ?>, '<?php echo htmlspecialchars($dorm['room_num']); ?>')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mobile-cards">
                    <?php if(empty($dormitories)): ?>
                    <div class="info-empty-overlay">
                        <i class="fa fa-home"></i>
                        <span>暂无宿舍绑定数据</span>
                    </div>
                    <?php else: ?>
                        <?php foreach($dormitories as $dorm): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($dorm['username']); ?></strong>
                                </div>
                                <span class="badge bg-secondary">#<?php echo $dorm['id']; ?></span>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">校区</span>
                                    <span><?php echo htmlspecialchars($dorm['school_area']); ?></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">楼栋</span>
                                    <span><?php echo htmlspecialchars($dorm['building']); ?></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">房间号</span>
                                    <span><strong><?php echo htmlspecialchars($dorm['room']); ?></strong></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">宿舍编号</span>
                                    <span><code><?php echo htmlspecialchars($dorm['room_num']); ?></code></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">别名</span>
                                    <span><?php echo htmlspecialchars($dorm['alias'] ?: '-'); ?></span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">默认</span>
                                    <span>
                                        <?php if($dorm['is_default'] == 1): ?>
                                        <span class="badge bg-success">默认</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="mobile-card-row">
                                    <span class="mobile-card-label">绑定时间</span>
                                    <span><small><?php echo $dorm['bind_time']; ?></small></span>
                                </div>
                            </div>
                            <div class="mobile-card-actions">
                                <button type="button" class="btn btn-sm btn-alt-primary flex-fill" 
                                        onclick="editDorm(<?php echo htmlspecialchars(json_encode($dorm)); ?>)">
                                    <i class="fa fa-edit"></i> 编辑
                                </button>
                                <button type="button" class="btn btn-sm btn-alt-danger flex-fill" 
                                        onclick="delDorm(<?php echo $dorm['id']; ?>, '<?php echo htmlspecialchars($dorm['room_num']); ?>')">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&user=<?php echo urlencode($searchUser); ?>&room=<?php echo urlencode($searchRoom); ?>">上一页</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php if($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&user=<?php echo urlencode($searchUser); ?>&room=<?php echo urlencode($searchRoom); ?>">1</a>
                            </li>
                            <?php if($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&user=<?php echo urlencode($searchUser); ?>&room=<?php echo urlencode($searchRoom); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($endPage < $totalPages): ?>
                            <?php if($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&user=<?php echo urlencode($searchUser); ?>&room=<?php echo urlencode($searchRoom); ?>"><?php echo $totalPages; ?></a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&user=<?php echo urlencode($searchUser); ?>&room=<?php echo urlencode($searchRoom); ?>">下一页</a>
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
                <h5 class="modal-title" id="editModalLabel">编辑宿舍绑定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">校区</label>
                        <input type="text" class="form-control" name="school_area" id="edit_school_area">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">楼栋</label>
                        <input type="text" class="form-control" name="building" id="edit_building">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">房间号</label>
                        <input type="text" class="form-control" name="room" id="edit_room">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">宿舍编号</label>
                        <input type="text" class="form-control" name="room_num" id="edit_room_num" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">别名</label>
                        <input type="text" class="form-control" name="alias" id="edit_alias">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_default" id="edit_is_default" value="1">
                            <label class="form-check-label" for="edit_is_default">设为默认宿舍</label>
                        </div>
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
function editDorm(dorm) {
    document.getElementById('edit_id').value = dorm.id;
    document.getElementById('edit_school_area').value = dorm.school_area || '';
    document.getElementById('edit_building').value = dorm.building || '';
    document.getElementById('edit_room').value = dorm.room || '';
    document.getElementById('edit_room_num').value = dorm.room_num || '';
    document.getElementById('edit_alias').value = dorm.alias || '';
    document.getElementById('edit_is_default').checked = dorm.is_default == 1;
    
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function saveEdit() {
    var formData = $('#editForm').serialize();
    
    $.ajax({
        url: 'Ajax.php?act=update_dormitory_binding',
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

function delDorm(id, roomNum) {
    layer.confirm('确定要删除宿舍 <strong>' + roomNum + '</strong> 的绑定记录吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'Ajax.php?act=delete_dormitory_binding',
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
