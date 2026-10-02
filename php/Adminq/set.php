<?php
$mod = 'admin';
$title='网站信息配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';

// ===== 每日维护时段（独立于「电费推送配置 → 服务停止时段」） =====
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// 字段是否已升级（未升级时页面只给出导入提示，不展示表单）
$maintColumnsReady = false;
try {
    $colChk = @$DB->query("SHOW COLUMNS FROM nteam_config LIKE 'Maintenance_Start'");
    if ($colChk && $colChk->fetch()) $maintColumnsReady = true;
} catch (Exception $e) {
    $maintColumnsReady = false;
}

// 当前生效时段（与 api.php 的 get_maintenance_config 同一套取值逻辑）：
// 只读本页维护的独立字段，留空 / 非法时使用内置默认 23:00 ~ 06:00，
// 与「电费推送配置 → 服务停止时段」完全无关。
$maintStart = $maintColumnsReady ? trim((string)conf('Maintenance_Start')) : '';
$maintEnd   = $maintColumnsReady ? trim((string)conf('Maintenance_End'))   : '';
$maintUsingDefault = (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $maintStart) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $maintEnd));
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $maintStart)) $maintStart = '23:00';
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $maintEnd))   $maintEnd   = '06:00';
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
                <h3 class="block-title"> 网站信息设置 </h3>
                <div class="block-options">
                    <button type="button" onclick="" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="Name"><b>网站名称</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Name" value="<?php echo conf('Name');?>" placeholder="请输入网站名称">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="SiteName"><b>网站标题</b></label>
                                    <input type="text" class="form-control form-control-lg" name="SiteName" value="<?php echo conf('SiteName');?>" placeholder="请输入网站简称">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-4">
                                <label class="form-label" for="Url"><b>网站域名</b></label>
                                <input type="text" class="form-control form-control-lg" id="Url" name="Url" value="<?php echo conf('Url');?>" placeholder="请输入网站域名">
                            </div>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-7">
                            <div class="mb-4">
                                <label class="form-label" for="Descriptison"><b>网站描述</b></label>
                                <textarea class="form-control form-control-lg" rows="4" name="Descriptison" placeholder="请输入站点描述"><?php echo conf('Descriptison');?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="Index_Fang"><b>防止xxs扒站JS开关</b></label>
                                <div class="space-x-2">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="index_fang" value="0" <?=conf('Index_Fang')==0?"checked":""?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="index_fang" value="1" <?=conf('Index_Fang')==1?"checked":""?>>启用
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="Jump"><b>QQVX访问跳出提示页</b></label>
                                <div class="space-x-2">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="Jump" value="0" <?=conf('Jump')==0?"checked":""?>>禁用
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="Jump" value="1" <?=conf('Jump')==1?"checked":""?>>启用
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-4">
                                <label class="form-label" for="Keywords"><b>网站关键词</b></label>
                                <input type="text" class="form-control form-control-lg" name="Keywords" value="<?php echo conf('Keywords');?>" placeholder="请输入站点关键词">
                            </div>
                            <div class="col-mb-5">
                                <div class="mb-4">
                                    <label class="form-label" for="ICP"><b>ICP备案号</b></label>
                                    <input type="text" class="form-control form-control-lg" id="ICP" name="ICP" value="<?php echo conf('ICP');?>" placeholder="请输入ICP备案号">
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============ 每日维护时段（独立配置） ============ -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-wrench me-2"></i>每日维护时段</h3>
            </div>
            <div class="block-content">
                <p class="text-muted fs-sm mb-4">
                    在此时段内，UNIAPP 端与微信小程序端启动、或从后台回到前台时会进入「系统维护」页；
                    开始时间晚于结束时间表示跨天（例如 23:00 ~ 次日 06:00）。
                </p>

                <?php if (!$maintColumnsReady): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    当前数据库缺少 <code>Maintenance_Start / Maintenance_End</code> 字段，
                    请先导入 <code>php/config_maintenance_update.sql</code> 后再设置。
                </div>
                <?php else: ?>
                <form onsubmit="return saveMaintenanceSetting(this)" method="post" name="maintenance-form">
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <label class="form-label" for="Maintenance_Start"><b>开始时间</b></label>
                                <input type="time" class="form-control form-control-lg" id="Maintenance_Start" name="Maintenance_Start"
                                       value="<?php echo h(conf('Maintenance_Start')); ?>" placeholder="23:00">
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <label class="form-label" for="Maintenance_End"><b>结束时间</b></label>
                                <input type="time" class="form-control form-control-lg" id="Maintenance_End" name="Maintenance_End"
                                       value="<?php echo h(conf('Maintenance_End')); ?>" placeholder="06:00">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light fs-sm">
                        <i class="fa fa-clock me-1"></i>
                        当前生效时段：<b><?php echo h($maintStart); ?> ~ <?php echo h($maintEnd); ?></b>
                        <?php if ($maintUsingDefault): ?>
                        <span class="text-muted">（尚未配置，客户端暂按系统默认 23:00 ~ 06:00 判断；在这里填写并保存后即以此处为准）</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存维护时段</b>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
function checkURL(obj)
{
  var url = $(obj).val();

  if (url.indexOf(" ")>=0){
    url = url.replace(/ /g,"");
  }
  if (url.toLowerCase().indexOf("http://")<0 && url.toLowerCase().indexOf("https://")<0){
    url = "http://"+url;
  }
  if (url.slice(url.length-1)!="/"){
    url = url+"/";
  }
  $(obj).val(url);
}
// ===== 每日维护时段：独立保存（走独立的 act，写入 nteam_config，与网站信息互不影响） =====
function saveMaintenanceSetting(obj){
  var ii = layer.load(2, {shade:[0.1,'#fff']});
  $.ajax({
    type : 'POST',
    url : 'Ajax.php?act=save_maintenance_config',
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
    error:function(){
      layer.close(ii);
      layer.msg('服务器错误');
    }
  });
  return false;
}
function saveSetting(obj){
  var ii = layer.load(2, {shade:[0.1,'#fff']});
  $.ajax({
    type : 'POST',
    url : 'Ajax.php?act=set',
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
function saveSettings(obj){
  var ii = layer.load(2, {shade:[0.1,'#fff']});
  $.ajax({
    type : 'POST',
    url : 'Ajax.php?act=sets',
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
