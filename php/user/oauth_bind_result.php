<?php
/**
 * 第三方账号绑定 / 登录结果页（供 App / 小程序 web-view 展示）
 *
 * 授权完成后由 oauth_callback.php 跳转至此页，用户点「返回 App」即可回到 App。
 * 返回方式按运行环境自动选择，任何一种方式失败都会给出明确反馈：
 *   1) uniapp App（5+ 引擎）：让父 webview 执行 uni.navigateBack 关闭 web-view 页；
 *   2) 微信小程序 web-view：wx.miniProgram.navigateBack；
 *   3) Android 浏览器：intent:// 按包名唤起（App 未安装时浏览器回落本页并提示）；
 *   4) iOS 浏览器：URL Scheme 唤起（需 App 打包时配置 scheme）；
 *   5) 微信 / QQ 内置浏览器：不允许唤起 App，直接给出「在浏览器打开」指引。
 */
$notLogin = true;
include('../Common/Core_brain.php');

// ===================== App 唤起配置（改这里即可） =====================
// Android 包名：填上即可直接按包名唤起 App（无需 App 重新打包、无需 URL Scheme）
// 当前值取自 App 安装包 AndroidManifest.xml 的 package 属性；换包名后请同步修改
$DEFAULT_APP_PACKAGE = 'com.gdnpc.smartcampus.school';
// App URL Scheme：已在 App 的 manifest.json 中注册（Android schemes / iOS urltypes 均为 smartcampus），
// 需重新云打包后生效；如改动 scheme 请与 manifest.json 保持一致
$DEFAULT_APP_SCHEME = 'smartcampus';

// 也支持通过 URL 参数覆盖，例如 oauth_bind_result.php?...&pkg=com.xxx.yyy&scheme=xxx
$appPackage = isset($_GET['pkg']) ? trim($_GET['pkg']) : '';
$appScheme  = isset($_GET['scheme']) ? trim($_GET['scheme']) : '';
if ($appPackage === '') $appPackage = $DEFAULT_APP_PACKAGE;
if ($appScheme === '')  $appScheme  = $DEFAULT_APP_SCHEME;
// Android intent 唤起失败（App 未安装）时浏览器会带该参数回到本页
$launchFailed = (isset($_GET['launch_failed']) && intval($_GET['launch_failed']) === 1);

// ===== 下载 / 更新入口：跳转官网下载页（地址来自系统动态配置）=====
// 优先「官网配置 → 网站地址」，留空回退系统「网站域名」配置；都没有时再用版本管理的直链兜底
$officialUrl = '';
if (is_file(__DIR__ . '/../Common/home_config.php')) {
    include_once(__DIR__ . '/../Common/home_config.php');
    if (function_exists('homeCfg')) {
        $officialUrl = trim((string) homeCfg('site_url'));
    }
}
if ($officialUrl === '') {
    $officialUrl = trim((string) conf('Url'));
}
if ($officialUrl !== '' && !preg_match('#^https?://#i', $officialUrl)) {
    $officialUrl = '//' . ltrim($officialUrl, '/');
}
// 官网下载页（官网「下载方式」所在页面）
$downloadUrl = $officialUrl !== '' ? rtrim($officialUrl, '/') . '/download.php' : '';

// 兜底：官网地址未配置时，取后台「版本管理」中对应平台的最新启用版本
$appDownload = array('android' => '', 'ios' => '');
try {
    $chk = $DB->query("SHOW TABLES LIKE 'nteam_app_versions'");
    if ($chk && $chk->fetch()) {
        foreach (array('android', 'ios') as $pf) {
            $row = $DB->getRow("SELECT download_url FROM nteam_app_versions WHERE status=1 AND platform IN ('all','" . $pf . "') ORDER BY version_code DESC LIMIT 1");
            if ($row && !empty($row['download_url'])) {
                $appDownload[$pf] = $row['download_url'];
            }
        }
    }
} catch (Exception $e) {}

