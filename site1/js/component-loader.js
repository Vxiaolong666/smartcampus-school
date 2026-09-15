// 公共组件加载器：将页眉 / 页脚注入各页面
// 组件 HTML 直接内嵌于此（不再使用 fetch），因此 file:// 双击打开也能正常工作。
(function () {
    'use strict';

    const HEADER_HTML = `<!-- Header Start -->
<header class="header d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.html" class="d-flex align-items-center gap-2">
            <img src="./images/logo-header.png" class="header-logo" alt="智校通 网站头部 logo">
           </a>
        <nav class="d-none d-lg-flex flex-grow-1 justify-content-center">
            <ul class="nav">
                <li>
                    <a href="index.html" class="nav-link px-3 text-dark fw-semibold">首页</a>
                </li>
                <li>
                    <a href="download.html" class="nav-link px-3 text-dark fw-semibold">下载</a>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link px-3 text-dark fw-semibold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">页面</a>
                    <ul class="dropdown-menu border-0 rounded-4 py-3" style="width: 250px;">
                        <li>
                            <a href="faq.html" class="dropdown-item px-4 py-3 text-dark fw-semibold">常见问题</a>
                        </li>
                        <li>
                            <a href="privacy-policy.html" class="dropdown-item px-4 py-3 text-dark fw-semibold">隐私政策</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="contact-us.html" class="nav-link px-3 text-dark fw-semibold">联系我们</a>
                </li>
            </ul>
        </nav>
        <div class="d-none d-lg-flex gap-4 align-items-center">
            <a href="contact-us.html" class="text-decoration-none text-dark fw-semibold">注册</a>
            <a href="contact-us.html" class="btn btn-sm btn-primary">登录</a>
        </div>
        <button class="btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
            <i class="fi fi-br-bars-staggered fs-3"></i>
        </button>
    </div>
</header>
<!-- Header End -->
<!-- Offcanvas Header Start -->
<div class="offcanvas offcanvas-end offcanvas-mainnav d-lg-none" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header px-5 pt-5 pb-0">
        <img src="./images/logo-header.png" class="offcanvas-img" alt="智校通 网站移动端 logo">
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-5">
        <ul class="nav flex-column mb-4">
            <li>
                <a href="index.html" class="nav-link px-0 py-3 text-dark fw-semibold">首页</a>
            </li>
            <li>
                <a href="download.html" class="nav-link px-0 py-3 text-dark fw-semibold">下载</a>
            </li>
            <li class="dropdown">
                <a href="#" class="nav-link px-0 py-3 text-dark fw-semibold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">页面</a>
                <ul class="dropdown-menu border-0 rounded-4 py-0 position-static" style="width: 250px;">
                    <li>
                        <a href="faq.html" class="dropdown-item px-4 py-3 text-dark fw-semibold">常见问题</a>
                    </li>
                    <li>
                        <a href="privacy-policy.html" class="dropdown-item px-4 py-3 text-dark fw-semibold">隐私政策</a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="contact-us.html" class="nav-link px-0 py-3 text-dark fw-semibold">联系我们</a>
            </li>
        </ul>
        <div class="d-flex gap-2">
            <a href="contact-us.html" class="btn btn-light">注册</a>
            <a href="contact-us.html" class="btn btn-primary">登录</a>
        </div>
    </div>
</div>
<!-- Offcanvas Header End -->`;

    const FOOTER_HTML = `<!-- Footer Section Start Here -->
<footer>
    <div class="footer_before">
        <div class="container">
            <div class="row">
               <div class="col-lg-4 col-md-4 col-sm-6">
                    <a href="index.html" class="footer_title footer-logo">智校通</a>
                    <p class="footer_desc">一站式智慧校园服务平台，连接师生、辅导员与后勤，让校园生活更智能、更便捷。</p>
                    <ul class="footer_url">
                        <li><i class="fad fa-map-marker-alt"></i><a href="javascript:void(0)">服务全国高校，赋能智慧校园</a></li>
                        <li><i class="fal fa-envelope-open-text"></i><a href="mailto:service@zhixiaoyun.com">service@zhixiaoyun.com</a></li>
                        <li><i class="far fa-globe"></i><a href="javascript:void(0)"> www.zhixiaoyun.com</a></li>
                    </ul>
               </div>
               <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer_title">支持</div>
                    <ul class="footer_url">
                        <li><a href="faq.html">常见问题</a></li>
                        <li><a href="contact-us.html">帮助中心</a></li>
                        <li><a href="javascript:void(0)">在线客服</a></li>
                        <li><a href="mailto:support@zhixiaoyun.com">邮件联系</a></li>
                    </ul>
               </div>
               <div class="col-lg-3 col-md-3 col-sm-6">
                    <div class="footer_title">产品</div>
                    <ul class="footer_url">
                        <li><a href="download.html">客户端下载</a></li>
                        <li><a href="index.html#features">功能特性</a></li>
                        <li><a href="javascript:void(0)">网页端</a></li>
                        <li><a href="contact-us.html">联系我们</a></li>
                    </ul>
               </div>
               <div class="col-lg-3 col-md-2 col-sm-6">
                    <div class="footer_title">条款</div>
                    <ul class="footer_url">
                        <li><a href="privacy-policy.html">隐私政策</a></li>
                        <li><a href="privacy-policy.html#privacyPolicy4">数据安全</a></li>
                    </ul>
               </div>
            </div>
        </div>
    </div>
    <div class="footer_after text-center">
        <div class="container">
            <p>Copyright @ 2026 智校通 All Rights Reserved. 一站式智慧校园服务平台</p>
        </div>
    </div>
</footer>
<!-- Footer Section End Here -->

<a onclick="topFunction()" id="back-to-top" title="Go to top" style="display: block;"><i class="far fa-arrow-up"></i></a>`;

    const MAP = {
        '[data-component="header"]': HEADER_HTML,
        '[data-component="footer"]': FOOTER_HTML
    };

    Object.keys(MAP).forEach(function (selector) {
        var el = document.querySelector(selector);
        if (el) {
            el.innerHTML = MAP[selector];
        }
    });

    setActiveNav();
    // 通知 main.js 等脚本：组件已注入完成
    // 注意：必须在其他脚本（main.js）注册监听器之后再派发，否则事件会丢失。
    // 用 setTimeout 延后到当前同步脚本执行完毕、main.js 已完成监听器注册后再触发。
    setTimeout(function () {
        document.dispatchEvent(new CustomEvent('componentsLoaded'));
    }, 0);

    // 根据当前页面自动高亮导航中的对应菜单项
    function setActiveNav() {
        var current = window.location.pathname.split('/').pop() || 'index.html';
        document.querySelectorAll('.header .nav a[href], .offcanvas-mainnav .nav a[href]').forEach(function (link) {
            var href = link.getAttribute('href');
            if (href && href.split('#')[0].split('?')[0] === current) {
                link.classList.add('active');
            }
        });
    }
})();
