<?php
$mod = 'user';
include('../Common/Core_brain.php');

if(!isset($_SESSION['userId'])){
    header('Location:./login.php');
    exit;
}

$userId = $_SESSION['userId'];
$userData = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");

if(!$userData){
    unset($_SESSION['userId']);
    unset($_SESSION['userName']);
    unset($_SESSION['userEmail']);
    unset($_SESSION['userQq']);
    header('Location:./login.php');
    exit;
}

$title='修改密码';
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
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"> 修改密码 </h3>
            </div>
            <div class="block-content">
                <form id="pwdForm">
                    <div class="mb-4">
                        <label class="form-label">旧密码 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg" name="old_password" placeholder="请输入当前密码" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">新密码 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg" name="new_password" placeholder="至少6位" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">确认新密码 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg" name="confirm_password" placeholder="再次输入新密码" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">安全验证 <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-alt-primary w-100" id="TencentCaptcha" data-appid="2047155482" data-cbfn="onTCaptchaResult">
                            <i class="fas fa-shield-alt"></i> 进行滑块验证
                        </button>
                        <div id="tcPassedTip" style="display:none; color:#059669; margin-top:8px;">
                            <small><i class="fas fa-check-circle"></i> 安全验证已通过</small>
                        </div>
                        <input type="hidden" name="tc_ticket" id="tc_ticket">
                        <input type="hidden" name="tc_randstr" id="tc_randstr">

                        <div class="captcha-wrapper" id="textCaptcha" style="display:none; margin-top:10px; align-items:center; gap:12px;">
                            <div class="input-group" style="flex:1;">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" name="captcha" id="captcha" class="form-control form-control-alt" placeholder="验证码">
                            </div>
                            <img src="../server-submit/captcha.php" id="captchaImg" alt="验证码" onclick="refreshCaptcha()" style="cursor:pointer;">
                        </div>
                        <div id="showTextCaptchaLink" style="text-align:right; margin-top:8px; display:none;">
                            <a href="javascript:void(0)" onclick="showTextCaptcha()">滑块异常？改用图片验证码</a>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">返回</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> 保存修改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include './foot.php'?>
<script>
window.onTCaptchaResult = function(res) {
    if (res && res.ret === 0) {
        $('#tc_ticket').val(res.ticket || '');
        $('#tc_randstr').val(res.randstr || '');
        const btn = $('#TencentCaptcha');
        btn.html('<i class="fas fa-check-circle"></i> 验证已通过');
        btn.css({
            'background': '#ecfdf5',
            'color': '#059669',
            'border': '1px solid #34d399',
            'cursor': 'default'
        });
        $('#tcPassedTip').show();
        $('#showTextCaptchaLink').hide();
    } else {
        $('#showTextCaptchaLink').show();
    }
};

function showTextCaptcha() {
    $('#TencentCaptcha').hide();
    $('#showTextCaptchaLink').hide();
    $('#tcPassedTip').hide();
    $('#textCaptcha').css('display', 'flex');
    $('#tc_ticket').val('');
    $('#tc_randstr').val('');
    refreshCaptcha();
}

function refreshCaptcha() {
    $('#captchaImg').attr('src', '../server-submit/captcha.php?' + Date.now());
}

$(document).ready(function(){
    $('#pwdForm').submit(function(){
        var tcTicket = $('#tc_ticket').val() || '';
        var tcRandstr = $('#tc_randstr').val() || '';
        var captcha = $('#captcha').val() || '';
        var oldPassword = $('input[name="old_password"]').val();
        var newPassword = $('input[name="new_password"]').val();
        var confirmPassword = $('input[name="confirm_password"]').val();
        
        if(tcTicket.length < 1 && captcha.length < 1){
            layer.alert('请先完成安全验证', {icon: 2});
            return false;
        }
        
        if(oldPassword == ''){
            layer.alert('请输入旧密码', {icon: 2});
            return false;
        }
        
        if(newPassword.length < 6){
            layer.alert('新密码长度不能少于6位', {icon: 2});
            return false;
        }
        
        if(newPassword !== confirmPassword){
            layer.alert('两次输入的新密码不一致', {icon: 2});
            return false;
        }
        
        if(oldPassword === newPassword){
            layer.alert('新密码不能与旧密码相同', {icon: 2});
            return false;
        }
        
        var formData = {
            old_password: oldPassword,
            new_password: newPassword,
            confirm_password: confirmPassword,
            tc_ticket: tcTicket,
            tc_randstr: tcRandstr,
            captcha: captcha
        };
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.attr('disabled', 'true');
        layer.msg('正在修改中，请稍后...');
        
        $.ajax({
            type: 'POST',
            url: 'ajax.php?act=change_password',
            data: formData,
            dataType: 'json',
            success: function(data){
                if(data.code == 0){
                    layer.alert(data.msg, {
                        icon: 1,
                        closeBtn: false
                    }, function(){
                        window.location.href = 'index.php';
                    });
                }else{
                    submitBtn.removeAttr('disabled');
                    if(captcha.length > 0){
                        refreshCaptcha();
                        $('#captcha').val('');
                    }
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
