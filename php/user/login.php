<?php
$notLogin = true;
include('../Common/Core_brain.php');

if(isset($_GET['logout'])){
    unset($_SESSION['userId']);
    unset($_SESSION['userName']);
    unset($_SESSION['userEmail']);
    unset($_SESSION['userQq']);
    header('Location:./login.php');
}

if(isset($_SESSION['userId']))header('Location:./');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>用户登录 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
    <style>
        body {
            background-color: #1a1a2e;
            overflow-y: auto;
            overflow-x: hidden;
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
        .oauth-btn {
            transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
            font-weight: 600;
            border-width: 1px;
        }
        .oauth-btn:hover {
            background-color: var(--oc) !important;
            border-color: var(--oc) !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 .35rem .9rem rgba(0, 0, 0, .15);
        }
        .oauth-btn:active {
            transform: translateY(0);
        }
        .oauth-btn i {
            font-size: 1.05rem;
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
                                        <div class="text-center mb-4">
                                            <img src="./assets/img/logo-header.png" alt="<?php echo conf('Name');?>" style="height:42px;max-width:100%;margin-bottom:12px;" class="img-fluid">
                                            <p class="fw-medium text-muted">欢迎登录，今天是个不错的日子</p>
                                        </div>
                                        <form class="px-4" id="loginForm" method="post" action="javascript:void(0)">
                                            <div class="py-3">
                                                <div class="mb-4">
                                                    <label class="form-label" for="username">用户名</label>
                                                    <input type="text" name="username" id="username" class="form-control form-control-alt form-control-lg" placeholder="请输入用户名">
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label" for="password">密码</label>
                                                    <input type="password" name="password" id="password" class="form-control form-control-alt form-control-lg" placeholder="请输入密码">
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
                                                    <i class="fa fa-fw fa-sign-in-alt me-1 opacity-50"></i> 登录
                                                </button>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <a href="./register.php" class="text-primary">
                                                    <i class="fa fa-user-plus me-1"></i> 注册账号
                                                </a>
                                                <a href="./forgot.php" class="text-primary">
                                                    <i class="fa fa-key me-1"></i> 忘记密码
                                                </a>
                                            </div>
                                        </form>
                                        <div id="oauthArea" class="text-center my-3" style="display:none;">
                                            <div class="d-flex align-items-center mb-3">
                                                <hr class="flex-grow-1">
                                                <span class="px-2 fs-sm" style="color:#6c757d !important;">第三方登录</span>
                                                <hr class="flex-grow-1">
                                            </div>
                                            <div id="oauthButtons" class="d-flex flex-wrap justify-content-center gap-2"></div>
                                        </div>
                                        <div class="text-center mt-4 mb-4">
                                            <a href="./send_verify.php" class="btn btn-sm btn-alt-secondary">
                                                <i class="fa fa-envelope me-1"></i> 未收到验证邮件？重新发送
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fs-sm text-muted text-center pb-5">
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

            // 显示聚合登录错误提示
            const oauthError = new URLSearchParams(window.location.search).get('oauth_error');
            if (oauthError) {
                layer.alert(decodeURIComponent(oauthError), {icon: 2});
            }

            // 加载已启用的第三方登录渠道
            loadOauthChannels();

            $("#loginForm").submit(function(){
                var username = $("#username").val() || '';
                var password = $("#password").val() || '';
                var tcTicket = $("#tc_ticket").val() || '';
                var tcRandstr = $("#tc_randstr").val() || '';
                var captcha = $("#captcha").val() || '';
                var data = {username:username,password:password,tc_ticket:tcTicket,tc_randstr:tcRandstr,captcha:captcha};
                var login = $("#submit");
                
                if(username.length < 1 || password.length < 1){
                    layer.alert('请确保每项都不为空！', {icon: 2});
                    return false;
                }
                
                if(tcTicket.length < 1 && captcha.length < 1){
                    layer.alert('请先完成人机验证！', {icon: 2});
                    return false;
                }
                
                login.attr('disabled', 'true');
                layer.msg('正在登录中，请稍后...');
                
                $.ajax({
                    type:'POST',
                    url:'ajax.php?act=login',
                    data: data,
                    dataType:'json',
                    success:function (data){
                        if(data.code == 0){
                            layer.alert(data.msg, {
                                icon: 1,
                                closeBtn: false
                            }, function(){
                                window.location.href = './';
                            });
                        }else{
                            login.removeAttr('disabled');
                            if(captcha.length > 0){
                                refreshCaptcha();
                                $("#captcha").val('');
                            }
                            layer.alert(data.msg, {icon: 2});
                        }
                    },
                    error:function(){
                        login.removeAttr('disabled');
                        layer.alert('网络错误，请稍后重试！', {icon: 2});
                    }
                });
                
                return false;
            });

            function loadOauthChannels() {
                $.ajax({
                    type: 'POST',
                    url: 'ajax.php?act=get_oauth_channels',
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 0 && data.enabled == 1 && data.channels && Object.keys(data.channels).length > 0) {
                            var html = '';
                            $.each(data.channels, function(type, meta) {
                                html += '<button type="button" class="btn oauth-btn rounded-pill d-inline-flex align-items-center justify-content-center px-3 py-2" data-type="' + type + '" '
                                    + 'style="--oc:' + meta.color + ';background-color:' + meta.color + '1a;border-color:' + meta.color + '59;color:' + meta.color + ';">'
                                    + '<i class="' + meta.icon + ' me-1"></i>' + meta.name + '</button>';
                            });
                            $('#oauthButtons').html(html);
                            $('#oauthArea').show();

                            $('.oauth-btn').on('click', function() {
                                var type = $(this).data('type');
                                var btn = $(this);
                                btn.attr('disabled', 'true');
                                layer.msg('正在跳转授权...');
                                $.ajax({
                                    type: 'POST',
                                    url: 'ajax.php?act=get_oauth_url',
                                    data: {type: type, state: 'login'},
                                    dataType: 'json',
                                    success: function(res) {
                                        if (res.code == 0 && res.url) {
                                            window.location.href = res.url;
                                        } else {
                                            btn.removeAttr('disabled');
                                            layer.alert(res.msg || '获取登录地址失败', {icon: 2});
                                        }
                                    },
                                    error: function() {
                                        btn.removeAttr('disabled');
                                        layer.alert('网络错误，请稍后重试', {icon: 2});
                                    }
                                });
                            });
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
