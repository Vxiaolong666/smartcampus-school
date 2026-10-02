<?php
$mod = 'admin';
$title = '官网配置';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}
include('../Common/home_config.php');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// 检测官网配置相关数据表是否已导入
$tablesReady = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_home_config LIMIT 1");
    if ($chk) $tablesReady = true;
} catch (Exception $e) {
    $tablesReady = false;
}

$cfg = $tablesReady ? homeConfig() : array();

function cfgVal($cfg, $k, $d = '') {
    return isset($cfg[$k]) && $cfg[$k] !== '' && $cfg[$k] !== null ? $cfg[$k] : $d;
}
function isOn($cfg, $k, $d = 1) {
    return intval(cfgVal($cfg, $k, $d)) === 1;
}

// 编辑态
$editDl = null;
if ($tablesReady && isset($_GET['edit_dl'])) {
    $eid = intval($_GET['edit_dl']);
    $rs = @$DB->query("SELECT * FROM nteam_home_download WHERE `id`='{$eid}'");
    if ($rs) $editDl = $rs->fetch();
}
$editFaq = null;
if ($tablesReady && isset($_GET['edit_faq'])) {
    $eid = intval($_GET['edit_faq']);
    $rs = @$DB->query("SELECT * FROM nteam_home_faq WHERE `id`='{$eid}'");
    if ($rs) $editFaq = $rs->fetch();
}

// ===== 列表分页（每个 tab 使用独立页码） =====
$pageSize = 15;
$dlPage = isset($_GET['dpage']) ? max(1, intval($_GET['dpage'])) : 1;
$fqPage = isset($_GET['fpage']) ? max(1, intval($_GET['fpage'])) : 1;
$dlOffset = ($dlPage - 1) * $pageSize;
$fqOffset = ($fqPage - 1) * $pageSize;

