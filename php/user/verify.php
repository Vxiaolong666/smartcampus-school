<?php
$notLogin = true;
include('../Common/Core_brain.php');

$code = isset($_GET['code']) ? daddslashes($_GET['code']) : '';
$email = isset($_GET['email']) ? daddslashes($_GET['email']) : '';

$message = '';
$success = false;
$showForm = true;
$tokenValid = false;
$user = null;

if ($code=='' || $email=='') {
    $message = '参数错误';
    $success = false;
    $showForm = false;
} else {
    $currentTime = date('Y-m-d H:i:s');
    $token = $DB->getRow("SELECT * FROM nteam_tokens WHERE token='$code' AND email='$email' AND token_type='email_verify' AND used=0 AND expire_time>'$currentTime'");
    
    if (!$token) {
        $message = '验证链接无效或已过期';
        $success = false;
        $showForm = false;
    } else {
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$token['user_id']}'");
        
        if (!$user) {
            $message = '用户不存在';
            $success = false;
            $showForm = false;
        } elseif ($user['email_verified'] == 1) {
            $message = '邮箱已验证，请直接登录';
            $success = true;
            $showForm = false;
        } else {
            $tokenValid = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? md5($_POST['password']) : '';
    
    header('Content-Type: application/json');
    
    if (!$tokenValid) {
        echo json_encode(['code' => -1, 'msg' => '验证链接无效或已过期']);
        exit;
    } elseif ($password=='') {
        echo json_encode(['code' => -1, 'msg' => '请输入密码']);
        exit;
    } elseif ($user['password'] !== $password) {
        echo json_encode(['code' => -1, 'msg' => '密码错误，请重新输入']);
        exit;
    } else {
        $updateSql = "UPDATE nteam_users SET email_verified=1 WHERE id='{$user['id']}'";
        $tokenUpdateSql = "UPDATE nteam_tokens SET used=1 WHERE id='{$token['id']}'";
        if ($DB->exec($updateSql) !== false && $DB->exec($tokenUpdateSql) !== false) {
            echo json_encode(['code' => 0, 'msg' => '邮箱验证成功，正在跳转到登录页面...']);
            exit;
        } else {
            echo json_encode(['code' => -1, 'msg' => '验证失败，请稍后重试']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>邮箱验证 - <?php echo conf('Name');?></title>
    <link rel="shortcut icon" href="../assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <?php include('../Common/css_guard.php'); ?>
    <link rel="stylesheet" href="./assets/css/scrollbar-immersive.css">
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
                                        <?php if ($showForm): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h1 class="h2 mb-1"><?php echo conf('Name');?></h1>
                                                    <p class="fw-medium text-muted mb-0">请验证您的邮箱</p>
                                                </div>
                                                <a href="./login.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-arrow-left me-1"></i> 返回登录
                                                </a>
                                            </div>
                                            <form class="px-4" id="verifyForm" method="post" action="javascript:void(0)">
                                                <input type="hidden" name="code" value="<?php echo $code;?>">
                                                <input type="hidden" name="email" value="<?php echo $email;?>">
                                                <div class="py-3">
                                                    <div class="mb-4">
                                                        <label class="form-label" for="password">密码</label>
                                                        <input type="password" name="password" id="password" class="form-control form-control-alt form-control-lg" placeholder="请输入您的密码">
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <button type="submit" id="submit" class="btn w-100 btn-success">
                                                        <i class="fa fa-fw fa-check me-1 opacity-50"></i> 验证邮箱
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <div class="mb-4">
                                                    <?php if ($success): ?>
                                                        <i class="fa fa-check-circle text-success fa-4x"></i>
                                                    <?php else: ?>
                                                        <i class="fa fa-times-circle text-danger fa-4x"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <h1 class="h3 mb-2"><?php echo $message; ?></h1>
                                                <p class="fw-medium text-muted mb-4">
                                                    <?php if ($success): ?>
                                                        您的邮箱已成功验证
                                                    <?php else: ?>
                                                        验证失败，请检查链接是否正确
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($success): ?>
                                                    <a href="./login.php" class="btn btn-primary">
                                                        <i class="fa fa-sign-in-alt me-1"></i> 前往登录
                                                    </a>
                                                <?php else: ?>
                                                    <a href="./login.php" class="btn btn-primary mb-3">
                                                        <i class="fa fa-arrow-left me-1"></i> 返回登录
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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

        $(document).ready(function(){
            initBackgroundSlider();
            setInterval(switchBackground, 3000);

            <?php if ($showForm): ?>
            $("#verifyForm").submit(function(){
                var password = $("#password").val() || '';
                var data = {password: password};
                var submitBtn = $("#submit");
                
                if(password.length < 1){
                    layer.alert('请输入密码！', {icon: 2});
                    return false;
                }
                
                submitBtn.attr('disabled', 'true');
                layer.msg('正在验证邮箱，请稍后...');
                
                $.ajax({
                    type:'POST',
                    url:'verify.php?code=<?php echo $code;?>&email=<?php echo $email;?>',
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
    <?php if ($success): ?>
    <script>
        setTimeout(function(){
            window.location.href = './login.php';
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
