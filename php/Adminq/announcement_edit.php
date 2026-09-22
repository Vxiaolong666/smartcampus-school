<?php
// 公告编辑页作为「公告管理」的下级页面：标题、面包屑与列表页保持层级一致
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = $editId > 0 ? '编辑公告' : '添加公告';
include('./head.php');

$id = $editId;
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
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <?php echo $title ?>
                    </h1>
                    <div class="text-muted fs-sm">公告正文支持 Markdown 与图片，保存后用户端公告页自动同步。</div>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="announcements.php">公告管理</a>
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
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-edit me-2"></i><?php echo $title; ?>
                </h3>
                <div class="block-options">
                    <a class="btn btn-sm btn-alt-secondary" href="announcements.php">
                        <i class="fa fa-arrow-left me-1"></i>返回列表
                    </a>
                </div>
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
                                <label class="form-label">公告内容 <span class="text-danger">*</span> <small class="text-muted">(支持 Markdown 语法，可使用工具栏实时预览)</small></label>
                                <textarea class="form-control" name="content" id="content" rows="15" style="font-family: monospace;"><?php echo $announcement ? htmlspecialchars($announcement['content']) : ''; ?></textarea>
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
                    
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-alt-primary me-2" onclick="saveAnnouncement()">
                            <i class="fa fa-save me-1"></i>保存公告
                        </button>
                        <span class="text-muted fs-sm me-3">Ctrl + S 保存</span>
                        <a href="announcements.php" class="btn btn-alt-secondary">
                            <i class="fa fa-arrow-left me-1"></i>返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>
.editor-toolbar {
    border-color: #e4e7ed;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}
.CodeMirror, .editor-preview-side, .editor-preview {
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}
.CodeMirror {
    border: 1px solid #e4e7ed;
    border-top: none;
    font-size: 14px;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
var announcementMDE = null;

$(function () {
    if (window.EasyMDE && document.getElementById('content')) {
        announcementMDE = new EasyMDE({
            element: document.getElementById('content'),
            spellChecker: false,
            status: false,
            autoDownloadFontAwesome: false,
            minHeight: '380px',
            placeholder: '在此使用 Markdown 编写公告内容…',
            toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link',
                {
                    name: 'insert-image',
                    action: function (editor) {
                        openImageDialog(editor);
                    },
                    className: 'fa fa-image',
                    title: '插入图片（支持远程链接或上传本地图片）'
                },
                'code', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
        });
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

// 工具栏「插入图片」：弹出选择窗口，支持「远程链接」与「上传本地图片」两种方式
var imageDialogLayer = null;
var activeEditor = null;

function openImageDialog(editor) {
    if (!editor || !editor.codemirror) return;
    activeEditor = editor;

    var html = [
        '<div style="padding:18px 20px;">',
        '  <div class="mb-2 fw-semibold">方式一：图片链接（远程 URL）</div>',
        '  <div class="mb-3">',
        '    <input type="text" id="insImgUrl" class="form-control" placeholder="https://example.com/a.jpg 或 /uploads/xxx.jpg">',
        '  </div>',
        '  <div class="mb-2">',
        '    <label class="form-label mb-1">图片描述（可选，作为 alt）</label>',
        '    <input type="text" id="insImgAlt" class="form-control" placeholder="图片说明">',
        '  </div>',
        '  <button type="button" class="btn btn-alt-primary btn-sm" onclick="insertRemoteImage()">',
        '    <i class="fa fa-link me-1"></i>插入链接图片',
        '  </button>',
        '  <hr class="my-4">',
        '  <div class="mb-2 fw-semibold">方式二：上传本地图片</div>',
        '  <div class="mb-3 text-muted fs-sm">保存到本站（与右侧缩略图/大图上传共用同一接口），插入后为相对路径。</div>',
        '  <button type="button" class="btn btn-alt-secondary btn-sm" onclick="pickLocalImage()">',
        '    <i class="fa fa-upload me-1"></i>选择本地图片上传',
        '  </button>',
        '</div>'
    ].join('');

    imageDialogLayer = layer.open({
        type: 1,
        title: '插入图片',
        area: ['460px', 'auto'],
        shadeClose: true,
        content: html
    });

    setTimeout(function () { var el = document.getElementById('insImgUrl'); if (el) el.focus(); }, 50);
}

function closeImageDialog() {
    if (imageDialogLayer !== null) {
        try { layer.close(imageDialogLayer); } catch (e) {}
        imageDialogLayer = null;
    }
}

// 方式一：插入远程链接
function insertRemoteImage() {
    var url = ($('#insImgUrl').val() || '').trim();
    var alt = ($('#insImgAlt').val() || '').trim();
    if (!url) { layer.msg('请输入图片链接', {icon: 0}); return; }
    if (!activeEditor) { layer.msg('编辑器未就绪', {icon: 2}); return; }

    activeEditor.codemirror.replaceSelection('![' + alt + '](' + url + ')');
    activeEditor.codemirror.focus();
    closeImageDialog();
    layer.msg('图片已插入', {icon: 1});
}

// 方式二：上传本地图片后插入（与右侧上传共用接口 Ajax.php?act=upload_announcement_image）
function pickLocalImage() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function () {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];

        var formData = new FormData();
        formData.append('file', file);
        formData.append('type', 'image');

        var loadIndex = layer.load(1);
        $.ajax({
            url: 'Ajax.php?act=upload_announcement_image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                layer.close(loadIndex);
                if (res.code == 0) {
                    var url = res.data.url;
                    var alt = (file.name || 'image').replace(/\.[^.]+$/, '');
                    // 在光标处插入 Markdown 图片语法，url 与右侧上传一致（相对路径）
                    if (activeEditor) {
                        activeEditor.codemirror.replaceSelection('![' + alt + '](' + url + ')');
                        activeEditor.codemirror.focus();
                    }
                    closeImageDialog();
                    layer.msg('图片已插入', {icon: 1});
                } else {
                    layer.msg(res.msg || '上传失败', {icon: 2});
                }
            },
            error: function () {
                layer.close(loadIndex);
                layer.msg('上传失败', {icon: 2});
            }
        });
    };
    input.click();
}

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
    // 防御：仅当处于公告编辑页（存在表单）时才保存，避免快捷键在其他页面被误触发
    if (!document.getElementById('announcementForm')) return;

    // 同步 Markdown 编辑器内容到原始 textarea，确保表单提交包含最新内容
    if (announcementMDE) { try { announcementMDE.codemirror.save(); } catch (e) {} }

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

// 快捷键：Ctrl+S / Cmd+S 保存公告（替换浏览器默认的「保存网页」）
// 注意：后台使用 pjax 局部刷新，document 上的监听会跨页面保留，
//       因此在注册前先移除上一次的监听，并在回调里判断当前是否处于公告编辑页，
//       避免在其他后台页面误触发、或重复注册导致多次保存。
if (window.__annSaveKeydown) {
    document.removeEventListener('keydown', window.__annSaveKeydown, true);
    window.__annSaveKeydown = null;
}
window.__annSaveKeydown = function (e) {
    if (!document.getElementById('announcementForm')) return; // 不在编辑页则忽略
    var key = e.key || '';
    if ((e.ctrlKey || e.metaKey) && !e.altKey && (key === 's' || key === 'S' || e.keyCode === 83)) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') { e.stopImmediatePropagation(); }
        saveAnnouncement();
        return false;
    }
};
document.addEventListener('keydown', window.__annSaveKeydown, true);
</script>

<?php include 'foot.php'; ?>
