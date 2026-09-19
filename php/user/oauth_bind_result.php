<?php
/**
 * 第三方账号绑定结果页（供 App / 小程序 web-view 展示）
 *
 * 授权完成后由 oauth_callback.php 跳转至此页，App 端用户看到结果后返回 App 即可。
 */
$notLogin = true;
include('../Common/Core_brain.php');

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
        }
        .result-btn:active { opacity: .9; }
        .result-tip {
            margin-top: 16px;
            font-size: 12px;
            color: #948eb3;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="result-card">
        <div class="result-icon <?php echo $ok ? 'ok' : 'fail'; ?>"><?php echo $ok ? '&#10003;' : '!'; ?></div>
        <div class="result-title"><?php echo $ok ? htmlspecialchars($okTitle) : htmlspecialchars($failTitle); ?></div>
        <div class="result-msg"><?php echo htmlspecialchars($msg); ?></div>
        <button class="result-btn" onclick="backToApp()">返回 App</button>
        <div class="result-tip">如未自动返回，请手动关闭本页回到 App</div>
    </div>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
        // 返回 App：根据运行环境选择最合适的返回方式
        // 1) uniapp App（HTML5+ 引擎）：通过父 webview 执行 uni.navigateBack 关闭 web-view 页
        // 2) 微信小程序 web-view：wx.miniProgram.navigateBack
        // 3) 普通浏览器 / H5：关闭窗口或后退
        function backToApp() {
            // uniapp App 平台（5+ 引擎）：当前页面由 <web-view> 组件以子 webview 形式承载，
            // 在其父 webview（即 uniapp 页面）中执行 uni.navigateBack 即可关闭 web-view 并返回上一页
            if (typeof plus !== 'undefined' && plus.webview && plus.webview.currentWebview) {
                try {
                    var wv = plus.webview.currentWebview();
                    var p = wv.parent();
                    if (p) {
                        if (typeof p.evalJS === 'function') {
                            p.evalJS('uni.navigateBack()');
                            return;
                        }
                        // 兜底：直接关闭承载 web-view 的 uniapp 页面（避免误关启动根 webview）
                        var launch = (typeof plus.webview.getLaunchWebview === 'function') ? plus.webview.getLaunchWebview() : null;
                        if (p !== launch && typeof plus.webview.close === 'function') {
                            plus.webview.close(p);
                            return;
                        }
                    }
                } catch (e) {}
            }
            // 微信小程序 web-view 环境
            try {
                if (window.wx && window.wx.miniProgram && typeof window.wx.miniProgram.navigateBack === 'function') {
                    window.wx.miniProgram.navigateBack();
                    return;
                }
            } catch (e) {}
            // 普通浏览器 / H5
            try {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }
            } catch (e) {}
            try { window.close(); } catch (e) {}
        }
    </script>
</body>
</html>
