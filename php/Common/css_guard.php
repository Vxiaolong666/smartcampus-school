<?php
/**
 * 样式加载守卫：主 CSS 未就绪时隐藏页面内容，避免无样式页面闪现。
 * 加载失败自动重试一次；仍失败则显示明确提示，而不是展示错乱的裸页面。
 * 在 <head> 中、id="css-main" 的样式表之后引入本文件。
 */
?>
<style>
html.css-loading body, html.css-failed body { visibility: hidden !important; }
.css-guard-tip {
    visibility: visible !important;
    position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483647;
    display: flex; align-items: center; justify-content: center;
    box-sizing: border-box; padding: 24px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9eef5 100%);
    color: #303548;
    font: 14px/1.6 -apple-system, BlinkMacSystemFont, "Microsoft YaHei", sans-serif;
}
.css-guard-card {
    width: 100%; max-width: 420px; padding: 36px 40px;
    border: 1px solid #e7ebf2; border-radius: 16px; text-align: center;
    background: #fff; box-shadow: 0 12px 40px rgba(30, 50, 90, 0.12);
}
.css-guard-icon {
    width: 56px; height: 56px; margin: 0 auto 16px;
    border-radius: 50%; background: #fff2f0; color: #e34d59;
    font-size: 30px; font-weight: 700; line-height: 56px;
}
.css-guard-title { margin-bottom: 8px; font-size: 20px; font-weight: 600; color: #1f2740; }
.css-guard-desc { margin-bottom: 22px; color: #6b7488; }
.css-guard-btn {
    padding: 10px 28px; border: 0; border-radius: 8px; cursor: pointer;
    font-size: 14px; font-weight: 600; color: #fff;
    background: linear-gradient(135deg, #409eff, #337ecc);
    box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
}
.css-guard-btn:hover { filter: brightness(1.06); }
.css-guard-btn:active { transform: translateY(1px); }
</style>
<script>
(function () {
    var d = document.documentElement;
    var main = document.getElementById('css-main');
    if (!main) return;
    d.className += ' css-loading';
    var retried = false;
    var timer;
    function removeTip() {
        var tip = document.getElementById('css-guard-tip');
        if (tip && tip.parentNode) tip.parentNode.removeChild(tip);
    }
    function ready() {
        clearTimeout(timer);
        removeTip();
        d.className = d.className.replace(/\bcss-loading\b|\bcss-failed\b/g, '');
    }
    function showTip() {
        if (document.getElementById('css-guard-tip')) return;
        clearTimeout(timer);
        d.className = d.className.replace(/\bcss-loading\b/g, '') + ' css-failed';
        var tip = document.createElement('div');
        tip.id = 'css-guard-tip';
        tip.className = 'css-guard-tip';
        tip.innerHTML = '<div class="css-guard-card">'
            + '<div class="css-guard-icon">!</div>'
            + '<div class="css-guard-title">页面样式加载失败</div>'
            + '<div class="css-guard-desc">请检查网络连接后重试，若持续失败请稍后再访问。</div>'
            + '<button type="button" class="css-guard-btn" onclick="location.reload()">刷新重试</button>'
            + '</div>';
        (document.body || document.documentElement).appendChild(tip);
    }
    function failed() {
        if (retried) { showTip(); return; }
        retried = true;
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = main.href.split('?')[0] + '?retry=' + Date.now();
        link.onload = ready;
        link.onerror = showTip;
        document.head.appendChild(link);
        timer = setTimeout(showTip, 6000);
    }
    main.onload = ready;
    main.onerror = failed;
    if (main.sheet) { ready(); } else { timer = setTimeout(failed, 6000); }
})();
</script>
