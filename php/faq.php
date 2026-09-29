<?php
$notLogin = true;
include('Common/Core_brain.php');
include('Common/home_config.php');
$siteName = htmlspecialchars(conf('Name') ?: '智校通', ENT_QUOTES);
$siteTitle = htmlspecialchars(conf('SiteName') ?: conf('Name') ?: '智校通', ENT_QUOTES);

// 后台「官网配置 → 常见问题配置」；为空时页面回退到原有静态内容
$faqGroups    = homeFaqGrouped();
$faqIntro     = homeCfg('faq_intro');
$hasFaqConfig = !empty($faqGroups);
// 页脚等公共组件使用的站点配置
$siteConfig = homeSiteConfig();
?><!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title>常见问题 - <?php echo $siteName; ?></title>
        <!-- Required Meta Tags -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Primary Meta Tags -->
        <meta name="title" content="常见问题 - <?php echo $siteName; ?>">
        <meta name="description" content="关于<?php echo $siteName; ?>智慧校园平台的常见问题解答：账号登录、校园服务、安全隐私、部署运维等。">
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
        <link rel="stylesheet" href="./css/style.css?v=15" type="text/css">
        <!-- custom css -->
        <link rel="stylesheet" href="./css/custom.css?v=2" type="text/css">
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
            <div class="page-header text-center">
                <div class="container">
                    <h1 class="page-title">常见问题</h1>
                    <ul class="breadcrumbs">
                        <li><a href="index.php#home">首页</a></li>
                        <li class="active">常见问题</li>
                    </ul>
                </div>
            </div>
            <!-- Page-Header End -->

            <!-- Read Most Frequent Questions Start -->
            <section class="faq-section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="heading">
                                <h2>常见问题解答</h2>
                                <img src="images/icons/headin_border.png" alt="headin_border">
                            </div>
                        </div>
                    </div>
                    <?php if ($hasFaqConfig): ?>
                    <?php if ($faqIntro !== ''): ?>
                    <div class="row mb-3">
                        <div class="col-md-12"><p class="text-muted"><?php echo htmlspecialchars($faqIntro, ENT_QUOTES); ?></p></div>
                    </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-12">
                            <?php $gi = 0; foreach ($faqGroups as $cat => $items): $gi++; $pid = 'faqDyn' . $gi; ?>
                            <h3 class="faq-cat-title"><?php echo htmlspecialchars($cat, ENT_QUOTES); ?></h3>
                            <div class="accordion_faq" id="<?php echo $pid; ?>">
                                <?php $ii = 0; foreach ($items as $it): $ii++; $cid = $pid . '_' . $ii; ?>
                                <div class="card">
                                    <div class="card-header" id="h_<?php echo $cid; ?>">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#c_<?php echo $cid; ?>" aria-expanded="<?php echo ($ii === 1) ? 'true' : 'false'; ?>" aria-controls="c_<?php echo $cid; ?>">
                                            <?php echo htmlspecialchars($it['question'], ENT_QUOTES); ?>
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="c_<?php echo $cid; ?>" class="collapse<?php echo ($ii === 1) ? ' show' : ''; ?>" aria-labelledby="h_<?php echo $cid; ?>" data-bs-parent="#<?php echo $pid; ?>">
                                        <div class="card-body"><?php echo $it['answer']; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row align-items-start justify-content-center"<?php echo $hasFaqConfig ? ' style="display:none"' : ''; ?>>
                        <div class="col-xl-5 col-lg-6">
                            <!-- Faq category 1 start -->
                            <h3 class="faq-cat-title">账号与登录</h3>
                            <div class="accordion_faq" id="accordionCat1">
                                <div class="card">
                                    <div class="card-header" id="headingFaqOne">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqOne" aria-expanded="true" aria-controls="collapseFaqOne">
                                            <?php echo $siteName; ?>支持哪些登录方式？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqOne" class="collapse show" aria-labelledby="headingFaqOne" data-bs-parent="#accordionCat1">
                                        <div class="card-body">
                                            支持学校统一身份认证（CAS / OAuth）、微信扫码与企业微信登录，并兼容手机号验证码登录。师生首次使用即可快速绑定校园账号，无需重复注册。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwo">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwo" aria-expanded="false" aria-controls="collapseFaqTwo">
                                            忘记密码或无法登录怎么办？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwo" class="collapse" aria-labelledby="headingFaqTwo" data-bs-parent="#accordionCat1">
                                        <div class="card-body">
                                            可通过学校统一认证找回密码，或在登录页点击「联系管理员」由院校管理员重置；管理员也可在 Web 后台批量处理账号异常。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqThree">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqThree" aria-expanded="false" aria-controls="collapseFaqThree">
                                            一个账号可以在多台设备登录吗？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqThree" class="collapse" aria-labelledby="headingFaqThree" data-bs-parent="#accordionCat1">
                                        <div class="card-body">
                                            默认支持手机、平板与 Web 同时在线；若学校出于安全考虑需要限制共用，可在后台设置单设备或多设备登录策略。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTen" aria-expanded="false" aria-controls="collapseFaqTen">
                                            辅导员 / 后勤如何开通后台权限？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTen" class="collapse" aria-labelledby="headingFaqTen" data-bs-parent="#accordionCat1">
                                        <div class="card-body">
                                            由学校超级管理员在网页端创建子账号并分配角色（辅导员、后勤、财务等），按最小权限原则赋权，操作全程留痕可审计。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqEleven">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqEleven" aria-expanded="false" aria-controls="collapseFaqEleven">
                                            数据如何与学校现有系统对接？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqEleven" class="collapse" aria-labelledby="headingFaqEleven" data-bs-parent="#accordionCat1">
                                        <div class="card-body">
                                            提供开放 API 与标准数据接口，可与教务系统、一卡通、宿舍管理系统等对接，支持定时同步与实时回调，平滑融入既有信息化架构。
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Faq category 1 end -->

                            <!-- Faq category 2 start -->
                            <h3 class="faq-cat-title">校园服务</h3>
                            <div class="accordion_faq" id="accordionCat2">
                                <div class="card">
                                    <div class="card-header" id="headingFaqFour">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqFour" aria-expanded="true" aria-controls="collapseFaqFour">
                                            <?php echo $siteName; ?>能为师生提供哪些服务？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqFour" class="collapse show" aria-labelledby="headingFaqFour" data-bs-parent="#accordionCat2">
                                        <div class="card-body">
                                            聚合课表查询、成绩查询、电费充值、宿舍报修、校园资讯、失物招领、请假审批与 AI 校园助手等高频服务，覆盖师生日常所需。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqFive">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqFive" aria-expanded="false" aria-controls="collapseFaqFive">
                                            电费充值安全吗？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqFive" class="collapse" aria-labelledby="headingFaqFive" data-bs-parent="#accordionCat2">
                                        <div class="card-body">
                                            充值通过学校指定通道与持牌第三方支付完成，订单实时对账，余额与流水可在后台查询，全程可追溯，资金安全有保障。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqSix">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqSix" aria-expanded="false" aria-controls="collapseFaqSix">
                                            宿舍报修怎么使用？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqSix" class="collapse" aria-labelledby="headingFaqSix" data-bs-parent="#accordionCat2">
                                        <div class="card-body">
                                            在 App 或小程序提交报修工单，选择楼栋房间与故障类型，后勤接单后实时推进，进度可全程跟踪并对服务进行评价。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwelve">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwelve" aria-expanded="false" aria-controls="collapseFaqTwelve">
                                            支持哪些通知方式？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwelve" class="collapse" aria-labelledby="headingFaqTwelve" data-bs-parent="#accordionCat2">
                                        <div class="card-body">
                                            支持 App 推送、微信模板消息与短信通知，停水停电、紧急事件等重要事项可一键群发，确保信息及时触达。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqThirteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqThirteen" aria-expanded="false" aria-controls="collapseFaqThirteen">
                                            AI 校园助手能做什么？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqThirteen" class="collapse" aria-labelledby="headingFaqThirteen" data-bs-parent="#accordionCat2">
                                        <div class="card-body">
                                            <?php echo $siteName; ?>内置基于大模型的校园 AI 助手，可解答校规校纪、办事流程、校园导航等问题，还能结合你的课表、成绩、电费等个人数据提供个性化提醒与建议，7×24 小时在线，帮助师生快速获取所需信息。
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Faq category 2 end -->

                            <!-- Faq category 3 start -->
                            <h3 class="faq-cat-title">安全与隐私</h3>
                            <div class="accordion_faq" id="accordionCat3">
                                <div class="card">
                                    <div class="card-header" id="headingFaqFourteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqFourteen" aria-expanded="true" aria-controls="collapseFaqFourteen">
                                            我的数据安全吗？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqFourteen" class="collapse show" aria-labelledby="headingFaqFourteen" data-bs-parent="#accordionCat3">
                                        <div class="card-body">
                                            全站 HTTPS 传输，敏感数据加密存储；通过等保合规与权限隔离机制，严格保护师生隐私与校园数据安全。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqFifteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqFifteen" aria-expanded="false" aria-controls="collapseFaqFifteen">
                                            <?php echo $siteName; ?>如何保护我的隐私？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqFifteen" class="collapse" aria-labelledby="headingFaqFifteen" data-bs-parent="#accordionCat3">
                                        <div class="card-body">
                                            遵循最小化采集原则，仅收集提供服务所必需的信息；数据按角色脱敏展示，未经授权不得访问，详细做法见《隐私政策》。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqSixteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqSixteen" aria-expanded="false" aria-controls="collapseFaqSixteen">
                                            如何防止账号被盗用？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqSixteen" class="collapse" aria-labelledby="headingFaqSixteen" data-bs-parent="#accordionCat3">
                                        <div class="card-body">
                                            支持设备绑定、异地登录提醒与登录风控，异常登录需二次验证；管理员可一键下线可疑会话，及时止损。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqSeventeen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqSeventeen" aria-expanded="false" aria-controls="collapseFaqSeventeen">
                                            学校可以自定义安全策略吗？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqSeventeen" class="collapse" aria-labelledby="headingFaqSeventeen" data-bs-parent="#accordionCat3">
                                        <div class="card-body">
                                            可在后台设置 IP 白名单、登录频率限制、敏感操作审计日志等，满足不同院校的安全合规要求。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqEighteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqEighteen" aria-expanded="false" aria-controls="collapseFaqEighteen">
                                            数据存储在哪里？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqEighteen" class="collapse" aria-labelledby="headingFaqEighteen" data-bs-parent="#accordionCat3">
                                        <div class="card-body">
                                            数据部署于国内合规云节点，符合《个人信息保护法》与教育行业数据管理要求，支持私有化部署满足数据不出校。
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Faq category 3 end -->
                        </div>

                        <div class="col-xl-5 col-lg-6">
                            <!-- Faq category 4 start -->
                            <h3 class="faq-cat-title">部署与运维</h3>
                            <div class="accordion_faq" id="accordionCat4">
                                <div class="card">
                                    <div class="card-header" id="headingFaqNineteen">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqNineteen" aria-expanded="true" aria-controls="collapseFaqNineteen">
                                            部署需要多久？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqNineteen" class="collapse show" aria-labelledby="headingFaqNineteen" data-bs-parent="#accordionCat4">
                                        <div class="card-body">
                                            标准 SaaS 版本开通当日即可使用；如需私有化部署，我们提供方案评估与实施支持，通常 1–2 周完成上线。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwenty">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwenty" aria-expanded="false" aria-controls="collapseFaqTwenty">
                                            支持私有化 / 本地化部署吗？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwenty" class="collapse" aria-labelledby="headingFaqTwenty" data-bs-parent="#accordionCat4">
                                        <div class="card-body">
                                            支持。可部署到学校自有服务器或教育专有云，满足数据不出校、自主可控的合规要求，并提供配套运维指引。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwentyOne">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwentyOne" aria-expanded="false" aria-controls="collapseFaqTwentyOne">
                                            系统稳定性如何保障？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwentyOne" class="collapse" aria-labelledby="headingFaqTwentyOne" data-bs-parent="#accordionCat4">
                                        <div class="card-body">
                                            采用多节点容灾、7×24 小时监控与自动告警机制，提供 SLA 可用性保障，并针对重大故障制定应急响应预案。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwentyTwo">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwentyTwo" aria-expanded="false" aria-controls="collapseFaqTwentyTwo">
                                            提供哪些运维支持？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwentyTwo" class="collapse" aria-labelledby="headingFaqTwentyTwo" data-bs-parent="#accordionCat4">
                                        <div class="card-body">
                                            提供在线帮助文档、工单系统、专属客户成功经理与定期回访，确保系统平稳运行，问题得到及时响应。
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header" id="headingFaqTwentyThree">
                                        <div class="faqs_header" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwentyThree" aria-expanded="false" aria-controls="collapseFaqTwentyThree">
                                            如何获取培训与对接文档？
                                            <i class="fal fa-long-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div id="collapseFaqTwentyThree" class="collapse" aria-labelledby="headingFaqTwentyThree" data-bs-parent="#accordionCat4">
                                        <div class="card-body">
                                            开通后提供管理员手册、对接 API 文档与视频教程，并支持到校培训与远程答疑，帮助学校快速上手并深度使用。
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Faq category 4 end -->
                        </div>
                    </div>
                </div>
            </section>
            <!-- Read Most Frequent Questions End -->

            <!-- Service Team Section Start -->
            <section class="faq-section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="heading">
                                <h2>我们的服务团队<br>将全程为你的智慧校园护航</h2>
                                <img src="images/icons/headin_border.png" alt="headin_border">
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-3">
                            <div class="about_bg white_bg">
                                <div class="about_img">
                                    <img src="images/about/about_img1.png" alt="about">
                                </div>
                                <div class="about_text">
                                    <h5>高效可靠</h5>
                                    <p>稳定流畅的校园服务平台，已助力众多院校快速完成接入与数字化落地。</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 m-t60">
                            <div class="about_bg white_bg">
                                <div class="about_img">
                                    <img src="images/about/about_img2.png" alt="about">
                                </div>
                                <div class="about_text">
                                    <h5>灵活适配</h5>
                                    <p>无论是中小学还是高校，均可根据规模与场景灵活适配你的信息化需求。</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="about_bg white_bg">
                                <div class="about_img">
                                    <img src="images/about/about_img3.png" alt="about">
                                </div>
                                <div class="about_text">
                                    <h5>安全合规</h5>
                                    <p>严格遵循隐私保护与教育数据规范，让师生用得放心、学校管得安心。</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 m-t60">
                            <div class="about_bg white_bg">
                                <div class="about_img">
                                    <img src="images/about/about_img4.png" alt="about">
                                </div>
                                <div class="about_text">
                                    <h5>全程陪伴</h5>
                                    <p>从开通、培训到日常运维，专属团队全程陪伴，保障服务持续稳定。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Service Team Section End -->

        </main>
        <!-- 公共底部组件（由 component-loader.js 注入） -->
        <div data-component="footer"></div>
        <script src="./js/bootstrap.bundle.min.js"></script>
        <script src="./js/aos.min.js"></script>
        <script src="./js/swiper-bundle.min.js"></script>
        <script src="./js/count-up.min.js"></script>
        <script src="./js/fancybox.umd.js"></script>
        <script>window.SITE_NAME = <?php echo json_encode($siteName); ?>; window.SITE_CONFIG = <?php echo json_encode($siteConfig, JSON_UNESCAPED_UNICODE); ?>;</script>
        <script src="./js/component-loader.js?v=2"></script>
        <script src="./js/form-validation.js"></script>
        <script src="./js/main.js?v=6"></script>
        <script src="./js/custom.js"></script>
    </body>
</html>
