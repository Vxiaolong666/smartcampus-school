<?php
$title = '公告列表';
include('./head.php');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$totalCount = $DB->getRow("SELECT COUNT(*) as count FROM nteam_announcements WHERE is_published=1")['count'];
$totalPages = ceil($totalCount / $pageSize);

$announcements = $DB->getAll("SELECT * FROM nteam_announcements WHERE is_published=1 ORDER BY is_top DESC, create_time DESC LIMIT $offset, $pageSize");
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bullhorn me-2"></i>公告列表
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php if(empty($announcements)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>暂无公告</h4>
                    <p class="text-muted">管理员尚未发布任何公告</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach($announcements as $ann): ?>
                    <?php 
                        $thumbnail = $ann['thumbnail'];
                        $image = $ann['image'];
                        $displayImage = $thumbnail ?: $image;
                        if ($displayImage && strpos($displayImage, 'http') !== 0 && strpos($displayImage, $siteBasePath) !== 0) {
                            $displayImage = $siteBasePath . $displayImage;
                        }
                        // 内容预览：先转纯文本再去取前 80 字，避免出现 #、**、![]() 等 Markdown 符号
                        $contentPreview = markdownToPlainText($ann['content'], 80);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <?php if($displayImage): ?>
                            <a href="announcement.php?id=<?php echo $ann['id']; ?>">
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($ann['title']); ?>" style="height: 160px; object-fit: cover;">
                            </a>
                            <?php else: ?>
                            <a href="announcement.php?id=<?php echo $ann['id']; ?>">
                                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 160px;">
                                    <i class="fa fa-bullhorn fa-3x text-white"></i>
                                </div>
                            </a>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="announcement.php?id=<?php echo $ann['id']; ?>" class="text-dark text-decoration-none">
                                        <?php if($ann['is_top'] == 1): ?>
                                        <span class="badge bg-danger me-1">置顶</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($ann['title']); ?>
                                    </a>
                                </h5>
                                <?php if($contentPreview !== ''): ?>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($contentPreview); ?>
                                </p>
                                <?php else: ?>
                                <p class="card-text text-muted small">暂无内容摘要</p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fa fa-clock me-1"></i><?php echo date('Y-m-d', strtotime($ann['create_time'])); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fa fa-eye me-1"></i><?php echo $ann['view_count']; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fa fa-angle-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'; ?>
