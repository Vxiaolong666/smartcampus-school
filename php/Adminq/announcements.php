<?php
$title = '公告管理';
include('./head.php');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 15;
$offset = ($page - 1) * $pageSize;

$searchTitle = isset($_GET['title']) ? trim($_GET['title']) : '';
$searchStatus = isset($_GET['status']) ? intval($_GET['status']) : -1;

$whereClause = "WHERE 1=1";
if ($searchTitle) {
    $searchTitleEsc = addslashes($searchTitle);
    $whereClause .= " AND title LIKE '%{$searchTitleEsc}%'";
}
if ($searchStatus >= 0) {
    $whereClause .= " AND is_published = '{$searchStatus}'";
}

$totalCount = $DB->getRow("SELECT COUNT(*) as count FROM nteam_announcements $whereClause")['count'];
$totalPages = ceil($totalCount / $pageSize);

$announcements = $DB->getAll("SELECT * FROM nteam_announcements $whereClause ORDER BY is_top DESC, create_time DESC LIMIT $offset, $pageSize");
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bullhorn me-2"></i>公告管理
                </h3>
                <div class="block-options">
                    <a href="announcement_edit.php" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-plus me-1"></i>添加公告
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <form method="GET" action="" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="title" placeholder="搜索公告标题" value="<?php echo htmlspecialchars($searchTitle); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="-1">全部状态</option>
                            <option value="1" <?php echo $searchStatus == 1 ? 'selected' : ''; ?>>已发布</option>
                            <option value="0" <?php echo $searchStatus == 0 ? 'selected' : ''; ?>>未发布</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button type="submit" class="btn btn-alt-primary me-2">
                            <i class="fa fa-search me-1"></i>搜索
                        </button>
                        <a href="?" class="btn btn-alt-secondary">
                            <i class="fa fa-redo me-1"></i>重置
                        </a>
                    </div>
                </form>
                
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
                                <th style="min-width: 80px;">缩略图</th>
                                <th style="min-width: 150px;">标题</th>
                                <th class="text-center" style="min-width: 60px;">置顶</th>
                                <th class="text-center" style="min-width: 60px;">状态</th>
                                <th class="text-center" style="min-width: 60px;">浏览</th>
                                <th style="min-width: 120px;">创建时间</th>
                                <th class="text-center" style="min-width: 100px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($announcements)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-bullhorn"></i>
                                        <span>暂无公告</span>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($announcements as $ann): ?>
                                <?php 
                                    $thumbnail = $ann['thumbnail'];
                                    $image = $ann['image'];
                                    $displayImage = $thumbnail ?: $image;
                                    if ($displayImage && strpos($displayImage, 'http') !== 0 && strpos($displayImage, $siteBasePath) !== 0) {
                                        $displayImage = $siteBasePath . $displayImage;
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $ann['id']; ?></td>
                                    <td>
                                        <?php if($displayImage): ?>
                                        <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="缩略图" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                        <span class="text-muted">无图</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                                        <?php if($ann['is_top'] == 1): ?>
                                        <span class="badge bg-danger ms-1">置顶</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($ann['is_top'] == 1): ?>
                                        <span class="badge bg-success">是</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">否</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($ann['is_published'] == 1): ?>
                                        <span class="badge bg-success">已发布</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">未发布</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $ann['view_count']; ?></td>
                                    <td><small><?php echo $ann['create_time']; ?></small></td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <a href="announcement_edit.php?id=<?php echo $ann['id']; ?>" class="btn btn-sm btn-alt-primary me-1">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-alt-danger" onclick="delAnnouncement(<?php echo $ann['id']; ?>, '<?php echo htmlspecialchars($ann['title']); ?>')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页，共 <?php echo $totalCount; ?> 条记录
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&title=<?php echo urlencode($searchTitle); ?>&status=<?php echo $searchStatus; ?>">上一页</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&title=<?php echo urlencode($searchTitle); ?>&status=<?php echo $searchStatus; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&title=<?php echo urlencode($searchTitle); ?>&status=<?php echo $searchStatus; ?>">下一页</a>
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

<script>
function delAnnouncement(id, title) {
    layer.confirm('确定要删除公告 <strong>' + title + '</strong> 吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'Ajax.php?act=delete_announcement',
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
</style>

<?php include 'foot.php'; ?>
