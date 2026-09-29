<?php
/**
 * 用户反馈（留言）管理 —— 独立页面（含「反馈记录」「反馈配置」两个 Tab）
 * ------------------------------------------------------------------
 * 数据表 nteam_contact_msgs，包含两个来源：
 *   - 用户端「联系我们」提交的反馈：source=user，带 user_id，用户可在用户端查看处理进度；
 *   - 官网「联系我们」表单的访客留言：source=site，user_id=0。
 *
 * 处理状态 handle_status：0 待处理、1 处理中、2 已解决、3 已驳回、4 已忽略；
 * 原有 status 字段继续表示管理员「已读 / 未读」，处理时若状态不是「待处理」会自动标记已读；
 * 处理备注（remark）用户端可见。
 *
 * 两个 Tab（与「官网配置」等页面一致，通过 ?tab= 切换）：
 *   - 反馈记录（?tab=list，默认）：筛选、处理、标已读、删除；
 *   - 反馈配置（?tab=config）     ：联系我们 / 意见反馈表单的两个开关
 *       contact_form_open    = 留言 / 反馈提交开关（关闭后三端都不能提交新反馈）；
 *       contact_form_captcha = 提交前人机验证开关（启用后三端提交前都需通过滑块 / 图片验证码）。
 *     这两个开关原先在「官网配置」中维护，已迁移到本页「反馈配置」Tab。
 */
$mod = 'admin';
$title = '用户反馈';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}
require_once('../funsion/ContactMsgs.php');
include('../Common/home_config.php');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ===== 当前 Tab：反馈记录（默认）/ 反馈配置 =====
$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'config') ? 'config' : 'list';

// ===== 反馈配置 Tab 的开关（未配置时按启用处理，与用户端 homeCfg($k, 1) 口径一致） =====
if (!function_exists('fbCfgOn')) {
    function fbCfgOn($k, $d = 1) {
        $v = homeCfg($k, $d);
        return intval($v === '' || $v === null ? $d : $v) === 1;
    }
}
$fcFormOpen    = fbCfgOn('contact_form_open', 1);
$fcFormCaptcha = fbCfgOn('contact_form_captcha', 1);

/**
 * 列表用的紧凑时间格式
 * 同年只显示「月-日 时:分」，跨年显示完整日期，避免列表里重复出现年份把列撑宽；
 * 时间非法（空 / 0000-00-00 / 1970-01-01）返回空字符串，模板据此决定是否展示该行。
 */
function fbShortTime($t) {
    $t = trim((string)$t);
    if ($t === '' || strpos($t, '0000-00-00') === 0 || strpos($t, '1970-01-01') === 0) {
        return '';
    }
    $ts = strtotime($t);
    if (!$ts || $ts <= 0) {
        return '';
    }
    return date(date('Y', $ts) === date('Y') ? 'm-d H:i' : 'Y-m-d H:i', $ts);
}

// 数据表 / 反馈字段是否就绪（字段缺失时自动补齐）
$tablesReady = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_contact_msgs LIMIT 1");
    if ($chk) $tablesReady = true;
} catch (Exception $e) {
    $tablesReady = false;
}
$msSchemaOk = $tablesReady ? contactMsgEnsureSchema($DB) : false;

// ===== 筛选条件：来源 + 处理状态 =====
$fbSource = (isset($_GET['source']) && in_array($_GET['source'], array('user', 'site'), true)) ? $_GET['source'] : '';
$fbStatus = (isset($_GET['mstatus']) && contactMsgStatusValid($_GET['mstatus'])) ? intval($_GET['mstatus']) : -1;

$pageSize = 15;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $pageSize;

