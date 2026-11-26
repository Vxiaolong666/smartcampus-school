<?php
$mod = 'user';
include('../Common/Core_brain.php');

if(!isset($_SESSION['userId'])){
    header('Location:./login.php');
    exit;
}

$userId = $_SESSION['userId'];
$userData = $DB->getRow("SELECT id, username, email, qq, points, level, register_time, last_login_time, nickname, avatar, wx_openid FROM nteam_users WHERE id='$userId'");

if(!$userData){
    nteam_session_unset(['userId', 'userName', 'userEmail', 'userQq']);
    header('Location:./login.php');
    exit;
}

$title='个人资料';
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
                <h3 class="block-title">基本信息</h3>
            </div>
            <div class="block-content">
                <form id="profileForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <img id="userAvatar" src="<?php echo htmlspecialchars(($userData['avatar'] && strpos($userData['avatar'], $siteBasePath) !== 0) ? $siteBasePath . $userData['avatar'] : $userData['avatar']); ?>" alt="头像" class="img-avatar" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #e5e5e5;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%236c757d%22/%3E%3Ccircle cx=%2250%22 cy=%2235%22 r=%2220%22 fill=%22%23fff%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2235%22 ry=%2225%22 fill=%22%23fff%22/%3E%3C/svg%3E'">
                                </div>
                                <label class="btn btn-alt-primary btn-sm" for="avatarInput">
                                    <i class="fa fa-upload me-1"></i> 更换头像
                                </label>
                                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;">
                                <div class="text-muted mt-2" style="font-size:12px;">支持JPG、PNG、GIF，不超过2MB</div>
                               
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">昵称</label>
                                        <input type="text" class="form-control form-control-lg" name="nickname" value="<?php echo htmlspecialchars($userData['nickname'] ?: ''); ?>" placeholder="请输入昵称" maxlength="20">
                                        <div class="form-text">昵称长度不超过20个字符</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">用户名</label>
                                        <input type="text" class="form-control form-control-lg" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                                        <div class="form-text">用户名不可修改</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">邮箱</label>
                                        <input type="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                                        <div class="form-text">邮箱不可修改</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">QQ</label>
                                        <input type="text" class="form-control form-control-lg" value="<?php echo htmlspecialchars($userData['qq'] ?: ''); ?>" disabled>
                                        <div class="form-text">QQ不可修改</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">积分</label>
                                        <input type="text" class="form-control form-control-lg" value="<?php echo (int)$userData['points']; ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">等级</label>
                                        <input type="text" class="form-control form-control-lg" value="<?php echo (int)$userData['level']; ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">微信绑定</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-lg" value="<?php echo !empty($userData['wx_openid']) ? '已绑定' : '未绑定'; ?>" disabled>
                                            <?php if(!empty($userData['wx_openid'])): ?>
                                                <button type="button" class="btn btn-outline-danger" id="unbindWxBtn">解绑</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-success" id="bindWxBtn">绑定</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">注册时间</label>
                                        <input type="text" class="form-control form-control-lg" value="<?php echo $userData['register_time']; ?>" disabled>
                                    </div>
                                </div>
                            </div>
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
var avatarChanged = false;
var newAvatarUrl = '';

$('#avatarInput').change(function(){
    var file = this.files[0];
    if(!file) return;
    
    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if(allowedTypes.indexOf(file.type) === -1){
        layer.alert('只支持JPG、PNG、GIF、WEBP格式的图片', {icon: 2});
        this.value = '';
        return;
    }
    
    if(file.size > 2 * 1024 * 1024){
        layer.alert('图片大小不能超过2MB', {icon: 2});
        this.value = '';
        return;
    }
    
    var formData = new FormData();
    formData.append('avatar', file);
    
    var submitBtn = $('#profileForm').find('button[type="submit"]');
    submitBtn.attr('disabled', 'true');
    layer.msg('正在上传头像...', {icon: 16, time: 0});
    
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=upload_avatar',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(data){
            layer.closeAll();
            submitBtn.removeAttr('disabled');
            
            if(data.code == 0){
                avatarChanged = true;
                newAvatarUrl = data.data.avatar;
                $('img.img-avatar').attr('src', data.data.displayUrl || data.data.avatar);
                layer.msg('头像上传成功', {icon: 1});
            } else {
                layer.alert(data.msg || '上传失败', {icon: 2});
            }
        },
        error: function(){
            layer.closeAll();
            submitBtn.removeAttr('disabled');
            layer.alert('网络错误，请稍后重试', {icon: 2});
        }
    });
});

$('#profileForm').submit(function(){
    var nickname = $('input[name="nickname"]').val().trim();
    
    if(nickname.length > 20){
        layer.alert('昵称不能超过20个字符', {icon: 2});
        return false;
    }
    
    var formData = {
        nickname: nickname,
        avatar: newAvatarUrl
    };
    
    var submitBtn = $(this).find('button[type="submit"]');
    submitBtn.attr('disabled', 'true');
    layer.msg('正在保存...', {icon: 16, time: 0});
    
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=update_profile',
        data: formData,
        dataType: 'json',
        success: function(data){
            layer.closeAll();
            if(data.code == 0){
                layer.alert(data.msg, {
                    icon: 1,
                    closeBtn: false
                }, function(){
                    window.location.reload();
                });
            } else {
                submitBtn.removeAttr('disabled');
                layer.alert(data.msg, {icon: 2});
            }
        },
        error: function(){
            layer.closeAll();
            submitBtn.removeAttr('disabled');
            layer.alert('网络错误，请稍后重试', {icon: 2});
        }
    });
    
    return false;
});

$('#unbindWxBtn').click(function(){
    layer.confirm('确定要解绑微信吗？解绑后将无法使用微信一键登录。', {
        btn: ['确定', '取消'],
        icon: 3
    }, function(){
        layer.msg('正在解绑...', {icon: 16, time: 0});
        $.ajax({
            type: 'POST',
            url: '../api.php?act=unbind_wx',
            dataType: 'json',
            success: function(data){
                layer.closeAll();
                if(data.code == 0){
                    layer.alert('解绑成功', {icon: 1}, function(){
                        window.location.reload();
                    });
                } else {
                    layer.alert(data.msg || '解绑失败', {icon: 2});
                }
            },
            error: function(){
                layer.closeAll();
                layer.alert('网络错误，请稍后重试', {icon: 2});
            }
        });
    });
});

$('#bindWxBtn').click(function(){
    layer.alert('请在小程序中绑定微信', {icon: 0});
});
</script>
</body>
</html>
