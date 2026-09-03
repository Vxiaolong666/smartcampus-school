<?php
$mod = 'admin';
$title='操作日志';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$pageSize = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $pageSize;

$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
$where = '1=1';
if ($filter) {
    $where .= " AND (adminUser LIKE '%{$filter}%' OR type LIKE '%{$filter}%' OR ip LIKE '%{$filter}%')";
}

$totalResult = $DB->query("SELECT COUNT(*) as total FROM nteam_log WHERE $where")->fetch();
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $pageSize);

$logs = $DB->getAll("SELECT * FROM nteam_log WHERE $where ORDER BY id DESC LIMIT $offset, $pageSize");
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
                <h3 class="block-title">操作日志列表</h3>
                <div class="block-options">
                    <span class="text-muted me-3">共 <?php echo $totalRows; ?> 条记录</span>
                    <button type="button" onclick="clearLogs()" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> 清空日志
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-auto">
                            <input type="text" class="form-control" name="filter" value="<?php echo htmlspecialchars($filter); ?>" placeholder="搜索操作者/操作内容/IP">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> 搜索
                            </button>
                            <?php if($filter): ?>
                            <a href="?" class="btn btn-secondary">
                                <i class="fa fa-times"></i> 清除
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 60px;">ID</th>
                                <th style="min-width: 100px;">操作者</th>
                                <th style="min-width: 120px;">操作类型</th>
                                <th style="min-width: 150px;">操作时间</th>
                                <th style="min-width: 120px;">IP地址</th>
                                <th style="min-width: 80px;">城市</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($logs): ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="text-center"><?php echo $log['id']; ?></td>
                                    <td><?php echo htmlspecialchars($log['adminUser'] ?: '系统'); ?></td>
                                    <td>
                                        <?php 
                                        $typeColors = [
                                            '登录后台中心' => 'text-success',
                                            '退出后台中心' => 'text-warning',
                                            '修改账号信息' => 'text-info',
                                            '添加' => 'text-primary',
                                            '删除' => 'text-danger',
                                            '编辑' => 'text-info',
                                            '更新' => 'text-info',
                                        ];
                                        $colorClass = '';
                                        foreach($typeColors as $key => $color) {
                                            if(strpos($log['type'], $key) !== false) {
                                                $colorClass = $color;
                                                break;
                                            }
                                        }
                                        ?>
                                        <span class="<?php echo $colorClass; ?>"><?php echo htmlspecialchars($log['type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['data']); ?></td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip']); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['city'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="info-empty-overlay">
                                            <i class="fa fa-history"></i>
                                            <span>暂无操作日志</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>">上一页</a>
                        </li>
                        <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>">下一页</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function clearLogs() {
    layer.confirm('确定要清空所有操作日志吗？此操作不可恢复！', {
        btn: ['确定', '取消'],
        icon: 3
    }, function(index) {
        layer.close(index);
        $.post('Ajax.php', {act: 'clear_admin_logs'}, function(res) {
            if(res.code == 0) {
                layer.msg('清空成功', {icon: 1}, function() {
                    location.reload();
                });
            } else {
                layer.msg(res.msg || '清空失败', {icon: 2});
            }
        }, 'json');
    });
}
</script>

<?php include './foot.php'; ?>
