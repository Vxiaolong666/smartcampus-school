<?php
$mod = 'admin';
$title = '协议管理';
include('./head.php');
require_once __DIR__ . '/../funsion/Agreements.php';

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

function hv($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tableReady = agreementTableReady($DB);
$agreements = $tableReady ? agreementList($DB, false) : [];
?>
<style>
@media (max-width: 768px) {
    .table-responsive .table th,
    .table-responsive .table td {
        padding: 0.5rem 0.5rem;
        font-size: 0.8125rem;
        white-space: nowrap;
    }
    .table-responsive .table td .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
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
                    <h1 class="h3 fw-bold mb-2"><?php echo $title ?></h1>
                    <div class="text-muted fs-sm">统一维护用户端的《协议中心》内容，支持新增、编辑、启停与删除；内容使用 Markdown，用户端按 Markdown 渲染。</div>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item"><a class="link-fx" href="javascript:void(0)">后台首页</a></li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo $title ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content animated fadeIn">
        <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            协议表未创建，请先执行 <code>php/agreements_update.sql</code>（或访问 <code>/agreements_update.php</code> 一次性执行）后再使用本功能。
        </div>
        <?php else: ?>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-file-signature me-2"></i>协议列表</h3>
                <div class="block-options">
                    <a class="btn btn-sm btn-alt-primary" href="agreement_edit.php">
                        <i class="fa fa-plus me-1"></i>新增协议
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:60px">ID</th>
                                <th>协议名称</th>
                                <th style="width:160px">标识(key)</th>
                                <th style="width:80px">排序</th>
                                <th style="width:110px">状态</th>
                                <th style="width:150px">更新时间</th>
                                <th style="width:200px">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($agreements)): ?>
                            <tr><td colspan="7" class="text-center text-muted">暂无协议数据</td></tr>
                        <?php else: foreach ($agreements as $a): ?>
                            <tr>
                                <td><?php echo intval($a['id']); ?></td>
                                <td>
                                    <span class="fw-semibold"><?php echo hv($a['title']); ?></span>
                                    <?php if (intval($a['is_system']) === 1): ?>
                                        <span class="badge bg-info ms-1">内置</span>
                                    <?php endif; ?>
                                </td>
                                <td><code class="key-badge"><?php echo hv($a['akey']); ?></code></td>
                                <td><?php echo intval($a['sort_order']); ?></td>
                                <td>
                                    <?php if (intval($a['status']) === 1): ?>
                                        <span class="badge bg-success">启用</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">停用</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fs-sm text-muted"><?php echo hv($a['updated_at']); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-alt-secondary" href="agreement_edit.php?id=<?php echo intval($a['id']); ?>">编辑</a>
                                    <button type="button" class="btn btn-sm btn-alt-warning" onclick="toggleStatus(<?php echo intval($a['id']); ?>, <?php echo intval($a['status']) === 1 ? 0 : 1; ?>)">
                                        <?php echo intval($a['status']) === 1 ? '停用' : '启用'; ?>
                                    </button>
                                    <?php if (intval($a['is_system']) !== 1): ?>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="delAgreement(<?php echo intval($a['id']); ?>)">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-text mt-2">
                    提示：标识(key)用于用户端按协议取内容，<b>内置协议请勿修改标识</b>；如「用户注销协议」的标识固定为 <code>cancel</code>，注销流程依赖它。
                    新增协议后无需更新 App，用户端会自动出现在《协议中心》列表中（需重新进入列表页）。
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'foot.php'?>
<script>
function postAct(act, data, tip) {
    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=' + act,
        data: data,
        dataType: 'json',
        success: function (res) {
            layer.close(ii);
            if (res && res.code === 0) {
                if (tip === false) {
                    layer.msg(res.msg || '操作成功', {icon: 1}, function () {
                        window.location.reload();
                    });
                } else {
                    layer.alert(res.msg || '操作成功', {icon: 1, closeBtn: false}, function () {
                        window.location.reload();
                    });
                }
            } else {
                layer.alert((res && res.msg) ? res.msg : '操作失败', {icon: 2});
            }
        },
        error: function () {
            layer.close(ii);
            layer.msg('服务器错误', {icon: 2});
        }
    });
}

// 启用 / 停用
function toggleStatus(id, status) {
    postAct('agreement_status', {id: id, status: status}, false);
}

// 删除（系统内置协议后台已隐藏删除按钮）
function delAgreement(id) {
    layer.confirm('确定要删除该协议吗？操作不可恢复。', {icon: 3, title: '提示', btn: ['确定删除', '取消']}, function (index) {
        layer.close(index);
        postAct('agreement_del', {id: id});
    });
}
</script>
</body>
</html>
