<?php
/*
 * 公告预览（独立页面）
 * 从公告管理列表点击「预览」新窗口打开，按前台展示方式渲染 Markdown 正文。
 * 与前台 user/announcement.php 保持一致：正文原样输出后由 marked.js 解析；
 * 区别：预览不校验发布状态、不增加浏览量，方便发布前确认效果。
 */
$title = '公告预览';
include('./head.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$announcement = null;

if ($id > 0) {
    $announcement = $DB->getRow("SELECT * FROM nteam_announcements WHERE id='$id'");
}

if (!$announcement) {
    echo '<script>layer.msg("公告不存在", {icon: 2, time: 1500}, function() { location.href = "announcements.php"; });</script>';
    include 'foot.php';
    exit;
}

// 补齐站点子目录前缀（兼容 /csgocs 等二级目录部署），与列表页保持一致
$thumbnail = $announcement['thumbnail'];
$image = $announcement['image'];
$displayImage = $image ?: $thumbnail;
if ($displayImage && strpos($displayImage, 'http') !== 0 && strpos($displayImage, $siteBasePath) !== 0) {
    $displayImage = $siteBasePath . $displayImage;
}
?>

<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <i class="fa fa-eye me-2"></i>公告预览
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="announcements.php">公告管理</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            公告预览
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
                    <i class="fa fa-bullhorn me-2"></i><?php echo $announcement['is_published'] == 1 ? '已发布' : '未发布'; ?>
                </h3>
                <div class="block-options">
                    <a href="announcement_edit.php?id=<?php echo intval($id); ?>" class="btn btn-sm btn-alt-primary me-1">
                        <i class="fa fa-edit me-1"></i>编辑
                    </a>
                    <a href="announcements.php" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-arrow-left me-1"></i>返回列表
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="fa fa-circle-info me-2"></i>
                            <span>这是发布前预览效果（不增加浏览量），实际展示以 App / 小程序为准。</span>
                        </div>

                        <article>
                            <header class="mb-4">
                                <h1 class="h2 mb-3">
                                    <?php if($announcement['is_top'] == 1): ?>
                                    <span class="badge bg-danger me-2">置顶</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </h1>
                                <div class="text-muted d-flex flex-wrap gap-3">
                                    <?php if($announcement['author']): ?>
                                    <span>
                                        <i class="fa fa-user me-1"></i><?php echo htmlspecialchars($announcement['author']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fa fa-clock me-1"></i><?php echo htmlspecialchars($announcement['create_time']); ?>
                                    </span>
                                    <span>
                                        <i class="fa fa-eye me-1"></i><?php echo intval($announcement['view_count']); ?> 次浏览
                                    </span>
                                </div>
                            </header>

                            <?php if($displayImage): ?>
                            <div class="mb-4">
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" class="img-fluid rounded" style="max-height: 400px; width: 100%; object-fit: cover;">
                            </div>
                            <?php endif; ?>

                            <div class="content markdown-body" id="announcementContent"><?php echo htmlspecialchars($announcement['content']); ?></div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Markdown 渲染样式已本地化（原 jsDelivr CDN 在部分服务器环境不可访问）：github-markdown-css 5.2.0 -->
<link rel="stylesheet" href="./assets/css/plugins/github-markdown/github-markdown.min.css">
<style>
.markdown-body {
    font-size: 15px;
    line-height: 1.8;
    background: transparent;
}
.markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4, .markdown-body h5, .markdown-body h6 {
    margin-top: 24px;
    margin-bottom: 16px;
    font-weight: 600;
    line-height: 1.25;
}
.markdown-body h1 { font-size: 2em; border-bottom: 1px solid #eaecef; padding-bottom: .3em; }
.markdown-body h2 { font-size: 1.5em; border-bottom: 1px solid #eaecef; padding-bottom: .3em; }
.markdown-body h3 { font-size: 1.25em; }
.markdown-body h4 { font-size: 1em; }
.markdown-body p { margin-bottom: 16px; }
.markdown-body ul, .markdown-body ol { padding-left: 2em; margin-bottom: 16px; }
.markdown-body li { margin-bottom: .25em; }
.markdown-body code { padding: .2em .4em; margin: 0; font-size: 85%; background-color: rgba(27,31,35,.05); border-radius: 3px; }
.markdown-body pre { padding: 16px; overflow: auto; font-size: 85%; line-height: 1.45; background-color: #f6f8fa; border-radius: 6px; margin-bottom: 16px; }
.markdown-body pre code { background-color: transparent; padding: 0; }
.markdown-body blockquote { padding: 0 1em; color: #6a737d; border-left: .25em solid #dfe2e5; margin-bottom: 16px; }
.markdown-body img { max-width: 100%; height: auto; border-radius: 4px; }
.markdown-body table { border-spacing: 0; border-collapse: collapse; margin-bottom: 16px; width: 100%; }
.markdown-body table th, .markdown-body table td { padding: 6px 13px; border: 1px solid #dfe2e5; }
.markdown-body table th { font-weight: 600; background-color: #f6f8fa; }
.markdown-body table tr:nth-child(2n) { background-color: #f6f8fa; }
.markdown-body a { color: #0366d6; text-decoration: none; }
.markdown-body a:hover { text-decoration: underline; }
.markdown-body hr { height: .25em; padding: 0; margin: 24px 0; background-color: #e1e4e8; border: 0; }
</style>
<!-- Markdown 解析库已本地化：marked v4.3.0 -->
<script src="./assets/js/plugins/marked/marked.min.js"></script>
<script>
// 与前台 user/announcement.php 相同的渲染方式：先取纯文本 Markdown，再交给 marked 解析
marked.setOptions({
    breaks: true,
    gfm: true
});

$(function () {
    var $el = $('#announcementContent');
    var content = $el.text();
    if (window.marked) {
        $el.html(marked.parse(content || ''));
    }
});
</script>

<?php include 'foot.php'; ?>
