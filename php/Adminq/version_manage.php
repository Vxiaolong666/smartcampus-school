<?php
$mod = 'admin';
$title = '版本管理';
include('./head.php');

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
<div id="page-container">
    <main id="main-container">
        <div class="content">

            <!-- ============ 全局更新推送开关 ============ -->
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-rocket me-2"></i>更新推送设置</h3>
                </div>
                <div class="block-content">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">开启 App 版本更新检测</div>
                            <div class="fs-sm text-muted">关闭后，所有客户端（关于我们-检查更新 / App 启动自动检测）均不会收到更新提示。</div>
                        </div>
                        <div class="form-check form-switch fs-3">
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
                                    <td class="fs-sm"><code class="text-break"><?php echo hv($v['download_url']); ?></code></td>
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
    </main>
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
                            <label class="form-check-label" for="vForce">开启后，用户必须更新才能继续使用（无取消按钮）</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">下载地址 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="download_url" id="vUrl" placeholder="应用商店链接或 apk 直链">
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
</script>
</body>
</html>
