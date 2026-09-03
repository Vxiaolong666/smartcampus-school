<?php
$mod = 'admin';
$title='添加用户';
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
                            <a class="link-fx" href="javascript:void(0)">用户管理</a>
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
                <h3 class="block-title"> 添加用户 </h3>
            </div>
            <div class="block-content">
                <form id="addForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">用户名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" name="username" placeholder="4-20位字母数字下划线" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">QQ号</label>
                                <input type="text" class="form-control form-control-lg" name="qq" placeholder="请输入QQ号">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-lg" name="password" placeholder="至少6位" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">确认密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-lg" name="repassword" placeholder="再次输入密码" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">邮箱 <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-lg" name="email" placeholder="请输入邮箱" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">积分</label>
                                <input type="number" class="form-control form-control-lg" name="points" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">等级</label>
                                <input type="number" class="form-control form-control-lg" name="level" value="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">账号状态</label>
                                <select class="form-control form-control-lg" name="is_banned">
                                    <option value="0">正常</option>
                                    <option value="1">已封禁</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">邮箱验证状态</label>
                        <select class="form-control form-control-lg" name="email_verified">
                            <option value="0">未验证</option>
                            <option value="1">已验证</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='ulist.php'">返回</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> 添加用户
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
$(document).ready(function(){
    $('#addForm').submit(function(){
        var username = $('input[name="username"]').val();
        var password = $('input[name="password"]').val();
        var repassword = $('input[name="repassword"]').val();
        var email = $('input[name="email"]').val();
        var qq = $('input[name="qq"]').val();
        var points = $('input[name="points"]').val();
        var level = $('input[name="level"]').val();
        var is_banned = $('select[name="is_banned"]').val();
        var email_verified = $('select[name="email_verified"]').val();
        
        if(username.length < 4 || username.length > 20){
            layer.alert('用户名长度必须在4-20位之间', {icon: 2});
            return false;
        }
        
        var usernameRegex = /^[a-zA-Z0-9_]+$/;
        if(!usernameRegex.test(username)){
            layer.alert('用户名只能包含字母、数字、下划线', {icon: 2});
            return false;
        }
        
        if(password.length < 6){
            layer.alert('密码长度不能少于6位', {icon: 2});
            return false;
        }
        
        if(password !== repassword){
            layer.alert('两次输入的密码不一致', {icon: 2});
            return false;
        }
        
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!emailRegex.test(email)){
            layer.alert('邮箱格式不正确', {icon: 2});
            return false;
        }
        
        var formData = {
            username: username,
            password: password,
            email: email,
            qq: qq,
            points: points,
            level: level,
            is_banned: is_banned,
            email_verified: email_verified
        };
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.attr('disabled', 'true');
        layer.msg('正在添加中，请稍后...');
        
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=setUser&type=add',
            data: formData,
            dataType: 'json',
            success: function(data){
                if(data.code == 0){
                    layer.alert(data.msg, {
                        icon: 1,
                        closeBtn: false
                    }, function(){
                        window.location.href = 'ulist.php';
                    });
                }else{
                    submitBtn.removeAttr('disabled');
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function(){
                submitBtn.removeAttr('disabled');
                layer.alert('网络错误，请稍后重试', {icon: 2});
            }
        });
        
        return false;
    });
});
</script>
</body>
</html>
