<?php
$mod = 'admin';
$title='发信邮箱配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';
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
                <h3 class="block-title"> 发信邮箱配置 </h3>
                <div class="block-options">
                    <button type="button" onclick="" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mb-4">
                                <div class="col-8">
                                    <label class="form-label" for="Mail_Site_Url"><b>网站域名（邮件链接地址）</b></label>
                                    <input type="text" class="form-control form-control-lg" id="Mail_Site_Url" name="Mail_Site_Url" value="<?php echo conf('Mail_Site_Url') ?: conf('Url');?>" placeholder="请输入网站域名，用于邮件中的链接">
                                    <small>邮件中"查看详情"等链接使用的域名地址，如：http://example.com/</small>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-alt-primary form-control-lg d-block w-100" onclick="autoGetUrl()">
                                        <i class="fa fa-magic me-1"></i>自动获取
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Smtp"><b>SMTP地址</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Mail_Smtp" value="<?php echo conf('Mail_Smtp');?>" placeholder="请输入SMTP地址">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Port"><b>SMTP端口</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Mail_Port" value="<?php echo conf('Mail_Port');?>" placeholder="请输入SMTP端口">
                                    <small>SSL:465, TLS:587</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Secure"><b>安全协议</b></label>
                                    <select class="form-control form-control-lg" name="Mail_Secure">
                                        <option value="ssl" <?php echo conf('Mail_Secure') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo conf('Mail_Secure') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="none" <?php echo conf('Mail_Secure') == 'none' ? 'selected' : ''; ?>>无加密</option>
                                    </select>
                                    <small>QQ邮箱推荐使用 SSL:465 或 TLS:587</small>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Name"><b>邮箱账号</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Mail_Name" value="<?php echo conf('Mail_Name');?>" placeholder="请输入邮箱账号">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Pwd"><b>邮箱密码（授权码）</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Mail_Pwd" value="<?php echo conf('Mail_Pwd');?>" placeholder="请输入邮箱密码（授权码）">
                                    <small>QQ邮箱请填写获取的授权码</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label class="form-label" for="Mail_Admin_Emails"><b>管理员接收邮箱地址</b></label>
                                    <input type="text" class="form-control form-control-lg" name="Mail_Admin_Emails" value="<?php echo conf('Mail_Admin_Emails');?>" placeholder="请输入管理员接收邮箱地址，多个用逗号分隔">
                                    <small>多个邮箱地址请用英文逗号(,)分隔，例如: admin1@example.com,admin2@example.com</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Same_Email_Interval"><b>同一邮箱最小发送间隔（秒）</b></label>
                                    <input type="number" class="form-control form-control-lg" name="Mail_Same_Email_Interval" value="<?php echo conf('Mail_Same_Email_Interval');?>" placeholder="请输入间隔秒数">
                                    <small>同一邮箱地址两次发送的最小间隔时间</small>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="Mail_Platform_Per_Minute"><b>平台1分钟内最多发送邮件数</b></label>
                                    <input type="number" class="form-control form-control-lg" name="Mail_Platform_Per_Minute" value="<?php echo conf('Mail_Platform_Per_Minute');?>" placeholder="请输入最大发送数量">
                                    <small>平台每分钟最多发送的邮件数量限制</small>
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
        <div class="block block-rounded mt-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"> 邮件发送测试 </h3>
                <div class="block-options">
                    <button type="button" onclick="" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form onsubmit="return sendTestMail(this)" method="post" name="test-form">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label class="form-label" for="test_email"><b>测试邮箱地址</b></label>
                                    <input type="email" class="form-control form-control-lg" name="test_email" value="" placeholder="请输入接收测试邮件的邮箱地址">
                                    <small>输入一个有效的邮箱地址来测试邮件发送功能</small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-paper-plane opacity-50 me-1"></i> <b>发送测试邮件</b>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
function sendTestMail(obj){
  var testEmail = $(obj).find('input[name="test_email"]').val();
  if(testEmail == ''){
    layer.alert('请输入测试邮箱地址', {icon: 2});
    return false;
  }
  var ii = layer.load(2, {shade:[0.1,'#fff']});
  $.ajax({
    type : 'POST',
    url : 'Ajax.php?act=testMail',
    data : {test_email: testEmail},
    dataType : 'json',
    success : function(data) {
      layer.close(ii);
      if(data.code == 0){
        layer.alert(data.msg, {
          icon: 1,
          closeBtn: false
        });
      }else{
        layer.alert(data.msg, {icon: 2})
      }
    },
    error:function(xhr, status, error){
      layer.close(ii);
      var errorMsg = '服务器错误';
      if(xhr.responseText){
        try {
          var response = JSON.parse(xhr.responseText);
          if(response.msg){
            errorMsg = response.msg;
          } else {
            errorMsg = xhr.responseText.substring(0, 200);
          }
        } catch(e) {
          errorMsg = xhr.responseText.substring(0, 200);
        }
      } else if(error) {
        errorMsg = error;
      } else if(status) {
        errorMsg = status;
      }
      layer.alert('发送失败：' + errorMsg, {icon: 2});
      return false;
    }
  });
  return false;
}
function autoGetUrl(){
  var currentUrl = window.location.origin;
  var pathName = window.location.pathname;
  var adminIndex = pathName.indexOf('/Adminq/');
  if(adminIndex > 0){
    currentUrl = currentUrl + pathName.substring(0, adminIndex + 1);
  }
  if(!currentUrl.endsWith('/')){
    currentUrl = currentUrl + '/';
  }
  $('#Mail_Site_Url').val(currentUrl);
  layer.msg('已自动获取当前域名：' + currentUrl, {icon: 1});
}
</script>
</body>
</html>