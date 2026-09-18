<?php
$mod = 'admin';
$title = '轮播图配置';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// 检测轮播图表是否已导入（未导入时提示执行 banner_update.sql）
$tablesReady = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_banners LIMIT 1");
    if ($chk) $tablesReady = true;
} catch (Exception $e) {
    $tablesReady = false;
}

// 站点基路径（用于图片预览）：使用 Core_brain.php 已推导好的 $siteBasePath
// （页面位于 /Adminq/ 下，不能再用 dirname(SCRIPT_NAME) 推导，否则会拼出 /adminq/uploads/... 404）
$siteRoot = isset($siteBasePath) ? $siteBasePath : '';

// 轮播图列表
$banners = array();
if ($tablesReady) {
    $rs = @$DB->query("SELECT * FROM nteam_banners ORDER BY `sort` ASC, `id` ASC");
    if ($rs) { while ($r = $rs->fetch()) $banners[] = $r; }
}

// 编辑态
$editBanner = null;
if ($tablesReady && isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $rs = @$DB->query("SELECT * FROM nteam_banners WHERE `id`='{$eid}'");
    if ($rs) $editBanner = $rs->fetch();
}
?>
<style>
.banner-preview {
    width: 120px;
    height: 54px;
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
.upload-box img { max-width: 100%; max-height: 160px; border-radius: 6px; }
/* ===== 移动端表格适配：压缩内边距/字号，图片缩小，保证横向可滑动 ===== */
@media (max-width: 768px) {
    .table-responsive .table th,
    .table-responsive .table td {
        padding: 0.5rem 0.5rem;
        font-size: 0.8125rem;
        white-space: nowrap;
    }
    .table-responsive .table .banner-preview {
        width: 80px;
        height: 36px;
    }
    .table-responsive .table .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .table-responsive .table td:last-child {
        white-space: normal;
        min-width: 130px;
    }
}
</style>

<div id="page-container">
    <main id="main-container">
        <div class="content">

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-images me-2"></i>轮播图配置</h3>
                    <div class="block-options">
                        <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                            <i class="si si-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content">

                    <?php if (!$tablesReady): ?>
                    <div class="alert alert-warning">
                        请先在数据库中执行 <code>php/banner_update.sql</code>（创建 <code>nteam_banners</code> 表并写入默认的 3 张轮播图）后再使用本功能。
                    </div>
                    <?php else: ?>

                    <!-- ============ 新增 / 编辑表单 ============ -->
                    <form id="bannerForm" onsubmit="return saveBanner(this)" method="post" name="banner-form">
                        <input type="hidden" name="id" id="bannerId" value="<?php echo $editBanner ? intval($editBanner['id']) : 0; ?>">
                        <input type="hidden" name="image" id="bannerImage" value="<?php echo h($editBanner ? $editBanner['image'] : ''); ?>">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-4">
                                    <label class="form-label"><b>轮播图图片</b> <span class="text-danger">*</span></label>
                                    <div class="upload-box" onclick="$('#bannerImageInput').click();">
                                        <img id="bannerImgPreview" src="<?php echo $editBanner && $editBanner['image'] ? $siteRoot . h($editBanner['image']) : ''; ?>" style="display: <?php echo $editBanner && $editBanner['image'] ? 'inline-block' : 'none'; ?>;">
                                        <div id="bannerUploadTip" style="display: <?php echo $editBanner && $editBanner['image'] ? 'none' : 'block'; ?>;">
                                            <i class="fa fa-cloud-arrow-up fa-2x mb-2"></i>
                                            <div>点击选择图片上传（jpg / png / gif / webp，≤5MB）</div>
                                            <div class="fs-sm text-muted">建议尺寸 750×360 以上</div>
                                        </div>
                                    </div>
                                    <input type="file" id="bannerImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="mb-4">
                                    <label class="form-label" for="bannerTitle"><b>名称</b></label>
                                    <input type="text" class="form-control form-control-lg" id="bannerTitle" name="title" value="<?php echo h($editBanner ? $editBanner['title'] : ''); ?>" placeholder="管理备注，如：开学季活动图">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="bannerUrl"><b>跳转链接</b></label>
                                    <input type="text" class="form-control form-control-lg" id="bannerUrl" name="url" value="<?php echo h($editBanner ? $editBanner['url'] : ''); ?>" placeholder="留空不跳转；站内如 /pages/academic-grade/index，站外如 https://xxx.com">
                                    <small class="text-muted">站内页面以 / 开头；站外链接需以 http(s):// 开头</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="bannerSort"><b>排序</b></label>
                                            <input type="number" class="form-control form-control-lg" id="bannerSort" name="sort" value="<?php echo $editBanner ? intval($editBanner['sort']) : 0; ?>">
                                            <small class="text-muted">数字越小越靠前</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label"><b>状态</b></label>
                                            <div class="space-x-2">
                                                <div class="form-check form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="status" value="0" <?php echo ($editBanner && !intval($editBanner['status'])) ? 'checked' : ''; ?>>禁用
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="radio" name="status" value="1" <?php echo (!$editBanner || intval($editBanner['status'])) ? 'checked' : ''; ?>>启用
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check opacity-50 me-1"></i> <b><?php echo $editBanner ? '保存修改' : '新增轮播图'; ?></b>
                            </button>
                            <?php if ($editBanner): ?>
                            <a href="banner_config.php" class="btn btn-alt-secondary">取消编辑</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>

                    <!-- ============ 轮播图列表 ============ -->
                    <?php if ($tablesReady): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-vcenter">
                            <thead><tr><th style="width:60px">ID</th><th style="width:150px">图片</th><th>名称</th><th>跳转链接</th><th style="width:80px">排序</th><th style="width:90px">状态</th><th style="width:150px">操作</th></tr></thead>
                            <tbody>
                            <?php if (empty($banners)): ?>
                                <tr><td colspan="7" class="text-center text-muted">暂无数据</td></tr>
                            <?php else: foreach ($banners as $b): ?>
                                <tr>
                                    <td><?php echo intval($b['id']); ?></td>
                                    <td><img class="banner-preview" src="<?php echo $siteRoot . h($b['image']); ?>" alt=""></td>
                                    <td><?php echo h($b['title']); ?></td>
                                    <td class="fs-sm"><?php echo $b['url'] ? '<code>' . h($b['url']) . '</code>' : '<span class="text-muted">不跳转</span>'; ?></td>
                                    <td><?php echo intval($b['sort']); ?></td>
                                    <td><?php echo intval($b['status']) ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-secondary">禁用</span>'; ?></td>
                                    <td>
                                        <a href="banner_config.php?edit=<?php echo intval($b['id']); ?>" class="btn btn-sm btn-alt-secondary">编辑</a>
                                        <button type="button" class="btn btn-sm btn-alt-danger" onclick="delRow('banner_del', <?php echo intval($b['id']); ?>, '确认删除该轮播图？')">删除</button>
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
<?php include 'foot.php'?>
<script>
// ===== 与 home_config.php 保持一致的提交方式：layer 加载 → Ajax.php → layer.alert → 刷新 =====
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
                    window.location.href = 'banner_config.php';
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
function saveBanner(obj) {
    if (!$('#bannerImage').val()) {
        layer.msg('请先上传轮播图图片', {icon: 2});
        return false;
    }
    return postAct('banner_save', $(obj).serialize());
}
function delRow(act, id, tip) {
    layer.confirm(tip || '确认删除？', {icon: 3, title: '提示'}, function (index) {
        layer.close(index);
        postAct(act, {id: id});
    });
    return false;
}
// 图片上传：选择文件后立即上传到 Ajax.php?act=upload_banner_image，成功后回填路径并显示预览
$('#bannerImageInput').on('change', function() {
    if (!this.files || !this.files[0]) return;
    var formData = new FormData();
    formData.append('file', this.files[0]);
    var loadIndex = layer.load(1);
    $.ajax({
        url: 'Ajax.php?act=upload_banner_image',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 0) {
                $('#bannerImage').val(res.data.url);
                $('#bannerImgPreview').attr('src', res.data.displayUrl || res.data.url).show();
                $('#bannerUploadTip').hide();
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
