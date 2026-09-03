<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: announcements.php');
    exit;
}

$mod = 'user';
include('../Common/Core_brain.php');

$announcement = $DB->getRow("SELECT * FROM nteam_announcements WHERE id='$id' AND is_published=1");

if (!$announcement) {
    header('Location: announcements.php');
    exit;
}

$DB->exec("UPDATE nteam_announcements SET view_count = view_count + 1 WHERE id='$id'");

$title = htmlspecialchars($announcement['title']);

$thumbnail = $announcement['thumbnail'];
$image = $announcement['image'];
$displayImage = $image ?: $thumbnail;

include('./head.php');
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <nav aria-label="breadcrumb" class="mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                                <li class="breadcrumb-item"><a href="announcements.php">公告列表</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($announcement['title']); ?></li>
                            </ol>
                        </nav>
                        
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
                                        <i class="fa fa-clock me-1"></i><?php echo $announcement['create_time']; ?>
                                    </span>
                                    <span>
                                        <i class="fa fa-eye me-1"></i><?php echo $announcement['view_count']; ?> 次浏览
                                    </span>
                                </div>
                            </header>
                            
                            <?php if($displayImage): ?>
                            <div class="mb-4">
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" class="img-fluid rounded" style="max-height: 400px; width: 100%; object-fit: cover;">
                            </div>
                            <?php endif; ?>
                            
                            <div class="content markdown-body" id="announcementContent">
                                <?php echo htmlspecialchars($announcement['content']); ?>
                            </div>
                        </article>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="announcements.php" class="btn btn-alt-secondary">
                                <i class="fa fa-arrow-left me-1"></i>返回列表
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/github-markdown-css@5.2.0/github-markdown.min.css">
<style>
.markdown-body {
    font-size: 15px;
    line-height: 1.8;
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
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
marked.setOptions({
    breaks: true,
    gfm: true
});

$(document).ready(function() {
    var content = $('#announcementContent').text();
    $('#announcementContent').html(marked.parse(content));
});
</script>

<?php include 'foot.php'; ?>
