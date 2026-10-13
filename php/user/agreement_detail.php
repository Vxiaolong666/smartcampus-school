<?php
/**
 * 协议详情（网页用户端）：agreement_detail.php?key=xxx
 * ------------------------------------------------------------------
 * 与 uniapp 端 pages/agreement/detail/index.vue + components/agreement-view 对应，
 * 渲染与排版参考公告详情页 user/announcement.php：
 *   1) 正文为后台「协议管理」维护的 Markdown，由本地 marked 解析成 HTML，
 *      再用本地 github-markdown-css + 统一排版规则展示（字号、行距、表格、引用等）；
 *   2) 正文自带的「# 协议名」与页面大标题重复时自动去掉，避免标题重复；
 *   3) 正文内相对路径图片（如 /uploads/agreements/xxx.jpg）自动补站点子目录前缀，
 *      兼容 /csgocs 等二级目录部署；
 *   4) 协议不存在或已停用时给出「当前协议未发布」兜底，不回退任何内置正文。
 */
$mod = 'user';
include('../Common/Core_brain.php');

$akey = isset($_GET['key']) ? trim($_GET['key']) : '';
// 协议标识仅允许字母数字下划线短横线（与后台保存规则一致），非法值直接回列表
if($akey === '' || !preg_match('/^[a-z0-9_-]{1,50}$/', $akey)){
    header('Location: agreement.php');
    exit;
}

require_once('../funsion/Agreements.php');

$agTableReady = agreementTableReady($DB);
$agreement = $agTableReady ? agreementGet($DB, $akey) : null;
// 未发布（不存在 / 已停用）：展示兜底提示
$agNotPublished = (!$agreement || intval($agreement['status']) !== 1);

$agTitle = '';
$agContent = '';
$agUpdated = '';

if(!$agNotPublished){
    $agTitle = (string)$agreement['title'];
    $agContent = (string)$agreement['content'];
    $agUpdated = !empty($agreement['updated_at']) ? date('Y-m-d', strtotime($agreement['updated_at'])) : '';

    // 正文首行是「# 协议名」且与协议标题一致（或包含标题）时去掉，
    // 页面已用大标题展示协议名，重复展示会显得冗余
    $agLines = preg_split("/\r\n|\n|\r/", $agContent);
    if(isset($agLines[0]) && preg_match('/^#\s+(.+)$/u', trim($agLines[0]), $h1Match)){
        $h1Text = trim(str_replace(array('*', '_', '`'), '', $h1Match[1]));
        if($h1Text !== '' && ($h1Text === $agTitle || mb_strpos($h1Text, $agTitle) !== false)){
            array_shift($agLines);
            $agContent = implode("\n", $agLines);
        }
    }

    // 去掉首尾空白：正文若以缩进开头，Markdown 会当成 4 空格代码块渲染
    $agContent = trim($agContent);
}

$title = $agNotPublished ? '协议详情' : $agTitle;
include('./head.php');
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="d-flex mb-3">
                            <a href="agreement.php" class="btn btn-alt-secondary btn-sm">
                                <i class="fa fa-chevron-left me-1"></i>返回协议中心
                            </a>
                        </div>

                        <?php if($agNotPublished): ?>
                        <div class="text-center py-5">
                            <i class="fa fa-exclamation-circle fa-4x text-muted mb-3"></i>
                            <h4><?php echo $agTableReady ? '当前协议未发布' : '协议数据未初始化'; ?></h4>
                            <p class="text-muted mb-0">
                                <?php echo $agTableReady
                                    ? '该协议尚未发布或已停用，如有疑问请联系管理员。'
                                    : '协议数据尚未初始化，请联系管理员执行更新脚本。'; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <article>
                            <header class="mb-4">
                                <h1 class="h2 mb-3"><?php echo htmlspecialchars($agTitle); ?></h1>
                                <div class="text-muted fs-sm d-flex flex-wrap gap-3">
                                    <?php if($agUpdated !== ''): ?>
                                    <span>
                                        <i class="fa fa-clock me-1"></i>最后更新日期：<?php echo $agUpdated; ?>
                                    </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fa fa-building me-1"></i>发布方：<?php echo htmlspecialchars(conf('Name')); ?>
                                    </span>
                                </div>
                            </header>

                            <?php /* 正文紧贴标签输出：模板缩进会被 Markdown 当作 4 空格代码块 */ ?>
                            <div class="content markdown-body" id="agreementContent"><?php echo htmlspecialchars($agContent); ?></div>

                            <div class="alert alert-light mt-4 mb-3 fs-sm">
                                <i class="fa fa-heart text-danger me-1"></i>感谢您信任并使用本服务。如对本协议有疑问，可通过「联系我们」与我们沟通。
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <a class="btn btn-alt-primary btn-sm" href="contact.php">
                                    <i class="fa fa-headset me-1"></i>联系我们
                                </a>
                                <a class="btn btn-alt-secondary btn-sm" href="agreement.php">
                                    <i class="fa fa-chevron-left me-1"></i>返回协议中心
                                </a>
                            </div>
                        </article>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Markdown 渲染样式已本地化（原 jsDelivr CDN 在部分服务器环境不可访问）：github-markdown-css 5.2.0 -->
