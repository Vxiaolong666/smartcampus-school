<?php
$title = 'AI 配置';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger m-3">您的账号没有权限使用此功能</div>';
    include('./foot.php');
    exit;
}

// 首次访问且未配置时，写入默认配置，确保开箱即用
$defaults = [
    'AI_Token'   => 'sk-B1yXxFfv52GKjmUaWJF7Ota9gvpJ6WxE',
    'AI_BaseURL' => 'https://token.sensenova.cn/v1',
    'AI_Model'   => 'deepseek-v4-flash',
];
if (empty(@\conf('AI_Token'))) {
    foreach ($defaults as $k => $v) {
        saveSetting($k, $v);
    }
}

function aiVal($k) { return htmlspecialchars((string)@\conf($k), ENT_QUOTES, 'UTF-8'); }
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
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-robot me-2"></i>AI 配置</h3>
                <div class="block-options">
                    <button type="button" onclick="window.location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form onsubmit="return saveAiConfig(this)" method="post" name="edit-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="AI_Token"><b>API Token</b></label>
                                <input type="text" class="form-control form-control-lg" id="AI_Token" name="AI_Token" value="<?php echo aiVal('AI_Token'); ?>" placeholder="sk-...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="AI_BaseURL"><b>Base URL</b></label>
                                <input type="text" class="form-control form-control-lg" id="AI_BaseURL" name="AI_BaseURL" value="<?php echo aiVal('AI_BaseURL'); ?>" placeholder="https://token.sensenova.cn/v1">
                                <small class="text-muted">填写到 /v1 即可，系统自动拼接 /chat/completions</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label" for="AI_Model"><b>模型 ID</b></label>
                                <input type="text" class="form-control form-control-lg" id="AI_Model" name="AI_Model" value="<?php echo aiVal('AI_Model'); ?>" placeholder="deepseek-v4-flash">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
function saveAiConfig(obj){
  var ii = layer.load(2, {shade:[0.1,'#fff']});
  $.ajax({
    type : 'POST',
    url : 'Ajax.php?act=ai_config_save',
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
      layer.msg('服务器错误');
      return false;
    }
  });
  return false;
}
</script>
</body>
</html>