$downloads = []; $downloadTotal = 0;
$faqs      = []; $faqTotal = 0;
if ($tablesReady) {
    $downloadTotal = (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_home_download");
    $downloads = $DB->getAll("SELECT * FROM nteam_home_download ORDER BY `sort` ASC, `id` ASC LIMIT $dlOffset, $pageSize") ?: [];
    $faqTotal = (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_home_faq");
    $faqs = $DB->getAll("SELECT * FROM nteam_home_faq ORDER BY `sort` ASC, `id` ASC LIMIT $fqOffset, $pageSize") ?: [];
}

// 当前 tab（编辑态强制定位到对应 tab）
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'site';
if ($editDl)  $activeTab = 'download';
if ($editFaq) $activeTab = 'faq';

// 分页渲染辅助（与 cancel_config 的 cancelPager 一致）
if (!function_exists('homePager')) {
    function homePager($page, $pages, $base) {
        if ($pages <= 1) return '';
        $html = '<nav class="mt-3"><ul class="pagination justify-content-center flex-wrap">';
        if ($page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . ($page - 1) . '">上一页</a></li>';
        }
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $page) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base . $i . '">' . $i . '</a></li>';
        }
        if ($page < $pages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . ($page + 1) . '">下一页</a></li>';
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
?>
<style>
/* ===== 移动端表格 → 卡片适配（与 academic_accounts.php 约定一致） ===== */
@media (max-width: 768px) {
    .table-responsive { display: none !important; }
    .mobile-cards { display: block !important; }
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
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
    }
    .mobile-card-body { font-size: 0.875rem; }
    .mobile-card-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.375rem 0;
        border-bottom: 1px dashed #f0f0f0;
    }
    .mobile-card-row:last-child { border-bottom: none; }
    .mobile-card-label { color: #6b7280; font-weight: 500; flex: 0 0 auto; }
    .mobile-card-value { text-align: right; word-break: break-word; }
    .mobile-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }
}
@media (min-width: 769px) {
    .mobile-cards { display: none !important; }
}
#page-container.dark-mode .mobile-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1;
}
#page-container.dark-mode .mobile-card-header,
#page-container.dark-mode .mobile-card-row {
    border-color: #334155 !important;
}
#page-container.dark-mode .mobile-card-label { color: #94a3b8 !important; }
</style>
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
                            <a class="link-fx" href="javascript:void(0)">后台首页</a>
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

        <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle me-2"></i>
            尚未检测到官网配置相关数据表，请先导入建表 SQL（<code>nteam_home_config / nteam_home_download / nteam_home_faq / nteam_contact_msgs</code>）后再配置。
        </div>
        <?php endif; ?>

        <?php if ($tablesReady): ?>

        <ul class="nav nav-tabs mt-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'site' ? 'active' : ''; ?>" href="?tab=site">网站设置</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'download' ? 'active' : ''; ?>" href="?tab=download">下载方式</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'faq' ? 'active' : ''; ?>" href="?tab=faq">常见问题</a>
            </li>
        </ul>

        <?php if ($activeTab === 'site'): ?>
        <!-- ============ 网站地址与联系方式 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-address-card me-2"></i>网站地址与联系方式</h3>
                <div class="block-options">
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form onsubmit="return saveHomeConfig(this)" method="post" name="edit-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="site_url"><b>网站地址</b></label>
                                <input type="text" class="form-control form-control-lg" id="site_url" name="site_url" value="<?php echo h(cfgVal($cfg,'site_url')); ?>" placeholder="请输入网站地址，如 https://www.example.com">
                                <small class="text-muted">用于页脚「网址」展示，留空则使用网站配置中的域名</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="contact_email"><b>联系邮箱</b></label>
                                <input type="text" class="form-control form-control-lg" id="contact_email" name="contact_email" value="<?php echo h(cfgVal($cfg,'contact_email')); ?>" placeholder="请输入联系邮箱">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label" for="contact_phone"><b>联系电话</b></label>
                                <input type="text" class="form-control form-control-lg" id="contact_phone" name="contact_phone" value="<?php echo h(cfgVal($cfg,'contact_phone')); ?>" placeholder="请输入联系电话">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label" for="contact_qq"><b>联系 QQ</b></label>
                                <input type="text" class="form-control form-control-lg" id="contact_qq" name="contact_qq" value="<?php echo h(cfgVal($cfg,'contact_qq')); ?>" placeholder="请输入联系 QQ">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label" for="contact_worktime"><b>工作时间</b></label>
                                <input type="text" class="form-control form-control-lg" id="contact_worktime" name="contact_worktime" value="<?php echo h(cfgVal($cfg,'contact_worktime')); ?>" placeholder="如：周一至周五 9:00-18:00">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="contact_address"><b>公司地址</b></label>
                                <input type="text" class="form-control form-control-lg" id="contact_address" name="contact_address" value="<?php echo h(cfgVal($cfg,'contact_address')); ?>" placeholder="请输入公司地址">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($activeTab === 'download'): ?>
        <!-- ============ 下载方式 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-download me-2"></i>下载方式</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary me-2" id="dlAddBtn" data-add-label="添加下载方式" onclick="toggleAddForm('dl')">
                        <?php if ($editDl): ?><i class="fa fa-times me-1"></i>收起表单<?php else: ?><i class="fa fa-plus me-1"></i>添加下载方式<?php endif; ?>
                    </button>
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <!-- 添加 / 编辑表单默认收起，点击右上角「添加下载方式」后展开；编辑态（?edit_dl=）由服务端直接展开 -->
                <form id="dlAddForm" onsubmit="return saveDownload(this)" method="post" name="dl-form" style="display:<?php echo $editDl ? 'block' : 'none'; ?>;">
                    <input type="hidden" name="id" value="<?php echo $editDl ? intval($editDl['id']) : 0; ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label" for="dl_title"><b>名称</b></label>
                                <input type="text" class="form-control form-control-lg" id="dl_title" name="title" required value="<?php echo h($editDl ? $editDl['title'] : ''); ?>" placeholder="如 Android APK">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-4">
                                <label class="form-label" for="dl_url"><b>下载地址</b></label>
                                <input type="text" class="form-control form-control-lg" id="dl_url" name="url" required value="<?php echo h($editDl ? $editDl['url'] : ''); ?>" placeholder="请输入下载地址">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-4">
                                <label class="form-label" for="dl_icon"><b>图标 class</b></label>
                                <input type="text" class="form-control form-control-lg" id="dl_icon" name="icon" value="<?php echo h($editDl ? $editDl['icon'] : ''); ?>" placeholder="fab fa-android">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-4">
                                <label class="form-label" for="dl_sort"><b>排序</b></label>
                                <input type="number" class="form-control form-control-lg" id="dl_sort" name="sort" value="<?php echo $editDl ? intval($editDl['sort']) : 0; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <label class="form-label" for="dl_desc"><b>说明</b></label>
                                <input type="text" class="form-control form-control-lg" id="dl_desc" name="description" value="<?php echo h($editDl ? $editDl['description'] : ''); ?>" placeholder="请输入说明">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label"><b>状态</b></label>
                                <div class="space-x-2">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="status" value="0" <?php echo ($editDl && !intval($editDl['status'])) ? 'checked' : ''; ?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="status" value="1" <?php echo (!$editDl || intval($editDl['status'])) ? 'checked' : ''; ?>>启用
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b><?php echo $editDl ? '保存修改' : '新增下载方式'; ?></b>
                        </button>
                        <?php if ($editDl): ?>
                        <a href="?tab=download" class="btn btn-alt-secondary">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mobile-cards">
                    <?php if (empty($downloads)): ?>
                    <div class="text-center text-muted py-3">暂无数据</div>
                    <?php else: foreach ($downloads as $d): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <span>#<?php echo intval($d['id']); ?> <?php echo h($d['title']); ?></span>
                            <?php echo intval($d['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>'; ?>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">下载地址</span>
                                <span class="mobile-card-value"><a href="<?php echo h($d['url']); ?>" target="_blank" rel="noopener"><?php echo h($d['url']); ?></a></span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">排序</span>
                                <span class="mobile-card-value"><?php echo intval($d['sort']); ?></span>
                            </div>
                        </div>
                        <div class="mobile-card-actions">
                            <a href="home_config.php?edit_dl=<?php echo intval($d['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                            <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('home_download_del', <?php echo intval($d['id']); ?>, '确认删除该下载方式？')">删除</button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead><tr><th style="width:60px">ID</th><th>名称</th><th>下载地址</th><th style="width:80px">排序</th><th style="width:90px">状态</th><th style="width:150px">操作</th></tr></thead>
                        <tbody>
                        <?php if (empty($downloads)): ?>
                            <tr><td colspan="6" class="text-center text-muted">暂无数据</td></tr>
                        <?php else: foreach ($downloads as $d): ?>
                            <tr>
                                <td><?php echo intval($d['id']); ?></td>
                                <td><?php echo h($d['title']); ?></td>
                                <td class="fs-sm"><a href="<?php echo h($d['url']); ?>" target="_blank" rel="noopener"><?php echo h($d['url']); ?></a></td>
                                <td><?php echo intval($d['sort']); ?></td>
                                <td><?php echo intval($d['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>'; ?></td>
                                <td>
                                    <a href="home_config.php?edit_dl=<?php echo intval($d['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('home_download_del', <?php echo intval($d['id']); ?>, '确认删除该下载方式？')">删除</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo homePager($dlPage, max(1, ceil($downloadTotal / $pageSize)), '?tab=download&dpage='); ?>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($activeTab === 'faq'): ?>
        <!-- ============ 常见问题 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-question-circle me-2"></i>常见问题配置</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary me-2" id="faqAddBtn" data-add-label="添加问题" onclick="toggleAddForm('faq')">
                        <?php if ($editFaq): ?><i class="fa fa-times me-1"></i>收起表单<?php else: ?><i class="fa fa-plus me-1"></i>添加问题<?php endif; ?>
                    </button>
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <!-- 添加 / 编辑表单默认收起，点击右上角「添加问题」后展开；编辑态（?edit_faq=）由服务端直接展开 -->
                <form id="faqAddForm" onsubmit="return saveFaq(this)" method="post" name="faq-form" style="display:<?php echo $editFaq ? 'block' : 'none'; ?>;">
                    <input type="hidden" name="id" value="<?php echo $editFaq ? intval($editFaq['id']) : 0; ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label" for="faq_cat"><b>分类</b></label>
                                <input type="text" class="form-control form-control-lg" id="faq_cat" name="category" value="<?php echo h($editFaq ? $editFaq['category'] : '常见问题'); ?>" placeholder="如 账号与登录">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-4">
                                <label class="form-label" for="faq_q"><b>问题</b></label>
                                <input type="text" class="form-control form-control-lg" id="faq_q" name="question" required value="<?php echo h($editFaq ? $editFaq['question'] : ''); ?>" placeholder="请输入问题">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-4">
                                <label class="form-label" for="faq_sort"><b>排序</b></label>
                                <input type="number" class="form-control form-control-lg" id="faq_sort" name="sort" value="<?php echo $editFaq ? intval($editFaq['sort']) : 0; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <label class="form-label" for="faq_a"><b>答案</b></label>
                                <textarea class="form-control form-control-lg" id="faq_a" name="answer" rows="4" placeholder="请输入答案（支持简单 HTML）"><?php echo h($editFaq ? $editFaq['answer'] : ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label"><b>状态</b></label>
                                <div class="space-x-2">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="status" value="0" <?php echo ($editFaq && !intval($editFaq['status'])) ? 'checked' : ''; ?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="status" value="1" <?php echo (!$editFaq || intval($editFaq['status'])) ? 'checked' : ''; ?>>启用
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b><?php echo $editFaq ? '保存修改' : '新增问题'; ?></b>
                        </button>
                        <?php if ($editFaq): ?>
                        <a href="?tab=faq" class="btn btn-alt-secondary">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mobile-cards">
                    <?php if (empty($faqs)): ?>
                    <div class="text-center text-muted py-3">暂无数据</div>
                    <?php else: foreach ($faqs as $f): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <span>#<?php echo intval($f['id']); ?> <?php echo h($f['question']); ?></span>
                            <?php echo intval($f['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>'; ?>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">分类</span>
                                <span class="mobile-card-value"><?php echo h($f['category']); ?></span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">排序</span>
                                <span class="mobile-card-value"><?php echo intval($f['sort']); ?></span>
                            </div>
                        </div>
                        <div class="mobile-card-actions">
                            <a href="home_config.php?edit_faq=<?php echo intval($f['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                            <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('home_faq_del', <?php echo intval($f['id']); ?>, '确认删除该问题？')">删除</button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead><tr><th style="width:60px">ID</th><th style="width:140px">分类</th><th>问题</th><th style="width:80px">排序</th><th style="width:90px">状态</th><th style="width:150px">操作</th></tr></thead>
                        <tbody>
                        <?php if (empty($faqs)): ?>
                            <tr><td colspan="6" class="text-center text-muted">暂无数据</td></tr>
                        <?php else: foreach ($faqs as $f): ?>
                            <tr>
                                <td><?php echo intval($f['id']); ?></td>
                                <td><?php echo h($f['category']); ?></td>
                                <td><?php echo h($f['question']); ?></td>
                                <td><?php echo intval($f['sort']); ?></td>
                                <td><?php echo intval($f['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>'; ?></td>
                                <td>
                                    <a href="home_config.php?edit_faq=<?php echo intval($f['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('home_faq_del', <?php echo intval($f['id']); ?>, '确认删除该问题？')">删除</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo homePager($fqPage, max(1, ceil($faqTotal / $pageSize)), '?tab=faq&fpage='); ?>
            </div>
        </div>

        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
<?php include 'foot.php'?>
<script>
// ===== 与 set.php 保持一致的提交方式：layer 加载 → Ajax.php → layer.alert → 刷新 =====
function postAct(act, data, opts) {
    opts = opts || {};
    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=' + act,
        data: data,
        dataType: 'json',
        success: function (data) {
            layer.close(ii);
            if (data.code == 0) {
                layer.alert(data.msg, {
                    icon: 1,
                    closeBtn: false
                }, function () {
                    if (opts.reload === false) {
                        window.location.href = window.location.href;
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                layer.alert(data.msg, {icon: 2});
            }
        },
        error: function () {
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
    return false;
}
function saveHomeConfig(obj) {
    return postAct('home_config_save', $(obj).serialize());
}
function saveDownload(obj) {
    return postAct('home_download_save', $(obj).serialize());
}
function saveFaq(obj) {
    return postAct('home_faq_save', $(obj).serialize());
}

// ===== 添加表单默认收起，点击「添加…」按钮后才展开 =====
// kind：'dl'（下载方式）、'faq'（常见问题）；编辑态由服务端直接把表单渲染为展开状态
function toggleAddForm(kind) {
    var form = document.getElementById(kind + 'AddForm');
    var btn = document.getElementById(kind + 'AddBtn');
    if (!form) return false;

    var willShow = (form.style.display === 'none' || !form.style.display);
    form.style.display = willShow ? 'block' : 'none';

    if (btn) {
        var label = btn.getAttribute('data-add-label') || '添加';
        btn.innerHTML = willShow
            ? '<i class="fa fa-times me-1"></i>收起表单'
            : '<i class="fa fa-plus me-1"></i>' + label;
    }

    if (willShow) {
        // 展开后定位到表单并聚焦第一个输入框，移动端点击后能直接开始填写
        var first = form.querySelector('input[name="title"], input[name="question"]');
        if (first && first.focus) {
            try { first.focus(); } catch (e) {}
        }
        if (form.scrollIntoView) {
            try { form.scrollIntoView({behavior: 'smooth', block: 'nearest'}); } catch (e) {}
        }
    }
    return false;
}
function delRow(act, id, tip) {
    layer.confirm(tip || '确认删除？', {icon: 3, title: '提示'}, function (index) {
        layer.close(index);
        postAct(act, {id: id});
    });
    return false;
}

</script>
</body>
</html>