<link rel="stylesheet" href="./assets/css/plugins/github-markdown/github-markdown.min.css">
<style>
/* ===== 协议正文排版（与公告详情页 user/announcement.php 保持一致的阅读体验） ===== */
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
.markdown-body h1 { font-size: 1.75em; border-bottom: 1px solid #eaecef; padding-bottom: .3em; }
.markdown-body h2 { font-size: 1.35em; border-bottom: 1px solid #eaecef; padding-bottom: .3em; }
.markdown-body h3 { font-size: 1.15em; }
.markdown-body h4 { font-size: 1em; }
.markdown-body p { margin-bottom: 16px; }
.markdown-body ul, .markdown-body ol { padding-left: 2em; margin-bottom: 16px; }
.markdown-body li { margin-bottom: .35em; }
.markdown-body code { padding: .2em .4em; margin: 0; font-size: 85%; background-color: rgba(27,31,35,.05); border-radius: 3px; }
.markdown-body pre { padding: 16px; overflow: auto; font-size: 85%; line-height: 1.45; background-color: #f6f8fa; border-radius: 6px; margin-bottom: 16px; }
.markdown-body pre code { background-color: transparent; padding: 0; }
.markdown-body blockquote { padding: 8px 1em; color: #6a737d; border-left: .25em solid #13c2c2; background: #f8fdfd; border-radius: 0 6px 6px 0; margin-bottom: 16px; }
.markdown-body blockquote p:last-child { margin-bottom: 0; }
.markdown-body table { border-spacing: 0; border-collapse: collapse; margin-bottom: 16px; width: 100%; }
.markdown-body table th, .markdown-body table td { padding: 6px 13px; border: 1px solid #dfe2e5; }
.markdown-body table th { font-weight: 600; background-color: #f6f8fa; }
.markdown-body table tr:nth-child(2n) { background-color: #f6f8fa; }
.markdown-body a { color: #0366d6; text-decoration: none; }
.markdown-body a:hover { text-decoration: underline; }
.markdown-body hr { height: .25em; padding: 0; margin: 24px 0; background-color: #e1e4e8; border: 0; }
/* 协议正文里的图片（如二维码、示意图）：居中、限宽、圆角 */
.markdown-body img {
    display: block;
    max-width: 100%;
    height: auto;
    margin: 0 auto 16px;
    border-radius: 6px;
}
@media (max-width: 768px) {
    .markdown-body { font-size: 14px; line-height: 1.8; }
}
</style>
<!-- Markdown 解析库已本地化：marked v4.3.0 -->
<script src="./assets/js/plugins/marked/marked.min.js"></script>
<script>
$(document).ready(function () {
    var $el = $('#agreementContent');
    if (!$el.length || !window.marked) return;

    // 与公告详情页一致的解析参数：先取纯文本，再交给 marked 解析
    marked.setOptions({ breaks: true, gfm: true });
    // 去掉首尾空白：内容前的缩进会被 Markdown 解析成 4 空格代码块
    // 统一入口 mdToHtml：修正「紧跟段落的 --- 被当成上文的下划线式标题」导致的渲染异常
    var src = $.trim($el.text());
    var html = window.mdToHtml ? window.mdToHtml(src) : marked.parse(src);
    $el.html(html);

    // 正文内相对路径图片补站点子目录前缀（兼容 /csgocs 等二级目录部署）
    var base = <?php echo json_encode($siteBasePath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    if (base) {
        $el.find('img').each(function () {
            var src = $(this).attr('src') || '';
            if (src.charAt(0) === '/' && src.charAt(1) !== '/' && src.indexOf(base + '/') !== 0) {
                $(this).attr('src', base + src);
            }
        });
    }
});
</script>

<?php include 'foot.php'; ?>