$msgs = array();
$total = 0;
$countsStatus = array();
$countsSource = array('user' => 0, 'site' => 0);
if ($tablesReady) {
    $where = array();
    if ($msSchemaOk) {
        if ($fbSource !== '') $where[] = "m.`source`='" . addslashes($fbSource) . "'";
        if ($fbStatus >= 0)   $where[] = "m.`handle_status`='" . $fbStatus . "'";
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $total = (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_contact_msgs m" . $whereSql);
    if ($msSchemaOk) {
        // 关联提交人账号：用户端来源展示昵称 / 账号 / 邮箱 / QQ
        $msgs = $DB->getAll("SELECT m.*, u.username, u.nickname, u.email AS user_email, u.qq AS user_qq
            FROM nteam_contact_msgs m LEFT JOIN nteam_users u ON u.id = m.user_id" . $whereSql . "
            ORDER BY m.`id` DESC LIMIT $offset, $pageSize") ?: [];
        // 各处理状态数量
        foreach (contactMsgStatusMap() as $sv => $st) { $countsStatus[$sv] = 0; }
        foreach (($DB->getAll("SELECT `handle_status`, COUNT(*) AS c FROM nteam_contact_msgs GROUP BY `handle_status`") ?: []) as $cr) {
            $sv = intval($cr['handle_status']);
            if (array_key_exists($sv, $countsStatus)) $countsStatus[$sv] = intval($cr['c']);
        }
        // 各来源数量
        foreach (($DB->getAll("SELECT `source`, COUNT(*) AS c FROM nteam_contact_msgs GROUP BY `source`") ?: []) as $cr) {
            $sk = (string)$cr['source'];
            if (array_key_exists($sk, $countsSource)) $countsSource[$sk] = intval($cr['c']);
        }
    } else {
        // 未升级环境：按原有字段展示，不提供来源 / 状态筛选
        $msgs = $DB->getAll("SELECT m.* FROM nteam_contact_msgs m" . $whereSql . " ORDER BY m.`id` DESC LIMIT $offset, $pageSize") ?: [];
        $countsSource['site'] = $total;
    }
}
$totalPages = max(1, (int)ceil($total / $pageSize));
$totalAll   = $countsSource['user'] + $countsSource['site'];

/**
 * 带当前筛选条件的链接
 */
function fbUrl($params = array()) {
    global $fbSource, $fbStatus;
    $q = array();
    if ($fbSource !== '') $q['source'] = $fbSource;
    if ($fbStatus >= 0)   $q['mstatus'] = $fbStatus;
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return 'user_feedback.php' . ($q ? '?' . http_build_query($q) : '');
}

/**
 * 分页条
 */
function fbPager($page, $pages) {
    if ($pages <= 1) return '';
    $html = '<nav class="mt-3"><ul class="pagination justify-content-center flex-wrap">';
    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . fbUrl(array('page' => $page - 1)) . '">上一页</a></li>';
    }
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . fbUrl(array('page' => $i)) . '">' . $i . '</a></li>';
    }
    if ($page < $pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . fbUrl(array('page' => $page + 1)) . '">下一页</a></li>';
    }
    return $html . '</ul></nav>';
}

/**
 * 行内操作按钮：处理（弹窗改状态 + 备注）/ 标已读 / 删除
 * 状态与备注通过 data-* 传给前端弹窗，避免拼接 JS 字符串的转义问题；
 * 按钮统一包在 .msg-actions 里（flex + nowrap），PC 表格中三个按钮保持一行不换行。
 */
if (!function_exists('contactMsgActions')) {
    function contactMsgActions($m) {
        $id     = intval($m['id']);
        $status = isset($m['handle_status']) ? intval($m['handle_status']) : 0;
        $remark = isset($m['remark']) ? (string)$m['remark'] : '';
        $html = '<div class="msg-actions">';
        $html .= '<button type="button" class="btn btn-sm btn-alt-primary msg-handle"'
            . ' data-id="' . $id . '" data-status="' . $status . '"'
            . ' data-remark="' . h($remark) . '">处理</button>';
        if (intval($m['status']) === 0) {
            $html .= '<button type="button" class="btn btn-sm btn-alt-secondary"'
                . ' onclick="postAct(\'contact_msg_read\', {id: ' . $id . '})">标已读</button>';
        }
        $html .= '<button type="button" class="btn btn-sm btn-alt-danger"'
            . ' onclick="delRow(\'contact_msg_del\', ' . $id . ', \'确认删除该留言？\')">删除</button>';
        $html .= '</div>';
        return $html;
    }
}
?>
<style>
/* ===== 移动端表格 → 卡片适配（与 home_config.php / academic_accounts.php 约定一致） ===== */
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

/* ===== PC 表格：列宽与换行控制 ===== */
/* 表头不折行；时间列整列 nowrap，避免日期与时分被拆成两行 */
.fb-table th { white-space: nowrap; }
.fb-table .fb-cell-time { white-space: nowrap; }
/* 联系方式里的长邮箱允许断行，避免撑破该列 */
.fb-table .fb-cell-contact { word-break: break-all; }
/* 处理备注限宽，长文本不至于把「主题 / 内容」列挤没 */
.fb-table .fb-cell-remark { max-width: 420px; word-break: break-word; }

/* 操作按钮组：默认一行排列，按钮之间等距 */
.msg-actions {
    display: inline-flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 0.25rem;
}
.msg-actions .btn { white-space: nowrap; }
/* 移动端卡片：按钮组空间不足时允许换行 */
@media (max-width: 768px) {
    .mobile-card-actions .msg-actions { flex-wrap: wrap; }
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

/* ===== 反馈配置 Tab：开关卡片（与后台其它配置页观感一致） ===== */
.fc-switch-card {
    border: 1px solid #e9ecf3;
    border-radius: .5rem;
    padding: 1rem 1.15rem;
    height: 100%;
    background: #fcfdff;
}
.fc-switch-title { font-weight: 600; margin-bottom: .25rem; }
.fc-switch-desc { font-size: .8125rem; color: #8a94a6; line-height: 1.7; }
.fc-switch-body { margin-top: .75rem; }
.fc-state { font-size: .8125rem; }
#page-container.dark-mode .fc-switch-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1;
}
#page-container.dark-mode .fc-switch-desc { color: #94a3b8 !important; }
</style>

<div id="pjax-container">
    <div class="content animated fadeIn">

        <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle me-2"></i>
            尚未检测到留言数据表 <code>nteam_contact_msgs</code>，请先导入官网配置相关建表 SQL。
        </div>
        <?php else: ?>

        <?php if (!$msSchemaOk): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle me-2"></i>
            当前数据表缺少反馈功能的字段（<code>user_id / source / handle_status / remark / update_time</code>）。
            请确认数据库账号具备 <code>ALTER</code> 权限，或手动导入 <code>php/contact_msgs_update.sql</code> 后刷新本页。
        </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mt-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'list' ? 'active' : ''; ?>" href="?tab=list">
                    <i class="fa fa-comments me-1"></i>反馈记录
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'config' ? 'active' : ''; ?>" href="?tab=config">
                    <i class="fa fa-sliders-h me-1"></i>反馈配置
                </a>
            </li>
        </ul>

        <?php if ($activeTab === 'list'): ?>
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-comments me-2"></i>用户反馈</h3>
                <div class="block-options d-flex align-items-center gap-2">
                    <span class="badge bg-primary">共 <?php echo $totalAll; ?> 条</span>
                    <?php if ($msSchemaOk && isset($countsStatus[0])): ?>
                    <span class="badge bg-warning">待处理 <?php echo intval($countsStatus[0]); ?></span>
                    <?php endif; ?>
                </div>
                <div class="block-options">
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">

                <?php if ($msSchemaOk): ?>
                <!-- 来源筛选 -->
                <div class="mb-2 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted fs-sm">来源</span>
                    <a class="btn btn-sm <?php echo $fbSource === '' ? 'btn-primary' : 'btn-alt-secondary'; ?>" href="<?php echo fbUrl(array('source' => null, 'page' => null)); ?>">
                        全部 (<?php echo $totalAll; ?>)
                    </a>
                    <a class="btn btn-sm <?php echo $fbSource === 'user' ? 'btn-primary' : 'btn-alt-secondary'; ?>" href="<?php echo fbUrl(array('source' => 'user', 'page' => null)); ?>">
                        用户端 (<?php echo intval($countsSource['user']); ?>)
                    </a>
                    <a class="btn btn-sm <?php echo $fbSource === 'site' ? 'btn-primary' : 'btn-alt-secondary'; ?>" href="<?php echo fbUrl(array('source' => 'site', 'page' => null)); ?>">
                        官网留言 (<?php echo intval($countsSource['site']); ?>)
                    </a>
                </div>
                <!-- 处理状态筛选 -->
                <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted fs-sm">状态</span>
                    <a class="btn btn-sm <?php echo $fbStatus < 0 ? 'btn-primary' : 'btn-alt-secondary'; ?>" href="<?php echo fbUrl(array('mstatus' => null, 'page' => null)); ?>">全部</a>
                    <?php foreach (contactMsgStatusMap() as $sv => $st): ?>
                    <a class="btn btn-sm <?php echo $fbStatus === intval($sv) ? 'btn-primary' : 'btn-alt-secondary'; ?>" href="<?php echo fbUrl(array('mstatus' => intval($sv), 'page' => null)); ?>">
                        <?php echo h($st); ?> (<?php echo intval(isset($countsStatus[$sv]) ? $countsStatus[$sv] : 0); ?>)
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mobile-cards">
                    <?php if (empty($msgs)): ?>
                    <div class="text-center text-muted py-3">暂无数据</div>
                    <?php else: foreach ($msgs as $m): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <span>#<?php echo intval($m['id']); ?> <?php echo h($m['subject'] !== '' ? $m['subject'] : '（无主题）'); ?></span>
                            <span>
                                <?php if ($msSchemaOk): ?>
                                <span class="badge <?php echo contactMsgStatusClass(isset($m['handle_status']) ? $m['handle_status'] : 0); ?>"><?php echo h(contactMsgStatusLabel(isset($m['handle_status']) ? $m['handle_status'] : 0)); ?></span>
                                <?php endif; ?>
                                <?php echo intval($m['status']) ? '<span class="badge bg-light text-muted">已读</span>' : '<span class="badge bg-danger">未读</span>'; ?>
                            </span>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">提交人</span>
                                <span class="mobile-card-value">
                                    <?php if (intval($m['user_id']) > 0): ?>
                                    用户 #<?php echo intval($m['user_id']); ?>
                                    <?php echo h(!empty($m['nickname']) ? $m['nickname'] : (isset($m['username']) ? $m['username'] : '')); ?>
                                    <?php else: ?>
                                    <?php echo h($m['name']); ?>（官网访客）
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">手机</span>
                                <span class="mobile-card-value"><?php echo h($m['phone']); ?></span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">邮箱</span>
                                <span class="mobile-card-value">
                                    <?php echo h($m['email']); ?>
                                    <?php if (intval($m['user_id']) > 0 && !empty($m['user_email']) && $m['email'] !== $m['user_email']): ?>
                                    <br><?php echo h($m['user_email']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (intval($m['user_id']) > 0 && !empty($m['user_qq'])): ?>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">QQ</span>
                                <span class="mobile-card-value"><?php echo h($m['user_qq']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">内容</span>
                                <span class="mobile-card-value"><?php echo nl2br(h($m['content'])); ?></span>
                            </div>
                            <?php if (!empty($m['remark'])): ?>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">备注</span>
                                <span class="mobile-card-value"><?php echo nl2br(h($m['remark'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">时间</span>
                                <span class="mobile-card-value">
                                    <?php echo h($m['create_time']); ?>
                                    <?php $updShort = fbShortTime($m['update_time']); ?>
                                    <?php if ($updShort !== ''): ?>
                                    <br><span class="text-muted" title="更新于 <?php echo h($m['update_time']); ?>">更新 <?php echo h($updShort); ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="mobile-card-actions"><?php echo contactMsgActions($m); ?></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-vcenter fb-table">
                        <thead><tr>
                            <th style="width:52px">ID</th>
                            <th style="width:118px">提交人</th>
                            <th style="width:158px">联系方式</th>
                            <th>主题 / 内容</th>
                            <th style="width:80px">来源</th>
                            <th style="width:96px">处理状态</th>
                            <th style="width:176px">时间</th>
                            <th style="width:190px">操作</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($msgs)): ?>
                            <tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>
                        <?php else: foreach ($msgs as $m): ?>
                            <tr>
                                <td><?php echo intval($m['id']); ?></td>
                                <td>
                                    <?php if (intval($m['user_id']) > 0): ?>
                                    <div class="fw-semibold"><?php echo h(!empty($m['nickname']) ? $m['nickname'] : (isset($m['username']) ? $m['username'] : '')); ?></div>
                                    <div class="fs-sm text-muted">用户 #<?php echo intval($m['user_id']); ?><?php echo isset($m['username']) && $m['username'] !== '' ? ' · ' . h($m['username']) : ''; ?></div>
                                    <?php else: ?>
                                    <div class="fw-semibold"><?php echo h($m['name']); ?></div>
                                    <div class="fs-sm text-muted">官网访客</div>
                                    <?php endif; ?>
                                </td>
                                <td class="fs-sm fb-cell-contact">
                                    <?php echo $m['phone'] !== '' ? h($m['phone']) . '<br>' : ''; ?>
                                    <?php echo h($m['email']); ?>
                                    <?php if (intval($m['user_id']) > 0 && !empty($m['user_email']) && $m['user_email'] !== $m['email']): ?>
                                    <br><span class="text-muted">账号邮箱：<?php echo h($m['user_email']); ?></span>
                                    <?php endif; ?>
                                    <?php if (intval($m['user_id']) > 0 && !empty($m['user_qq'])): ?>
                                    <br><span class="text-muted">QQ：<?php echo h($m['user_qq']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo h($m['subject']); ?></div>
                                    <div class="fs-sm text-muted"><?php echo nl2br(h($m['content'])); ?></div>
                                    <?php if (!empty($m['remark'])): ?>
                                    <div class="fs-sm text-info mt-1 fb-cell-remark"><i class="fa fa-comment-alt me-1"></i>处理备注：<?php echo nl2br(h($m['remark'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo intval($m['user_id']) > 0
                                        ? '<span class="badge bg-info">用户端</span>'
                                        : '<span class="badge bg-secondary">官网</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($msSchemaOk): ?>
                                    <span class="badge <?php echo contactMsgStatusClass(isset($m['handle_status']) ? $m['handle_status'] : 0); ?>"><?php echo h(contactMsgStatusLabel(isset($m['handle_status']) ? $m['handle_status'] : 0)); ?></span>
                                    <?php endif; ?>
                                    <?php if (intval($m['status']) === 0): ?>
                                    <div class="mt-1"><span class="badge bg-danger">未读</span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fs-sm fb-cell-time">
                                    <?php echo h($m['create_time']); ?>
                                    <?php $updShort = fbShortTime($m['update_time']); ?>
                                    <?php if ($updShort !== ''): ?>
                                    <div class="text-muted" title="更新于 <?php echo h($m['update_time']); ?>">更新 <?php echo h($updShort); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fb-cell-actions"><?php echo contactMsgActions($m); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo fbPager($page, $totalPages); ?>
            </div>
        </div>
        <?php else: ?>
        <!-- ============ 反馈配置：联系我们 / 意见反馈表单的两个开关 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-sliders-h me-2"></i>反馈配置</h3>
                <div class="block-options">
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <p class="text-muted fs-sm mb-4">
                    这里的开关同时作用于<b>官网留言表单</b>、<b>用户网页端（联系我们 / 意见反馈）</b>、
                    <b>UNIAPP 安卓端</b>与<b>微信小程序端</b>的反馈提交，保存后立即生效；
                    反馈记录的处理在<a href="?tab=list">反馈记录</a> Tab 中完成。
                </p>

                <form onsubmit="return saveFeedbackConfig(this)" method="post" name="feedback-config-form">
                    <div class="row g-3">
                        <!-- 留言 / 反馈提交开关 -->
                        <div class="col-md-6">
                            <div class="fc-switch-card">
                                <div class="fc-switch-title">
                                    <i class="fa fa-comment-dots me-1 text-primary"></i>联系我们表单提交开关
                                </div>
                                <div class="fc-switch-desc">
                                    关闭后三端均不能提交新的反馈 / 留言；
                                    历史反馈记录与页面展示的联系方式不受影响。
                                </div>
                                <div class="fc-switch-body">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="contact_form_open" value="0" <?php echo $fcFormOpen ? '' : 'checked'; ?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="contact_form_open" value="1" <?php echo $fcFormOpen ? 'checked' : ''; ?>>启用
                                        </label>
                                    </div>
                                    <div class="fc-state text-muted mt-1">
                                        当前：<?php echo $fcFormOpen
                                            ? '<span class="text-success">已启用，允许提交反馈</span>'
                                            : '<span class="text-danger">已禁用，三端均不能提交反馈</span>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 提交前人机验证开关 -->
                        <div class="col-md-6">
                            <div class="fc-switch-card">
                                <div class="fc-switch-title">
                                    <i class="fa fa-shield-alt me-1 text-primary"></i>提交前滑块 / 验证码验证
                                </div>
                                <div class="fc-switch-desc">
                                    启用后，提交反馈前必须先通过人机验证：
                                    网页端与 App 端优先滑块验证（异常可改用图片验证码），小程序端使用图片验证码；
                                    关闭后所有端提交均免验证。
                                </div>
                                <div class="fc-switch-body">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="contact_form_captcha" value="0" <?php echo $fcFormCaptcha ? '' : 'checked'; ?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="contact_form_captcha" value="1" <?php echo $fcFormCaptcha ? 'checked' : ''; ?>>启用
                                        </label>
                                    </div>
                                    <div class="fc-state text-muted mt-1">
                                        当前：<?php echo $fcFormCaptcha
                                            ? '<span class="text-success">已启用，提交前需完成人机验证</span>'
                                            : '<span class="text-danger">已禁用，提交前无需验证</span>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-4 fs-sm">
                        <i class="fa fa-info-circle me-1"></i>
                        提示：人机验证开关被关闭时，反馈提交接口不再校验验证码参数；
                        开启后，未携带有效验证结果的提交会被后端直接拒绝。
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                        </button>
                        <a class="btn btn-alt-secondary ms-2" href="?tab=list">
                            <i class="fa fa-comments me-1"></i>查看反馈记录
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
<?php include 'foot.php'?>
<script>
// ===== 与其它后台页保持一致的提交方式：layer 加载 → Ajax.php → layer.alert → 刷新 =====
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
                layer.alert(data.msg, {icon: 1, closeBtn: false}, function () {
                    window.location.reload();
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
function delRow(act, id, tip) {
    layer.confirm(tip || '确认删除？', {icon: 3, title: '提示'}, function (index) {
        layer.close(index);
        postAct(act, {id: id});
    });
    return false;
}

// ===== 反馈配置 Tab：保存「联系我们表单提交开关」与「提交前人机验证」两个开关 =====
function saveFeedbackConfig(obj) {
    return postAct('feedback_config_save', $(obj).serialize());
}

// ===== 处理弹窗：处理状态 + 处理备注（备注用户端可见） =====
var MS_STATUS_MAP = <?php echo json_encode(contactMsgStatusMap(), JSON_UNESCAPED_UNICODE); ?>;

function openMsgHandle(id, status, remark) {
    var opts = '';
    for (var k in MS_STATUS_MAP) {
        if (!MS_STATUS_MAP.hasOwnProperty(k)) continue;
        opts += '<option value="' + k + '"' + (String(k) === String(status) ? ' selected' : '') + '>'
            + MS_STATUS_MAP[k] + '</option>';
    }
    var html = '<div style="padding:18px 20px;">'
        + '<div class="mb-2 fw-semibold">处理状态</div>'
        + '<select id="msgHandleStatus" class="form-select mb-3">' + opts + '</select>'
        + '<div class="mb-2 fw-semibold">处理备注 <span class="text-muted fs-sm">（用户端可见，选填）</span></div>'
        + '<textarea id="msgHandleRemark" class="form-control" rows="3" maxlength="500"'
        + ' placeholder="如：已修复，请更新到最新版本后重试"></textarea>'
        + '</div>';
    layer.open({
        type: 1,
        title: '处理 #' + id,
        area: ['460px', 'auto'],
        content: html,
        btn: ['保存', '取消'],
        success: function (layero) {
            // 备注用 value 赋值，避免拼 HTML 造成的转义问题
            var ta = layero.find('#msgHandleRemark').get(0) || document.getElementById('msgHandleRemark');
            if (ta) ta.value = remark || '';
        },
        yes: function (index) {
            var s = $('#msgHandleStatus').val();
            var r = $('#msgHandleRemark').val();
            layer.close(index);
            postAct('contact_msg_status', {id: id, handle_status: s, remark: r});
        }
    });
}

$(document).on('click', '.msg-handle', function () {
    var b = $(this);
    openMsgHandle(b.data('id'), b.data('status'), b.attr('data-remark') || '');
    return false;
});
</script>
</body>
</html>
