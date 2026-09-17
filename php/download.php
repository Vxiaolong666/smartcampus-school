<?php
$notLogin = true;
include('Common/Core_brain.php');
$siteName = htmlspecialchars(conf('Name') ?: '智校通', ENT_QUOTES);
$siteTitle = htmlspecialchars(conf('SiteName') ?: conf('Name') ?: '智校通', ENT_QUOTES);
?><!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title>下载 - <?php echo $siteName; ?></title>
        <!-- Required Meta Tags -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Primary Meta Tags -->
        <meta name="title" content="下载 - <?php echo $siteName; ?>">
        <meta name="description" content="下载<?php echo $siteName; ?>智慧校园平台：支持微信小程序、Android App 与网页端，师生、辅导员与后勤随时随地享便捷校园服务。">
        <!-- favicon icon -->
        <link rel="shortcut icon" href="./images/favicon.png">
        <link rel="apple-touch-icon" href="./images/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="72x72" href="./images/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="114x114" href="./images/apple-icon-114x114.png">
        <!-- google font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
        <!-- main css -->
        <link rel="stylesheet" href="./css/all.min.css" type="text/css">
        <link rel="stylesheet" href="./css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="./css/aos.min.css" type="text/css">
        <link rel="stylesheet" href="./css/swiper-bundle.min.css" type="text/css">
        <link rel="stylesheet" href="./css/uicons.min.css" type="text/css">
        <link rel="stylesheet" href="./css/fancybox.min.css" type="text/css">
        <link rel="stylesheet" href="./css/style.css?v=27" type="text/css">
        <!-- custom css -->
        <link rel="stylesheet" href="./css/custom.css" type="text/css">
