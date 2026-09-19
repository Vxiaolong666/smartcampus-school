<?php
$mod = 'user';
include('../Common/Core_brain.php');

if(!isset($requireLogin)) {
    $requireLogin = false;
}

if($requireLogin && !isset($_SESSION['userId'])){
    header('Location: login.php');
    exit;
}

if(!isset($title)) {
    $title = '';
}

$userAvatar = '';
$userNickname = '';
if(isset($_SESSION['userId'])){
    $userRow = $DB->getRow("SELECT avatar, nickname FROM nteam_users WHERE id='{$_SESSION['userId']}'");
    if($userRow){
        $userAvatar = $userRow['avatar'] ?? '';
        $userNickname = $userRow['nickname'] ?? '';
    }
}

function getUserAvatar($avatar, $qq) {
    global $siteBasePath;
    if(!empty($avatar)){
        // 已是完整地址（含 http）直接返回
        if (strpos($avatar, 'http') === 0) {
            return $avatar;
        }
        // 相对路径：补齐站点子目录前缀（兼容 /csgocs 等二级目录部署）
        if (!empty($siteBasePath) && strpos($avatar, $siteBasePath) !== 0) {
            return $siteBasePath . $avatar;
        }
        return $avatar;
    }
    return 'https://q2.qlogo.cn/g?b=qq&nk=' . ($qq ?? '') . '&s=100';
}

