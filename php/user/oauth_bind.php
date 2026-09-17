<?php
$mod = 'user';
include('../Common/Core_brain.php');

if (!isset($_SESSION['userId'])) {
    header('Location:./login.php');
    exit;
}

$userId = $_SESSION['userId'];
$title = '第三方账号绑定';
$requireLogin = true;
include('./head.php');
?>
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
                            <a class="link-fx" href="index.php">用户中心</a>
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
                <h3 class="block-title"><i class="fab fa-weixin me-2"></i>第三方账号绑定</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>说明：</strong>绑定第三方账号后，可使用对应平台一键登录本系统。绑定前请确认已通过账号密码登录本账号。
                    </div>
                </div>
                <div id="oauthBindArea">
                    <div class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin me-1"></i> 加载中...</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include './foot.php'?>
<script>
// 绑定成功提示
const oauthAlert = new URLSearchParams(window.location.search).get('oauth_bind');
if (oauthAlert) {
    layer.alert('第三方账号绑定成功', {icon: 1});
}

loadOauthBinds();

function loadOauthBinds() {
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=get_oauth_binds',
        dataType: 'json',
        success: function(data) {
            if (data.code != 0) {
                $('#oauthBindArea').html('<div class="text-muted">加载失败</div>');
                return;
            }
            if (data.enabled != 1) {
                $('#oauthBindArea').html('<div class="text-muted">管理员未开启聚合登录功能</div>');
                return;
            }
            var boundMap = {};
            $.each(data.binds, function(i, b) { boundMap[b.type] = b; });

            var html = '<div class="row">';
            $.each(data.channels, function(type, meta) {
                var isBound = boundMap[type];
                html += '<div class="col-md-6 mb-3">';
                html += '<div class="d-flex align-items-center justify-content-between border rounded p-3">';
                html += '<div class="d-flex align-items-center">';
                html += '<i class="' + meta.icon + ' fa-lg me-2" style="color:' + meta.color + ';"></i>';
                html += '<div><div class="fw-medium">' + meta.name + '</div>';
                if (isBound) {
                    html += '<div class="fs-sm text-muted">' + (isBound.nickname ? escapeHtml(isBound.nickname) : '已绑定') + '</div>';
                } else {
                    html += '<div class="fs-sm text-muted">未绑定</div>';
                }
                html += '</div></div>';
                if (isBound) {
                    html += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="unbindOauth(\'' + type + '\')">解绑</button>';
                } else {
                    html += '<button type="button" class="btn btn-sm btn-outline-success" onclick="bindOauth(\'' + type + '\')">绑定</button>';
                }
                html += '</div></div>';
            });
            html += '</div>';

            if (Object.keys(data.channels).length === 0) {
                html = '<div class="text-muted">暂无可绑定的登录渠道</div>';
            }

            $('#oauthBindArea').html(html);
        },
        error: function() {
            $('#oauthBindArea').html('<div class="text-muted">加载失败</div>');
        }
    });
}

function bindOauth(type) {
    layer.msg('正在跳转授权...');
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=get_oauth_url',
        data: {type: type, state: 'bind'},
        dataType: 'json',
        success: function(res) {
            if (res.code == 0 && res.url) {
                window.location.href = res.url;
            } else {
                layer.alert(res.msg || '获取授权地址失败', {icon: 2});
            }
        },
        error: function() {
            layer.alert('网络错误，请稍后重试', {icon: 2});
        }
    });
}

function unbindOauth(type) {
    layer.confirm('确定要解绑该第三方账号吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        layer.msg('正在解绑...', {icon: 16, time: 0});
        $.ajax({
            type: 'POST',
            url: 'ajax.php?act=unbind_oauth',
            data: {type: type},
            dataType: 'json',
            success: function(data) {
                layer.closeAll();
                if (data.code == 0) {
                    layer.msg('解绑成功', {icon: 1});
                    loadOauthBinds();
                } else {
                    layer.alert(data.msg || '解绑失败', {icon: 2});
                }
            },
            error: function() {
                layer.closeAll();
                layer.alert('网络错误，请稍后重试', {icon: 2});
            }
        });
    });
}

function escapeHtml(str) {
    return $('<div>').text(str).html();
}
</script>