</head>
    <body class="inner-page">
        <!-- Preloader Start -->
        <div id="preloader" class="active">
            <div class="spinner">
                <div class="double-bounce1"></div>
                <div class="double-bounce2"></div>
            </div>
        </div>
        <!-- Preloader End -->
        <!-- Custom Cursor Start -->
        <div class="custom-cursor">
            <span class="cursor-text">
                <i class="fi fi-rr-arrow-small-left fs-4"></i>
                <i class="fi fi-rr-arrow-small-right fs-4"></i>
            </span>
        </div>
        <!-- Custom Cursor End -->
        <!-- Scroll Up Buttton Start -->
        <a onclick="topFunction()" id="back-to-top" title="Go to top" style="display: block;">
            <i class="far fa-arrow-up"></i>
        </a>
        <!-- Scroll Up Button End -->
        <!-- 公共头部组件（由 component-loader.js 注入） -->
        <div data-component="header"></div>
        <main>
            <!-- Page-Header Start -->
            <div id="breadcrumb" class="page-header text-center">
                <div class="container">
                    <h1 class="page-title">下载</h1>
                    <ul class="breadcrumbs">
                        <li><a href="index.php#home">首页</a></li>
                        <li class="active">下载</li>
                    </ul>
                </div>
            </div>
            <!-- Page-Header End -->
            <!-- Section Platforms Start -->
            <section class="features overflow-hidden py-5">
                <div class="container py-5">
                    <div class="row justify-content-between align-items-end pb-5 mb-4">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">多端覆盖</span>
                            <h2 class="fs-1 mb-0" data-aos="fade-right" data-aos-delay="100">选择适合你的下载方式</h2>
                        </div>
                        <div class="col-lg-4">
                            <p class="fs-5 mb-3 pb-3" data-aos="fade-left" data-aos-delay="200">无论你是学生、老师还是后勤管理员，总有一款客户端适合你。</p>
                        </div>
                    </div>
                    <div class="row g-4 justify-content-center">
                        <div class="col-md-6 col-xl-3" id="platform-wechat" data-aos="fade-up">
                            <div class="card-feature d-flex flex-column gap-3 bg-white h-100 text-center align-items-center">
                                <div class="icon mb-2 bg-primary position-relative">
                                    <i class="fi fi-rr-comment-dots fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">微信小程序</h4>
                                <p class="mb-0 position-relative">扫码即用，免安装。覆盖课表、成绩、电费充值、报修等高频校园场景。</p>
                                <div class="qr-placeholder mt-2 mb-2" aria-label="微信小程序二维码">
                                    <img src="./images/app/wechat_qr.png" alt="微信小程序二维码" class="img-fluid" style="max-width:140px;">
                                    <span class="d-block mt-2 fw-semibold text-dark">微信扫一扫</span>
                                </div>
                                <a href="#" class="btn btn-primary mt-auto">打开小程序</a>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3" id="platform-android" data-aos="fade-up" data-aos-delay="100">
                            <div class="card-feature d-flex flex-column gap-3 bg-white h-100 text-center align-items-center">
                                <div class="icon mb-2 bg-warning position-relative">
                                    <i class="fi fi-rr-mobile fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">Android App</h4>
                                <p class="mb-0 position-relative">功能更全的 uni-app 客户端，支持离线课表、消息推送与深度交互。</p>
                                <div class="qr-placeholder mt-2 mb-2" aria-label="Android 下载二维码">
                                    <img src="./images/app/wechat_qr.png" alt="Android 下载二维码" class="img-fluid" style="max-width:140px;">
                                    <span class="d-block mt-2 fw-semibold text-dark">扫码下载 APK</span>
                                </div>
                                <a href="#" class="btn btn-primary mt-auto">下载 APK</a>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="200">
                            <div class="card-feature d-flex flex-column gap-3 bg-white h-100 text-center align-items-center">
                                <div class="icon mb-2 bg-success fs-3 position-relative">
                                    <i class="fi fi-rr-browser fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">网页端</h4>
                                <p class="mb-0 position-relative">面向辅导员与后勤的网页管理端，数据看板、审批流与权限管理一应俱全。</p>
                                <div class="qr-placeholder mt-2 mb-2" aria-label="网页端地址">
                                    <img src="./images/app/wechat_qr.png" alt="网页端地址" class="img-fluid" style="max-width:140px;">
                                    <span class="d-block mt-2 fw-semibold text-dark">admin.zhixiaoyun.com</span>
                                </div>
                                <a href="contact-us.php" class="btn btn-primary mt-auto">申请开通</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Platforms End -->
            <!-- Section Download Hero Start -->
            <section class="download_app_area overflow-hidden">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="app_content">
                                <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">下载中心</span>
                                <h2 data-aos="fade-right">下载<?php echo $siteName; ?><br />开启智慧校园生活</h2>
                                <p class="fs-5 text-dark mb-4" data-aos="fade-right" data-aos-delay="100"><?php echo $siteName; ?>支持微信小程序、Android App 与网页端，覆盖课表查询、电费充值、宿舍报修、校园资讯与 AI 助手等全场景服务。师生扫码即用，辅导员与后勤随时办公。</p>
                                <ul class="app_btn" data-aos="fade-right" data-aos-delay="100">
                                    <li>
                                        <a href="#platform-wechat"><img src="./images/app/app_btn.png" alt="微信小程序"></a>
                                    </li>
                                    <li>
                                        <a href="#platform-android"><img src="./images/app/app_btn2.png" alt="Android"></a>
                                    </li>
                                </ul>
                                <div class="apps_shape bounce-animate">
                                    <img src="./images/app/shape_arrow.png" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="app_thumb" data-aos="fade-left" data-aos-delay="150">
                                <img src="./images/app/app_img.png" alt="<?php echo $siteName; ?>客户端">
                                <img class="app_rotate bounce-animate" src="./images/app/app_shape.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Download Hero End -->
            <!-- Section Steps Start -->
            <section class="benefits bg-light overflow-hidden">
                <div class="container">
                    <div class="row justify-content-between align-items-end pb-5 mb-4">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">三步上手</span>
                            <h2 class="fs-1 mb-0" data-aos="fade-right" data-aos-delay="100">几分钟开启智慧校园</h2>
                        </div>
                        <div class="col-lg-4">
                            <p class="fs-5 mb-3 pb-3" data-aos="fade-left" data-aos-delay="200">从下载到使用，简单直观，无需复杂配置。</p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-4" data-aos="fade-up">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <span class="big-number text-primary">01</span>
                                <h4 class="mb-0">下载并安装</h4>
                                <p class="mb-0">选择微信小程序或 App，扫码即可使用，师生零门槛接入。</p>
                            </div>
                        </div>
                        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <span class="big-number text-primary">02</span>
                                <h4 class="mb-0">绑定学校账号</h4>
                                <p class="mb-0">使用学校统一身份认证登录，自动同步院系、班级与宿舍信息。</p>
                            </div>
                        </div>
                        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <span class="big-number text-primary">03</span>
                                <h4 class="mb-0">畅享校园服务</h4>
                                <p class="mb-0">课表、电费、报修、资讯与 AI 助手即刻可用，校园生活更轻松。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Steps End -->
            <!-- Section CTA Start -->
            <section class="cta overflow-hidden bg-primary position-relative">
                <div class="container position-relative z-3">
                    <div class="row">
                        <div class="col-lg-8 pb-4 pb-lg-0">
                            <h2 class="fs-1 text-dark mb-3" data-aos="fade-right">还没有学校账号？</h2>
                            <p class="text-dark fs-5" data-aos="fade-right" data-aos-delay="100">联系我们，为你的院校开通<?php echo $siteName; ?>服务，让师生即刻享受智慧校园便利。</p>
                        </div>
                        <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
                            <a class="btn btn-lg btn-light" href="contact-us.php" data-aos="fade-left">联系我们</a>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section CTA End -->
        </main>
        <!-- 公共底部组件（由 component-loader.js 注入） -->
        <div data-component="footer"></div>
        <script src="./js/bootstrap.bundle.min.js"></script>
        <script src="./js/aos.min.js"></script>
        <script src="./js/swiper-bundle.min.js"></script>
        <script src="./js/count-up.min.js"></script>
        <script src="./js/fancybox.umd.js"></script>
        <script>window.SITE_NAME = <?php echo json_encode($siteName); ?>;</script>
        <script src="./js/component-loader.js?v=2"></script>
        <script src="./js/form-validation.js"></script>
        <script src="./js/main.js?v=7"></script>
        <script src="./js/custom.js"></script>
    </body>
</html>
