<?php
$title = '宿舍绑定';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'dormitory_bind')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'dormitory_bind');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$dormitories = DormitorySystem::getUserDormitories($DB, $userId);
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-home me-2"></i>宿舍绑定
                </h3>
                <div class="block-options">
                    <?php if (count($dormitories) < 5): ?>
                    <a href="dormitory_add.php" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-plus me-1"></i>添加宿舍
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'dormitory_bind'); ?>
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>提示</strong> - 您最多可以绑定5个宿舍，绑定后可快速查询电费。当前已绑定 <?php echo count($dormitories); ?>/5 个宿舍。
                    </div>
                </div>
                
                <?php if (count($dormitories) > 0): ?>
                <h4 class="mb-3">已绑定的宿舍</h4>
                
                <div class="table-responsive mb-4 d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>校区</th>
                                <th>楼栋</th>
                                <th>房间号</th>
                                <th>宿舍编号</th>
                                <th>别名</th>
                                <th>绑定时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dormitories as $dorm): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dorm['school_area']); ?></td>
                                <td><?php echo htmlspecialchars($dorm['building']); ?></td>
                                <td><strong><?php echo htmlspecialchars($dorm['room']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($dorm['room_num']); ?></code></td>
                                <td>
                                    <span id="alias-display-<?php echo $dorm['id']; ?>"><?php echo htmlspecialchars($dorm['alias'] ?: '-'); ?></span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-1" onclick="editAlias(<?php echo $dorm['id']; ?>, '<?php echo htmlspecialchars($dorm['alias']); ?>')">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </td>
                                <td><?php echo $dorm['bind_time']; ?></td>
                                <td>
                                    <?php if ($dorm['is_default'] != 1): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="setDefault(<?php echo $dorm['id']; ?>)">
                                        <i class="fa fa-star"></i> 设为默认
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-success">默认</span>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="unbindDorm(<?php echo $dorm['id']; ?>)">
                                        <i class="fa fa-trash"></i> 解绑
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-md-none mb-4">
                    <?php foreach ($dormitories as $dorm): ?>
                    <div class="card mb-2">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($dorm['building']); ?> - <?php echo htmlspecialchars($dorm['room']); ?>
                                        <?php if ($dorm['is_default'] == 1): ?>
                                        <span class="badge bg-success ms-1">默认</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($dorm['school_area']); ?> · 
                                        编号: <?php echo htmlspecialchars($dorm['room_num']); ?>
                                    </small>
                                    <div class="mt-1">
                                        <small>别名: <span id="alias-display-<?php echo $dorm['id']; ?>"><?php echo htmlspecialchars($dorm['alias'] ?: '-'); ?></span></small>
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-1" onclick="editAlias(<?php echo $dorm['id']; ?>, '<?php echo htmlspecialchars($dorm['alias']); ?>')">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($dorm['is_default'] != 1): ?>
                                        <button type="button" class="btn btn-outline-success" onclick="setDefault(<?php echo $dorm['id']; ?>)" title="设为默认">
                                            <i class="fa fa-star"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="unbindDorm(<?php echo $dorm['id']; ?>)" title="解绑">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="d-flex">
                    <a href="power_fee.php" class="btn btn-alt-primary me-2">
                        <i class="fa fa-bolt me-1"></i> 查询电费
                    </a>
                    <?php if (count($dormitories) < 5): ?>
                    <a href="dormitory_add.php" class="btn btn-alt-secondary">
                        <i class="fa fa-plus me-1"></i> 添加更多宿舍
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa fa-home fa-4x text-muted mb-3"></i>
                    <h4>暂无绑定的宿舍</h4>
                    <p class="text-muted mb-4">绑定宿舍后可以快速查询电费</p>
                    <a href="dormitory_add.php" class="btn btn-alt-primary">
                        <i class="fa fa-plus me-1"></i> 添加宿舍
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function unbindDorm(id) {
    layer.confirm('确定要解绑这个宿舍吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'ajax.php?act=unbind_dormitory',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.code == 1) {
                    layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            }
        });
    });
}

function setDefault(id) {
    $.ajax({
        url: 'ajax.php?act=set_default_dormitory',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        }
    });
}

function editAlias(id, currentAlias) {
    layer.prompt({
        formType: 0,
        value: currentAlias,
        title: '修改宿舍别名'
    }, function(value, index) {
        $.ajax({
            url: 'ajax.php?act=update_dormitory_alias',
            type: 'POST',
            data: { id: id, alias: value },
            dataType: 'json',
            success: function(res) {
                if (res.code == 1) {
                    layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            }
        });
        layer.close(index);
    });
}
</script>

<?php include 'foot.php'; ?>
<style>
@media (max-width: 768px) {
    .alert {
        padding: 0.75rem;
        font-size: 14px;
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
    .d-flex > .btn {
        font-size: 14px;
    }
}
</style>
