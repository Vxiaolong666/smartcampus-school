<?php
$notLogin = true;
include('../Common/Core_brain.php');

if(isset($_SESSION['userId']))header('Location:./');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>找回密码 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
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
                    <div class="row justify-content-center push">
                        <div class="col-md-8 col-lg-6 col-xl-4">
                            <div class="block block-rounded mb-0">
                                <div class="block-content">
                                    <div class="p-sm-3 px-lg-4 px-xxl-5 py-lg-5">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h1 class="h2 mb-1"><?php echo conf('Name');?></h1>
                                                <p class="fw-medium text-muted mb-0">验证码找回密码</p>
                                            </div>
                                            <a href="./login.php" class="btn btn-outline-primary">
                                                <i class="fa fa-arrow-left me-1"></i> 返回登录
                                            </a>
                                        </div>
                                        <form class="px-4" id="forgotForm">
                                            <div class="py-3">
                                                <div class="mb-4">
                                                    <label class="form-label" for="email">请输入注册邮箱</label>
                                                    <input type="email" name="email" id="email" class="form-control form-control-alt form-control-lg" placeholder="请输入注册邮箱">
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
                                            <div class="mb-4">
                                                <button type="submit" id="submit" class="btn w-100 btn-success">
                                                    <i class="fa fa-fw fa-paper-plane me-1 opacity-50"></i> 发送重置邮件
                                                </button>
                                            </div>
                                        </form>
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

            $("#forgotForm").submit(function(){
                var email = $("#email").val() || '';
                var tcTicket = $("#tc_ticket").val() || '';
                var tcRandstr = $("#tc_randstr").val() || '';
                var captcha = $("#captcha").val() || '';
                var data = {email:email,tc_ticket:tcTicket,tc_randstr:tcRandstr,captcha:captcha};
                var submitBtn = $("#submit");
                
                if(email.length < 1){
                    layer.alert('请输入邮箱！', {icon: 2});
                    return false;
                }
                
                if(tcTicket.length < 1 && captcha.length < 1){
                    layer.alert('请先完成人机验证！', {icon: 2});
                    return false;
                }
                
                submitBtn.attr('disabled', 'true');
                layer.msg('正在发送重置邮件，请稍后...');
                
                $.ajax({
                    type:'POST',
                    url:'ajax.php?act=forgot_password',
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
