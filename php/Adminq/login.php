<?php
$_POST && SafeFilter($_POST);

function SafeFilter (&$arr)
{
    $ra = Array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/','/script/','/javascript/','/vbscript/','/expression/','/applet/','/meta/','/xml/','/blink/','/link/','/style/','/embed/','/object/','/frame/','/layer/','/title/','/bgsound/','/base/','/onload/','/onunload/','/onchange/','/onsubmit/','/onreset/','/onselect/','/onblur/','/onfocus/','/onabort/','/onkeydown/','/onkeypress/','/onkeyup/','/onclick/','/ondblclick/','/onmousedown/','/onmousemove/','/onmouseout/','/onmouseover/','/onmouseup/','/onunload/');
    if (is_array($arr))
    {
        foreach ($arr as $key => $value)
        {
            if (!is_array($value))
            {
                $value = addslashes($value);
                $value = preg_replace($ra,'',$value);
                $arr[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
            else
            {
                SafeFilter($arr[$key]);
            }
        }
    }
}
?>

<?php


?>
<?php
$mod = 'admin';
$notLogin = true;
include('../Common/Core_brain.php');

if(isset($_GET['logout'])){
    unset($_SESSION['adminUser']);
    header('Location:./login.php');
}

if($isLogin)header('Location:./');
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>后台登录 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
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
                                        <h1 class="h2 mb-1"><?php echo conf('Name');?></h1>
                                        <p class="fw-medium text-muted">欢迎登录，今天是个不错的日子</p>
                                        <form class="px-4" action="login.php" method="post">
                                            <div class="py-3">
                                                <div class="mb-4">
                                                    <input type="text" name="adminUser" class="form-control form-control-alt form-control-lg"  placeholder="管理员账号">
                                                </div>
                                                <div class="mb-4">
                                                    <input type="password" name="adminPwd" class="form-control form-control-alt form-control-lg" placeholder="管理员密码" autocomplete>
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
                                                            <input type="text" name="captcha" class="form-control form-control-alt" placeholder="验证码">
                                                        </div>
                                                        <img src="../server-submit/captcha.php" id="captchaImg" alt="验证码" onclick="refreshCaptcha()" style="cursor:pointer;">
                                                    </div>
                                                    <div id="showTextCaptchaLink" style="text-align:right; margin-top:8px; display:none;">
                                                        <a href="javascript:void(0)" onclick="showTextCaptcha()">滑块异常？改用图片验证码</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-4">
                                                <div class="col-md-6 col-xl-5">
                                                    <button type="submit" id="submit" class="btn w-100 btn-alt-primary">
                                                        <i class="fa fa-fw fa-sign-in-alt me-1 opacity-50"></i> 登录
                                                    </button>
                                                </div>
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
    <!-- jquery -->
    <script src="./assets/js/lib/jquery.min.js"></script>
    <!-- layer -->
    <script src="./assets/js/lib/layer.min.js"></script>
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

            $("#submit").click(function(){
                var adminUser = $("input[name='adminUser']").val();
                var adminPwd = $("input[name='adminPwd']").val();
                var tcTicket = $("input[name='tc_ticket']").val();
                var tcRandstr = $("input[name='tc_randstr']").val();
                var captcha = $("input[name='captcha']").val();
                var data = {adminUser:adminUser,adminPwd:adminPwd,tc_ticket:tcTicket,tc_randstr:tcRandstr,captcha:captcha};
                var login = $("button[type='submit']");
                if(adminUser.length < 1 || adminPwd.length < 1){
                    layer.alert('请确保每项项都不为空！', {icon: 2});
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
                    url:'Ajax.php?act=login',
                    data: data,
                    dataType:'json',
                    success:function (data){
                        if(data.code == 1){
                            setTimeout(function (){
                                location.href = './'
                            },1000);
                            layer.alert(data.msg, {icon: 1});
                        }else{
                            login.removeAttr('disabled');
                            if(captcha.length > 0){
                                refreshCaptcha();
                                $("input[name='captcha']").val('');
                            }
                            layer.alert(data.msg, {icon: 2});
                        }
                    }
                });
                return false;
            });
        });
    </script>
</body>
</html>
