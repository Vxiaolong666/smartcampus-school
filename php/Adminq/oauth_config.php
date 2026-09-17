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
</script>
</body>
</html>