function getUserDisplayName($nickname, $username) {
    return !empty($nickname) ? $nickname : $username;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="author" content="一个失踪的Ma.">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?php echo $title?> | <?php echo conf('Name');?></title>
    <meta name="keywords" content="<?php echo conf('Keywords');?>">
    <meta name="description" content="<?php echo conf('Description');?>">
    <link rel="shortcut icon" href="/assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" id="css-main" href="./assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="./assets/css/scrollbar.css">
    <link rel="stylesheet" href="./assets/js/plugins/magnific-popup/magnific-popup.css">
    <link href="./assets/js/plugins/nprogress/nprogress.min.css" rel="stylesheet">
    <script src="./assets/js/lib/jquery.min.js"></script>
    <script src="./assets/js/plugins/jquery.pjax/jquery.pjax.min.js"></script>
    <script src="./assets/js/plugins/nprogress/nprogress.min.js"></script>
    <script src="./assets/js/lib/layer.min.js"></script>
    <script src="./assets/js/oneui.app.min-5.6.js"></script>
    <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
    <script src="./assets/js/plugins/chart.js/chart.min.js"></script>
    <script src="./assets/js/plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="./assets/js/plugins/magnific-popup/jquery.magnific-popup.min.js"></script>
    <script src="./assets/js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="./assets/js/plugins/bootstrap-table/bootstrap-table.min.js"></script>
    <script src="./assets/js/plugins/bootstrap-table/bootstrap-table-zh-CN.min.js"></script>
    <link rel="stylesheet" href="./assets/js/plugins/bootstrap-table/bootstrap-table-filter-control.css">
    <script src="./assets/js/plugins/bootstrap-table/bootstrap-table-filter-control.js"></script>
    <script src="./assets/js/app.js"></script>
    <script>
    $(document).ajaxComplete(function(event, xhr, settings) {
        try {
            var response = typeof xhr.responseJSON === 'object' ? xhr.responseJSON : JSON.parse(xhr.responseText);
            if (response && response.code === -1 && response.msg === '请先登录') {
                layer.msg('登录状态已过期，正在跳转到登录页面...', {icon: 7, time: 1500}, function() {
                    window.location.href = 'login.php';
                });
            }
        } catch(e) {}
    });
    </script>

    <style>
        .btn-xs {
            --bs-btn-padding-y: 0.125rem;
            --bs-btn-padding-x: 0.25rem;
            --bs-btn-font-size: 0.75rem;
        }
        .table thead th {
            text-transform: none;
        }
        tbody, td, tfoot, th, thead, tr {
            border-color: inherit;
            border-style: none;
            border-width: 0;
        }

        /* AI 分析弹窗 Markdown 渲染：收紧 marked.js 默认段落/列表/标题边距 */
        .markdown-body {
            line-height: 1.6;
            /* 覆盖外层 <pre> 继承的 white-space:pre-wrap，否则块级元素间的换行会变成可见空行 */
            white-space: normal;
        }
        .markdown-body p {
            margin-bottom: 0.4rem;
        }
        .markdown-body p:empty {
            display: none;
            margin: 0;
        }
        .markdown-body li > p:last-child,
        .markdown-body li > p {
            margin-bottom: 0.2rem;
        }
        .markdown-body ul,
        .markdown-body ol {
            margin-bottom: 0.6rem;
            padding-left: 1.25rem;
        }
        .markdown-body h1,
        .markdown-body h2,
        .markdown-body h3,
        .markdown-body h4,
        .markdown-body h5,
        .markdown-body h6 {
            margin-top: 0.85rem;
            margin-bottom: 0.4rem;
        }
        .markdown-body h1:first-child,
        .markdown-body h2:first-child,
        .markdown-body h3:first-child {
            margin-top: 0;
        }
        .markdown-body pre {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.25rem;
            margin-bottom: 0.6rem;
        }
        .markdown-body code {
            background: #f8f9fa;
            padding: 0.125rem 0.35rem;
            border-radius: 0.2rem;
            font-size: 0.9em;
        }

        @media (max-width: 991.98px) {
            #page-container {
                padding-left: 0 !important;
            }
            #page-container:not(.sidebar-o-xs) #sidebar {
                transform: translateX(-100%) !important;
                box-shadow: none !important;
            }
            #page-container.sidebar-o-xs #sidebar {
                transform: translateX(0) !important;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3) !important;
            }
            #page-overlay {
                display: none !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background-color: rgba(0, 0, 0, 0.5) !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 1050 !important;
                cursor: pointer !important;
            }
            #page-container.sidebar-o-xs #page-overlay {
                display: block !important;
            }
            #sidebar {
                position: fixed !important;
                width: 280px !important;
                max-width: 80vw !important;
                left: 0 !important;
                z-index: 1051 !important;
                transition: transform 0.3s ease, box-shadow 0.3s ease !important;
            }
            #page-header {
                z-index: 1040 !important;
            }
            #main-container, #page-header {
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
        }

        /* 侧边栏当前菜单高亮（OneUI 默认仅改文字色，深色侧栏下辨识度低） */
        #sidebar .nav-main-link.active {
            background-color: rgba(0, 0, 0, .08);
            border-radius: .375rem;
            font-weight: 600;
        }
        #sidebar .nav-main-link.active::before {
            content: "";
            position: absolute;
            left: -.625rem;
            top: 50%;
            transform: translateY(-50%);
            width: .25rem;
            height: 1.25rem;
            border-radius: .25rem;
            background-color: #4c78dd;
        }
        .sidebar-dark #sidebar .nav-main-link.active {
            background-color: rgba(255, 255, 255, .14);
            color: #fff;
        }
        .sidebar-dark #sidebar .nav-main-link.active > .nav-main-link-icon { color: #fff; }
    </style>
    <script>
        (function() {
            var customOverlay = null;
            
            function createOverlay() {
                if (customOverlay) return customOverlay;
                
                customOverlay = document.createElement('div');
                customOverlay.id = 'mobile-sidebar-overlay';
                document.body.appendChild(customOverlay);
                
                customOverlay.style.cssText = 'display:none;position:fixed;top:0;left:0;right:0;bottom:0;width:100%;height:100%;background-color:#000000;opacity:0.5;z-index:1050;cursor:pointer;';
                
                customOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var pageContainer = document.getElementById('page-container');
                    if (pageContainer) {
                        pageContainer.classList.remove('sidebar-o-xs');
                    }
                    updateCustomOverlay();
                });
                
                return customOverlay;
            }
            
            function updateCustomOverlay() {
                var overlay = createOverlay();
                if (!overlay) return;
                
                var pageContainer = document.getElementById('page-container');
                var isOpen = pageContainer && pageContainer.classList.contains('sidebar-o-xs') && window.innerWidth < 992;
                
                overlay.style.display = isOpen ? 'block' : 'none';
            }
            
            function handleMobileSidebar() {
                var pageContainer = document.getElementById('page-container');
                if (!pageContainer) return;
                if (window.innerWidth < 992) {
                    pageContainer.classList.remove('sidebar-o');
                    pageContainer.classList.remove('sidebar-o-xs');
                } else {
                    pageContainer.classList.add('sidebar-o');
                }
                updateCustomOverlay();
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    handleMobileSidebar();
                });
            } else {
                handleMobileSidebar();
            }
            
            window.addEventListener('resize', handleMobileSidebar);
            
            document.addEventListener('click', function(e) {
                var pageContainer = document.getElementById('page-container');
                if (!pageContainer) return;
                if (window.innerWidth >= 992) return;
                
                var toggleBtn = e.target.closest('[data-action="sidebar_toggle"]');
                var closeBtn = e.target.closest('[data-action="sidebar_close"]');
                var sidebar = e.target.closest('#sidebar');
                
                if (toggleBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    pageContainer.classList.toggle('sidebar-o-xs');
                    updateCustomOverlay();
                    return;
                }
                
                if (closeBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    pageContainer.classList.remove('sidebar-o-xs');
                    updateCustomOverlay();
                    return;
                }
                
                if (!sidebar && pageContainer.classList.contains('sidebar-o-xs')) {
                    pageContainer.classList.remove('sidebar-o-xs');
                    updateCustomOverlay();
                }
            }, true);
        })();
    </script>
    <script>
        /* ===== 主题色 / 暗黑模式持久化（Cookie） =====
           OneUI 仅在 #page-container 带 remember-theme 时才读写 localStorage，
           页面默认没有该 class，导致主题刷新或跳转后丢失。
           这里改用 Cookie 保存，有效期 365 天，全站 path=/ 共享。 */
        (function () {
            var EXPIRE_DAYS = 365;

            function setCookie(name, value) {
                var d = new Date();
                d.setTime(d.getTime() + EXPIRE_DAYS * 24 * 60 * 60 * 1000);
                document.cookie = name + '=' + encodeURIComponent(value)
                    + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
            }
            function getCookie(name) {
                var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : '';
            }

            // 应用主题：与 OneUI 的 _uiUpdateTheme 保持同样做法（维护 #css-theme 链接）
            function applyTheme(name) {
                if (!name) return;
                var link = document.getElementById('css-theme');
                if (name === 'default') {
                    if (link && link.parentNode) link.parentNode.removeChild(link);
                    return;
                }
                if (link) {
                    link.setAttribute('href', name);
                    return;
                }
                var main = document.getElementById('css-main');
                if (!main) return;
                link = document.createElement('link');
                link.id = 'css-theme';
                link.setAttribute('rel', 'stylesheet');
                link.setAttribute('href', name);
                main.insertAdjacentElement('afterend', link);
            }

            function syncThemeActive(name) {
                var items = document.querySelectorAll('[data-toggle="theme"]');
                Array.prototype.forEach.call(items, function (a) {
                    var t = a.getAttribute('data-theme');
                    var target = (!t || t === 'default') ? 'default' : t;
                    a.classList.toggle('active', target === name);
                });
            }

            function applyDark(on) {
                var pc = document.getElementById('page-container');
                if (!pc) return;
                if (on) {
                    pc.classList.add('sidebar-dark');
                    pc.classList.add('page-header-dark');
                    pc.classList.add('dark-mode');
                } else {
                    pc.classList.remove('dark-mode');
                }
            }

            // 主题 CSS 立即恢复（#css-main 已存在），避免闪烁
            var savedTheme = getCookie('oneui_theme');
            if (savedTheme) applyTheme(savedTheme);

            document.addEventListener('DOMContentLoaded', function () {
                var dark = getCookie('oneui_dark');
                if (dark !== '') applyDark(dark === '1');
                if (savedTheme) syncThemeActive(savedTheme);

                // 主题切换 → 写入 Cookie
                Array.prototype.forEach.call(document.querySelectorAll('[data-toggle="theme"]'), function (a) {
                    a.addEventListener('click', function () {
                        setCookie('oneui_theme', a.getAttribute('data-theme') || 'default');
                    });
                });

                // 暗黑模式切换 → 等 OneUI 改完 class 后读取真实状态再写入
                document.addEventListener('click', function (e) {
                    var btn = e.target && e.target.closest ? e.target.closest('[data-action="dark_mode_toggle"]') : null;
                    if (!btn) return;
                    setTimeout(function () {
                        var pc = document.getElementById('page-container');
                        if (!pc) return;
                        setCookie('oneui_dark', pc.classList.contains('dark-mode') ? '1' : '0');
                    }, 0);
                });
            });
        })();
    </script>
    <script>
        /* ===== 侧边栏当前菜单高亮 ===== */
        (function () {
            // 子页面归入其父菜单，避免详情页等二级页面侧栏无高亮
            var ALIAS = {
                'announcement.php': 'announcements.php',
                'power_fee_batch.php': 'power_fee.php'
            };

            function currentPage() {
                var path = window.location.pathname || '';
                var file = path.substring(path.lastIndexOf('/') + 1);
                if (!file) file = 'index.php';
                if (ALIAS[file]) file = ALIAS[file];
                return file.split('#')[0].split('?')[0];
            }

            // 让当前菜单项落在侧栏可视区内（已可见则不动）
            function scrollActiveIntoView(el) {
                var wrap = document.querySelector('#sidebar .simplebar-content-wrapper')
                        || document.querySelector('#sidebar .simplebar-offset')
                        || document.querySelector('#sidebar .js-sidebar-scroll');
                if (!wrap) return;
                var wr = wrap.getBoundingClientRect();
                var er = el.getBoundingClientRect();
                if (er.top >= wr.top && er.bottom <= wr.bottom) return; // 已完全可见
                if (wrap.scrollHeight <= wrap.clientHeight) return;     // 无需滚动
                var delta = (er.top - wr.top) - (wr.height - er.height) / 2;
                try {
                    wrap.scrollTo({ top: wrap.scrollTop + delta, behavior: 'smooth' });
                } catch (err) {
                    wrap.scrollTop += delta;
                }
            }

            function highlight() {
                var cur = currentPage();
                var matched = null;
                Array.prototype.forEach.call(document.querySelectorAll('.nav-main .nav-main-link'), function (a) {
                    var href = (a.getAttribute('href') || '').split('#')[0].split('?')[0];
                    if (!href || href === 'javascript:void(0)' || href.charAt(0) === '#') return;
                    if (href === cur) {
                        a.classList.add('active');
                        if (!matched) matched = a;
                    } else {
                        a.classList.remove('active');
                    }
                });
                if (!matched) return;
                // 存在子菜单时展开父级
                var parentItem = matched.closest('.nav-main-submenu');
                if (parentItem) {
                    parentItem.style.display = 'block';
                    var pi = parentItem.closest('.nav-main-item');
                    if (pi) pi.classList.add('open');
                }
                // 等布局稳定后再滚动，保证当前项可见
                setTimeout(function () { scrollActiveIntoView(matched); }, 80);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', highlight);
            } else {
                highlight();
            }
            // pjax 局部刷新后重新高亮
            if (window.jQuery) {
                window.jQuery(document).on('pjax:end pjax:complete', highlight);
            }
        })();
    </script>