$isLogin = (isset($_GET['type']) && $_GET['type'] === 'login');
$ok  = (isset($_GET['ok']) && intval($_GET['ok']) === 1);
$msg = isset($_GET['msg']) ? trim(daddslashes($_GET['msg'])) : '';
if ($msg === '') {
    $msg = $ok ? ($isLogin ? '登录成功' : '绑定成功') : ($isLogin ? '登录失败' : '绑定失败');
}
$sceneName = $isLogin ? '第三方登录' : '第三方账号绑定';
$okTitle   = $isLogin ? '登录成功' : '绑定成功';
$failTitle = $isLogin ? '登录失败' : '绑定失败';
$siteName  = conf('Name');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($sceneName); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
            background: #eff1ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }
        .result-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 48px 28px 36px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }
        .result-icon {
            width: 84px;
            height: 84px;
            margin: 0 auto 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            font-weight: bold;
            color: #fff;
        }
        .result-icon.ok { background: #34c759; }
        .result-icon.fail { background: #ff4d4f; }
        .result-title {
            font-size: 20px;
            font-weight: bold;
            color: #48416f;
            margin-bottom: 12px;
        }
        .result-msg {
            font-size: 14px;
            color: #6b6786;
            line-height: 1.7;
            margin-bottom: 28px;
            word-break: break-word;
        }
        .result-btn {
            display: block;
            width: 100%;
            height: 46px;
            line-height: 46px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .result-btn:active { opacity: .9; }
        .result-btn.alt {
            margin-top: 12px;
            color: #13c2c2;
            background: #e8fbfb;
            font-weight: normal;
            font-size: 15px;
        }
        .result-tip {
            margin-top: 16px;
            font-size: 12px;
            color: #948eb3;
            line-height: 1.6;
        }
        /* 唤起失败反馈区 */
        .result-feedback {
            display: none;
            margin-top: 20px;
            padding: 16px 14px;
            border-radius: 10px;
            background: #fff8e6;
            border: 1px solid #ffe1a8;
            text-align: left;
        }
        .fb-title {
            font-size: 14px;
            font-weight: bold;
            color: #b25e09;
            margin-bottom: 8px;
        }
        .fb-text {
            font-size: 13px;
            color: #8a6d3b;
            line-height: 1.8;
            word-break: break-word;
        }
        .fb-actions { margin-top: 4px; }
    </style>
</head>
<body>
    <div class="result-card">
        <div class="result-icon <?php echo $ok ? 'ok' : 'fail'; ?>"><?php echo $ok ? '&#10003;' : '!'; ?></div>
        <div class="result-title"><?php echo $ok ? htmlspecialchars($okTitle) : htmlspecialchars($failTitle); ?></div>
        <div class="result-msg"><?php echo htmlspecialchars($msg); ?></div>

        <button class="result-btn" id="backBtn" onclick="backToApp()">返回 App</button>

        <!-- 下载 / 更新：跳转官网下载页（地址由系统动态配置，后台「官网配置 → 网站地址」可改） -->
        <a class="result-btn alt" id="downloadBtn" style="display:none" rel="noopener">前往官网下载 / 更新</a>

        <!-- 唤起失败 / 环境不允许时的反馈（含重试入口） -->
        <div class="result-feedback" id="launchFeedback">
            <div class="fb-title" id="fbTitle">未能自动返回 App</div>
            <div class="fb-text" id="fbText"></div>
            <div class="fb-actions">
                <button class="result-btn alt" onclick="retryLaunch()">重试打开 App</button>
            </div>
        </div>

        <div class="result-tip" id="resultTip">如未自动返回，请手动切换到 App；点「返回 App」可重试，或前往官网下载最新版本。</div>
    </div>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
    (function () {
        // ===== 服务端注入的配置 =====
        var APP_PACKAGE = <?php echo json_encode($appPackage); ?>;
        var APP_SCHEME  = <?php echo json_encode($appScheme); ?>;
        var DOWNLOAD_URL = <?php echo json_encode($downloadUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;   // 官网下载页（系统动态配置）
        var DOWNLOADS    = <?php echo json_encode($appDownload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;   // 兜底直链（官网未配置时）
        var LAUNCH_FAILED = <?php echo $launchFailed ? 'true' : 'false'; ?>;

        var ua = navigator.userAgent || '';
        function hasPlus() {
            return typeof plus !== 'undefined' && plus && plus.webview;
        }
        function isMiniProgram() {
            if (/miniprogram/i.test(ua)) return true;
            try { return window.__wxjs_environment === 'miniprogram'; } catch (e) { return false; }
        }
        function isWeChat() { return /micromessenger/i.test(ua); }
        function isQQ() { return /\bqq\//i.test(ua) || /qqbrowser/i.test(ua); }
        function isAndroid() { return /android/i.test(ua); }
        function isIOS() {
            if (/iphone|ipad|ipod/i.test(ua)) return true;
            return /macintosh/i.test(ua) && 'ontouchend' in document;
        }

        // 下载 / 更新目标：优先官网下载页（系统动态配置），未配置时按平台回退版本管理直链
        var DOWNLOAD_TARGET = DOWNLOAD_URL || (isAndroid()
            ? (DOWNLOADS.android || DOWNLOADS.ios)
            : (isIOS() ? (DOWNLOADS.ios || DOWNLOADS.android) : (DOWNLOADS.android || DOWNLOADS.ios)));

        var feedbackEl = document.getElementById('launchFeedback');
        var fbTextEl = document.getElementById('fbText');
        var downloadEl = document.getElementById('downloadBtn');
        var watchTimer = null;
        var leftPage = false;
        var launchAttempts = 0;   // 重试时在「按包名」与「按 scheme」之间交替尝试

        // 下载 / 更新按钮：只要拿到可用地址就展示（点击直接跳官网下载页）
        if (downloadEl && DOWNLOAD_TARGET) {
            downloadEl.href = DOWNLOAD_TARGET;
            downloadEl.style.display = 'block';
        }

        var REASONS = {
            fail: '已尝试打开 App，但未检测到 App 响应。若已安装，请手动切换到 App；版本过旧可前往官网下载更新后重试。',
            wechat: '微信内置浏览器不允许直接打开 App：请点击右上角「···」，选择「在浏览器打开」后再点「返回 App」。',
            qq: 'QQ 内置浏览器不允许直接打开 App：请点击右上角「···」，选择「在浏览器打开」后再点「返回 App」。',
            ios: 'iOS 需要 App 配置 URL Scheme 才能被网页唤起。可手动切换到 App，或前往官网下载 / 更新。',
            noconfig: '当前未配置 App 唤起信息，无法自动打开 App，请手动切换回 App，或前往官网下载最新版本。'
        };

        function showFeedback(reason) {
            if (!feedbackEl) return;
            fbTextEl.innerHTML = REASONS[reason] || REASONS.fail;
            feedbackEl.style.display = 'block';
        }

        function hideFeedback() {
            if (feedbackEl) feedbackEl.style.display = 'none';
        }

        // 唤起后监听页面是否离开（离开说明 App 已被拉起）
        function watchLaunch() {
            if (watchTimer) clearTimeout(watchTimer);
            leftPage = false;
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) leftPage = true;
            }, false);
            window.addEventListener('pagehide', function () { leftPage = true; }, false);
            watchTimer = setTimeout(function () {
                watchTimer = null;
                if (!leftPage) showFeedback('fail');
            }, 2500);
        }

        // 唤起失败回落地址（Android intent 的 browser_fallback_url）
        function fallbackUrl() {
            var href = location.href.split('#')[0];
            return href + (href.indexOf('?') >= 0 ? '&' : '?') + 'launch_failed=1';
        }

        // ===== 返回 App：按环境自动选择方式 =====
        function backToApp() {
            hideFeedback();

            // 1) uniapp App（5+ 引擎）：当前页由 <web-view> 承载，在其父 webview 中执行 uni.navigateBack
            if (hasPlus()) {
                try {
                    var wv = plus.webview.currentWebview();
                    var p = wv.parent();
                    if (p) {
                        if (typeof p.evalJS === 'function') {
                            p.evalJS('uni.navigateBack()');
                            return true;
                        }
                        // 兜底：关闭承载 web-view 的页面（避免误关启动根 webview）
                        var launch = (typeof plus.webview.getLaunchWebview === 'function') ? plus.webview.getLaunchWebview() : null;
                        if (p !== launch && typeof plus.webview.close === 'function') {
                            plus.webview.close(p);
                            return true;
                        }
                    }
                } catch (e) {}
            }

            // 2) 微信小程序 web-view
            if (isMiniProgram()) {
                try {
                    if (window.wx && window.wx.miniProgram && typeof window.wx.miniProgram.navigateBack === 'function') {
                        window.wx.miniProgram.navigateBack();
                        return true;
                    }
                } catch (e) {}
            }

            // 3) 微信 / QQ 内置浏览器：系统禁止唤起外部 App，直接给指引
            if (isWeChat()) { showFeedback('wechat'); return false; }
            if (isQQ()) { showFeedback('qq'); return false; }

            // 4) Android：用 intent 唤起（无弹窗选择；App 未安装时浏览器回落到本页）
            //    首次优先按包名（旧版本 App 未注册 scheme 也能唤起），重试时改用 scheme
            if (isAndroid()) {
                var useScheme = APP_SCHEME && (!APP_PACKAGE || launchAttempts % 2 === 1);
                var intent = '';
                if (useScheme) {
                    intent = 'intent://oauth#Intent;scheme=' + APP_SCHEME + ';'
                        + 'S.browser_fallback_url=' + encodeURIComponent(fallbackUrl()) + ';end';
                } else if (APP_PACKAGE) {
                    intent = 'intent://app#Intent;action=android.intent.action.MAIN;category=android.intent.category.LAUNCHER;'
                        + 'package=' + APP_PACKAGE + ';'
                        + 'S.browser_fallback_url=' + encodeURIComponent(fallbackUrl()) + ';end';
                }
                if (intent) {
                    launchAttempts++;
                    watchLaunch();
                    try {
                        window.location.href = intent;
                    } catch (e) {
                        showFeedback('fail');
                    }
                    return true;
                }
            }

            // 5) iOS（或 Android 未配置包名）：URL Scheme 唤起
            if (APP_SCHEME) {
                watchLaunch();
                try {
                    window.location.href = APP_SCHEME + '://';
                } catch (e) {
                    showFeedback('fail');
                }
                return true;
            }

            // 6) iOS 无 scheme / 无任何配置：无法唤起，直接反馈
            if (isIOS()) { showFeedback('ios'); return false; }
            showFeedback('noconfig');
            return false;
        }

        function retryLaunch() {
            backToApp();
        }

        // 明确需要下载时自动跳转官网下载页（地址来自系统动态配置）
        function autoGoDownload() {
            if (!DOWNLOAD_TARGET) return;
            var tipEl = document.getElementById('resultTip');
            // 官网下载页：给用户一个短暂提示后再跳转
            if (tipEl) tipEl.innerHTML = '未检测到 App，正在前往官网下载页…（若未跳转请点上方按钮）';
            setTimeout(function () {
                window.location.href = DOWNLOAD_TARGET;
            }, 1200);
        }

        window.backToApp = backToApp;
        window.retryLaunch = retryLaunch;

        // Android intent 回落（说明未安装 / 无法唤起 App）：给出反馈并自动前往官网下载页
        if (LAUNCH_FAILED) {
            showFeedback('fail');
            autoGoDownload();
        }
        // 环境明确无法唤起时，进入页面即给出指引，避免用户反复点击无反应
        else if (isWeChat()) showFeedback('wechat');
        else if (isQQ()) showFeedback('qq');
        else if (isIOS() && !APP_SCHEME && !hasPlus() && !isMiniProgram()) showFeedback('ios');
    })();
    </script>
</body>
</html>
