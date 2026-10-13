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
// 补齐站点子目录前缀（兼容 /csgocs 等二级目录部署），与公告列表页保持一致
if ($displayImage && strpos($displayImage, 'http') !== 0 && strpos($displayImage, $siteBasePath) !== 0) {
    $displayImage = $siteBasePath . $displayImage;
}

include('./head.php');
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="d-flex mb-3">
                            <a href="announcements.php" class="btn btn-alt-secondary btn-sm">
                                <i class="fa fa-arrow-left me-1"></i>返回列表
                            </a>
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
                                        <i class="fa fa-clock me-1"></i><?php echo $announcement['create_time']; ?>
                                    </span>
                                    <span>
                                        <i class="fa fa-eye me-1"></i><?php echo $announcement['view_count']; ?> 次浏览
                                    </span>
                                </div>
                            </header>
                            
                            <!-- AI 智能总结（页面加载自动异步生成，优先复用历史缓存；样式与 uniapp 浅色主题统一） -->
                            <div class="ai-summary-card" id="aiSummaryCard" style="display:none;">
                                <div class="ai-summary-head">
                                    <span class="ai-summary-badge"><i class="fa fa-robot"></i>AI 摘要</span>
                                </div>
                                <div class="ai-summary-body" id="aiSummary"></div>
                                <div class="ai-summary-foot">本卡片由 AI 阅读公告后生成，可能存在疏漏，请以公告正文为准。</div>
                            </div>

                            <?php if($displayImage): ?>
                            <div class="mb-4">
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" class="img-fluid rounded" style="max-height: 400px; width: 100%; object-fit: cover;">
                            </div>
                            <?php endif; ?>

                            <?php /* 正文紧贴标签输出：模板缩进会被 Markdown 当作 4 空格代码块 */ ?>
                            <div class="content markdown-body" id="announcementContent"><?php echo htmlspecialchars(trim($announcement['content'])); ?></div>
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
/* ===== AI 智能总结卡片（浅色主题渐变，与 uniapp 端 #13c2c2→#1890ff 主题呼应） ===== */
.ai-summary-card {
    background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 55%, #e6fffb 100%);
    border: 1px solid rgba(19, 194, 194, .18);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 1.5rem;
    box-shadow: 0 6px 18px rgba(24, 144, 255, .08);
}
.ai-summary-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.ai-summary-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 999px;
    box-shadow: 0 2px 8px rgba(19, 194, 194, .35);
}
.ai-summary-meta { font-size: 12px; color: #8a94a6; }
.ai-summary-title {
    font-size: 17px;
    font-weight: 700;
    color: #2a303b;
    line-height: 1.4;
    margin: 0 0 10px;
}
.ai-summary-body { font-size: 14px; line-height: 1.8; color: #3c4554; }
.ai-summary-body .markdown-body { font-size: 14px; line-height: 1.8; color: #3c4554; background: transparent; }
.ai-summary-body .markdown-body p:last-child,
.ai-summary-body .markdown-body ul:last-child,
.ai-summary-body .markdown-body ol:last-child { margin-bottom: 0; }
.ai-summary-loading { text-align: center; color: #8a94a6; padding: 14px 0; }
.ai-summary-foot {
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px dashed rgba(19, 194, 194, .25);
    font-size: 12px;
    color: #8a94a6;
}
@media (max-width: 768px) {
    .ai-summary-card { padding: 14px 16px; border-radius: 12px; }
    .ai-summary-title { font-size: 15px; }
}

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
<!-- Markdown 解析库已本地化：marked v4.3.0 -->
<script src="./assets/js/plugins/marked/marked.min.js"></script>
<script>
marked.setOptions({
    breaks: true,
    gfm: true
});

// 页面加载后自动异步调用 AI 接口总结本文，命中页面缓存/历史缓存则不重复消耗额度
// 总结与要点合并为单个 markdown-body 渲染，由 marked 自然处理段落/列表间距
function renderAiSummary(text) {
    var toHtml = function (md) {
        return window.mdToHtml ? window.mdToHtml(md) : $('<div>').text(md).html();
    };
    $('#aiSummary').html('<div class="markdown-body">' + toHtml(text || '') + '</div>');
}

function aiSummarizeAnnouncement() {
    AiAnalyze.run({
        url: 'ajax.php?act=ai_analyze_announcement',
        modalId: '',
        resultId: 'aiSummary',
        fabSel: '',
        payload: function () { return { id: <?php echo intval($id); ?> }; },
        cacheKey: function () { return 'announcement:' + <?php echo intval($id); ?>; },
        validate: function () { return true; },
        onDone: function (res) {
            if (res && res.code === 0 && res.content) {
                renderAiSummary(res.content);
                $('#aiSummaryCard').show();
            }
        }
    });
}

$(document).ready(function() {
    // 去掉首尾空白：内容前的缩进会被 Markdown 解析成 4 空格代码块
    var content = $.trim($('#announcementContent').text());
    var $el = $('#announcementContent');
    // 统一入口 mdToHtml：修正「紧跟段落的 --- 被当成上文的下划线式标题」，
    // 避免 # / > 等原始 Markdown 符号被原样吞进 <h2> 展示
    $el.html(window.mdToHtml ? window.mdToHtml(content) : marked.parse(content));

    // 正文内相对路径图片补站点子目录前缀（与协议详情页一致，兼容 /csgocs 等二级目录部署）
    var base = <?php echo json_encode($siteBasePath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    if (base) {
        $el.find('img').each(function () {
            var src = $(this).attr('src') || '';
            if (src.charAt(0) === '/' && src.charAt(1) !== '/' && src.indexOf(base + '/') !== 0) {
                $(this).attr('src', base + src);
            }
        });
    }

    aiSummarizeAnnouncement();
});
</script>

<?php include 'foot.php'; ?>
