<?php
$notLogin = true;
include('Common/Core_brain.php');
include('Common/home_config.php');
$siteConfig = homeSiteConfig();
$siteName = htmlspecialchars(conf('Name') ?: '智校通', ENT_QUOTES);
$siteTitle = htmlspecialchars(conf('SiteName') ?: conf('Name') ?: '智校通', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title><?php echo $siteTitle; ?> - 一站式智慧校园服务平台</title>
        <!-- Required Meta Tags -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Primary Meta Tags -->
        <meta name="title" content="<?php echo $siteName; ?> - 一站式智慧校园服务平台">
        <meta name="description" content="<?php echo $siteName; ?>是一站式智慧校园服务平台，集教务查询、宿舍管理、电费充值、校园资讯与 AI 助手于一体，为高校与职业院校提供覆盖师生校园生活全场景的智慧化解决方案。">
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
        <link rel="stylesheet" href="./css/icofont.min.css" type="text/css">
        <link rel="stylesheet" href="./css/slick.css" type="text/css">
        <link rel="stylesheet" href="./css/style.css?v=25" type="text/css">
        <!-- custom css -->
        <link rel="stylesheet" href="./css/custom.css?v=2" type="text/css">
</head>
    <body>
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
            <!-- Section Hero Banner Start -->
            <section id="home" class="hero-banner bg-light overflow-hidden">
                <div class="container text-center position-relative z-3">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 pb-4 d-flex flex-column gap-4">
                            <h2 class="hero-heading mb-0" data-aos="fade-up">让校园生活更智慧、更便捷</h2>
                            <span class="fs-5 mb-4" data-aos="fade-up" data-aos-delay="100"><?php echo $siteName; ?>一站式智慧校园服务平台，集教务查询、宿舍管理、电费充值、校园资讯与 AI 助手于一体，覆盖师生校园生活全场景，让你专注学习与生活。</span>
                            <div class="d-flex flex-row flex-wrap justify-content-center gap-3">
                                <a href="download.html" class="btn btn-lg btn-primary" data-aos="fade-up" data-aos-delay="200">免费下载</a>
                                <a href="#features" class="btn btn-lg btn-light btn-gradient-border" data-aos="fade-up" data-aos-delay="300">了解功能</a>
                            </div>
                        </div>
                        <div class="col-lg-12 px-0 px-lg-4 pt-5">
                            <img src="./images/hero-banner.png" class="w-100" data-aos="fade-up" data-aos-delay="400" alt="hero_banner_img">
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Hero Banner End -->
            <!-- Section About Us Start -->
            <section id="about" class="about-us bg-light overflow-hidden">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5 d-flex flex-column justify-content-center mb-5 pe-lg-5 pb-4 mb-lg-0 pb-lg-0">
                            <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">关于<?php echo $siteName; ?></span>
                            <h2 class="fs-1 mb-4 pb-2" data-aos="fade-right" data-aos-delay="100">一站式智慧校园综合服务平台</h2>
                            <p class="mb-4 pb-4" data-aos="fade-right" data-aos-delay="200">在 <?php echo $siteName; ?>，我们相信科技应当服务于教育。我们打造了覆盖教务、生活、服务的智慧校园平台，帮助学校提升管理效率，帮助学生享受便捷校园生活，专注真正重要的事——学习与成长。</p>
                            <div class="d-flex flex-row flex-wrap gap-3">
                                <a href="download.html" class="btn btn-light" data-aos="fade-right" data-aos-delay="300">下载客户端</a>
                                <a href="contact-us.html" class="btn btn-primary" data-aos="fade-right" data-aos-delay="400">预约演示</a>
                            </div>
                        </div>
                        <div class="col-lg-7 ps-lg-5">
                            <img src="./images/about.png" class="w-100" data-aos="fade-left" data-aos-delay="200" alt="about_img">
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section About Us End -->
            <!-- Section Features Start -->
            <section id="features" class="features overflow-hidden">
                <div class="container">
                    <div class="row justify-content-between align-items-end pb-5 mb-4 mb-sm-5">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">功能特性</span>
                            <h2 class="fs-1 mb-0" data-aos="fade-right" data-aos-delay="100">覆盖校园生活全场景</h2>
                        </div>
                        <div class="col-lg-4">
                            <p class="fs-5 mb-3 pb-3" data-aos="fade-left" data-aos-delay="200">为师生与学校管理而生——没有复杂门槛，只有让校园更智能的服务。</p>
                            <a href="download.html" class="btn btn-primary" data-aos="fade-left" data-aos-delay="300">立即下载</a>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6 col-xl-3" data-aos="fade-up">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <div class="icon mb-2 bg-primary position-relative">
                                    <i class="fi fi-rr-calendar-lines fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">教务一键查询</h4>
                                <p class="mb-0 position-relative">课表、成绩、考试安排实时同步，随时随地掌握学业动态。</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="200">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <div class="icon mb-2 bg-danger fs-3 position-relative">
                                    <i class="fi fi-rr-bolt fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">电费在线充值</h4>
                                <p class="mb-0 position-relative">宿舍电费一键查询与充值，余额不足及时提醒，告别断电烦恼。</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="300">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <div class="icon mb-2 bg-success fs-3 position-relative">
                                    <i class="fi fi-rr-bell-ring fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">公告与订阅提醒</h4>
                                <p class="mb-0 position-relative">校园公告、学术讲座推送与微信订阅消息，重要信息准时送达，不再错过。</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="400">
                            <div class="card-feature d-flex flex-column gap-3 bg-white">
                                <div class="icon mb-2 bg-info fs-3 position-relative">
                                    <i class="fi fi-rr-robot fs-3 position-relative"></i>
                                </div>
                                <h4 class="mb-0 position-relative">校园 AI 助手</h4>
                                <p class="mb-0 position-relative">基于大模型的智能问答，课业咨询、校园导航、办事指南有问必答。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Features End -->
            <!-- service area start -->
            <div class="glowbal service_area">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <!-- section title -->
                             <div class="section_title" data-aos="fade-up">
                                <h4>提供专业智慧校园服务</h4>
                                <h2>我们<span class="highlight">提供</span>最好的校园<span class="highlight">服务</span></h2>
                                <p class="mx-auto mt-3 mb-0" style="max-width: 680px;">已为全国各地 200+ 所合作院校提供教务、生活、宿舍与移动办公一体化智慧服务，覆盖师生超百万。</p>
                             </div>
                        </div>
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="swiper service_slide" id="serviceSlider" data-aos="fade-up" data-aos-delay="150">
                        <div class="swiper-wrapper">
                        <div class="swiper-slide service-item">
                            <div class="main_service_item">
                                <div class="single_service">
                                    <!-- service img -->
                                    <img src="./images/service/ser_1.png" alt="教务服务">
                                    <!-- title -->
                                    <h3><a href="#">教务服务</a></h3>
                                    <!-- content -->
                                    <p>课表、成绩、考试、空教室查询，学业信息一屏掌握，学习规划更从容。 </p>
                                    <!-- service btn -->
                                     <div class="service_btn">
                                        <a href="download.html">了解更多 <i class="icofont-arrow-right"></i></a>
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide service-item">
                            <div class="main_service_item">
                                <div class="single_service">
                                    <!-- service img -->
                                    <img src="./images/service/ser_2.png" alt="生活服务">
                                    <!-- title -->
                                    <h3><a href="#">生活服务</a></h3>
                                    <!-- content -->
                                    <p>电费充值、报修、失物招领，校园生活大小事轻松办，省去排队奔波。 </p>
                                    <!-- service btn -->
                                     <div class="service_btn">
                                        <a href="download.html">了解更多 <i class="icofont-arrow-right"></i></a>
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide service-item">
                            <div class="main_service_item">
                                <div class="single_service">
                                    <!-- service img -->
                                    <img src="./images/service/ser_3.png" alt="宿舍管理">
                                    <!-- title -->
                                    <h3><a href="#">宿舍管理</a></h3>
                                    <!-- content -->
                                    <p>归寝统计、住宿调整、访客管理，安全与便利兼得，辅导员工作更高效。 </p>
                                    <!-- service btn -->
                                     <div class="service_btn">
                                        <a href="download.html">了解更多 <i class="icofont-arrow-right"></i></a>
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide service-item">
                            <div class="main_service_item">
                                <div class="single_service">
                                    <!-- service img -->
                                    <img src="./images/service/ser_4.png" alt="资讯通知">
                                    <!-- title -->
                                    <h3><a href="#">资讯通知</a></h3>
                                    <!-- content -->
                                    <p>校园公告、活动报名、院系动态精准触达，重要信息不再错过。 </p>
                                    <!-- service btn -->
                                     <div class="service_btn">
                                        <a href="download.html">了解更多 <i class="icofont-arrow-right"></i></a>
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide service-item">
                            <div class="main_service_item">
                                <div class="single_service">
                                    <!-- service img -->
                                    <img src="./images/service/ser_4.png" alt="移动办公">
                                    <!-- title -->
                                    <h3><a href="#">移动办公</a></h3>
                                    <!-- content -->
                                    <p>师生、辅导员、后勤多端协同，审批流与数据看板让校园事务高效流转。 </p>
                                    <!-- service btn -->
                                     <div class="service_btn">
                                        <a href="download.html">了解更多 <i class="icofont-arrow-right"></i></a>
                                     </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="swiper-pagination service-pagination"></div>
                    </div>
                </div>
            </div>
            <!-- service area end -->
            <!-- Section Benefits Start -->
            <section id="benefits" class="benefits bg-light overflow-hidden">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5 order-lg-2 ps-lg-2 mb-5 mb-lg-0 d-flex flex-column justify-content-center">
                            <div class="pb-4">
                                <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-left">多端覆盖</span>
                                <h2 class="fs-1 mb-3" data-aos="fade-left" data-aos-delay="200">适配每一种使用场景</h2>
                            </div>
                            <div class="accordion pt-3" id="benefitAccordion">
                                <div class="accordion-item mb-2" data-aos="fade-left" data-aos-delay="100">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" data-img="img-designers">
                                            <span class="accordion-icon bg-primary">
                                                <i class="fi fi-rr-chart-user"></i>
                                            </span>
                                            <span class="fs-4 fw-bold text-dark">微信小程序</span>
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#benefitAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-4">师生最常用的轻量入口，扫码即用，覆盖课表查询、电费充值、报修等高频场景，无需下载安装，校园服务触手可及。</p>
                                            <a href="download.html" class="btn btn-primary">立即使用</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item mb-2" data-aos="fade-left" data-aos-delay="200">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" data-img="img-writers">
                                            <span class="accordion-icon bg-warning">
                                                <i class="fi fi-rr-user-pen"></i>
                                            </span>
                                            <span class="fs-4 fw-bold text-dark">移动 App</span>
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#benefitAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-4">功能更全的 uni-app 客户端，支持离线课表、消息推送与深度交互，适合需要完整校园功能的师生使用。</p>
                                            <a href="download.html" class="btn btn-primary">下载 App</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item" data-aos="fade-left" data-aos-delay="300">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" data-img="img-agencies">
                                            <span class="accordion-icon bg-danger">
                                                <i class="fi fi-rr-users-alt"></i>
                                            </span>
                                            <span class="fs-4 fw-bold text-dark">网页端</span>
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#benefitAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-4">面向辅导员与后勤的网页端，数据看板、审批流与权限管理一应俱全，校园事务统一调度更高效。</p>
                                            <a href="contact-us.html" class="btn btn-primary">申请开通</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 order-lg-1 pe-lg-5 d-flex align-items-center" data-aos="fade-right">
                            <div class="image-box">
                                <img src="./images/benefit-writers.png" id="img-designers" class="w-100 object-fit-contain active" alt="img_benefit_1">
                                <img src="./images/benefit-writers.png" id="img-writers" class="w-100 object-fit-contain" alt="img_benefit_2">
                                <img src="./images/benefit-writers.png" id="img-agencies" class="w-100 object-fit-contain" alt="img_benefit_3">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Benefits End -->
            <!-- Section Integration Start -->
            <section class="integrations overflow-hidden">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5 offset-lg-1 order-lg-2">
                            <div class="heading-section pb-5">
                                <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-left">开放对接</span>
                                <h2 class="fs-1 mb-4" data-aos="fade-left" data-aos-delay="100">与学校现有系统无缝衔接</h2>
                                <p class="fs-5 mb-0" data-aos="fade-left" data-aos-delay="200"><?php echo $siteName; ?> 提供开放 API 与标准数据接口，可与学校统一身份认证、一卡通、教务系统及数据中心平滑对接，保护既有信息化投资。</p>
                            </div>
                        </div>
                        <div class="col-lg-6 order-lg-1 d-flex align-items-center">
                            <ul class="list-logo d-flex flex-wrap w-100 list-unstyled gap-3 ps-0 mb-0">
                                <li data-aos="zoom-in">
                                    <a href="#" class="bg-white">
                                        <img src="./images/trello.webp" alt="logo_trello">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="100">
                                    <a href="#" class="bg-white">
                                        <img src="./images/slack.webp" alt="logo_slack">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="200">
                                    <a href="#" class="bg-white">
                                        <img src="./images/bitbucket.webp" alt="logo_bitbucket">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="300">
                                    <a href="#" class="bg-white">
                                        <img src="./images/teamwork.webp" alt="logo_teamwork">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="400">
                                    <a href="#" class="bg-white">
                                        <img src="./images/notion.webp" alt="logo_notion">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="500">
                                    <a href="#" class="bg-white">
                                        <img src="./images/gdrive.webp" alt="logo_gdrive">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="600">
                                    <a href="#" class="bg-white">
                                        <img src="./images/gmeet.webp" alt="logo_gmeet">
                                    </a>
                                </li>
                                <li data-aos="zoom-in" data-aos-delay="700">
                                    <a href="#" class="bg-white">
                                        <img src="./images/monday.webp" alt="logo_monday">
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Integration End -->
            <!-- Section Testimonials Start -->
            <section id="testimonials" class="testimonials bg-light position-relative overflow-hidden">
                <div class="container position-relative z-3">
                    <div class="row justify-content-between align-items-end pb-5 mb-5">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">用户评价</span>
                            <h2 class="fs-1 mb-0" data-aos="fade-right" data-aos-delay="100">深受广大师生与院校信赖</h2>
                        </div>
                        <div class="col-lg-4">
                            <p class="fs-5" data-aos="fade-left">学生、辅导员与后勤团队都在使用 <?php echo $siteName; ?>，让校园服务更高效、更温暖。</p>
                        </div>
                    </div>

                    <!-- Slider Testimonial Start -->
                    <div class="swiper pb-5 mb-5" id="sliderTestimonials" data-aos="fade-up">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide d-flex flex-column bg-white">
                                <div class="d-flex gap-2 mb-4">
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-rr-star-sharp-half-stroke fs-5"></i>
                                </div>
                                <p class="mb-4">用<?php echo $siteName; ?>查课表和充宿舍电费太方便了，再也不用跑后勤大厅，消息推送也很及时，漏缴电费的情况再没发生过。</p>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="frame-photo">
                                        <img src="./images/testim-1.webp" class="w-100" alt="image_client_1">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">李明</span>
                                        <span class="text-dark">大三学生</span>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide d-flex flex-column bg-white">
                                <div class="d-flex gap-2 mb-4">
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-rr-star-sharp-half-stroke fs-5"></i>
                                </div>
                                <p class="mb-4">归寝签到和报修审批都在手机上完成，省去了大量纸质流程，宿舍管理效率提升非常明显，学生也更配合。</p>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="frame-photo">
                                        <img src="./images/testim-2.webp" class="w-100" alt="image_client_2">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">王芳</span>
                                        <span class="text-dark">辅导员</span>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide d-flex flex-column bg-white">
                                <div class="d-flex gap-2 mb-4">
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-rr-star-sharp-half-stroke fs-5"></i>
                                </div>
                                <p class="mb-4">数据看板让我们实时掌握各宿舍楼用电情况，报修工单一目了然，后勤管理变得更精细、更主动了。</p>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="frame-photo">
                                        <img src="./images/testim-3.webp" class="w-100" alt="image_client_3">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">张主任</span>
                                        <span class="text-dark">后勤管理处</span>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide d-flex flex-column bg-white">
                                <div class="d-flex gap-2 mb-4">
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-sr-star fs-5"></i>
                                    <i class="fi fi-rr-star-sharp-half-stroke fs-5"></i>
                                </div>
                                <p class="mb-4">开放 API 顺利对接了学校统一身份认证和一卡通，师生单点登录很顺畅，既保留既有系统又拓展了移动服务。</p>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="frame-photo">
                                        <img src="./images/testim-4.webp" class="w-100" alt="image_client_4">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">陈老师</span>
                                        <span class="text-dark">信息中心主任</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Slider Testimonial End -->    
                    <!-- Trusted Partner Start -->
                    <div class="d-block pb-5 mb-5">
                        <h5 class="mb-4 pb-2" data-aos="fade-up">合作院校</h5>
                        <div class="swiper" id="sliderPartners" data-aos="fade-up" data-aos-delay="100">
                            <div class="swiper-wrapper align-items-center">
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/google.webp" alt="logo_partner_1">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/github.webp" alt="logo_partner_2">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/gdnpc.jpg" alt="logo_partner_2">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/microsoft.webp" alt="logo_partner_3">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/bing.webp" alt="logo_partner_4">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/chrome.webp" alt="logo_partner_5">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/google.webp" alt="logo_partner_6">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/github.webp" alt="logo_partner_7">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/microsoft.webp" alt="logo_partner_8">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/bing.webp" alt="logo_partner_9">
                                    </a>
                                </div>
                                <div class="swiper-slide">
                                    <a href="#" class="d-flex justify-content-center">
                                        <img src="./images/chrome.webp" alt="logo_partner_10">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Trusted Partnerr End -->
                    <!-- Card Achievement Start -->
                    <div class="card-achievement overflow-hidden">
                        <div class="row gy-5 position-relative">
                            <div class="col-lg-4">
                                <div class="d-block px-lg-5 count" data-aos="fade-up">
                                    <h2 class="fs-1 text-dark counter mb-0">
                                        <count-up class="fw-bolder">200</count-up>
                                        <span class="text-dark">+</span>
                                    </h2>
                                    <span class="d-block fs-4 fw-semibold text-dark">接入院校</span>
                                    <span class="d-block text-dark">本科院校、职业院校与中小学正在使用<?php echo $siteName; ?>开展智慧校园服务。</span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-block px-lg-5 count" data-aos="fade-up" data-aos-delay="100">
                                    <h2 class="fs-1 text-dark counter mb-0">
                                        <count-up clas="fw-bolder">50</count-up>
                                        <span class="text-dark">万+</span>
                                    </h2>
                                    <span class="d-block fs-4 fw-semibold text-dark">师生用户</span>
                                    <span class="d-block text-dark">学生与教职工借助<?php echo $siteName; ?>享受便捷、高效的校园生活。</span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-block px-lg-5 count" data-aos="fade-up" data-aos-delay="200">
                                    <h2 class="fs-1 text-dark counter mb-0">
                                        <count-up class="fw-bolder">99.9</count-up>
                                        <span class="text-dark">%</span>
                                    </h2>
                                    <span class="d-block fs-4 fw-semibold text-dark">服务可用性</span>
                                    <span class="d-block text-dark">得益于多节点部署、健康检查与 7×24 小时安全监控。</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card Achievement End -->
                </div>
            </section>
            <!-- Section Testimonial End -->       
            <!-- Section FAQ Start -->
            <section class="faq bg-light overflow-hidden">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="pb-5 mb-5 pb-lg-0 mb-lg-0">
                                <span class="fs-5 fw-semibold text-primary d-block mb-3" data-aos="fade-right">常见问题</span>
                                <h2 class="fs-1 mb-4 pb-1" data-aos="fade-right" data-aos-delay="100">有问题？我们为你解答</h2>
                                <p class="fs-5 mb-4 pb-3" data-aos="fade-right" data-aos-delay="200">我们整理了师生最常关心的问题，覆盖教务、生活、宿舍、AI 助手与隐私安全，帮助你快速了解并熟练使用<?php echo $siteName; ?>。</p>
                                <div class="d-flex flex-row flex-wrap gap-3 pt-3">
                                    <a class="btn btn-light" href="faq.html" data-aos="fade-right">查看更多</a>
                                    <a class="btn btn-primary" href="#" data-aos="fade-right" data-bs-toggle="modal" data-bs-target="#modalFaqQuestion">有疑问？</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 offset-lg-1">
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item border-0 mb-2" data-aos="fade-left">
                                    <div class="accordion-header" id="headingFaqOne">
                                        <button class="accordion-button bg-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqOne" aria-expanded="true" aria-controls="collapseFaqOne">
                                            <span class="fs-5 fw-bold text-dark"><?php echo $siteName; ?>提供哪些校园服务？</span>
                                        </button>
                                    </div>
                                    <div id="collapseFaqOne" class="accordion-collapse collapse show" aria-labelledby="headingFaqOne" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body pt-0">
                                            <p class="mb-0"><?php echo $siteName; ?>覆盖校园生活全场景：教务查询（课表、成绩、考试、空教室）、生活服务（电费查询、报修、失物招领）、宿舍管理（归寝统计、住宿调整、访客管理）、资讯通知，以及基于大模型的校园 AI 助手，师生扫码即用。</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 mb-2" data-aos="fade-left" data-aos-delay="100">
                                    <div class="accordion-header" id="headingFaqTwo">
                                        <button class="accordion-button collapsed bg-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwo" aria-expanded="false" aria-controls="collapseFaqTwo">
                                            <span class="fs-5 fw-bold text-dark">如何查询课表、成绩和考试安排？</span>
                                        </button>
                                    </div>
                                    <div id="collapseFaqTwo" class="accordion-collapse collapse" aria-labelledby="headingFaqTwo" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body pt-0">
                                            <p class="mb-0">登录微信小程序或 App 后进入「教务服务」，即可一键查询本学期课表、历史成绩、考试安排与空教室，App 还支持离线课表，无网络也能随时查看。</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 mb-2" data-aos="fade-left" data-aos-delay="200">
                                    <div class="accordion-header" id="headingFaqFour">
                                        <button class="accordion-button collapsed bg-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqFour" aria-expanded="false" aria-controls="collapseFaqFour">
                                            <span class="fs-5 fw-bold text-dark">校园 AI 助手能帮我做什么？</span>
                                        </button>
                                    </div>
                                    <div id="collapseFaqFour" class="accordion-collapse collapse" aria-labelledby="headingFaqFour" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body pt-0">
                                            <p class="mb-0">内置基于大模型的校园 AI 助手，可解答校规校纪、办事流程、校园导航等问题，还能结合你的课表、成绩、电费等个人数据，提供个性化的提醒与办事建议，7×24 小时在线。</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 mb-2" data-aos="fade-left" data-aos-delay="400">
                                    <div class="accordion-header" id="headingFaqFive">
                                        <button class="accordion-button collapsed bg-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqFive" aria-expanded="false" aria-controls="collapseFaqFive">
                                            <span class="fs-5 fw-bold text-dark">我的个人信息和数据安全吗？</span>
                                        </button>
                                    </div>
                                    <div id="collapseFaqFive" class="accordion-collapse collapse" aria-labelledby="headingFaqFive" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body pt-0">
                                            <p class="mb-0">我们严格遵循《个人信息保护法》，数据全程加密存储与传输，按最小权限原则分配角色，操作全程留痕可审计；敏感信息仅用于提供服务所需，绝不违规外泄。</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section FAQ End -->
            <!-- Section Download App Start -->
            <section class="download_app_area overflow-hidden">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="app_content">
                                <h2 data-aos="fade-right">极速下载<br /><?php echo $siteName; ?> 客户端</h2>
                                <ul class="app_btn" data-aos="fade-right" data-aos-delay="100">
                                    <li>
                                        <a href="download.html#platform-wechat"><img src="./images/app/app_btn.png" alt="微信小程序"></a>
                                    </li>
                                    <li>
                                        <a href="download.html#platform-android"><img src="./images/app/app_btn2.png" alt="Android"></a>
                                    </li>
                                </ul>
                                <div class="apps_shape bounce-animate">
                                    <img src="./images/app/shape_arrow.png" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="app_thumb" data-aos="fade-left" data-aos-delay="150">
                                <img src="./images/app/app_img.png" alt="<?php echo $siteName; ?> 客户端">
                                <img class="app_rotate bounce-animate" src="./images/app/app_shape.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section Download App End -->
            <!-- Section Marquee (orange decoration bar) Start -->
            <div class="marquee_area style_two style_three">
                <div class="marquee_bg">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="marquee">
                                    <div class="marquee-block">
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>智慧校园平台</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>课表成绩查询</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>电费充值报修</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>校园资讯助手</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>数据安全合规</h3>
                                    </div>
                                    <div class="marquee-block">
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>智慧校园平台</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>课表成绩查询</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>电费充值报修</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>校园资讯助手</h3>
                                        <h3><span><img src="./images/app/marquee_img.png" class="rotates" alt=""></span>数据安全合规</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Section Marquee End -->
            <!-- Section CTA Start -->
            <section class="cta overflow-hidden bg-primary position-relative">
                <div class="container position-relative z-3">
                    <div class="row">
                        <div class="col-lg-8 pb-4 pb-lg-0">
                            <h2 class="fs-1 text-dark mb-3" data-aos="fade-right">立即开始保护你的软件</h2>
                            <p class="text-dark fs-5" data-aos="fade-right" data-aos-delay="100">加入超过 5 万开发者，他们用 <?php echo $siteName; ?> 守护自己的软件资产。</p>
                        </div>
                        <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
                            <a class="btn btn-lg btn-light" href="contact-us.html" data-aos="fade-left">免费试用</a>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section CTA End -->
            
            <!-- Modal Form FAQ's Question Start -->
            <div class="modal fade" id="modalFaqQuestion" tabindex="-1" aria-labelledby="modalFaqQuestionLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content rounded-4 border-0 p-3">
                        <div class="modal-header border-0 p-4 align-items-start">
                            <div class="modal-title pe-5" id="modalFaqQuestionLabel">
                                <h3 class="mb-3">没有找到你要的答案？直接向我们提问！</h3>
                                <p>有未在下方列出的问题？填写表单，我们的支持团队会尽快回复你。</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4 pt-0 pb-4">
                            <form class="needs-validation" novalidate>
                                <div class="d-block mb-3">
                                    <label for="inputQuestion" class="form-label fw-bold">你的问题</label>
                                    <textarea id="inputQuestion" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="写下你的问题" rows="5" required></textarea>
                                    <span class="invalid-feedback">请输入你的问题</span>
                                </div>
                                <button type="submit" class="btn btn-primary">提交</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Form FAQ's Question End -->
        </main>
        <!-- 公共底部组件（由 component-loader.js 注入） -->
        <div data-component="footer"></div>
        <script src="./js/jquery.min.js"></script>
        <script src="./js/bootstrap.bundle.min.js"></script>
        <script src="./js/aos.min.js"></script>
        <script src="./js/swiper-bundle.min.js"></script>
        <script src="./js/slick.min.js"></script>
        <script src="./js/count-up.min.js"></script>
        <script src="./js/fancybox.umd.js"></script>
        <script>window.SITE_NAME = <?php echo json_encode($siteName); ?>; window.SITE_CONFIG = <?php echo json_encode($siteConfig, JSON_UNESCAPED_UNICODE); ?>;</script>
        <script src="./js/component-loader.js?v=3"></script>
        <script src="./js/form-validation.js"></script>
        <script src="./js/main.js?v=8"></script>
        <script src="./js/custom.js"></script>
    </body>
</html>