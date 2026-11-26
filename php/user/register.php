<?php
$notLogin = true;
include('../Common/Core_brain.php');

if(isset($_GET['logout'])){
    nteam_session_unset(['userId', 'userName', 'userEmail', 'userQq']);
    header('Location:./login.php');
}

if(isset($_SESSION['userId']))header('Location:./');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>用户注册 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <?php include('../Common/css_guard.php'); ?>
    <link rel="stylesheet" href="./assets/css/scrollbar-immersive.css">
    <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
    <style>
        body {
            background-color: #1a1a2e;
            overflow-y: auto;
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
            filter: blur(3px);
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
                    <div class="row justify-content-center push mt-3">
                        <div class="col-md-8 col-lg-6 col-xl-5">
                            <div class="block block-rounded mb-0">
                                <div class="block-content">
                                    <div class="p-sm-3 px-lg-4 px-xxl-5 py-lg-5">
                                        <h1 class="h2 mb-1"><?php echo conf('Name');?></h1>
                                        <p class="fw-medium text-muted">欢迎注册，请填写以下信息</p>
                                        <form class="px-4" id="registerForm" method="post" action="javascript:void(0)">
                                            <div class="py-3">
                                                <div class="mb-4">
                                                    <label class="form-label" for="username">用户名</label>
                                                    <input type="text" name="username" id="username" class="form-control form-control-alt form-control-lg" placeholder="4-10位字母数字下划线">
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label" for="email">邮箱（需验证）</label>
                                                    <input type="email" name="email" id="email" class="form-control form-control-alt form-control-lg" placeholder="请输入邮箱">
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label" for="password">密码</label>
                                                    <input type="password" name="password" id="password" class="form-control form-control-alt form-control-lg" placeholder="请输入密码（至少6位）">
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label" for="repassword">确认密码</label>
                                                    <input type="password" name="repassword" id="repassword" class="form-control form-control-alt form-control-lg" placeholder="请再次输入密码">
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label" for="qq">QQ号</label>
                                                    <input type="text" name="qq" id="qq" class="form-control form-control-alt form-control-lg" placeholder="请输入QQ号">
                                                </div>
                                                <div class="mb-4">
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
                                            </div>
                                            <div class="row mb-4">
                                                <div class="mb-4">
                                                    <button type="submit" id="submit" class="btn w-100 btn-success">
                                                        <i class="fa fa-fw fa-user-plus me-1 opacity-50"></i> 注册
                                                    </button>
                                                </div>
                                                <div class="text-center">
                                                    <a href="./login.php" class="text-primary">
                                                        <i class="fa fa-arrow-left me-1"></i> 已有账号？去登录
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fs-sm text-muted text-center mb-3">
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
            'https://cdn.mcblock.top/uploads/wallpapers/wp_1772944263284_zauo0gb5/compressed.webp',
            'https://cdn.mcblock.top/uploads/wallpapers/wp_1777078526111_rsdnocss/compressed.webp'
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

        function refreshCaptcha() {
            document.getElementById('captchaImg').src = '../server-submit/captcha.php?' + Date.now();
        }

        function showTextCaptcha() {
            document.getElementById('TencentCaptcha').style.display = 'none';
            document.getElementById('showTextCaptchaLink').style.display = 'none';
            document.getElementById('tcPassedTip').style.display = 'none';
            const textCaptcha = document.getElementById('textCaptcha');
            textCaptcha.style.display = 'flex';
            document.getElementById('tc_ticket').value = '';
            document.getElementById('tc_randstr').value = '';
            refreshCaptcha();
        }

        window.onTCaptchaResult = function(res) {
            if (res && res.ret === 0) {
                document.getElementById('tc_ticket').value = res.ticket || '';
                document.getElementById('tc_randstr').value = res.randstr || '';
                const btn = document.getElementById('TencentCaptcha');
                btn.innerHTML = '<i class="fas fa-check-circle"></i> 验证已通过';
                btn.style.background = '#ecfdf5';
                btn.style.color = '#059669';
                btn.style.border = '1px solid #34d399';
                btn.style.cursor = 'default';
                document.getElementById('tcPassedTip').style.display = 'block';
                document.getElementById('showTextCaptchaLink').style.display = 'none';
            } else {
                document.getElementById('showTextCaptchaLink').style.display = 'block';
            }
        };

        $(document).ready(function(){
            initBackgroundSlider();
            setInterval(switchBackground, 3000);

            $("#registerForm").submit(function(){
                var username = $("#username").val() || '';
                var email = $("#email").val() || '';
                var password = $("#password").val() || '';
                var repassword = $("#repassword").val() || '';
                var qq = $("#qq").val() || '';
                var tcTicket = $("#tc_ticket").val() || '';
                var tcRandstr = $("#tc_randstr").val() || '';
                var captcha = $("#captcha").val() || '';
                var data = {username:username,email:email,password:password,qq:qq,tc_ticket:tcTicket,tc_randstr:tcRandstr,captcha:captcha};
                var submitBtn = $("#submit");
                
                if(username.length < 4 || username.length > 20){
                    layer.alert('用户名长度必须在4-20位之间！', {icon: 2});
                    return false;
                }
                
                var usernameRegex = /^[a-zA-Z0-9_]+$/;
                if(!usernameRegex.test(username)){
                    layer.alert('用户名只能包含字母、数字、下划线！', {icon: 2});
                    return false;
                }
                
                if(email.length < 1){
                    layer.alert('请输入邮箱！', {icon: 2});
                    return false;
                }
                
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if(!emailRegex.test(email)){
                    layer.alert('邮箱格式不正确！', {icon: 2});
                    return false;
                }
                
                if(password.length < 6){
                    layer.alert('密码长度不能少于6位！', {icon: 2});
                    return false;
                }
                
                if(password !== repassword){
                    layer.alert('两次输入的密码不一致！', {icon: 2});
                    return false;
                }
                
                if(qq.length < 5 || qq.length > 12){
                    layer.alert('QQ号长度必须在5-12位之间！', {icon: 2});
                    return false;
                }
                
                var qqRegex = /^[1-9]\d{4,11}$/;
                if(!qqRegex.test(qq)){
                    layer.alert('QQ号格式不正确！', {icon: 2});
                    return false;
                }
                
                if(tcTicket.length < 1 && captcha.length < 1){
                    layer.alert('请先完成人机验证！', {icon: 2});
                    return false;
                }
                
                submitBtn.attr('disabled', 'true');
                layer.msg('正在注册中，请稍后...');
                
                $.ajax({
                    type:'POST',
                    url:'ajax.php?act=register',
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
                            if(captcha.length > 0){
                                refreshCaptcha();
                                $("#captcha").val('');
                            }
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
        });
    </script>
</body>
</html>
