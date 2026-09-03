<?php
$title = '编辑公告';
include('./head.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$announcement = null;

if ($id > 0) {
    $announcement = $DB->getRow("SELECT * FROM nteam_announcements WHERE id='$id'");
    if (!$announcement) {
        echo '<script>layer.msg("公告不存在", {icon: 2, time: 1500}, function() { location.href = "announcements.php"; });</script>';
        exit;
    }
}

$uploadDir = '../uploads/announcements/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function resolveUploadUrl($url) {
    global $siteBasePath;
    if ($url && strpos($url, 'http') !== 0 && strpos($url, $siteBasePath) !== 0) {
        return $siteBasePath . $url;
    }
    return $url;
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-edit me-2"></i><?php echo $id > 0 ? '编辑公告' : '添加公告'; ?>
                </h3>
            </div>
            <div class="block-content block-content-full">
                <form id="announcementForm">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">公告标题 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="title" value="<?php echo $announcement ? htmlspecialchars($announcement['title']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">公告内容 <span class="text-danger">*</span> <small class="text-muted">(支持Markdown语法)</small></label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <textarea class="form-control" name="content" id="content" rows="15" style="font-family: monospace;" required><?php echo $announcement ? htmlspecialchars($announcement['content']) : ''; ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <strong>预览</strong>
                                            </div>
                                            <div class="card-body" id="preview" style="max-height: 400px; overflow-y: auto;">
                                                <p class="text-muted">预览区域</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">缩略图</label>
                                <input type="file" class="form-control" id="thumbnailInput" accept="image/*">
                                <input type="hidden" name="thumbnail" id="thumbnail" value="<?php echo $announcement ? htmlspecialchars($announcement['thumbnail']) : ''; ?>">
                                <?php if($announcement && $announcement['thumbnail']): ?>
                                <div class="mt-2" id="thumbnailPreview">
                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($announcement['thumbnail'])); ?>" style="max-width: 100%; max-height: 150px; border-radius: 4px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeThumbnail()">删除</button>
                                </div>
                                <?php else: ?>
                                <div class="mt-2" id="thumbnailPreview" style="display:none;">
                                    <img id="thumbnailImg" src="" style="max-width: 100%; max-height: 150px; border-radius: 4px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeThumbnail()">删除</button>
                                </div>
                                <?php endif; ?>
                                <small class="text-muted">建议尺寸: 300x200</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">大图</label>
                                <input type="file" class="form-control" id="imageInput" accept="image/*">
                                <input type="hidden" name="image" id="image" value="<?php echo $announcement ? htmlspecialchars($announcement['image']) : ''; ?>">
                                <?php if($announcement && $announcement['image']): ?>
                                <div class="mt-2" id="imagePreview">
                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($announcement['image'])); ?>" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeImage()">删除</button>
                                </div>
                                <?php else: ?>
                                <div class="mt-2" id="imagePreview" style="display:none;">
                                    <img id="imageImg" src="" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeImage()">删除</button>
                                </div>
                                <?php endif; ?>
                                <small class="text-muted">用于公告详情页展示</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">作者</label>
                                <input type="text" class="form-control" name="author" value="<?php echo $announcement ? htmlspecialchars($announcement['author']) : ''; ?>" placeholder="留空则显示系统">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_top" id="is_top" value="1" <?php echo ($announcement && $announcement['is_top'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_top">置顶显示</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_published" id="is_published" value="1" <?php echo (!$announcement || $announcement['is_published'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_published">立即发布</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <button type="button" class="btn btn-alt-primary me-2" onclick="saveAnnouncement()">
                            <i class="fa fa-save me-1"></i>保存
                        </button>
                        <a href="announcements.php" class="btn btn-alt-secondary">
                            <i class="fa fa-arrow-left me-1"></i>返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/github-markdown-css@5.2.0/github-markdown.min.css">
<style>
.markdown-body {
    font-size: 14px;
}
.markdown-body img {
    max-width: 100%;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
marked.setOptions({
    breaks: true,
    gfm: true
});

$('#content').on('input', function() {
    var content = $(this).val();
    if (content) {
        $('#preview').html('<div class="markdown-body">' + marked.parse(content) + '</div>');
    } else {
        $('#preview').html('<p class="text-muted">预览区域</p>');
    }
});

$(document).ready(function() {
    var content = $('#content').val();
    if (content) {
        $('#preview').html('<div class="markdown-body">' + marked.parse(content) + '</div>');
    }
});

$('#thumbnailInput').on('change', function() {
    var formData = new FormData();
    formData.append('file', this.files[0]);
    formData.append('type', 'thumbnail');
    
    var loadIndex = layer.load(1);
    
    $.ajax({
        url: 'Ajax.php?act=upload_announcement_image',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 0) {
                $('#thumbnail').val(res.data.url);
                $('#thumbnailImg').attr('src', res.data.displayUrl || res.data.url);
                $('#thumbnailPreview').show();
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

$('#imageInput').on('change', function() {
    var formData = new FormData();
    formData.append('file', this.files[0]);
    formData.append('type', 'image');
    
    var loadIndex = layer.load(1);
    
    $.ajax({
        url: 'Ajax.php?act=upload_announcement_image',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 0) {
                $('#image').val(res.data.url);
                $('#imageImg').attr('src', res.data.displayUrl || res.data.url);
                $('#imagePreview').show();
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

function removeThumbnail() {
    $('#thumbnail').val('');
    $('#thumbnailInput').val('');
    $('#thumbnailPreview').hide();
}

function removeImage() {
    $('#image').val('');
    $('#imageInput').val('');
    $('#imagePreview').hide();
}

function saveAnnouncement() {
    var title = $('#title').val().trim();
    var content = $('#content').val().trim();
    
    if (!title) {
        layer.msg('请输入公告标题', {icon: 0});
        return;
    }
    
    if (!content) {
        layer.msg('请输入公告内容', {icon: 0});
        return;
    }
    
    var formData = $('#announcementForm').serialize();
    var loadIndex = layer.load(1);
    
    $.ajax({
        url: 'Ajax.php?act=save_announcement',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 0) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.href = 'announcements.php';
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.close(loadIndex);
            layer.msg('保存失败', {icon: 2});
        }
    });
}
</script>

<?php include 'foot.php'; ?>
