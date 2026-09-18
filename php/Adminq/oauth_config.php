<?php
$mod = 'admin';
$title='聚合登录配置';
/** @var array|null $adminData */
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
    echo "您的账号没有权限使用此功能";
    exit;
}
include './head.php';

require_once '../funsion/OAuthHelper.php';
$config = OAuthHelper::getConfig($DB);
$enabledChannels = $config && !empty($config['channels_arr']) ? $config['channels_arr'] : [];

// 用户第三方绑定信息（关联用户表，便于判断账号是否存在）
$bindings = $DB->getAll("SELECT o.*, u.username, u.email FROM nteam_user_oauth o LEFT JOIN nteam_users u ON o.user_id = u.id ORDER BY o.id DESC");
if (!is_array($bindings)) {
    $bindings = [];
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
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
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
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">聚合登录设置</h3>
            </div>
            <div class="block-content">
                <form onsubmit="return saveOauthConfig(this)" method="post" name="oauth-config-form">
                    <div class="mb-4">
                        <label class="form-label"><b>聚合登录总开关</b></label>
                        <div class="space-x-2">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="enable" value="1" <?=($config && $config['enable']==1)?"checked":""?>> 启用
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="enable" value="0" <?=($config && $config['enable']==0)?"checked":""?>> 禁用
                                </label>
                            </div>
                        </div>
                        <div class="form-text">开启后，用户登录页与个人资料页将显示已启用渠道的第三方登录 / 绑定入口</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="appid"><b>AppID</b></label>
                                <input type="text" class="form-control form-control-lg" name="appid" id="appid" value="<?php echo $config['appid'] ?? ''; ?>" placeholder="请输入聚合登录 AppID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="appkey"><b>AppKey</b></label>
                                <input type="text" class="form-control form-control-lg" name="appkey" id="appkey" value="<?php echo $config['appkey'] ?? ''; ?>" placeholder="请输入聚合登录 AppKey">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><b>登录渠道</b></label>
                        <div class="row">
                            <?php foreach (OAuthHelper::CHANNELS as $type => $meta): ?>
                            <div class="col-md-3 col-sm-4 col-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="channels[]" value="<?php echo $type; ?>" id="ch_<?php echo $type; ?>" <?=(in_array($type, $enabledChannels, true))?"checked":""?>>
                                    <label class="form-check-label" for="ch_<?php echo $type; ?>">
                                        <i class="<?php echo $meta['icon']; ?>" style="color:<?php echo $meta['color']; ?>;"></i> <?php echo $meta['name']; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">仅勾选的渠道会在用户端展示，可随时调整</div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">用户绑定信息</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-danger me-2" onclick="clearInvalidOauth()"><i class="fa fa-broom me-1"></i>清除无效</button>
                    <span class="badge bg-primary">共 <?php echo count($bindings); ?> 条</span>
                </div>
            </div>
            <div class="block-content">
                <?php if (empty($bindings)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-link-slash fa-2x mb-2 d-block opacity-50"></i>
                        暂无用户绑定记录
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:60px;">ID</th>
                                <th style="min-width:90px;">渠道</th>
                                <th style="min-width:130px;">第三方账号</th>
                                <th class="d-none d-md-table-cell" style="min-width:150px;">第三方UID</th>
                                <th style="min-width:130px;">绑定用户</th>
                                <th class="d-none d-sm-table-cell" style="min-width:150px;">绑定时间</th>
                                <th class="text-center" style="min-width:90px;">状态</th>
                                <th class="text-center" style="min-width:80px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bindings as $b):
                                $meta = OAuthHelper::CHANNELS[$b['oauth_type']] ?? ['name' => $b['oauth_type'], 'icon' => 'fa fa-link', 'color' => '#6c757d'];
                                $userExists = !empty($b['username']);
                            ?>
                            <tr>
                                <td class="text-center"><?php echo intval($b['id']); ?></td>
                                <td>
                                    <i class="<?php echo $meta['icon']; ?> me-1" style="color:<?php echo $meta['color']; ?>;"></i>
                                    <?php echo htmlspecialchars($meta['name']); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($b['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($b['avatar']); ?>" alt="avatar" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;" onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($b['nickname'] !== '' && $b['nickname'] !== null ? $b['nickname'] : '未设置'); ?></span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><code><?php echo htmlspecialchars($b['oauth_uid']); ?></code></td>
                                <td>
                                    <?php if ($userExists): ?>
                                        <a href="./uset.php?my=edit&id=<?php echo intval($b['user_id']); ?>"><?php echo htmlspecialchars($b['username']); ?></a>
                                        <div class="fs-sm text-muted">ID: <?php echo intval($b['user_id']); ?></div>
                                    <?php else: ?>
                                        <span class="text-danger">用户不存在</span>
                                        <div class="fs-sm text-muted">ID: <?php echo intval($b['user_id']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars($b['create_time']); ?></td>
                                <td class="text-center">
                                    <?php if ($userExists): ?>
                                        <span class="badge bg-success">正常</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">账号不存在</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="unbindOauthBinding(<?php echo intval($b['id']); ?>)">解绑</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">配置说明</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5 class="alert-heading">聚合登录是什么？</h5>
                    <hr>
                    <p class="mb-0">聚合登录基于 OAuth2.0，对接 <a href="https://u.daib.cn" target="_blank">黎洛云聚合平台</a>，可一站式接入 QQ、微信、支付宝、微博、GitHub 等 18 种第三方登录。用户授权后，系统自动创建账号（或绑定到已有账号）并登录。</p>
                </div>
                <div class="alert alert-warning">
                    <h5 class="alert-heading">注意事项</h5>
                    <hr>
                    <ul class="mb-0">
                        <li>开启聚合登录前，请务必填写正确的 AppID 与 AppKey</li>
                        <li>回调地址由系统自动拼接为：<code><?php echo OAuthHelper::buildCallbackUrl(); ?>?type={渠道}&state={状态}</code></li>
                        <li>请在聚合平台后台将本站点回调地址加入白名单</li>
                        <li>第三方授权绑定数据存储在 <code>nteam_user_oauth</code> 表，已对 (oauth_type, oauth_uid) 建立唯一索引，同一平台同一账号不会重复写入</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
function saveOauthConfig(obj){
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'Ajax.php?act=save_oauth_config',
        data : $(obj).serialize(),
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.alert(data.msg, {
                    icon: 1,
                    closeBtn: false
                }, function(){
                    window.location.reload()
                });
            }else{
                layer.alert(data.msg, {icon: 2})
            }
        },
        error:function(data){
            layer.close(ii);
            layer.msg('服务器错误');
            return false;
        }
    });
    return false;
}

// 解绑单条第三方绑定
function unbindOauthBinding(id){
    layer.confirm('确定要解绑该第三方账号吗？解绑后该账号将无法通过此方式登录。', {
        btn: ['确定解绑', '取消'],
        icon: 3
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=delete_oauth_binding',
            data: {id: id},
            dataType: 'json',
            success: function(data){
                layer.close(ii);
                if(data.code == 0){
                    layer.msg(data.msg, {icon: 1}, function(){ window.location.reload(); });
                }else{
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function(){
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
    });
}

// 清除无效绑定（关联用户已不存在）
function clearInvalidOauth(){
    layer.confirm('确定要清除全部「账号不存在」的无效绑定记录吗？此操作不可恢复。', {
        btn: ['确定清除', '取消'],
        icon: 3
    }, function(){
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clear_invalid_oauth',
            dataType: 'json',
            success: function(data){
                layer.close(ii);
                if(data.code == 0){
                    layer.msg(data.msg, {icon: 1}, function(){ window.location.reload(); });
                }else{
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function(){
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
    });
}
</script>
</body>
</html>
