<?php
$notLogin = true;
include('Common/Core_brain.php');
include('Common/home_config.php');
$siteConfig = homeSiteConfig();
$siteName = htmlspecialchars(conf('Name') ?: '智校通', ENT_QUOTES);
$siteTitle = htmlspecialchars(conf('SiteName') ?: conf('Name') ?: '智校通', ENT_QUOTES);

// 后台「官网配置」中的联系方式与表单开关（未配置时使用下方默认值）
$hcEmail    = homeCfg('contact_email', 'support@zhixiaoyun.com');
$hcPhone    = homeCfg('contact_phone', '');
$hcQq       = homeCfg('contact_qq', '');
$hcAddr     = homeCfg('contact_address', '');
$hcWork     = homeCfg('contact_worktime', '周一至周五 9:00–18:00（GMT+8）');
$hcDesc     = homeCfg('contact_desc', '');
$formOpen    = intval(homeCfg('contact_form_open', 1));
$formCaptcha = intval(homeCfg('contact_form_captcha', 1));
?>
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title>联系我们 - <?php echo $siteName; ?></title>
        <!-- Required Meta Tags -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Primary Meta Tags -->
        <meta name="title" content="联系我们 - <?php echo $siteName; ?>">
        <meta name="description" content="有任何关于功能、部署、演示或其他问题？欢迎联系<?php echo $siteName; ?>智慧校园平台团队，我们随时为你服务。">
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
        <!-- 腾讯滑块验证（与登录页一致） -->
        <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
        <style>
            /* ===== 人机验证：滑块 / 图形验证码（与页面表单风格一致） ===== */
            .cv-slider-btn {
                width: 100%;
                padding: .75rem 1rem;
                font-weight: 600;
                border-radius: .75rem;
                background: #f4f5f8;
                border: 1px solid #e5e7eb;
                color: #4b5563;
            }
            .cv-slider-btn:hover {
                background: #e9ebf1;
                border-color: #d7dae3;
                color: #374151;
            }
            .cv-slider-btn i { margin-right: .4rem; }
            .cv-slider-btn,
            .cv-slider-btn:hover,
            .cv-slider-btn:focus,
            .cv-slider-btn:focus-visible,
            .cv-slider-btn:active,
            .cv-slider-btn:not(:disabled):active:focus {
                box-shadow: none !important;
            }
            .cv-slider-btn.is-passed,
            .cv-slider-btn.is-passed:hover,
            .cv-slider-btn.is-passed:focus {
                background: #ecfdf5 !important;
                border-color: #34d399 !important;
                color: #059669 !important;
                cursor: default;
            }
            .cv-tip { display: none; margin-top: .5rem; font-size: .875rem; color: #059669; }
            .cv-switch { display: none; margin-top: .5rem; text-align: right; font-size: .875rem; }
            .cv-switch a { color: #6b7280; text-decoration: none; }
            .cv-switch a:hover { color: #0160e7; text-decoration: underline; }

            .captcha-wrapper { display: none; align-items: center; gap: .75rem; margin-top: .75rem; }
            .captcha-wrapper .form-control { flex: 1 1 auto; min-width: 0; }
            .captcha-wrapper img {
                flex: 0 0 auto;
                height: 3.25rem;
                width: auto;
                border: 1px solid #e5e7eb;
                border-radius: .75rem;
                cursor: pointer;
            }
            .contact-status { margin-top: .75rem; font-size: .875rem; }
            @media (max-width: 575.98px) {
                .captcha-wrapper { flex-wrap: wrap; }
                .captcha-wrapper img { height: 3rem; }
            }
        </style>
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
                    <h1 class="page-title">联系我们</h1>
                    <ul class="breadcrumbs">
                        <li><a href="index.php#home">首页</a></li>
                        <li class="active">联系我们</li>
                    </ul>
                </div>
            </div>
            <!-- Page-Header End -->
            <section class="py-5">
                <div class="container py-5">
                    <div class="row gx-5 gy-5">
                        <div class="col-lg-5 pe-lg-5">
                            <h4 class="fw-bold mb-4 mb-sm-5" style="line-height: 1.4;">我们期待听到你的声音。无论是关于系统部署、功能配置还是其他任何问题，我们的团队随时为你服务。</h4>
                            <h3 class="mb-3">需要支持？</h3>
                            <p class="mb-0">遇到使用问题或需要帮助？</p>
                            <p class="mb-4">访问帮助中心或直接联系我们，我们会尽快回复你。</p>
                            <ul class="list-unstyled d-flex flex-column gap-3 mb-5">
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-envelope"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">邮件支持</h6>
                                        <a href="mailto:<?php echo htmlspecialchars($hcEmail, ENT_QUOTES); ?>" class="text-decoration-none text-primary"><?php echo htmlspecialchars($hcEmail, ENT_QUOTES); ?></a>
                                    </div>
                                </li>
                                <?php if ($hcPhone !== ''): ?>
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-phone-call"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">联系电话</h6>
                                        <span><?php echo htmlspecialchars($hcPhone, ENT_QUOTES); ?></span>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <?php if ($hcQq !== ''): ?>
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-comment-user"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">在线 QQ</h6>
                                        <span><?php echo htmlspecialchars($hcQq, ENT_QUOTES); ?></span>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-clock"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">支持时间</h6>
                                        <span><?php echo htmlspecialchars($hcWork, ENT_QUOTES); ?></span>
                                    </div>
                                </li>
                            </ul>
                            <h3 class="mb-3">商务合作</h3>
                            <p class="mb-4">对院校开通、私有化部署或定制解决方案感兴趣？</p>
                            <ul class="list-unstyled d-flex flex-column gap-3">
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-envelope"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">商务联系</h6>
                                        <a href="mailto:<?php echo htmlspecialchars($hcEmail, ENT_QUOTES); ?>" class="text-decoration-none text-primary"><?php echo htmlspecialchars($hcEmail, ENT_QUOTES); ?></a>
                                    </div>
                                </li>
                                <li class="d-flex gap-3">
                                    <div class="icon-contact d-flex flex-shrink-0 justify-content-center align-items-center bg-primary text-white">
                                        <i class="fi fi-rr-postal-address"></i>
                                    </div>
                                    <div class="d-block">
                                        <h6 class="mb-0">公司地址</h6>
                                        <span><?php echo htmlspecialchars($hcAddr !== '' ? $hcAddr : ($siteName . '智慧校园平台，服务全国高校，赋能数字化校园建设。'), ENT_QUOTES); ?></span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-lg-7 ps-lg-5">
                            <div class="card card-contact border-0 rounded-5">
                                <div class="card-body">
                                    <form class="needs-validation" id="contactForm" novalidate>
                                        <h3 class="mb-4 pb-2">联系我们</h3>
                                        <?php if ($hcDesc !== ''): ?>
                                        <p class="text-muted mb-4"><?php echo htmlspecialchars($hcDesc, ENT_QUOTES); ?></p>
                                        <?php endif; ?>
                                        <div class="d-block mb-4">
                                            <label for="inputFullName" class="form-label fw-bold">你的姓名</label>
                                            <input type="text" id="inputFullName" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入你的姓名" required>
                                            <span class="invalid-feedback">请填写你的姓名</span>
                                        </div>
                                        <div class="d-block mb-4">
                                            <div class="row gy-4">
                                                <div class="col-lg-6">
                                                    <label for="inputYourPhone" class="form-label fw-bold">手机号码</label>
                                                    <input type="text" id="inputYourPhone" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入手机号码" required>
                                                    <span class="invalid-feedback">请填写手机号码</span>
                                                </div>
                                                <div class="col-lg-6">
                                                    <label for="inputYourEmail" class="form-label fw-bold">邮箱</label>
                                                    <input type="text" id="inputYourEmail" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入邮箱" required>
                                                    <span class="invalid-feedback">请填写邮箱</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-block mb-4">
                                            <label for="inputSubject" class="form-label fw-bold">主题</label>
                                            <input type="text" id="inputSubject" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入主题" required>
                                            <span class="invalid-feedback">请填写主题</span>
                                        </div>
                                        <div class="d-block mb-4">
                                            <label for="inputMessage" class="form-label fw-bold">留言内容</label>
                                            <textarea id="inputMessage" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入留言内容" rows="6" required></textarea>
                                            <span class="invalid-feedback">请填写留言内容</span>
                                        </div>
                                        <?php if ($formOpen && $formCaptcha): ?>
                                        <!-- 人机验证：腾讯滑块（参考登录页），异常时可切换图形验证码 -->
                                        <div class="mb-4">
                                            <button type="button" class="btn cv-slider-btn" id="TencentCaptcha" data-appid="2047155482" data-cbfn="onTCaptchaResult">
                                                <i class="fas fa-shield-alt"></i>进行滑块验证
                                            </button>
                                            <div class="cv-tip" id="tcPassedTip">
                                                <i class="fas fa-check-circle"></i> 安全验证已通过
                                            </div>
                                            <input type="hidden" name="tc_ticket" id="tc_ticket">
                                            <input type="hidden" name="tc_randstr" id="tc_randstr">

                                            <div class="captcha-wrapper" id="textCaptcha">
                                                <input type="text" name="captcha" id="captcha" class="form-control form-control-lg fs-6 px-4 py-3 rounded-3" placeholder="请输入右侧验证码" autocomplete="off">
                                                <img src="./server-submit/captcha.php" id="captchaImg" alt="验证码" title="点击刷新验证码" onclick="refreshCaptcha()">
                                            </div>
                                            <div class="cv-switch" id="showTextCaptchaLink">
                                                <a href="javascript:void(0)" onclick="showTextCaptcha()">滑块异常？改用图片验证码</a>
                                            </div>
                                            <div class="cv-switch" id="showSliderLink">
                                                <a href="javascript:void(0)" onclick="showSlider()">返回滑块验证</a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="form-check mb-4">
                                            <input type="checkbox" class="form-check-input" id="agreeCheck">
                                            <label class="form-check-label text-muted" for="agreeCheck">我同意接收邮件、资讯和推广消息</label>
                                        </div>
                                        <?php if ($formOpen): ?>
                                        <button type="submit" id="submitBtn" class="btn btn-primary">发送消息</button>
                                        <div class="contact-status" id="contactStatus"></div>
                                        <?php else: ?>
                                        <div class="alert alert-info mb-0">留言功能已关闭，请通过左侧展示的联系方式与我们联系。</div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
        </main>
        <!-- 公共底部组件（由 component-loader.js 注入） -->
        <div data-component="footer"></div>

        <!-- 提交结果弹窗 -->
        <div class="modal fade" id="contactResultModal" tabindex="-1" aria-labelledby="contactResultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="contactResultModalLabel">提交结果</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                    </div>
                    <div class="modal-body text-center pt-3 pb-4">
                        <div id="contactResultIcon" class="mb-3" style="font-size:3.25rem;line-height:1;"></div>
                        <p class="mb-0 fs-6" id="contactResultText"></p>
                    </div>
                    <div class="modal-footer border-0 pt-0 justify-content-center">
                        <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">确定</button>
                    </div>
                </div>
            </div>
        </div>

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
        <script>
        (function () {
            var FORM_OPEN    = <?php echo $formOpen ? 'true' : 'false'; ?>;
            var NEED_CAPTCHA = <?php echo $formCaptcha ? 'true' : 'false'; ?>;

            function el(id) { return document.getElementById(id); }
            function val(id) { var n = el(id); return n ? (n.value || '').trim() : ''; }

            window.refreshCaptcha = function () {
                var img = el('captchaImg');
                if (img) img.src = './server-submit/captcha.php?' + Date.now();
            };

            function showEl(id, on) {
                var n = el(id);
                if (!n) return;
                n.style.display = on ? (id === 'textCaptcha' ? 'flex' : 'block') : 'none';
            }

            // 切换到图形验证码
            window.showTextCaptcha = function () {
                showEl('TencentCaptcha', false);
                showEl('tcPassedTip', false);
                showEl('textCaptcha', true);
                showEl('showTextCaptchaLink', false);
                showEl('showSliderLink', true);
                if (el('tc_ticket')) el('tc_ticket').value = '';
                if (el('tc_randstr')) el('tc_randstr').value = '';
                window.refreshCaptcha();
            };

            // 切回滑块验证
            window.showSlider = function () {
                showEl('textCaptcha', false);
                showEl('showSliderLink', false);
                showEl('showTextCaptchaLink', false);
                showEl('tcPassedTip', false);
                var btn = el('TencentCaptcha');
                if (btn) {
                    btn.classList.remove('is-passed');
                    btn.innerHTML = '<i class="fas fa-shield-alt"></i>进行滑块验证';
                }
                showEl('TencentCaptcha', true);
                if (el('tc_ticket')) el('tc_ticket').value = '';
                if (el('tc_randstr')) el('tc_randstr').value = '';
            };

            window.onTCaptchaResult = function (res) {
                if (res && res.ret === 0) {
                    if (el('tc_ticket')) el('tc_ticket').value = res.ticket || '';
                    if (el('tc_randstr')) el('tc_randstr').value = res.randstr || '';
                    var btn = el('TencentCaptcha');
                    if (btn) {
                        btn.classList.add('is-passed');
                        btn.innerHTML = '<i class="fas fa-check-circle"></i>验证已通过';
                    }
                    showEl('tcPassedTip', true);
                    showEl('showTextCaptchaLink', false);
                    showEl('showSliderLink', false);
                } else {
                    showEl('showTextCaptchaLink', true);
                }
            };

            if (!FORM_OPEN) return;

            var form = el('contactForm');
            var statusBox = el('contactStatus');
            if (!form) return;

            function setStatus(msg, ok) {
                if (!statusBox) return;
                statusBox.innerHTML = msg
                    ? '<span style="color:' + (ok ? '#059669' : '#dc2626') + '">' + msg + '</span>'
                    : '';
            }

            // 提交结果弹窗（Bootstrap Modal，与页面样式一致；未加载到 bootstrap 时回退 alert）
            function showResult(ok, msg) {
                setStatus('', true);
                var icon = el('contactResultIcon');
                if (icon) {
                    icon.innerHTML = ok
                        ? '<i class="fas fa-check-circle" style="color:#059669"></i>'
                        : '<i class="fas fa-times-circle" style="color:#dc2626"></i>';
                }
                var text  = el('contactResultText');
                if (text) text.textContent = msg || (ok ? '提交成功' : '提交失败');
                var title = el('contactResultModalLabel');
                if (title) title.textContent = ok ? '提交成功' : '提交失败';

                var m = el('contactResultModal');
                if (m && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(m).show();
                } else {
                    alert(msg || (ok ? '提交成功' : '提交失败'));
                }
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var name    = val('inputFullName');
                var phone   = val('inputYourPhone');
                var email   = val('inputYourEmail');
                var subject = val('inputSubject');
                var content = val('inputMessage');

                if (!name || !content) { setStatus('请填写姓名与留言内容', false); return; }

                var tcTicket  = val('tc_ticket');
                var tcRandstr = val('tc_randstr');
                var captcha   = val('captcha');

                if (NEED_CAPTCHA && !((tcTicket && tcRandstr) || captcha)) {
                    setStatus('请先完成滑块 / 验证码验证', false);
                    return;
                }

                var btn = el('submitBtn');
                if (btn) btn.disabled = true;
                setStatus('正在提交，请稍后…', true);

                var body = new URLSearchParams();
                body.append('name', name);
                body.append('phone', phone);
                body.append('email', email);
                body.append('subject', subject);
                body.append('content', content);
                body.append('tc_ticket', tcTicket);
                body.append('tc_randstr', tcRandstr);
                body.append('captcha', captcha);

                fetch('./server-submit/contact_submit.php', {
                    method: 'POST',
                    body: body,
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (r) {
                    return r.json();
                }).then(function (data) {
                    if (btn) btn.disabled = false;
                    if (data && data.code === 0) {
                        form.reset();
                        window.showSlider();
                        window.refreshCaptcha();
                        showResult(true, data.msg || '提交成功，我们会尽快与你联系');
                    } else {
                        window.refreshCaptcha();
                        showResult(false, (data && data.msg) || '提交失败，请稍后重试');
                    }
                }).catch(function () {
                    if (btn) btn.disabled = false;
                    showResult(false, '网络错误，请稍后重试');
                });
            });
        })();
        </script>
    </body>
</html>
