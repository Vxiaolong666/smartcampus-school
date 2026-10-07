<?php
$notLogin = true;
include('Common/Core_brain.php');
include('Common/home_config.php');
$siteConfig = homeSiteConfig();
$siteName = htmlspecialchars(conf('Name') ?: '智校通', ENT_QUOTES);
$siteTitle = htmlspecialchars(conf('SiteName') ?: conf('Name') ?: '智校通', ENT_QUOTES);
?><!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title>隐私政策 - <?php echo $siteName; ?></title>
        <!-- Required Meta Tags -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Primary Meta Tags -->
        <meta name="title" content="隐私政策 - <?php echo $siteName; ?>">
        <meta name="description" content="了解<?php echo $siteName; ?>智慧校园平台如何收集、使用与保护师生数据。我们重视你的隐私，并以行业合规措施保障信息安全。">
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
                    <h1 class="page-title">隐私政策</h1>
                    <ul class="breadcrumbs">
                        <li><a href="index.php#home">首页</a></li>
                        <li class="active">隐私政策</li>
                    </ul>
                </div>
            </div>
            <!-- Page-Header End -->
            <!-- Section Start -->
            <section class="py-5">
                <div class="container py-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-5">
                        <div>
                            <p class="text-muted mb-1">最近更新：2026 年 8 月 21 日 · 生效日期：2026 年 8 月 21 日</p>
                            <p class="mb-0">本政策适用于<?php echo $siteName; ?>智慧校园平台及其关联服务。当你注册账号、使用校园服务或接入网页端时，即视为你已阅读并同意本政策所述做法。</p>
                        </div>
                        <a href="contact-us.php" class="btn btn-primary text-nowrap">如有疑问，联系我们</a>
                    </div>
                    <div class="row gy-5">
                        <div class="col-lg-4 col-xl-3">
                            <div id="privacyPolicyList" class="list-group list-group-cst list-group-sticky rounded-4 z-1">
                                <a href="#privacyPolicy1" class="list-group-item list-group-item-action active border-0 px-4 py-3">我们收集的信息</a>
                                <a href="#privacyPolicy2" class="list-group-item list-group-item-action border-0 px-4 py-3">我们如何使用你的信息</a>
                                <a href="#privacyPolicy3" class="list-group-item list-group-item-action border-0 px-4 py-3">信息共享</a>
                                <a href="#privacyPolicy4" class="list-group-item list-group-item-action border-0 px-4 py-3">数据安全</a>
                                <a href="#privacyPolicy5" class="list-group-item list-group-item-action border-0 px-4 py-3">数据保留期限</a>
                                <a href="#privacyPolicy6" class="list-group-item list-group-item-action border-0 px-4 py-3">你的权利</a>
                                <a href="#privacyPolicy7" class="list-group-item list-group-item-action border-0 px-4 py-3">Cookie 与跟踪</a>
                                <a href="#privacyPolicy8" class="list-group-item list-group-item-action border-0 px-4 py-3">第三方链接</a>
                                <a href="#privacyPolicy9" class="list-group-item list-group-item-action border-0 px-4 py-3">数据存储与传输</a>
                                <a href="#privacyPolicy10" class="list-group-item list-group-item-action border-0 px-4 py-3">未成年人信息</a>
                                <a href="#privacyPolicy11" class="list-group-item list-group-item-action border-0 px-4 py-3">如何联系我们</a>
                                <a href="#privacyPolicy12" class="list-group-item list-group-item-action border-0 px-4 py-3">本政策的变更</a>
                            </div>
                        </div>
                        <div class="col-lg-8 col-xl-9 ps-lg-5">
                            <div id="privacyPolicy1">
                                <h4 class="mb-2">我们收集的信息</h4>
                                <p class="mb-0">在使用<?php echo $siteName; ?>的过程中，我们依据《个人信息保护法》等法律法规，遵循合法、正当、必要原则，仅在提供服务所必需的范围内收集以下类型的信息：</p>
                                <ul class="mb-4">
                                    <li><strong>账号信息</strong>：学校分配的学工号 / 工号、姓名、手机号与登录凭证，用于身份核验与账号安全。</li>
                                    <li><strong>校园数据</strong>：院系、班级、宿舍等组织信息，以及课表、成绩、报修、充值等经学校授权同步的业务数据。</li>
                                    <li><strong>设备信息</strong>：IP 地址、设备型号与设备标识，用于登录风控、异地提醒与安全防护。</li>
                                    <li><strong>服务与日志数据</strong>：功能使用情况、操作记录与错误日志，用于排障、统计与体验优化。</li>
                                    <li><strong>支付与充值数据</strong>：电费充值、校园消费等产生的订单信息（支付凭证由持牌支付机构保管，我们不会存储你的完整银行卡信息）。</li>
                                    <li><strong>沟通数据</strong>：你通过工单、在线客服或反馈渠道发送给我们的消息与咨询。</li>
                                    <li><strong>AI 互动数据</strong>：你与校园 AI 助手对话的内容、提问与上下文，用于生成回答并持续改进服务质量，相关对话按本政策予以保护。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy2">
                                <h4 class="mb-2">我们如何使用你的信息</h4>
                                <p class="mb-0">我们使用你的数据用于以下目的：</p>
                                <ul class="mb-4">
                                    <li>提供课表、成绩、报修、充值、资讯与审批等核心校园服务。</li>
                                    <li>核验身份、保障账号安全，识别并拦截异常登录与盗用风险。</li>
                                    <li>与教务、一卡通等学校系统同步数据，支撑业务功能正常运行。</li>
                                    <li>生成服务使用统计与数据看板，帮助学校掌握运营状况。</li>
                                    <li>改进平台的功能、性能与安全性。</li>
                                    <li>发送重要通知（如停水停电、紧急公告），你可随时管理接收偏好。</li>
                                    <li>为校园 AI 助手提供上下文，结合你的学业与校园数据，给出个性化提醒与办事建议。</li>
                                    <li>响应支持请求、处理工单与反馈。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy3">
                                <h4 class="mb-2">信息共享</h4>
                                <p class="mb-0">我们不会出售、出租你的个人数据。我们仅在以下必要情况下共享你的信息：</p>
                                <ul class="mb-4">
                                    <li>根据学校授权，与校内相关业务系统（如教务、一卡通、后勤）共享必要数据。</li>
                                    <li>与受信任的第三方服务提供商共享（例如：支付通道、短信 / 消息通知、云托管与内容分发），仅提供完成服务所必需的数据。</li>
                                    <li>根据法律要求，向有权执法机关、监管机构或司法机关披露。</li>
                                    <li>在公司重组、合并或出售资产时，作为交易的一部分进行转移。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy4">
                                <h4 class="mb-2">数据安全</h4>
                                <p class="mb-4">我们采取行业合规的组织与技术措施保护你的数据免受未授权访问、丢失或滥用：</p>
                                <ul class="mb-4">
                                    <li><strong>传输与存储加密</strong>：全站 HTTPS 传输，敏感数据加密存储，保障数据在传输与落盘过程中的安全。</li>
                                    <li><strong>权限隔离</strong>：严格基于角色的访问控制与最小权限原则，仅授权人员可在职责范围内接触数据。</li>
                                    <li><strong>安全审计</strong>：关键操作全程留痕，配合登录风控与异地提醒，及时发现并处置异常。</li>
                                    <li><strong>等保合规</strong>：参照网络安全等级保护与教育行业数据管理要求建设安全体系。</li>
                                    <li><strong>数据备份</strong>：定期备份与异地冗余，保障服务可用性与数据可恢复性。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy5">
                                <h4 class="mb-2">数据保留期限</h4>
                                <p class="mb-4">我们会按服务所需的最短时间保留数据：账号与业务数据在账号存续期内保留；日志与统计数据的保留期限通常不超过 12 个月，用于安全审计与排障。当你注销账号后，我们将在合理期限内删除或匿名化处理你的个人信息，但法律法规或学校管理要求保留的数据除外。</p>
                            </div>
                            <div id="privacyPolicy6">
                                <h4 class="mb-2">你的权利</h4>
                                <p class="mb-0">你有权：</p>
                                <ul class="mb-4">
                                    <li>访问、更正或更新你的个人信息。</li>
                                    <li>请求删除你的账号与相关数据。</li>
                                    <li>导出或获取你的部分数据副本。</li>
                                    <li>管理已登录设备，下线可疑会话。</li>
                                    <li>随时调整通知接收偏好。</li>
                                    <li>对信息处理方式提出异议或投诉。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy7">
                                <h4 class="mb-2">Cookie 与跟踪</h4>
                                <p class="mb-0">我们使用 Cookie 与类似的跟踪技术来实现以下目的：</p>
                                <ul class="mb-4">
                                    <li><strong>必要 Cookie</strong>：保持你的登录状态与基本功能可用。</li>
                                    <li><strong>安全 Cookie</strong>：用于频率限制、会话校验与风险拦截。</li>
                                    <li><strong>分析 Cookie</strong>：分析访问模式，改善网站性能与体验。</li>
                                    <li>你可以通过浏览器设置管理或禁用 Cookie，但这可能影响部分功能的使用。</li>
                                </ul>
                            </div>
                            <div id="privacyPolicy8">
                                <h4 class="mb-2">第三方链接</h4>
                                <p class="mb-4">我们的平台可能包含指向第三方网站、SDK 或文档的链接（例如支付机构、地图服务）。我们不对其隐私实践或内容负责，也无法控制其在授权交互之外的行为。在向任何第三方提交数据前，请查阅其隐私政策。</p>
                            </div>
                            <div id="privacyPolicy9">
                                <h4 class="mb-2">数据存储与传输</h4>
                                <p class="mb-4"><?php echo $siteName; ?>数据部署于国内合规云节点，默认不出境。对于选择私有化部署的院校，数据可完全存储于学校自有服务器。我们确保数据传输符合适用法律要求，并采取等效的加密与安全保护措施，以保障你的数据安全。</p>
                            </div>
                            <div id="privacyPolicy10">
                                <h4 class="mb-2">未成年人信息</h4>
                                <p class="mb-4"><?php echo $siteName; ?>面向学校与教育机构提供服务，可能涉及未成年学生信息。我们在收集与使用此类信息时严格遵循《个人信息保护法》《未成年人保护法》等相关规定，并仅在获得学校合法授权的前提下处理，采取更严格的保护措施。</p>
                            </div>
                            <div id="privacyPolicy11">
                                <h4 class="mb-2">如何联系我们</h4>
                                <p class="mb-0">如你对本政策或你的数据权益有任何疑问，可通过以下方式与我们联系：</p>
                                <ul class="mb-4">
                                    <li>网站在线客服，工作时段即时响应。</li>
                                    <li>后台工单系统，提交问题后我们将尽快处理。</li>
                                    <li>学校管理员或联系我们页面中公布的官方邮箱。</li>
                                </ul>
                                <p class="mb-0">我们通常会在 7 个工作日内回复你的请求。</p>
                            </div>
                            <div id="privacyPolicy12">
                                <h4 class="mb-2">本政策的变更</h4>
                                <p class="mb-0">我们可能会不时更新本政策，以反映服务变化或法律要求。如果我们做出重大变更，将在此页面显著位置公告，并通过站内信或邮件通知你，同时说明本次主要变更内容。变更发布后继续使用服务即视为你接受更新后的政策。本政策的最近更新日期见页面顶部，历史版本可应你的要求提供。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section End -->
            
        </main>
        <!-- 公共底部组件（由 component-loader.js 注入） -->
        <div data-component="footer"></div>
        <script src="./js/bootstrap.bundle.min.js"></script>
        <script src="./js/aos.min.js"></script>
        <script src="./js/swiper-bundle.min.js"></script>
        <script src="./js/count-up.min.js"></script>
        <script src="./js/fancybox.umd.js"></script>
        <script>window.SITE_NAME = <?php echo json_encode($siteName); ?>; window.SITE_CONFIG = <?php echo json_encode($siteConfig, JSON_UNESCAPED_UNICODE); ?>;</script>
        <script src="./js/component-loader.js?v=3"></script>
        <script src="./js/form-validation.js"></script>
        <script src="./js/main.js?v=8"></script>
        <script src="./js/custom.js"></script>
    </body>
</html>
