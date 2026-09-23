<?php
$mod = 'admin';
$title = '版本管理';
include('./head.php');
require_once __DIR__ . '/../funsion/LanzouHelper.php';

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

function hv($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// 检测版本表是否已导入
$tablesReady = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_app_versions LIMIT 1");
    if ($chk) $tablesReady = true;
} catch (Exception $e) {
    $tablesReady = false;
}

// 全局开关：开启 App 版本更新检测
$updateEnabled = 0;
try {
    $cfg = $DB->getRow("SELECT app_update_enabled FROM nteam_config WHERE id=1");
    if ($cfg !== null && isset($cfg['app_update_enabled']) && intval($cfg['app_update_enabled']) === 1) {
        $updateEnabled = 1;
    }
} catch (Exception $e) {}

// 版本列表
$versions = array();
$latestCode = 0;
if ($tablesReady) {
    $rs = @$DB->query("SELECT * FROM nteam_app_versions ORDER BY version_code DESC, id DESC");
    if ($rs) {
        while ($r = $rs->fetch()) {
            $versions[] = $r;
            if (intval($r['status']) === 1 && intval($r['version_code']) > $latestCode) {
                $latestCode = intval($r['version_code']);
            }
        }
    }
}

$platformText = array('all' => '全平台', 'android' => 'Android', 'ios' => 'iOS', 'harmony' => '鸿蒙');
?>
<style>
/* ===== 版本列表：移动端表格适配（压缩内边距/字号，操作按钮竖排，保证横向可滑动不挤压） ===== */
.table-responsive {
    -webkit-overflow-scrolling: touch;
}
@media (max-width: 768px) {
    .table-responsive .table th,
    .table-responsive .table td {
        padding: 0.5rem 0.5rem;
        font-size: 0.8125rem;
        white-space: nowrap;
    }
    /* 下载地址列允许换行，避免撑宽表格 */
    .table-responsive .table td:nth-child(6) {
        white-space: normal;
        word-break: break-all;
        min-width: 150px;
    }
    /* 操作列：按钮竖排并撑满，避免两个按钮挤在一行溢出 */
    .table-responsive .table td:last-child {
        min-width: 96px;
    }
    .table-responsive .table td:last-child .btn {
        display: block;
        width: 100%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .table-responsive .table td:last-child .btn + .btn {
        margin-top: 0.375rem;
    }
}
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

            <!-- ============ 全局更新推送开关 ============ -->
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-rocket me-2"></i>更新推送设置</h3>
                </div>
                <div class="block-content pb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="flex-grow-1 pe-2">
                            <div class="fw-semibold">开启 App 版本更新检测</div>
                            <div class="fs-sm text-muted mt-1">关闭后，所有客户端（关于我们-检查更新 / App 启动自动检测）均不会收到更新提示。</div>
                        </div>
                        <div class="form-check form-switch fs-3 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="updateEnabled"
                                <?php echo $updateEnabled ? 'checked' : ''; ?>
                                onchange="toggleUpdate(this.checked)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ 版本列表 ============ -->
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-code-branch me-2"></i>版本列表</h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-alt-primary" onclick="openAdd()">
                            <i class="fa fa-plus me-1"></i>新增版本
                        </button>
                    </div>
                </div>
                <div class="block-content">

                    <?php if (!$tablesReady): ?>
                    <div class="alert alert-warning">
                        请先在数据库中执行 <code>php/app_versions_update.sql</code>（创建 <code>nteam_app_versions</code> 表）后再使用本功能。
                    </div>
                    <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width:60px">ID</th>
                                    <th>版本号</th>
                                    <th style="width:100px">版本数字</th>
                                    <th style="width:90px">平台</th>
                                    <th style="width:90px">强制更新</th>
                                    <th>下载地址</th>
                                    <th style="width:80px">状态</th>
                                    <th style="width:120px">更新时间</th>
                                    <th style="width:140px">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($versions)): ?>
                                <tr><td colspan="9" class="text-center text-muted">暂无版本数据</td></tr>
                            <?php else: foreach ($versions as $v): ?>
                                <tr>
                                    <td><?php echo intval($v['id']); ?></td>
                                    <td>
                                        <span class="fw-semibold"><?php echo hv($v['version']); ?></span>
                                        <?php if (intval($v['status']) === 1 && intval($v['version_code']) === $latestCode): ?>
                                            <span class="badge bg-info ms-1">最新</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($v['version_code']); ?></td>
                                    <td><?php echo $platformText[$v['platform']] ?? hv($v['platform']); ?></td>
                                    <td><?php echo intval($v['force_update']) ? '<span class="badge bg-danger">强制</span>' : '<span class="text-muted">否</span>'; ?></td>
                                    <td class="fs-sm">
                                        <code class="text-break"><?php echo hv($v['download_url']); ?></code>
                                        <?php
                                        $vRawUrl = (string) $v['download_url'];
                                        $vSplit = lanzouSplitPassword($vRawUrl);
                                        ?>
                                        <?php if (lanzouIsUrl($vSplit['url'])): ?>
                                            <span class="badge bg-primary ms-1">蓝奏云</span>
                                        <?php endif; ?>
                                        <?php if ($vSplit['pwd'] !== ''): ?>
                                            <span class="badge bg-info ms-1">含密码</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($v['status']) ? '<span class="badge bg-success">上架</span>' : '<span class="badge bg-secondary">下架</span>'; ?></td>
                                    <td class="fs-sm text-muted"><?php echo hv($v['updated_at']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-alt-secondary" onclick="openEdit(<?php echo intval($v['id']); ?>)">编辑</button>
                                        <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('app_version_del', <?php echo intval($v['id']); ?>, '确认删除该版本？')">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

    </div>
</div>

<!-- ============ 新增 / 编辑 弹窗 ============ -->
<div class="modal fade" id="versionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionModalTitle">新增版本</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="versionForm">
                    <input type="hidden" name="id" id="vId" value="0">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">版本号 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="version" id="vVersion" placeholder="如 1.3.26">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">版本数字 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="version_code" id="vCode" placeholder="如 1002">
                            <small class="text-muted">用于比较大小，越大越新</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">平台</label>
                            <select class="form-select" name="platform" id="vPlatform">
                                <option value="all">全平台</option>
                                <option value="android">Android</option>
                                <option value="ios">iOS</option>
                                <option value="harmony">鸿蒙</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态（版本开关）</label>
                            <select class="form-select" name="status" id="vStatus">
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">强制更新</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="force_update" id="vForce" value="1">
                            <label class="form-check-label" for="vForce">开启后，用户必须更新才能继续使用</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">下载地址 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="download_url" id="vUrl" placeholder="apk 直链 / 应用商店链接 / 蓝奏云分享链接">
                            <!-- 仅识别到蓝奏云链接时才展示（由 refreshUrlTip() 控制显隐） -->
                            <button type="button" class="btn btn-alt-info d-none" id="vTestBtn" onclick="testLanzou()"><i class="fa fa-cloud-download-alt me-1"></i>测试解析</button>
                        </div>
                        <div class="form-text">
                            支持 apk 直链、应用商店链接或蓝奏云分享链接（蓝奏云支持自动解析）。
                        </div>
                        <div id="vLanzouTip" class="alert alert-info py-2 px-3 mt-2 mb-0 fs-sm d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">更新日志</label>
                        <textarea class="form-control" name="changelog" id="vLog" rows="4" placeholder="每行一条更新内容"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveVersion()">保存</button>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'?>
<style>
    /* ===== 蓝奏云解析结果弹窗（移动端适配）=====
       窄屏下弹窗宽度由 JS 按视口计算（约 92%），长直链可换行、可滚动、可长按复制 */
    .lanzou-result {
        font-size: 13px;
        line-height: 1.8;
        word-break: break-word;
    }
    .lanzou-result-row {
        display: flex;
        align-items: flex-start;
    }
    .lanzou-result-label {
        flex: 0 0 60px;
        color: #868e96;
    }
    .lanzou-result-value {
        flex: 1;
        min-width: 0;
        word-break: break-all;
    }
    .lanzou-result-url-title {
        margin-top: 10px;
        color: #868e96;
    }
    .lanzou-result-url {
        margin-top: 4px;
        padding: 8px 10px;
        max-height: 32vh;
        overflow: auto;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        -webkit-overflow-scrolling: touch;
        font-size: 12px;
        color: #6c757d;
        word-break: break-all;
        white-space: pre-wrap;
        -webkit-user-select: text;
        user-select: text;
    }
    /* 窄屏：收紧内边距，避免出现横向滚动 */
    @media (max-width: 575.98px) {
        .layui-layer.lanzou-result-layer .layui-layer-content {
            padding: 12px 14px;
        }
    }
</style>
<script>
// 版本数据（供编辑弹窗回填）
var versions = <?php echo json_encode($versions, JSON_UNESCAPED_UNICODE); ?>;

// ===== 与 banner_config.php 一致的提交方式：layer 加载 → Ajax.php → layer.alert → 刷新 =====
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
                if (opts.silent) {
                    layer.msg(data.msg, {icon: 1});
                } else {
                    layer.alert(data.msg, {icon: 1, closeBtn: false}, function () {
                        window.location.href = 'version_manage.php';
                    });
                }
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

// 全局开关
function toggleUpdate(on) {
    postAct('set', {app_update_enabled: on ? 1 : 0}, {silent: true});
}

// 弹窗
function openAdd() {
    $('#versionModalTitle').text('新增版本');
    $('#versionForm')[0].reset();
    $('#vId').val(0);
    $('#vStatus').val(1);
    $('#vForce').prop('checked', false);
    refreshUrlTip();
    new bootstrap.Modal(document.getElementById('versionModal')).show();
}

function openEdit(id) {
    var v = null;
    for (var i = 0; i < versions.length; i++) {
        if (parseInt(versions[i].id) === parseInt(id)) { v = versions[i]; break; }
    }
    if (!v) return;
    $('#versionModalTitle').text('编辑版本 #' + id);
    $('#vId').val(v.id);
    $('#vVersion').val(v.version);
    $('#vCode').val(v.version_code);
    $('#vPlatform').val(v.platform);
    $('#vStatus').val(v.status);
    $('#vForce').prop('checked', parseInt(v.force_update) === 1);
    $('#vUrl').val(v.download_url);
    $('#vLog').val(v.changelog);
    refreshUrlTip();
    new bootstrap.Modal(document.getElementById('versionModal')).show();
}

function saveVersion() {
    var version = $('#vVersion').val().trim();
    var code = $('#vCode').val().trim();
    var url = $('#vUrl').val().trim();
    if (!version) { layer.msg('请填写版本号', {icon: 2}); return false; }
    if (!code) { layer.msg('请填写版本数字', {icon: 2}); return false; }
    if (!url) { layer.msg('请填写下载地址', {icon: 2}); return false; }
    postAct('app_version_save', $('#versionForm').serialize());
    return false;
}

// ===== 蓝奏云链接识别与提示（与后端 funsion/LanzouHelper.php 规则保持一致） =====
var LANZOU_RE = /^https?:\/\/([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(\/|$)/i;

function escHtml(s) {
    return $('<div>').text(s == null ? '' : s).html();
}

// 解析链接里的 pwd 参数（末尾追加的 &pwd=xxxx 或 ?pwd=xxxx）
function getUrlPwd(url) {
    var m = (url || '').match(/[?&]pwd=([^&\s#]+)/i);
    return m ? m[1] : '';
}

// 根据当前填写的下载地址，动态提示是否为蓝奏云、是否需要补密码
function refreshUrlTip() {
    var url = ($('#vUrl').val() || '').trim();
    var $tip = $('#vLanzouTip');
    var isLanzou = LANZOU_RE.test(url);
    // 「测试解析」只对蓝奏云链接有意义：非蓝奏云（apk 直链 / 应用商店）时隐藏按钮
    $('#vTestBtn').toggleClass('d-none', !isLanzou);
    if (!isLanzou) {
        $tip.addClass('d-none').empty();
        return;
    }
    var pwd = getUrlPwd(url);
    var msg = pwd
        ? '<i class="fa fa-check-circle me-1"></i>已识别蓝奏云链接，下载密码：<b>' + escHtml(pwd) + '</b>。保存后客户端检查更新时会自动解析为直链下载。'
        : '<i class="fa fa-exclamation-circle me-1"></i>已识别蓝奏云链接。若该文件设置了分享密码，请在链接末尾追加 <code>&pwd=密码</code>，否则客户端将解析失败。';
    $tip.removeClass('d-none').html(msg);
}

// 弹窗尺寸按视口自适应：窄屏用百分比宽度并限制最大高度，避免超出手机屏幕
function lanzouAlertOpts(extra) {
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var vh = window.innerHeight || document.documentElement.clientHeight || 0;
    var opts = {
        title: '蓝奏云解析结果',
        area: [vw < 576 ? '92%' : '520px', 'auto'],
        maxHeight: Math.max(220, Math.round(vh * 0.8)),
        skin: 'lanzou-result-layer',
        shadeClose: true
    };
    if (extra) { $.extend(opts, extra); }
    return opts;
}

// 复制直链：优先 Clipboard API，非安全上下文回退 execCommand（移动端浏览器兼容）
function copyLanzouUrl(btn) {
    var text = ($(btn).closest('.lanzou-result').find('.lanzou-result-url').text() || '').trim();
    if (!text || text === '-') { layer.msg('暂无可复制的直链', {icon: 0}); return; }
    var done = function () { layer.msg('已复制直链', {icon: 1}); };
    var fallback = function () {
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(ta);
            if (ok) { done(); return; }
        } catch (e) {}
        layer.msg('复制失败，请长按选中直链复制', {icon: 2});
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done, fallback);
    } else {
        fallback();
    }
}

// 测试解析：调用后端，展示直链/文件名/大小，便于核对链接与密码是否正确
function testLanzou() {
    var url = ($('#vUrl').val() || '').trim();
    if (!url) { layer.msg('请先填写下载地址', {icon: 2}); return; }
    if (!LANZOU_RE.test(url)) { layer.msg('不是蓝奏云链接，无需解析（普通直链客户端会直接下载）', {icon: 0}); return; }
    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=app_version_test_lanzou',
        data: {url: url},
        dataType: 'json',
        success: function (res) {
            layer.close(ii);
            if (res && res.code === 0 && res.data) {
                var d = res.data;
                var html = '<div class="lanzou-result">'
                    + '<div class="lanzou-result-row"><span class="lanzou-result-label">文件名</span><span class="lanzou-result-value">' + escHtml(d.name || '-') + '</span></div>'
                    + '<div class="lanzou-result-row"><span class="lanzou-result-label">大小</span><span class="lanzou-result-value">' + escHtml(d.filesize || '-') + '</span></div>'
                    + (d.pwd ? '<div class="lanzou-result-row"><span class="lanzou-result-label">密码</span><span class="lanzou-result-value"><b>' + escHtml(d.pwd) + '</b></span></div>' : '')
                    + '<div class="lanzou-result-url-title">直链（可长按选中或点下方按钮复制）</div>'
                    + '<div class="lanzou-result-url">' + escHtml(d.direct_url || '-') + '</div>'
                    + '<div class="mt-2"><button type="button" class="btn btn-sm btn-alt-primary" onclick="copyLanzouUrl(this)"><i class="fa fa-copy me-1"></i>复制直链</button></div>'
                    + '</div>';
                layer.alert(html, lanzouAlertOpts());
            } else {
                layer.alert((res && res.msg) ? res.msg : '解析失败', lanzouAlertOpts({icon: 2, title: '解析失败'}));
            }
        },
        error: function () {
            layer.close(ii);
            layer.msg('服务器错误', {icon: 2});
        }
    });
}

// 输入下载地址时实时刷新提示
$(document).on('input', '#vUrl', refreshUrlTip);
</script>
</body>
</html>
