<?php
$notLogin = true;
include('../Common/Core_brain.php');

$token = isset($_GET['token']) ? addslashes($_GET['token']) : '';
$email = isset($_GET['email']) ? addslashes($_GET['email']) : '';

$errorMsg = '';

if ($token=='' || $email=='') {
    $errorMsg = '参数错误';
} else {
    $currentTime = date('Y-m-d H:i:s');
    $tokenData = $DB->getRow("SELECT * FROM nteam_tokens WHERE token='$token' AND email='$email' AND token_type='password_reset' AND used=0 AND expire_time>'$currentTime'");

    if (!$tokenData) {
        $errorMsg = '重置链接无效或已过期';
    } else {
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$tokenData['user_id']}'");

        if (!$user) {
            $errorMsg = '用户不存在';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>重置密码 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <style>
        body {
            background-color: #1a1a2e;
            overflow: hidden;
        }
        .bg-slider {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        .bg-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            filter: blur(8px);
        }
        .bg-slide.active {
            opacity: 1;
        }
        #page-container {
            position: relative;
            z-index: 1;
        }
        .fs-sm {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <div class="bg-slider" id="bgSlider"></div>
    <div id="page-container">
        <main id="main-container">
            <div class="hero-static d-flex align-items-center">
                <div class="content">
                    <div class="row justify-content-center push">
                        <div class="col-md-8 col-lg-6 col-xl-4">
                            <div class="block block-rounded mb-0">
                                <div class="block-content">
                                    <div class="p-sm-3 px-lg-4 px-xxl-5 py-lg-5">
                                        <?php if ($errorMsg): ?>
                                            <div class="text-center">
                                                <div class="mb-4">
                                                    <i class="fa fa-times-circle text-danger fa-4x"></i>
                                                </div>
                                                <h1 class="h3 mb-2"><?php echo $errorMsg;?></h1>
                                                <p class="fw-medium text-muted mb-4">
                                                    重置链接无效或已过期，请重新申请
                                                </p>
                                                <a href="./login.php" class="btn btn-primary mb-3">
                                                    <i class="fa fa-arrow-left me-1"></i> 返回登录
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h1 class="h2 mb-1"><?php echo conf('Name');?></h1>
                                                    <p class="fw-medium text-muted mb-0">重置密码，请输入新密码</p>
                                                </div>
                                                <a href="./login.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-arrow-left me-1"></i> 返回登录
                                                </a>
                                            </div>
                                            <form class="px-4" id="resetForm">
                                                <input type="hidden" name="token" id="token" value="<?php echo $token;?>">
                                                <input type="hidden" name="email" id="email" value="<?php echo $email;?>">
                                                <div class="py-3">
                                                    <div class="mb-4">
                                                        <label class="form-label" for="new_password">新密码</label>
                                                        <input type="password" name="new_password" id="new_password" class="form-control form-control-alt form-control-lg" placeholder="请输入新密码（至少6位）">
                                                    </div>
                                                    <div class="mb-4">
                                                        <label class="form-label" for="confirm_password">确认密码</label>
                                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-alt form-control-lg" placeholder="请再次输入新密码">
                                                    </div>
                                            </div>
                                            <div class="mb-4">
                                                <button type="submit" id="submit" class="btn w-100 btn-success">
                                                    <i class="fa fa-fw fa-save me-1 opacity-50"></i> 重置密码
                                                </button>
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fs-sm text-muted text-center">
                        <strong><?php echo conf('Name');?></strong> © <span data-toggle="year-copy" class="js-year-copy-enabled"><?php echo date('Y');?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="./assets/js/lib/jquery.min.js"></script>
    <script src="./assets/js/lib/layer.min.js"></script>
    <script src="./assets/js/oneui.app.min-5.6.js"></script>
    <script>
        const bgImages = [
            '../assets/media/various/nn.png',
            '../assets/media/various/cc.png',
            '../png/xs.jpg',
            '../png/qq.png',
            '../png/aaqq.jpg',
            '../png/dd.jpg',
            '../png/jj.jpg'
        ];
        let currentIndex = 0;
        let shuffledImages = [];

        function shuffleArray(array) {
            const shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }

        function initBackgroundSlider() {
            const slider = document.getElementById('bgSlider');
            shuffledImages = shuffleArray(bgImages);
            
            bgImages.forEach((img, index) => {
                const slide = document.createElement('div');
                slide.className = 'bg-slide' + (index === 0 ? ' active' : '');
                slide.style.backgroundImage = `url('${img}')`;
                slider.appendChild(slide);
            });
        }

        function switchBackground() {
            const slides = document.querySelectorAll('.bg-slide');
            slides[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % slides.length;
            slides[currentIndex].classList.add('active');
        }

        $(document).ready(function(){
            initBackgroundSlider();
            setInterval(switchBackground, 3000);

            <?php if (!$errorMsg): ?>
            $("#resetForm").submit(function(){
                var token = $("#token").val() || '';
                var email = $("#email").val() || '';
                var newPassword = $("#new_password").val() || '';
                var confirmPassword = $("#confirm_password").val() || '';
                var data = {token:token,email:email,new_password:newPassword,confirm_password:confirmPassword};
                var submitBtn = $("#submit");
                
                if(newPassword.length < 6){
                    layer.alert('密码长度不能少于6位！', {icon: 2});
                    return false;
                }
                
                if(newPassword !== confirmPassword){
                    layer.alert('两次输入的密码不一致！', {icon: 2});
                    return false;
                }
                
                submitBtn.attr('disabled', 'true');
                layer.msg('正在重置密码，请稍后...');
                
                $.ajax({
                    type:'POST',
                    url:'ajax.php?act=reset_password',
                    data: data,
                    dataType:'json',
                    success:function (data){
                        if(data.code == 0){
                            layer.alert(data.msg, {
                                icon: 1,
                                closeBtn: false
                            }, function(){
                                window.location.href = './login.php';
                            });
                        }else{
                            submitBtn.removeAttr('disabled');
                            layer.alert(data.msg, {icon: 2});
                        }
                    },
                    error:function(){
                        submitBtn.removeAttr('disabled');
                        layer.alert('网络错误，请稍后重试！', {icon: 2});
                    }
                });
                
                return false;
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
