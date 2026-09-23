<?php
/**
 * 协议管理：新增 / 编辑（独立页面，替代原弹窗实现）
 * 入口：agreement_edit.php（新增） / agreement_edit.php?id=xx（编辑）
 */
$mod = 'admin';
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = $editId > 0 ? '编辑协议' : '新增协议';
include('./head.php');
require_once __DIR__ . '/../funsion/Agreements.php';

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

function hv($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tableReady = agreementTableReady($DB);
$row = ($tableReady && $editId > 0) ? agreementGetById($DB, $editId) : null;
$notFound = ($editId > 0 && !$row);

$data = array(
    'id'         => $row ? intval($row['id']) : 0,
    'akey'       => $row ? (string) $row['akey'] : '',
    'title'      => $row ? (string) $row['title'] : '',
    'content'    => $row ? (string) $row['content'] : '',
    'sort_order' => $row ? intval($row['sort_order']) : 0,
    'status'     => $row ? intval($row['status']) : 1,
    'is_system'  => $row ? intval($row['is_system']) : 0,
);
$isEdit = ($data['id'] > 0);
$keyReadonly = ($data['is_system'] === 1);
$pageName = $isEdit ? ('编辑协议 #' . $data['id']) : '新增协议';
?>
<style>
.key-badge {
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
}
</style>

<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2"><?php echo $pageName; ?></h1>
                    <div class="text-muted fs-sm"></div>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item"><a class="link-fx" href="javascript:void(0)">后台首页</a></li>
                        <li class="breadcrumb-item"><a class="link-fx" href="agreement_manage.php">协议管理</a></li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo $isEdit ? '编辑协议' : '新增协议'; ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content animated fadeIn">
        <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            协议表未创建，请先执行 <code>php/agreements_update.sql</code>（或访问 <code>/agreements_update.php</code> 一次性执行）后再使用本功能。
            <div class="mt-2"><a class="btn btn-sm btn-alt-primary" href="agreement_manage.php">返回协议管理</a></div>
        </div>
        <?php elseif ($notFound): ?>
        <div class="alert alert-warning">
            未找到该协议（可能已被删除）。
            <div class="mt-2"><a class="btn btn-sm btn-alt-primary" href="agreement_manage.php">返回协议管理</a></div>
        </div>
        <?php else: ?>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-file-signature me-2"></i><?php echo $isEdit ? '编辑协议' : '新增协议'; ?>
                    <?php if ($isEdit && $data['is_system'] === 1): ?>
                        <span class="badge bg-info ms-1">系统内置</span>
                    <?php endif; ?>
                </h3>
                <div class="block-options">
                    <a class="btn btn-sm btn-alt-secondary" href="agreement_manage.php">
                        <i class="fa fa-arrow-left me-1"></i>返回列表
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <form id="agreementForm" onsubmit="return saveAgreement();">
                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">协议名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="aTitle" maxlength="100"
                                   placeholder="如 用户服务协议" value="<?php echo hv($data['title']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">标识(key)</label>
                            <input type="text" class="form-control" name="akey" id="aKey"
                                   placeholder="留空自动生成，如 service"
                                   value="<?php echo hv($data['akey']); ?>" <?php echo $keyReadonly ? 'readonly' : ''; ?>>
                            <div class="form-text">
                                仅字母、数字、下划线、短横线。用户端按 key 取协议内容与路由；
                                <?php if ($keyReadonly): ?>
                                    <b>系统内置协议标识固定，不可修改</b>。
                                <?php else: ?>
                                    已被引用的协议不建议修改标识（如注销流程依赖 <code>cancel</code>）。
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" name="sort_order" id="aSort" value="<?php echo $data['sort_order']; ?>">
                            <div class="form-text">数字越小越靠前。</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态</label>
                            <select class="form-select" name="status" id="aStatus">
                                <option value="1" <?php echo $data['status'] === 1 ? 'selected' : ''; ?>>启用（在协议中心展示）</option>
                                <option value="0" <?php echo $data['status'] === 0 ? 'selected' : ''; ?>>停用（不在协议中心展示）</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">协议内容（Markdown）<span class="text-danger">*</span> <small class="text-muted"></small></label>
                        <textarea class="form-control" name="content" id="aContent" rows="18" style="font-size:13px;"><?php echo hv($data['content']); ?></textarea>
                    </div>

                    <div class="mb-1">
                        <button type="button" class="btn btn-primary" onclick="saveAgreement()">
                            <i class="fa fa-check opacity-50 me-1"></i> 保存
                        </button>
                        <a class="btn btn-alt-secondary" href="agreement_manage.php">取消</a>
                        <span class="text-muted fs-sm ms-3">Ctrl + S 保存</span>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'foot.php'?>
<!-- Markdown 编辑器资源已本地化（原 jsDelivr CDN 在部分服务器环境不可访问）：EasyMDE v2.18.0 -->
<link rel="stylesheet" href="./assets/css/plugins/easymde/easymde.min.css">
<script src="./assets/js/plugins/easymde/easymde.min.js"></script>
<script>
var editorMDE = null;
var agreementSaving = false; // 保存请求进行中（防止 Ctrl+S 连按重复提交）

$(function () {
    // 独立页面：元素可见，直接初始化即可（无需弹窗那套 refresh 处理）
    if (window.EasyMDE && document.getElementById('aContent')) {
        editorMDE = new EasyMDE({
            element: document.getElementById('aContent'),
            spellChecker: false,
            status: false,
            autoDownloadFontAwesome: false,
            minHeight: '420px',
            placeholder: '在此使用 Markdown 编写协议内容…',
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

// ===== 工具栏「插入图片」：支持「远程链接」与「上传本地图片」两种方式（与公告编辑器一致） =====
var imageDialogLayer = null;
var activeEditor = null;

function openImageDialog(editor) {
    if (!editor || !editor.codemirror) return;
    activeEditor = editor;

    var html = [
        '<div style="padding:18px 20px;">',
        '  <div class="mb-2 fw-semibold">方式一：图片链接（远程 URL）</div>',
        '  <div class="mb-3">',
        '    <input type="text" id="insImgUrl" class="form-control" placeholder="https://example.com/a.jpg 或 /uploads/agreements/xxx.jpg">',
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
        '  <div class="mb-3 text-muted fs-sm">图片保存到本协议目录（/uploads/agreements/），插入后为相对路径，用户端会自动补全为完整地址。</div>',
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

// 方式二：上传本地图片后插入（Ajax.php?act=upload_agreement_image）
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

        var ii = layer.load(1);
        $.ajax({
            url: 'Ajax.php?act=upload_agreement_image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                layer.close(ii);
                if (res.code == 0) {
                    var url = res.data.url;
                    var alt = (file.name || 'image').replace(/\.[^.]+$/, '');
                    // 在光标处插入 Markdown 图片语法（相对路径，用户端渲染时补全为完整地址）
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
                layer.close(ii);
                layer.msg('上传失败', {icon: 2});
            }
        });
    };
    input.click();
}

function saveAgreement() {
    // 防御：仅当处于协议编辑页（存在表单）时才保存，避免快捷键在其他页面被误触发
    if (!document.getElementById('agreementForm')) return false;
    // 防御：Ctrl+S 连按时不重复提交
    if (agreementSaving) return false;

    var title = ($('#aTitle').val() || '').trim();
    if (!title) { layer.msg('请填写协议名称', {icon: 2}); return false; }

    // 同步编辑器内容到原始 textarea，保证表单可提交
    if (editorMDE) { try { editorMDE.codemirror.save(); } catch (e) {} }
    var content = $('#aContent').val();
    if (!content || !String(content).trim()) { layer.msg('请填写协议内容', {icon: 2}); return false; }

    agreementSaving = true;
    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=agreement_save',
        data: $('#agreementForm').serialize(),
        dataType: 'json',
        success: function (res) {
            layer.close(ii);
            agreementSaving = false;
            if (res && res.code === 0) {
                layer.alert(res.msg || '保存成功', {icon: 1, closeBtn: false}, function () {
                    window.location.href = 'agreement_manage.php';
                });
            } else {
                layer.alert((res && res.msg) ? res.msg : '保存失败', {icon: 2});
            }
        },
        error: function () {
            layer.close(ii);
            agreementSaving = false;
            layer.msg('服务器错误', {icon: 2});
        }
    });
    return false;
}

// 快捷键：Ctrl+S / Cmd+S 保存协议（替换浏览器默认的「保存网页」）
// 注意：后台使用 pjax 局部刷新，document 上的监听会跨页面保留，
//       因此在注册前先移除上一次的监听，并在回调里判断当前是否处于协议编辑页，
//       避免在其他后台页面误触发、或重复注册导致多次保存。
if (window.__agrSaveKeydown) {
    document.removeEventListener('keydown', window.__agrSaveKeydown, true);
    window.__agrSaveKeydown = null;
}
window.__agrSaveKeydown = function (e) {
    if (!document.getElementById('agreementForm')) return; // 不在协议编辑页则忽略
    var key = e.key || '';
    if ((e.ctrlKey || e.metaKey) && !e.altKey && (key === 's' || key === 'S' || e.keyCode === 83)) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') { e.stopImmediatePropagation(); }
        saveAgreement();
        return false;
    }
};
document.addEventListener('keydown', window.__agrSaveKeydown, true);
</script>
</body>
</html>
