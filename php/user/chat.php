<?php
$title = 'AI 对话';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

// 后台「功能设置」中的「网页端 / App 端 AI 对话」开关（功能标识 web_ai_chat）：
// 后台尚未创建该标识时不限制（与移动端 feature.checkFeature 的默认值保持一致）；
// 标识存在且被禁用时给出「暂未开放」提示（文案取后台公告），并终止渲染对话界面。
$aiChatFeature = getFeatureSetting($DB, 'web_ai_chat');
if ($aiChatFeature && intval($aiChatFeature['is_enabled']) !== 1) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'web_ai_chat');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}
?>
    <!-- 页面加载遮罩：加载完成后淡出并移除 -->
    <div id="pageLoadingMask">
        <span class="plm-ring"></span>
        <span class="plm-text">加载中…</span>
    </div>
    <div id="pjax-container">
        <div class="content animated fadeIn">
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-robot me-2"></i>AI 助手
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-alt-secondary" id="clearBtn">
                            <i class="fa fa-trash-alt me-1"></i>清空对话
                        </button>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    <?php renderFeatureAnnouncement($DB, 'web_ai_chat'); ?>
                    <div class="aic-topic" id="chatTopic"></div>
                    <div class="aic-topic-sub" id="chatTopicSub">AI 生成可能有误，注意核实</div>
                    <div class="aic-body" id="chatList">
                    <div class="aic-welcome" id="welcomeBox">
                        <div class="aic-avatar"><i class="fa fa-robot"></i></div>
                        <div class="aic-welcome-text">
                            <div class="aic-welcome-title">你好，我是智能助手</div>
                            <div class="aic-welcome-sub">我可以帮助你解决使用中遇到的问题~</div>
                        </div>
                    </div>

                    <div class="aic-suggest" id="suggestWrap">
                        <div class="aic-suggest-label">💻 你可以这样问我：</div>
                        <button type="button" class="aic-chip" data-q="我最近的成绩怎么样？">📊 我最近的成绩怎么样？</button>
                        <button type="button" class="aic-chip" data-q="我的宿舍用电情况如何？">⚡ 我的宿舍用电情况如何？</button>
                        <button type="button" class="aic-chip" data-q="怎么查询我的课程安排？">📅 怎么查询我的课程安排？</button>
                        <button type="button" class="aic-chip" data-q="校园卡怎么充值？">💳 校园卡怎么充值？</button>
                    </div>
                </div>

                <div class="aic-input">
                    <div class="aic-input-box">
                        <textarea id="msgInput" rows="1" placeholder="按 Enter 发送，按 Shift + Enter 换行"></textarea>
                        <div class="aic-toolbar">
                            <div class="aic-toolbar-left">
                                <div class="aic-dropdown">
                                    <button type="button" class="aic-pill" id="modelBtn">
                                        <i class="fa fa-star"></i> <span class="pill-text">模型选择</span> <i class="fa fa-chevron-down aic-caret"></i>
                                    </button>
                                    <div class="aic-menu" id="modelMenu">
                                        <div class="aic-menu-item active">智能助手（默认）</div>
                                    </div>
                                </div>
                                <button type="button" class="aic-pill active" id="deepBtn">
                                    <i class="fa fa-lightbulb"></i> 深度思考
                                </button>
                            </div>
                            <div class="aic-toolbar-right">
                                <button type="button" class="aic-icon" id="attachBtn" title="附件"><i class="fa fa-paperclip"></i></button>
                                <button type="button" class="aic-icon" id="micBtn" title="语音输入"><i class="fa fa-microphone"></i></button>
                                <button type="button" class="aic-send" id="sendBtn" title="发送" disabled><i class="fa fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <style>
            /* 页面加载遮罩：仅覆盖内容区，不遮挡侧栏与顶栏 */
            #main-container { position: relative; }
            #pageLoadingMask { position: absolute; inset: 0; z-index: 5; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; background: #fff; transition: opacity .3s ease, visibility .3s ease; }
            #pageLoadingMask.plm-hide { opacity: 0; visibility: hidden; pointer-events: none; }
            .plm-ring { width: 40px; height: 40px; box-sizing: border-box; border: 3px solid #e5e7eb; border-top-color: #6366f1; border-radius: 50%; display: block; animation: plm-spin .8s linear infinite; }
            .plm-text { font-size: 13px; color: #9ca3af; }
            @keyframes plm-spin { to { transform: rotate(360deg); } }

            /* 本页不展示底部版权/备案页脚 */
            #page-footer { display: none !important; }

            /* 整页滚动条 */
            html { scrollbar-width: thin; scrollbar-color: #cbd0d8 transparent; }
            html::-webkit-scrollbar { width: 9px; height: 9px; }
            html::-webkit-scrollbar-track { background: transparent; }
            html::-webkit-scrollbar-thumb { background: #cbd0d8; border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
            html::-webkit-scrollbar-thumb:hover { background: #aeb4bf; }

            .aic-body { height: 60vh; min-height: 400px; overflow-y: auto; padding: 4px 2px 14px; scrollbar-width: none; -ms-overflow-style: none; }
            .aic-body::-webkit-scrollbar { width: 0; height: 0; display: none; }

            .aic-topic { display: none; text-align: center; font-size: 14px; font-weight: 600; color: #1f2937; padding: 0 16px 12px; margin-bottom: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .aic-topic-sub { display: none; text-align: center; font-size: 11px; color: #9ca3af; margin: -24px 0 10px; }
            .aic-welcome { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-radius: 16px; background: linear-gradient(90deg, #eef2ff 0%, #f3ecff 60%, #f7f0ff 100%); margin-bottom: 26px; }
            .aic-avatar { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; background: radial-gradient(circle at 30% 25%, #4f46e5, #1e1b4b 70%); box-shadow: 0 6px 16px rgba(79, 70, 229, .35); flex-shrink: 0; }
            .aic-welcome-title { font-size: 17px; font-weight: 700; color: #111827; }
            .aic-welcome-sub { font-size: 13px; color: #6b7280; margin-top: 2px; }

            .aic-suggest { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
            .aic-suggest-label { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 2px; }
            .aic-chip { background: #fff; border: 1px solid #e6e8ef; color: #374151; border-radius: 12px; padding: 10px 16px; font-size: 14px; cursor: pointer; transition: .15s; text-align: left; }
            .aic-chip:hover { border-color: #c7cbfa; background: #f7f8ff; color: #4338ca; transform: translateY(-1px); }

            .msg-row { display: flex; margin-bottom: 18px; align-items: flex-start; }
            .msg-row.user { justify-content: flex-end; }
            .msg-row.ai { justify-content: flex-start; gap: 10px; }
            .msg-col { display: flex; flex-direction: column; max-width: 82%; }
            .msg-row.user .msg-col { align-items: flex-end; }
            .ai-avatar-sm { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; background: radial-gradient(circle at 30% 25%, #4f46e5, #1e1b4b 70%); box-shadow: 0 4px 10px rgba(79, 70, 229, .3); }
            .ai-name { font-size: 12px; color: #9ca3af; margin-bottom: 6px; }
            .bubble { max-width: 100%; padding: 11px 15px; border-radius: 14px; line-height: 1.65; word-break: break-word; font-size: 14px; }
            .msg-row.user .bubble { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border-bottom-right-radius: 4px; }
            .msg-row.ai .bubble { background: #f6f7fb; color: #1f2937; border-bottom-left-radius: 4px; }
            .ai-typing { color: #9ca3af; }

            .aic-input { padding-top: 14px; border-top: 1px solid #f1f3f7; }
            .aic-input-box { border: 1px solid #e6e8ef; border-radius: 16px; padding: 12px 14px; transition: .15s; background: #fff; }
            .aic-input-box:focus-within { border-color: #c7cbfa; box-shadow: 0 0 0 3px rgba(99, 102, 241, .1); }
            .aic-input-box textarea { width: 100%; border: 0; outline: 0; resize: none; font-size: 14px; line-height: 1.6; max-height: 140px; background: transparent; color: #1f2937; scrollbar-width: none; -ms-overflow-style: none; }
            .aic-input-box textarea::-webkit-scrollbar { width: 0; height: 0; display: none; }
            .aic-toolbar { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; gap: 10px; flex-wrap: wrap; }
            .aic-toolbar-left, .aic-toolbar-right { display: flex; align-items: center; gap: 8px; }
            .aic-pill { display: inline-flex; align-items: center; gap: 6px; background: #f4f5f8; border: 0; color: #4b5563; border-radius: 999px; padding: 7px 14px; font-size: 13px; cursor: pointer; transition: .15s; white-space: nowrap; }
            .aic-pill:hover { background: #e9ebf1; }
            .aic-pill.active { background: #eef2ff; color: #4338ca; font-weight: 600; }
            .aic-pill > i { flex: 0 0 auto; }
            .pill-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
            .aic-caret { font-size: 10px; opacity: .7; }

            .aic-dropdown { position: relative; }
            .aic-menu { position: absolute; left: 0; bottom: calc(100% + 8px); min-width: 180px; background: #fff; border: 1px solid #eceef4; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); padding: 6px; display: none; z-index: 20; }
            .aic-menu.show { display: block; }
            .aic-menu-item { padding: 9px 12px; border-radius: 8px; font-size: 13px; color: #374151; cursor: pointer; }
            .aic-menu-item:hover { background: #f4f5f8; }
            .aic-menu-item.active { background: #eef2ff; color: #4338ca; font-weight: 600; }

            .aic-icon { width: 38px; height: 38px; border-radius: 50%; border: 0; background: #f4f5f8; color: #6b7280; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
            .aic-icon:hover { background: #e9ebf1; color: #4338ca; }
            .aic-send { position: relative; width: 42px; height: 42px; border-radius: 50%; border: 0; color: #fff; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 6px 16px rgba(99, 102, 241, .35); flex: 0 0 auto; }
            .aic-send:hover { filter: brightness(1.05); transform: translateY(-1px); }
            .aic-send:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
            /* 终止态：去掉投影，避免与外圈叠加显脏 */
            .aic-send.sending { box-shadow: none; }
            /* 终止态外圈：按钮边框上的一道旋转弧（体内不再画圈） */
            .aic-send.sending::after { content: ''; position: absolute; left: -5px; top: -5px; right: -5px; bottom: -5px; box-sizing: border-box; border-radius: 50%; border: 2px solid transparent; border-top-color: #6366f1; border-right-color: rgba(99, 102, 241, .5); border-bottom-color: rgba(99, 102, 241, .14); animation: aic-spin .85s linear infinite; pointer-events: none; }
            /* 终止图标：居中方块（约占按钮 1/3） */
            .aic-send .spin-sq { width: 13px; height: 13px; border-radius: 2.5px; background: #fff; }
            @keyframes aic-spin { to { transform: rotate(360deg); } }

            .markdown-body pre { background: #282c34; color: #abb2bf; padding: .75rem; border-radius: .25rem; overflow-x: auto; margin-bottom: .6rem; }
            .markdown-body code { background: #f1f3f5; padding: .1rem .3rem; border-radius: .2rem; font-size: .9em; }
            .markdown-body pre code { background: transparent; padding: 0; }
            .markdown-body table { display: block; width: 100%; overflow-x: auto; }

            /* 气泡底部操作按钮 */
            .msg-actions { display: flex; align-items: center; gap: 4px; margin-top: 6px; }
            .act-btn { width: 30px; height: 30px; border: 0; background: transparent; color: #9ca3af; border-radius: 8px; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; transition: .15s; }
            .act-btn:hover { background: #f1f3f7; color: #4b5563; }
            .act-btn.active { color: #4338ca; }
            .act-btn:disabled { opacity: .35; cursor: not-allowed; }
            .ver-indicator { font-size: 12px; color: #9ca3af; min-width: 28px; text-align: center; user-select: none; }

            /* 内联编辑卡片（浅色承载框） */
            .edit-card { width: 100%; }
            .msg-row.user .bubble.edit-bubble { background: #fff; color: #1f2937; border: 1.5px solid #c7cbfa; border-bottom-right-radius: 14px; box-shadow: 0 0 0 3px rgba(99, 102, 241, .08); }
            .edit-row { display: flex; align-items: flex-start; gap: 8px; }
            .edit-icon { color: #9ca3af; font-size: 13px; margin-top: 9px; }
            .edit-area { width: 100%; min-height: 40px; border: 0; padding: 6px 0 0; font-size: 14px; line-height: 1.6; resize: vertical; color: #1f2937; background: transparent; outline: 0; }
            .edit-actions { display: flex; gap: 8px; margin-top: 8px; justify-content: flex-end; }
            .edit-btn-cancel { background: #fff; border: 1px solid #e5e7eb; color: #4b5563; border-radius: 999px; padding: 5px 14px; font-size: 13px; cursor: pointer; transition: .15s; }
            .edit-btn-cancel:hover { background: #f4f5f8; }
            .edit-btn-save { background: #4f46e5; border: 0; color: #fff; border-radius: 999px; padding: 5px 16px; font-size: 13px; cursor: pointer; transition: .15s; }
            .edit-btn-save:hover { filter: brightness(1.05); }

            /* 已取消回答提示 */
            .canceled-tip { font-size: 12px; color: #9ca3af; margin-top: 4px; }
            @media (hover: none) { .msg-actions { display: flex; } }

            /* 思考过程框 */
            .think-box { border: 1px solid #ececf1; background: #fafafc; border-radius: 12px; margin-bottom: 8px; overflow: hidden; width: 100%; }
            .think-head { display: flex; align-items: center; gap: 8px; padding: 9px 14px; font-size: 13px; color: #6b7280; }
            .think-box[data-state="done"] .think-head { cursor: pointer; color: #4b5563; }
            .think-icon { color: #8b5cf6; }
            .think-box[data-state="thinking"] .think-icon { animation: thinkSpin 1.4s ease-in-out infinite; }
            .think-caret { font-size: 11px; opacity: .7; transition: transform .2s; margin-left: 2px; }
            .think-box.open .think-caret { transform: rotate(90deg); }
            .think-dots { display: inline-flex; gap: 3px; margin-left: 2px; }
            .think-dots i { width: 5px; height: 5px; border-radius: 50%; background: #a5b4fc; animation: thinkBlink 1.2s infinite ease-in-out; }
            .think-dots i:nth-child(2) { animation-delay: .2s; }
            .think-dots i:nth-child(3) { animation-delay: .4s; }
            .think-body { display: none; padding: 0 14px 10px 38px; font-size: 13px; color: #6b7280; }
            .think-box.open .think-body { display: block; }
            @keyframes thinkBlink { 0%, 80%, 100% { opacity: .25; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }
            @keyframes thinkSpin { 0%, 100% { transform: scale(1); opacity: .75; } 50% { transform: scale(1.18); opacity: 1; } }

            /* ===== 暗黑模式适配（OneUI 在 #page-container 上添加 .dark-mode） ===== */
            .dark-mode #pageLoadingMask { background: #19222d; }
            .dark-mode .plm-ring { border-color: #38455a; border-top-color: #a5b4fc; }
            .dark-mode .plm-text { color: #8e9bb0; }

            .dark-mode .aic-topic { color: #dde2e9; }
            .dark-mode .aic-topic-sub { color: #8e9bb0; }
            .dark-mode .aic-welcome { background: linear-gradient(90deg, #232f43 0%, #2b2a49 60%, #2c2542 100%); }
            .dark-mode .aic-welcome-title { color: #e9edf4; }
            .dark-mode .aic-welcome-sub { color: #9aa8bc; }
            .dark-mode .aic-suggest-label { color: #cbd5e1; }
            .dark-mode .aic-chip { background: #222e3c; border-color: #33445a; color: #cdd7e4; }
            .dark-mode .aic-chip:hover { background: #2a3849; border-color: #4c5f7a; color: #a5b4fc; }

            .dark-mode .msg-row.ai .bubble { background: #222e3c; color: #dde2e9; }
            .dark-mode .ai-name { color: #8e9bb0; }
            .dark-mode .think-box { border-color: #2f3e52; background: #1e2836; }
            .dark-mode .think-head { color: #9aa8bc; }
            .dark-mode .think-box[data-state="done"] .think-head { color: #c3cddb; }
            .dark-mode .think-body { color: #93a1b5; }
            .dark-mode .canceled-tip, .dark-mode .ver-indicator { color: #8e9bb0; }
            .dark-mode .act-btn { color: #8e9bb0; }
            .dark-mode .act-btn:hover { background: #2a3849; color: #cdd7e4; }

            .dark-mode .msg-row.user .bubble.edit-bubble { background: #222e3c; color: #e5e9f0; border-color: #4c5f7a; box-shadow: 0 0 0 3px rgba(129, 140, 248, .18); }
            .dark-mode .edit-area { color: #e5e9f0; }
            .dark-mode .edit-icon { color: #8e9bb0; }
            .dark-mode .edit-btn-cancel { background: #26313f; border-color: #3a4a60; color: #cdd7e4; }
            .dark-mode .edit-btn-cancel:hover { background: #2f3d4e; }

            .dark-mode .aic-input { border-top-color: #2a3849; }
            .dark-mode .aic-input-box { background: #222e3c; border-color: #33445a; }
            .dark-mode .aic-input-box:focus-within { border-color: #4c5f7a; box-shadow: 0 0 0 3px rgba(129, 140, 248, .18); }
            .dark-mode .aic-input-box textarea { color: #e5e9f0; }
            .dark-mode .aic-input-box textarea::placeholder { color: #7d8ba0; }

            .dark-mode .aic-pill { background: #26313f; color: #c3cddb; }
            .dark-mode .aic-pill:hover { background: #2f3d4e; }
            .dark-mode .aic-pill.active { background: #2c3350; color: #a5b4fc; }
            .dark-mode .aic-icon { background: #26313f; color: #9aa8bc; }
            .dark-mode .aic-icon:hover { background: #2f3d4e; color: #a5b4fc; }
            /* 深色模式下的终止外圈：用更亮的紫蓝保证可见 */
            .dark-mode .aic-send.sending::after { border-top-color: #a5b4fc; border-right-color: rgba(165, 180, 252, .5); border-bottom-color: rgba(165, 180, 252, .16); }

            .dark-mode .aic-menu { background: #222e3c; border-color: #33445a; box-shadow: 0 10px 30px rgba(0, 0, 0, .45); }
            .dark-mode .aic-menu-item { color: #cdd7e4; }
            .dark-mode .aic-menu-item:hover { background: #2a3849; }
            .dark-mode .aic-menu-item.active { background: #2c3350; color: #a5b4fc; }

            .dark-mode .markdown-body pre { background: #1b2430; color: #abb2bf; }
            .dark-mode .markdown-body code { background: #2a3849; color: #e685b5; }
            .dark-mode .markdown-body pre code { background: transparent; color: inherit; }

            @media (max-width: 575.98px) {
                .aic-body { height: 58vh; min-height: 320px; padding: 14px 10px; }
                .msg-col { max-width: 92%; }
                .aic-welcome { padding: 14px; gap: 10px; }
                .aic-avatar { width: 44px; height: 44px; font-size: 20px; border-radius: 14px; }
                .aic-input { padding-top: 10px; }
                .aic-input-box { padding: 10px 12px; border-radius: 14px; }
                /* 16px 可避免 iOS 聚焦输入框时自动放大页面 */
                .aic-input-box textarea { font-size: 16px; }
                .edit-area { font-size: 16px; }
                /* 移动端不展示顶部话题标题与“AI 生成可能有误”提示区域 */
                #chatTopic, #chatTopicSub { display: none !important; }
                .aic-toolbar { gap: 8px 6px; }
                .aic-toolbar-left { flex: 1 1 auto; gap: 6px; min-width: 0; }
                .aic-toolbar-right { flex: 0 0 auto; gap: 6px; margin-left: auto; }
                .aic-dropdown { min-width: 0; }
                #modelBtn { min-width: 0; max-width: 100%; }
                #deepBtn { flex: 0 0 auto; }
                .aic-pill { padding: 6px 11px; font-size: 12px; }
                .aic-icon { width: 34px; height: 34px; font-size: 13px; }
                .aic-send { width: 38px; height: 38px; }
            }
        </style>
        <script>
        // 页面加载完成前展示遮罩，加载成功后淡出移除
        (function () {
            function hideMask() {
                var el = document.getElementById('pageLoadingMask');
                if (!el || el.classList.contains('plm-hide')) return;
                el.classList.add('plm-hide');
                setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 320);
            }
            if (document.readyState === 'complete') {
                hideMask();
            } else {
                window.addEventListener('load', hideMask);
            }
            // 兜底：个别资源（如 CDN）阻塞 load 时，最多 8 秒后强制移除
            setTimeout(hideMask, 8000);
        })();

        $(function () {
            var apiBase = '../api.php?act=ai_chat';
            var messages = [{
                role: 'system',
                content: '你是“智慧校园”AI 助手，帮助学生解答校园生活、学习规划、教务查询（成绩/课表）、宿舍用电等方面的问题。回答简洁友好、条理清晰，可适当使用 Markdown。展示数据时使用友好的中文名称（如宿舍名称、“第X节/上午第1节/下午第2节”等），不要展示系统内部的编码或编号（如宿舍 ID、classTime 等）。'
            }];
            var sending = false;
            var activeCtrl = null;
            var models = [];
            var currentModel = '';

            function scrollBottom() {
                var el = $('#chatList');
                el.scrollTop(el[0].scrollHeight);
            }
            function esc(s) {
                return $('<div>').text(s).html();
            }
            function renderMd(s) {
                // 统一入口 mdToHtml（foot.php 提供）：修正紧跟段落的 --- 被当成上文标题下划线的问题
                if (window.mdToHtml) {
                    try {
                        return '<div class="markdown-body">' + window.mdToHtml(s) + '</div>';
                    } catch (e) {}
                }
                return '<div class="markdown-body">' + esc(s) + '</div>';
            }
            function updateSendState() {
                var hasText = $.trim($('#msgInput').val()) !== '';
                // 生成/思考中：按钮保留可点击（用于终止），其余情况按是否有内容决定禁用
                $('#sendBtn').prop('disabled', !sending && !hasText);
                if (sending) {
                    // 终止态：按钮内部只留方块，加载圆环由 .sending::after 在按钮外侧绘制
                    $('#sendBtn').addClass('sending').html('<span class="spin-sq"></span>').attr('title', '终止回答');
                } else {
                    $('#sendBtn').removeClass('sending').html('<i class="fa fa-paper-plane"></i>').attr('title', '发送');
                }
            }
            function autoGrow() {
                var el = $('#msgInput')[0];
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 140) + 'px';
            }
            function addUser(text) {
                var html = '<div class="msg-row user">'
                    + '<div class="msg-col">'
                    + '<div class="bubble">' + esc(text) + '</div>'
                    + '<div class="msg-actions">'
                    + '<button type="button" class="act-btn act-copy" title="复制"><i class="fa fa-copy"></i></button>'
                    + '<button type="button" class="act-btn act-edit" title="编辑"><i class="fa fa-pencil-alt"></i></button>'
                    + '<button type="button" class="act-btn act-speak" title="朗读"><i class="fa fa-volume-up"></i></button>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
                var $el = $(html).data('text', text);
                $('#chatList').append($el);
                scrollBottom();
                return $el;
            }
            function addAiPlaceholder() {
                var id = 'ai-' + Date.now();
                var html = '<div class="msg-row ai" id="' + id + '">'
                    + '<div class="ai-avatar-sm"><i class="fa fa-robot"></i></div>'
                    + '<div class="msg-col">'
                    + '<div class="ai-name">智能助手</div>'
                    + '<div class="think-box" data-state="thinking">'
                    + '<div class="think-head">'
                    + '<i class="fa fa-robot think-icon"></i>'
                    + '<span class="think-text">正在思考</span>'
                    + '<span class="think-dots"><i></i><i></i><i></i></span>'
                    + '</div>'
                    + '</div>'
                    + '<div class="bubble" style="display:none;"></div>'
                    + '<div class="msg-actions">'
                    + '<button type="button" class="act-btn act-prev" title="上一个回复" style="display:none;"><i class="fa fa-chevron-left"></i></button>'
                    + '<span class="ver-indicator" style="display:none;"></span>'
                    + '<button type="button" class="act-btn act-next" title="下一个回复" style="display:none;"><i class="fa fa-chevron-right"></i></button>'
                    + '<button type="button" class="act-btn act-regen" title="重新生成"><i class="fa fa-redo"></i></button>'
                    + '<button type="button" class="act-btn act-copy" title="复制"><i class="fa fa-copy"></i></button>'
                    + '<button type="button" class="act-btn act-speak" title="朗读"><i class="fa fa-volume-up"></i></button>'
                    + '<button type="button" class="act-btn act-stop" title="终止思考" style="display:none;"><i class="fa fa-stop-circle"></i></button>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
                var $el = $(html);
                $el.data('startTs', Date.now()).data('raw', '').data('versions', []).data('verIdx', -1);
                $('#chatList').append($el);
                scrollBottom();
                return id;
            }
            function setAi(id, html, raw) {
                var $row = $('#' + id);
                var $bubble = $row.find('.bubble');
                // 首个内容到达：结束“正在思考”动画，折叠为“思考已完成”
                if ($bubble.is(':hidden')) {
                    $bubble.show();
                    var $box = $row.find('.think-box');
                    var secs = Math.max(1, Math.round((Date.now() - ($row.data('startTs') || Date.now())) / 1000));
                    $box.attr('data-state', 'done');
                    $box.find('.think-dots').remove();
                    $box.find('.think-text').text('思考已完成');
                    $box.find('.think-head').append('<i class="fa fa-chevron-right think-caret"></i>');
                    $box.append('<div class="think-body">已完成对本问题的思考（用时 ' + secs + ' 秒）。</div>');
                }
                $bubble.html(html);
                if (typeof raw === 'string') {
                    $row.data('raw', raw);
                }
                scrollBottom();
            }

            // 生成中：仅展示“终止思考”，隐藏重新生成/复制/朗读
            function showGenActions($row) {
                $row.addClass('generating');
                $row.find('.act-stop').show();
                $row.find('.act-regen, .act-copy, .act-speak, .act-prev, .act-next, .ver-indicator').hide();
            }
            // 生成结束：隐藏“终止思考”，展示常规操作并按版本数刷新分页
            function showFinalActions($row) {
                $row.find('.act-stop').hide();
                $row.find('.act-regen, .act-copy, .act-speak').show();
                updatePager($row);
                $row.removeClass('generating');
            }

            // 版本分页：仅当存在多个版本时显示 ‹ x/y › 切换
            function updatePager($row) {
                var vers = $row.data('versions') || [];
                var idx = $row.data('verIdx') || 0;
                var multi = vers.length > 1;
                $row.find('.ver-indicator').toggle(multi).text((idx + 1) + '/' + vers.length);
                $row.find('.act-prev').toggle(multi).prop('disabled', idx <= 0);
                $row.find('.act-next').toggle(multi).prop('disabled', idx >= vers.length - 1);
            }
            function showVersion($row, idx) {
                var vers = $row.data('versions') || [];
                if (idx < 0 || idx >= vers.length) return;
                $row.data('verIdx', idx);
                var raw = vers[idx];
                $row.find('.bubble').show().html(renderMd(raw));
                $row.data('raw', raw);
                updatePager($row);
                scrollBottom();
            }
            // 重新生成前把气泡恢复为“正在思考”状态（保留版本历史）
            function resetRowForRegen($row) {
                $row.data('raw', '');
                var $bubble = $row.find('.bubble');
                $bubble.hide().empty();
                var $box = $row.find('.think-box');
                $box.attr('data-state', 'thinking');
                $box.find('.think-body').remove();
                $box.find('.think-caret').remove();
                $box.find('.think-text').text('正在思考');
                if ($box.find('.think-dots').length === 0) {
                    $box.find('.think-head').append('<span class="think-dots"><i></i><i></i><i></i></span>');
                }
                $row.find('.ver-indicator, .act-prev, .act-next').hide();
                $row.find('.canceled-tip').remove();
                $row.removeClass('canceled');
                scrollBottom();
            }

            // 发送请求并流式渲染到新的 AI 气泡（可传入已有气泡 id 用于重新生成并保留历史版本）
            function requestAi(reuseId) {
                var aiId = reuseId || addAiPlaceholder();
                var full = '';
                sending = true;
                updateSendState();

                var controller = new AbortController();
                activeCtrl = controller;
                var $row = $('#' + aiId);
                $row.data('ctrl', controller);
                showGenActions($row);

                var finished = false;
                function finish(skipPush) {
                    if (finished) return; // done 行与流结束会各触发一次，只处理一次
                    finished = true;
                    if (!skipPush && full) {
                        messages.push({ role: 'assistant', content: full });
                        var $r = $('#' + aiId);
                        var vers = $r.data('versions') || [];
                        vers.push(full);
                        $r.data('versions', vers).data('verIdx', vers.length - 1).data('raw', full);
                        updatePager($r);
                    }
                    showFinalActions($('#' + aiId));
                    sending = false;
                    activeCtrl = null;
                    updateSendState();
                }
                // fetch 阶段与流式读取阶段的中断/错误统一在此处理（终止思考在流式中途也可能抛出 AbortError）
                function onFail(err) {
                    if (err && err.name === 'AbortError') {
                        // 用户主动终止：保留已生成内容，并标注“已取消回答”
                        setAi(aiId, renderMd(full), full);
                        var $r = $('#' + aiId);
                        if ($r.find('.canceled-tip').length === 0) {
                            $r.addClass('canceled').find('.msg-col').append('<div class="canceled-tip">已取消回答</div>');
                        }
                        finish(true);
                        if (!full) {
                            // 没有任何输出：保留头像与名称基础框架，仅隐藏空的思考框与气泡
                            $r.find('.think-box, .bubble').hide();
                        }
                        return;
                    }
                    setAi(aiId, renderMd('网络错误，请稍后重试'));
                    finish(true);
                }

                fetch(apiBase, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'messages=' + encodeURIComponent(JSON.stringify(messages)) + (currentModel ? '&model=' + encodeURIComponent(currentModel) : ''),
                    signal: controller.signal
                }).then(function (res) {
                    if (!res.ok) {
                        setAi(aiId, renderMd('请求失败（HTTP ' + res.status + '）'));
                        finish(true);
                        return;
                    }
                    var reader = res.body.getReader();
                    var decoder = new TextDecoder('utf-8');
                    var buf = '';
                    function handleLine(line) {
                        line = line.trim();
                        if (!line) return;
                        var obj;
                        try { obj = JSON.parse(line); } catch (e) { return; }
                        if (obj.error) { setAi(aiId, renderMd('出错了：' + obj.error)); finish(true); return; }
                        if (obj.done) { finish(); return; }
                        if (typeof obj.content === 'string') { full += obj.content; setAi(aiId, renderMd(full), full); }
                    }
                    function pump() {
                        return reader.read().then(function (r) {
                            if (r.done) { finish(); return; }
                            buf += decoder.decode(r.value, { stream: true });
                            var parts = buf.split('\n');
                            buf = parts.pop();
                            for (var i = 0; i < parts.length; i++) handleLine(parts[i]);
                            return pump();
                        });
                    }
                    pump().catch(onFail);
                }).catch(onFail);
            }

            // 会话开始：隐藏欢迎区与推荐问题，并以首条提问作为会话主题
            function startSession(text) {
                $('#welcomeBox, #suggestWrap').hide();
                var $t = $('#chatTopic');
                if (!$t.is(':visible')) {
                    $t.text(text.length > 40 ? text.slice(0, 40) + '…' : text).show();
                    $('#chatTopicSub').show();
                }
            }

            function send() {
                if (sending) return;
                var text = $.trim($('#msgInput').val());
                if (!text) return;
                closeAllEdits();

                startSession(text);
                addUser(text);
                messages.push({ role: 'user', content: text });
                $('#msgInput').val('');
                autoGrow();
                requestAi();
            }

            updateSendState();
            autoGrow();

            $('#sendBtn').on('click', function () {
                // 生成/思考中点击转圈按钮即终止当前对话；否则发送
                if (sending) {
                    if (activeCtrl) { activeCtrl.abort(); }
                } else {
                    send();
                }
            });
            $('#msgInput').on('input', function () {
                autoGrow();
                updateSendState();
            }).on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
            });

            // 推荐问题：点击后填入并发送
            $(document).on('click', '.aic-chip', function () {
                if (sending) return;
                $('#msgInput').val($(this).data('q')).trigger('input');
                send();
            });

            // 模型选择下拉：动态加载提供方实际支持的模型列表
            function setModel(m) {
                currentModel = m;
                $('#modelMenu .aic-menu-item').removeClass('active');
                $('#modelMenu .aic-menu-item').filter(function () { return $(this).text() === m; }).addClass('active');
                $('#modelBtn').html('<i class="fa fa-star"></i> <span class="pill-text">' + esc(m) + '</span> <i class="fa fa-chevron-down aic-caret"></i>');
            }
            function renderModels(payload) {
                var list = (payload && payload.models) || [];
                var def = (payload && payload.default) || '';
                models = list.slice();
                // 确保后台默认模型出现在列表中并优先选中
                if (def && models.indexOf(def) === -1) {
                    models.unshift(def);
                }
                var $menu = $('#modelMenu');
                $menu.empty();
                if (!models.length) {
                    $menu.append('<div class="aic-menu-item active">默认模型</div>');
                    currentModel = '';
                    $('#modelBtn').html('<i class="fa fa-star"></i> <span class="pill-text">默认模型</span> <i class="fa fa-chevron-down aic-caret"></i>');
                    return;
                }
                models.forEach(function (m) {
                    $menu.append('<div class="aic-menu-item' + (m === def ? ' active' : '') + '">' + esc(m) + '</div>');
                });
                setModel((def && models.indexOf(def) !== -1) ? def : models[0]);
            }
            $('#modelBtn').on('click', function (e) {
                e.stopPropagation();
                $('#modelMenu').toggleClass('show');
            });
            $('#modelMenu').on('click', '.aic-menu-item', function () {
                setModel($(this).text());
                $('#modelMenu').removeClass('show');
            });
            $(document).on('click', function () { $('#modelMenu').removeClass('show'); });

            // 拉取模型列表（失败则回退为默认）
            (function loadModels() {
                fetch('../api.php?act=ai_models', { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d && d.code === 0 && d.data && d.data.models && d.data.models.length) {
                            renderModels(d.data);
                        } else {
                            renderModels([]);
                        }
                    })
                    .catch(function () { renderModels([]); });
            })();

            // 深度思考开关（UI 占位）
            $('#deepBtn').on('click', function () { $(this).toggleClass('active'); });

            // ===== 气泡操作按钮 =====
            function copyText(text) {
                if (!text) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () { layer.msg('已复制'); }, function () { layer.msg('复制失败'); });
                } else {
                    var $t = $('<textarea>').val(text).appendTo('body').css({ position: 'fixed', opacity: 0 });
                    $t[0].select();
                    try { document.execCommand('copy'); layer.msg('已复制'); } catch (e) { layer.msg('复制失败'); }
                    $t.remove();
                }
            }
            function rowText($row) {
                return $row.hasClass('user') ? ($row.data('text') || '') : ($row.data('raw') || $row.find('.bubble').text());
            }
            // 依据 DOM 重建对话历史，保证编辑后上下文一致
            function rebuildMessages() {
                var rebuilt = [messages[0]];
                $('#chatList .msg-row').each(function () {
                    var $r = $(this);
                    if ($r.hasClass('user')) {
                        rebuilt.push({ role: 'user', content: $r.data('text') || '' });
                    } else {
                        if ($r.hasClass('canceled')) return; // 已取消的回答不计入上下文，仍可重新编辑/发送后续
                        var raw = $r.data('raw') || '';
                        if (raw) rebuilt.push({ role: 'assistant', content: raw });
                    }
                });
                messages = rebuilt;
            }

            // 复制
            $(document).on('click', '.act-copy', function () {
                closeAllEdits();
                copyText(rowText($(this).closest('.msg-row')));
            });

            // 关闭所有正在编辑的卡片（恢复原内容），用于用户进行其他操作时自动收起
            function closeAllEdits() {
                $('#chatList .msg-row.editing').each(function () {
                    var $r = $(this);
                    $r.removeClass('editing').find('.bubble').removeClass('edit-bubble').html(esc($r.data('origText') || ''));
                });
            }

            // 编辑：在原位置展示编辑卡片，而不是直接移除对话内容
            $(document).on('click', '.act-edit', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                if ($row.hasClass('editing')) return;
                var text = $row.data('text') || '';
                var $b = $row.find('.bubble');
                $row.addClass('editing').data('origText', text);
                $b.addClass('edit-bubble');
                $b.data('origHtml', $b.html()).html(
                    '<div class="edit-card">'
                    + '<div class="edit-row">'
                    + '<i class="fa fa-pencil-alt edit-icon"></i>'
                    + '<textarea class="edit-area" rows="1">' + esc(text) + '</textarea>'
                    + '</div>'
                    + '<div class="edit-actions">'
                    + '<button type="button" class="edit-btn-cancel edit-cancel">取消</button>'
                    + '<button type="button" class="edit-btn-save edit-save">发送</button>'
                    + '</div></div>'
                );
                var $ta = $b.find('.edit-area');
                $ta.focus().val(text);
                var el = $ta[0];
                if (el && el.setSelectionRange) el.setSelectionRange(text.length, text.length);
            });

            // 保存编辑：更新该条用户消息，并重新生成其后的 AI 回复
            $(document).on('click', '.edit-save', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                var newText = $.trim($row.find('.edit-area').val());
                if (!newText) { layer.msg('内容不能为空'); return; }
                $row.removeClass('editing').find('.bubble').removeClass('edit-bubble').html(esc(newText));
                $row.data('text', newText);
                $row.nextAll('.msg-row').remove();
                rebuildMessages();
                requestAi();
            });

            // 取消编辑：恢复原内容
            $(document).on('click', '.edit-cancel', function () {
                var $row = $(this).closest('.msg-row');
                $row.removeClass('editing').find('.bubble').removeClass('edit-bubble').html(esc($row.data('origText') || ''));
            });

            // 终止思考：中断当前流式请求，保留已生成内容
            $(document).on('click', '.act-stop', function () {
                if (!sending) return;
                var $row = $(this).closest('.msg-row');
                var ctrl = $row.data('ctrl');
                if (ctrl && ctrl.abort) ctrl.abort();
            });

            // 重新生成：复用当前气泡，旧回复保留为可切换的历史版本
            $(document).on('click', '.act-regen', function () {
                if (sending) return;
                closeAllEdits();
                var $row = $(this).closest('.msg-row');
                $row.nextAll('.msg-row').remove();
                if (messages.length && messages[messages.length - 1].role === 'assistant') messages.pop();
                resetRowForRegen($row);
                requestAi($row.attr('id'));
            });

            // 切换查看历史版本回复
            $(document).on('click', '.act-prev', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                showVersion($row, ($row.data('verIdx') || 0) - 1);
            });
            $(document).on('click', '.act-next', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                showVersion($row, ($row.data('verIdx') || 0) + 1);
            });

            // 朗读
            $(document).on('click', '.act-speak', function () {
                if (!('speechSynthesis' in window)) { layer.msg('当前浏览器不支持朗读'); return; }
                if (window.speechSynthesis.speaking) { window.speechSynthesis.cancel(); return; }
                var u = new SpeechSynthesisUtterance(rowText($(this).closest('.msg-row')));
                u.lang = 'zh-CN';
                window.speechSynthesis.speak(u);
            });

            // 展开/收起思考过程
            $(document).on('click', '.think-box[data-state="done"] .think-head', function () {
                $(this).closest('.think-box').toggleClass('open');
            });

            // 附件 / 语音：暂未开放
            $('#attachBtn, #micBtn').on('click', function () {
                layer.msg('该功能暂未开放');
            });

            $('#clearBtn').on('click', function () {
                if (sending) return;
                messages = [messages[0]];
                $('#chatList .msg-row').remove();
                $('#chatTopic, #chatTopicSub').hide();
                $('#welcomeBox, #suggestWrap').show();
                $('#msgInput').val('');
                autoGrow();
                updateSendState();
            });
        });
        </script>
<?php include('./foot.php'); ?>
