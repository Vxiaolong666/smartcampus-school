<?php
$mod = 'admin';
$title='小程序配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

$config = $DB->getRow("SELECT * FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
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
                <h3 class="block-title">微信小程序配置</h3>
            </div>
            <div class="block-content">
                <form onsubmit="return saveWxConfig(this)" method="post" name="wx-config-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="appid"><b>AppID (小程序ID)</b></label>
                                <input type="text" class="form-control" name="appid" id="appid" value="<?php echo $config['appid'] ?? ''; ?>" placeholder="请输入小程序AppID">
                                <div class="form-text">在微信公众平台 - 开发管理 - 开发设置中获取</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="secret"><b>AppSecret (小程序密钥)</b></label>
                                <input type="text" class="form-control" name="secret" id="secret" value="<?php echo $config['secret'] ?? ''; ?>" placeholder="请输入小程序AppSecret">
                                <div class="form-text">在微信公众平台 - 开发管理 - 开发设置中获取</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="name"><b>配置名称</b></label>
                                <input type="text" class="form-control" name="name" id="name" value="<?php echo $config['name'] ?? ''; ?>" placeholder="请输入配置名称（可选）">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存配置</b>
                        </button>
                        <a href="subscription_manage.php?tab=templates" class="btn btn-alt-primary ms-2">
                            <i class="fa fa-list opacity-50 me-1"></i> <b>管理消息模板</b>
                        </a>
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
                    <h5 class="alert-heading">如何获取小程序配置信息？</h5>
                    <hr>
                    <ol class="mb-0">
                        <li>登录 <a href="https://mp.weixin.qq.com" target="_blank">微信公众平台</a></li>
                        <li>进入「开发管理」-「开发设置」</li>
                        <li>在「开发者ID」区域可以找到 AppID 和 AppSecret</li>
                        <li>AppSecret 需要管理员扫码才能查看</li>
                    </ol>
                </div>
                <div class="alert alert-warning">
                    <h5 class="alert-heading">注意事项</h5>
                    <hr>
                    <ul class="mb-0">
                        <li>AppSecret 非常重要，请妥善保管，不要泄露</li>
                        <li>如果 AppSecret 泄露，请立即重置</li>
                        <li>配置保存后，小程序推送功能才能正常使用</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
function saveWxConfig(obj){
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'Ajax.php?act=save_wx_config',
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
