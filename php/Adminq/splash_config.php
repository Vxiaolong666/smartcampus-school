<?php
$mod = 'admin';
$title = '开屏广告';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// 检测开屏广告表是否已导入
$tablesReady = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_splash_ads LIMIT 1");
    if ($chk) $tablesReady = true;
} catch (Exception $e) {
    $tablesReady = false;
}

// 全局开关：开启开屏广告
$splashEnabled = 0;
try {
    $cfg = $DB->getRow("SELECT splash_enabled FROM nteam_config WHERE id=1");
    if ($cfg !== null && isset($cfg['splash_enabled']) && intval($cfg['splash_enabled']) === 1) {
        $splashEnabled = 1;
    }
} catch (Exception $e) {}

// 广告列表
$ads = array();
if ($tablesReady) {
    $rs = @$DB->query("SELECT * FROM nteam_splash_ads ORDER BY `sort` ASC, `id` ASC");
    if ($rs) { while ($r = $rs->fetch()) $ads[] = $r; }
}

// 编辑态
$editAd = null;
if ($tablesReady && isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $rs = @$DB->query("SELECT * FROM nteam_splash_ads WHERE `id`='{$eid}'");
    if ($rs) $editAd = $rs->fetch();
}

// 站点基路径（用于图片预览）
$siteRoot = isset($siteBasePath) ? $siteBasePath : '';
?>
<style>
.splash-preview {
    width: 54px;
    height: 96px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #f8f9fa;
}
.upload-box {
    border: 2px dashed #d5dbe3;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    color: #6c757e;
    cursor: pointer;
    transition: all .15s ease;
}
.upload-box:hover { border-color: #0d80f2; color: #0d80f2; }
.upload-box img { max-width: 100%; max-height: 300px; border-radius: 6px; }
/* ===== 移动端表格适配 ===== */
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
    .table-responsive .table .splash-preview {
        width: 40px;
        height: 72px;
    }
    /* 跳转链接列允许换行 */
    .table-responsive .table td:nth-child(4) {
        white-space: normal;
        word-break: break-all;
        min-width: 150px;
    }
    /* 操作列按钮竖排撑满 */
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

        <!-- ============ 全局开关 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-tv me-2"></i>开屏广告设置</h3>
            </div>
            <div class="block-content pb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="flex-grow-1 pe-2">
                        <div class="fw-semibold">开启开屏广告</div>
                        <div class="fs-sm text-muted mt-1">关闭后，小程序与 Uniapp 启动时均不会展示开屏广告。</div>
                    </div>
                    <div class="form-check form-switch fs-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="splashEnabled"
                            <?php echo $splashEnabled ? 'checked' : ''; ?>
                            onchange="toggleSplash(this.checked)">
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ 广告列表 ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-image me-2"></i>广告列表</h3>
            </div>
            <div class="block-content pb-4">

                <?php if (!$tablesReady): ?>
                <div class="alert alert-warning">
                    请先在数据库中执行 <code>php/splash_ad_update.sql</code>（创建 <code>nteam_splash_ads</code> 表）后再使用本功能。
                </div>
                <?php else: ?>

                <!-- ============ 新增 / 编辑表单 ============ -->
                <?php if (!$editAd): ?>
                <button type="button" class="btn btn-primary mb-3" id="addSplashBtn" onclick="document.getElementById('splashForm').style.display='block'; this.style.display='none';">
                    <i class="fa fa-plus me-1"></i> 增加广告
                </button>
                <?php endif; ?>
                <form id="splashForm" onsubmit="return saveSplash(this)" method="post" name="splash-form"<?php if(!$editAd) echo ' style="display:none;"'; ?>>
                    <input type="hidden" name="id" id="splashId" value="<?php echo $editAd ? intval($editAd['id']) : 0; ?>">
                    <input type="hidden" name="image" id="splashImage" value="<?php echo h(isset($editAd['image']) ? $editAd['image'] : ''); ?>">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-4">
                                <label class="form-label"><b>开屏图片</b> <span class="text-danger">*</span></label>
                                <div class="upload-box" onclick="$('#splashImageInput').click();">
                                    <img id="splashImgPreview" src="<?php echo ($editAd && !empty($editAd['image'])) ? $siteRoot . h($editAd['image']) : ''; ?>" style="display: <?php echo ($editAd && !empty($editAd['image'])) ? 'inline-block' : 'none'; ?>;">
                                    <div id="splashUploadTip" style="display: <?php echo ($editAd && !empty($editAd['image'])) ? 'none' : 'block'; ?>;">
                                        <i class="fa fa-cloud-arrow-up fa-2x mb-2"></i>
                                        <div>点击选择图片上传（jpg / png / gif / webp，≤5MB）</div>
                                        <div class="fs-sm text-muted">建议使用竖屏图，如 1080×1920</div>
                                    </div>
                                </div>
                                <input type="file" id="splashImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label" for="splashTitle"><b>名称</b></label>
                                <input type="text" class="form-control form-control-lg" id="splashTitle" name="title" value="<?php echo h(isset($editAd['title']) ? $editAd['title'] : ''); ?>" placeholder="管理备注，如：开学季开屏">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="splashUrlWx"><b>小程序跳转链接</b></label>
                                <input type="text" class="form-control form-control-lg" id="splashUrlWx" name="url_wx" value="<?php echo h(isset($editAd['url_wx']) ? $editAd['url_wx'] : ''); ?>" placeholder="留空回退通用链接；站内如 /pages/academic-grade/index">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="splashUrlApp"><b>Uniapp（App）跳转链接</b></label>
                                <input type="text" class="form-control form-control-lg" id="splashUrlApp" name="url_app" value="<?php echo h(isset($editAd['url_app']) ? $editAd['url_app'] : ''); ?>" placeholder="留空回退通用链接；站内如 /pages/academic-grade/index">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="splashUrl"><b>通用跳转链接（兜底）</b></label>
                                <input type="text" class="form-control form-control-lg" id="splashUrl" name="url" value="<?php echo h(isset($editAd['url']) ? $editAd['url'] : ''); ?>" placeholder="站内以 / 开头，站外需 http(s)://；留空则点击不跳转">
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="splashDuration"><b>展示时长（秒）</b></label>
                                        <input type="number" class="form-control form-control-lg" id="splashDuration" name="duration" min="1" max="15" value="<?php echo $editAd ? max(1, intval($editAd['duration'])) : 3; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="splashSort"><b>排序</b></label>
                                        <input type="number" class="form-control form-control-lg" id="splashSort" name="sort" value="<?php echo $editAd ? intval($editAd['sort']) : 0; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="splashStatus"><b>状态</b></label>
                                        <select class="form-select form-select-lg" name="status" id="splashStatus">
                                            <option value="1" <?php echo (!$editAd || intval($editAd['status'])) ? 'selected' : ''; ?>>启用</option>
                                            <option value="0" <?php echo ($editAd && !intval($editAd['status'])) ? 'selected' : ''; ?>>停用</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b><?php echo $editAd ? '保存修改' : '新增广告'; ?></b>
                        </button>
                        <?php if ($editAd): ?>
                        <a href="splash_config.php" class="btn btn-alt-secondary">取消编辑</a>
                        <?php else: ?>
                        <a href="javascript:void(0)" class="btn btn-alt-secondary" onclick="document.getElementById('splashForm').style.display='none'; document.getElementById('addSplashBtn').style.display='inline-block';">取消</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- ============ 列表 ============ -->
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:60px">ID</th>
                                <th style="width:110px">图片</th>
                                <th>名称</th>
                                <th>跳转链接（小程序 / Uniapp / 通用）</th>
                                <th style="width:80px">时长</th>
                                <th style="width:70px">排序</th>
                                <th style="width:80px">状态</th>
                                <th style="width:140px">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($ads)): ?>
                            <tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>
                        <?php else: foreach ($ads as $ad): ?>
                            <tr>
                                <td><?php echo intval($ad['id']); ?></td>
                                <td><img class="splash-preview" src="<?php echo $siteRoot . h($ad['image']); ?>" alt=""></td>
                                <td><?php echo h($ad['title']); ?></td>
                                <td class="fs-sm">
                                    <div>小程序：<?php echo !empty($ad['url_wx']) ? '<code>' . h($ad['url_wx']) . '</code>' : '<span class="text-muted">—</span>'; ?></div>
                                    <div>Uniapp：<?php echo !empty($ad['url_app']) ? '<code>' . h($ad['url_app']) . '</code>' : '<span class="text-muted">—</span>'; ?></div>
                                    <div>通用：<?php echo !empty($ad['url']) ? '<code>' . h($ad['url']) . '</code>' : '<span class="text-muted">—</span>'; ?></div>
                                </td>
                                <td><?php echo intval($ad['duration']); ?>s</td>
                                <td><?php echo intval($ad['sort']); ?></td>
                                <td><?php echo intval($ad['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">停用</span>'; ?></td>
                                <td>
                                    <a href="splash_config.php?edit=<?php echo intval($ad['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('splash_del', <?php echo intval($ad['id']); ?>, '确认删除该开屏广告？')">删除</button>
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
<?php include 'foot.php'?>
<script>
// ===== 与 banner_config.php 保持一致的提交方式：layer 加载 → Ajax.php → layer.alert → 刷新 =====
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
                        window.location.href = 'splash_config.php';
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
function saveSplash(obj) {
    if (!$('#splashImage').val()) {
        layer.msg('请先上传开屏图片', {icon: 2});
        return false;
    }
    return postAct('splash_save', $(obj).serialize());
}
function delRow(act, id, tip) {
    layer.confirm(tip || '确认删除？', {icon: 3, title: '提示'}, function (index) {
        layer.close(index);
        postAct(act, {id: id});
    });
    return false;
}
// 全局开关
function toggleSplash(on) {
    postAct('set', {splash_enabled: on ? 1 : 0}, {silent: true});
}
// 图片上传
$('#splashImageInput').on('change', function() {
    if (!this.files || !this.files[0]) return;
    var formData = new FormData();
    formData.append('file', this.files[0]);
    var loadIndex = layer.load(1);
    $.ajax({
        url: 'Ajax.php?act=upload_splash_image',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 0) {
                $('#splashImage').val(res.data.url);
                $('#splashImgPreview').attr('src', res.data.displayUrl || res.data.url).show();
                $('#splashUploadTip').hide();
                layer.msg('上传成功', {icon: 1});
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.close(loadIndex);
            layer.msg('上传失败', {icon: 2});
        }
    });
});
</script>
</body>
</html>