</head>

<body>
<div id="page-container"
     class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-fixed main-content-narrow side-trans-enabled">
    <div id="page-overlay"></div>
    <nav id="sidebar" aria-label="Main Navigation">
        <div class="content-header">
            <a class="fw-semibold text-dual" data-pjax href="index.php">
                <span class="smini-visible">
                    <i class="fa fa-circle-notch text-primary"></i>
                </span>
                <span class="smini-hide fs-5 tracking-wider"><?php echo conf('Name');?></span>
            </a>
            <div>
                <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="dark_mode_toggle">
                    <i class="far fa-moon"></i>
                </button>
                <div class="dropdown d-inline-block ms-1">
                    <button type="button" class="btn btn-sm btn-alt-secondary" id="sidebar-themes-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-paint-brush"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end fs-sm smini-hide border-0"
                         aria-labelledby="sidebar-themes-dropdown">
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium active"
                           data-toggle="theme" data-theme="default" href="#">
                            <span>Default</span>
                            <i class="fa fa-circle text-default"></i>
                        </a>
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium"
                           data-toggle="theme" data-theme="./assets/css/themes/amethyst.min-5.5.css" href="#">
                            <span>Amethyst</span>
                            <i class="fa fa-circle text-amethyst"></i>
                        </a>
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium"
                           data-toggle="theme" data-theme="./assets/css/themes/city.min-5.5.css" href="#">
                            <span>City</span>
                            <i class="fa fa-circle text-city"></i>
                        </a>
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium"
                           data-toggle="theme" data-theme="./assets/css/themes/flat.min-5.5.css" href="#">
                            <span>Flat</span>
                            <i class="fa fa-circle text-flat"></i>
                        </a>
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium"
                           data-toggle="theme" data-theme="./assets/css/themes/modern.min-5.5.css" href="#">
                            <span>Modern</span>
                            <i class="fa fa-circle text-modern"></i>
                        </a>
                        <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium"
                           data-toggle="theme" data-theme="./assets/css/themes/smooth.min-5.5.css" href="#">
                            <span>Smooth</span>
                            <i class="fa fa-circle text-smooth"></i>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item fw-medium" data-toggle="layout" data-action="sidebar_style_light"
                           href="javascript:void(0)">
                            <span>Sidebar Light</span>
                        </a>
                        <a class="dropdown-item fw-medium" data-toggle="layout" data-action="sidebar_style_dark"
                           href="javascript:void(0)">
                            <span>Sidebar Dark</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item fw-medium" data-toggle="layout" data-action="header_style_light"
                           href="javascript:void(0)">
                            <span>Header Light</span>
                        </a>
                        <a class="dropdown-item fw-medium" data-toggle="layout" data-action="header_style_dark"
                           href="javascript:void(0)">
                            <span>Header Dark</span>
                        </a>
                    </div>
                </div>
                <a class="d-lg-none btn btn-sm btn-alt-secondary ms-1" data-toggle="layout"
                   data-action="sidebar_close" href="javascript:void(0)">
                    <i class="fa fa-fw fa-times"></i>
                </a>
            </div>
        </div>
        <div class="js-sidebar-scroll" data-simplebar="init">
            <div class="simplebar-wrapper" style="margin: 0px;">
                <div class="simplebar-height-auto-observer-wrapper">
                    <div class="simplebar-height-auto-observer"></div>
                </div>
                <div class="simplebar-mask">
                    <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                        <div class="simplebar-content-wrapper" tabindex="0" role="region"
                             aria-label="scrollable content" style="height: 100%; overflow: hidden;">
                            <div class="simplebar-content" style="padding: 0px;">
                                <div class="content-side" id="nav-main">
                                    <ul class="nav-main">
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="index.php">
                                                <i class="nav-main-link-icon fa fa-house-user"></i>
                                                <span class="nav-main-link-name"><b>用户中心</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="chat.php">
                                                <i class="nav-main-link-icon fa fa-robot"></i>
                                                <span class="nav-main-link-name"><b>AI 对话</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="announcements.php">
                                                <i class="nav-main-link-icon fa fa-bullhorn"></i>
                                                <span class="nav-main-link-name"><b>公告列表</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-heading">Academic</li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="today_schedule.php">
                                                <i class="nav-main-link-icon fa fa-calendar-day"></i>
                                                <span class="nav-main-link-name"><b>本周课程</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="grade.php">
                                                <i class="nav-main-link-icon fa fa-chart-bar"></i>
                                                <span class="nav-main-link-name"><b>成绩查询</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="schedule.php">
                                                <i class="nav-main-link-icon fa fa-calendar-alt"></i>
                                                <span class="nav-main-link-name"><b>课程查询</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="student_info.php">
                                                <i class="nav-main-link-icon fa fa-id-card"></i>
                                                <span class="nav-main-link-name"><b>学籍查询</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="academic_push_settings.php">
                                                <i class="nav-main-link-icon fa fa-bell"></i>
                                                <span class="nav-main-link-name"><b>学术推送</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-heading">Dormitory</li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="power_fee.php">
                                                <i class="nav-main-link-icon fa fa-bolt"></i>
                                                <span class="nav-main-link-name"><b>电费查询</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="power_statistics.php">
                                                <i class="nav-main-link-icon fa fa-chart-line"></i>
                                                <span class="nav-main-link-name"><b>用电统计</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="dormitory_bind.php">
                                                <i class="nav-main-link-icon fa fa-home"></i>
                                                <span class="nav-main-link-name"><b>宿舍绑定</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="push_settings.php">
                                                <i class="nav-main-link-icon fa fa-bell"></i>
                                                <span class="nav-main-link-name"><b>推送设置</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="subscription.php">
                                                <i class="nav-main-link-icon fab fa-weixin"></i>
                                                <span class="nav-main-link-name"><b>订阅消息</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-heading">Account</li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="profile.php">
                                                <i class="nav-main-link-icon fa fa-user"></i>
                                                <span class="nav-main-link-name"><b>个人资料</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="oauth_bind.php">
                                                <i class="nav-main-link-icon fa fa-link"></i>
                                                <span class="nav-main-link-name"><b>第三方账号绑定</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="academic_bind.php">
                                                <i class="nav-main-link-icon fa fa-graduation-cap"></i>
                                                <span class="nav-main-link-name"><b>教务账号绑定</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="edit_pwd.php">
                                                <i class="nav-main-link-icon fa fa-key"></i>
                                                <span class="nav-main-link-name"><b>修改密码</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="cancel_account.php">
                                                <i class="nav-main-link-icon fa fa-user-slash"></i>
                                                <span class="nav-main-link-name"><b>注销账户</b></span>
                                            </a>
                                        </li>
                                        <li class="nav-main-heading text-center">- 更多功能敬请期待 -</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="simplebar-placeholder" style="width: auto; height: 735px;"></div>
            </div>
            <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
            </div>
            <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
            </div>
        </div>
    </nav>
    <header id="page-header">
        <div class="content-header">
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-lg-none" data-toggle="layout"
                        data-action="sidebar_toggle">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-none d-lg-inline-block"
                        data-toggle="layout" data-action="sidebar_mini_toggle">
                    <i class="fa fa-fw fa-ellipsis-v"></i>
                </button>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown d-inline-block ms-2">
                    <button type="button" class="btn btn-sm btn-alt-secondary d-flex align-items-center" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="rounded-circle" src="<?=getUserAvatar($userAvatar, $_SESSION['userQq'] ?? '')?>" alt="Header Avatar" style="width: 21px;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%236c757d%22/%3E%3Ccircle cx=%2250%22 cy=%2235%22 r=%2220%22 fill=%22%23fff%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2235%22 ry=%2225%22 fill=%22%23fff%22/%3E%3C/svg%3E'">
                        <span class="d-none d-sm-inline-block ms-2"><?=getUserDisplayName($userNickname, $_SESSION['userName'] ?? '用户')?></span>
                        <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block opacity-50 ms-1 mt-1"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0" aria-labelledby="page-header-user-dropdown">
                        <div class="p-3 text-center bg-body-light border-bottom rounded-top">
                            <img class="img-avatar img-avatar48 img-avatar-thumb"
                                 src="<?=getUserAvatar($userAvatar, $_SESSION['userQq'] ?? '')?>" alt="" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%236c757d%22/%3E%3Ccircle cx=%2250%22 cy=%2235%22 r=%2220%22 fill=%22%23fff%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2235%22 ry=%2225%22 fill=%22%23fff%22/%3E%3C/svg%3E'">
                            <p class="mt-2 mb-0 fw-medium"><?=getUserDisplayName($userNickname, $_SESSION['userName'] ?? '用户')?></p>
                            <p class="mb-0 text-muted fs-sm fw-medium">普通用户</p>
                        </div>
                        <div class="px-2 py-3 bg-body-light rounded-top">
                            <h5 class="h6 fs-xs text-center mb-0">
                                QQ：<?=(isset($_SESSION['userQq'])?$_SESSION['userQq']:'未设置')?>
                            </h5>
                        </div>
                        <div class="p-2">
                            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="profile.php">
                                <span>个人资料</span>
                                <i class="fa fa-fw fa-user opacity-25"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="edit_pwd.php">
                                <span>修改密码</span>
                                <i class="fa fa-fw fa-key opacity-25"></i>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="dormitory_bind.php">
                                <span>宿舍绑定</span>
                                <i class="fa fa-fw fa-home opacity-25"></i>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="academic_bind.php">
                                <span>教务账号绑定</span>
                                <i class="fa fa-fw fa-graduation-cap opacity-25"></i>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="./login.php?logout">
                                <span>退出登入</span>
                                <i class="fa fa-fw fa-sign-out-alt opacity-25"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="page-header-loader" class="overlay-header bg-body-extra-light">
            <div class="content-header">
                <div class="w-100 text-center">
                    <i class="fa fa-fw fa-circle-notch fa-spin"></i>
                </div>
            </div>
        </div>
    </header>
    <main id="main-container">
