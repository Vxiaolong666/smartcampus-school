<?php
$title = 'AI 对话';
$requireLogin = true;
include('./head.php');
?>
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
                    <div class="aic-body" id="chatList">
                    <div class="aic-topic" id="chatTopic"></div>
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
                                        <i class="fa fa-star"></i> 模型选择 <i class="fa fa-chevron-down aic-caret"></i>
                                    </button>
                                    <div class="aic-menu" id="modelMenu">
                                        <div class="aic-menu-item active">智能助手（默认）</div>
                                    </div>
                                </div>
                                <button type="button" class="aic-pill" id="deepBtn">
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
            /* 整页滚动条 */
            html { scrollbar-width: thin; scrollbar-color: #cbd0d8 transparent; }
            html::-webkit-scrollbar { width: 9px; height: 9px; }
            html::-webkit-scrollbar-track { background: transparent; }
            html::-webkit-scrollbar-thumb { background: #cbd0d8; border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
            html::-webkit-scrollbar-thumb:hover { background: #aeb4bf; }

            .aic-body { height: 60vh; min-height: 400px; overflow-y: auto; padding: 4px 2px 14px; scrollbar-width: thin; scrollbar-color: #cbd0d8 transparent; }
            .aic-body::-webkit-scrollbar { width: 8px; }
            .aic-body::-webkit-scrollbar-track { background: transparent; }
            .aic-body::-webkit-scrollbar-thumb { background: #cbd0d8; border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
            .aic-body::-webkit-scrollbar-thumb:hover { background: #aeb4bf; }

            .aic-topic { display: none; text-align: center; font-size: 14px; font-weight: 600; color: #1f2937; padding: 0 16px 12px; margin-bottom: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
            .aic-input-box textarea { width: 100%; border: 0; outline: 0; resize: none; font-size: 14px; line-height: 1.6; max-height: 140px; background: transparent; color: #1f2937; }
            .aic-toolbar { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; gap: 10px; flex-wrap: wrap; }
            .aic-toolbar-left, .aic-toolbar-right { display: flex; align-items: center; gap: 8px; }
            .aic-pill { display: inline-flex; align-items: center; gap: 6px; background: #f4f5f8; border: 0; color: #4b5563; border-radius: 999px; padding: 7px 14px; font-size: 13px; cursor: pointer; transition: .15s; }
            .aic-pill:hover { background: #e9ebf1; }
            .aic-pill.active { background: #eef2ff; color: #4338ca; font-weight: 600; }
            .aic-caret { font-size: 10px; opacity: .7; }

            .aic-dropdown { position: relative; }
            .aic-menu { position: absolute; left: 0; bottom: calc(100% + 8px); min-width: 180px; background: #fff; border: 1px solid #eceef4; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); padding: 6px; display: none; z-index: 20; }
            .aic-menu.show { display: block; }
            .aic-menu-item { padding: 9px 12px; border-radius: 8px; font-size: 13px; color: #374151; cursor: pointer; }
            .aic-menu-item:hover { background: #f4f5f8; }
            .aic-menu-item.active { background: #eef2ff; color: #4338ca; font-weight: 600; }

            .aic-icon { width: 38px; height: 38px; border-radius: 50%; border: 0; background: #f4f5f8; color: #6b7280; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; justify-content: center; }
            .aic-icon:hover { background: #e9ebf1; color: #4338ca; }
            .aic-send { width: 42px; height: 42px; border-radius: 50%; border: 0; color: #fff; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 6px 16px rgba(99, 102, 241, .35); }
            .aic-send:hover { filter: brightness(1.05); transform: translateY(-1px); }
            .aic-send:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }

            .markdown-body pre { background: #282c34; color: #abb2bf; padding: .75rem; border-radius: .25rem; overflow-x: auto; margin-bottom: .6rem; }
            .markdown-body code { background: #f1f3f5; padding: .1rem .3rem; border-radius: .2rem; font-size: .9em; }
            .markdown-body pre code { background: transparent; padding: 0; }
            .markdown-body table { display: block; width: 100%; overflow-x: auto; }

            /* 气泡底部操作按钮 */
            .msg-actions { display: flex; align-items: center; gap: 4px; margin-top: 6px; opacity: 0; transition: opacity .15s; }
            .msg-row:hover .msg-actions { opacity: 1; }
            .act-btn { width: 30px; height: 30px; border: 0; background: transparent; color: #9ca3af; border-radius: 8px; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; transition: .15s; }
            .act-btn:hover { background: #f1f3f7; color: #4b5563; }
            .act-btn.active { color: #4338ca; }
            @media (hover: none) { .msg-actions { opacity: 1; } }

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

            @media (max-width: 575.98px) {
                .aic-body { height: 60vh; padding: 16px 12px; }
                .msg-col { max-width: 90%; }
                .aic-welcome { padding: 16px; gap: 12px; }
                .aic-avatar { width: 46px; height: 46px; font-size: 20px; }
                .aic-input { padding-top: 12px; }
                .aic-toolbar-left { gap: 6px; }
            }
        </style>
        <script>
        $(function () {
            var apiBase = '../api.php?act=ai_chat';
            var messages = [{
                role: 'system',
                content: '你是“智慧校园”AI 助手，帮助学生解答校园生活、学习规划、教务查询、宿舍用电等方面的问题。回答简洁友好、条理清晰，可适当使用 Markdown。'
            }];
            var sending = false;

            function scrollBottom() {
                var el = $('#chatList');
                el.scrollTop(el[0].scrollHeight);
            }
            function esc(s) {
                return $('<div>').text(s).html();
            }
            function renderMd(s) {
                if (window.marked) {
                    try {
                        return '<div class="markdown-body">' + marked.parse(s) + '</div>';
                    } catch (e) {}
                }
                return '<div class="markdown-body">' + esc(s) + '</div>';
            }
            function updateSendState() {
                var hasText = $.trim($('#msgInput').val()) !== '';
                $('#sendBtn').prop('disabled', sending || !hasText);
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
                    + '<i class="fa fa-brain think-icon"></i>'
                    + '<span class="think-text">正在思考</span>'
                    + '<span class="think-dots"><i></i><i></i><i></i></span>'
                    + '</div>'
                    + '</div>'
                    + '<div class="bubble" style="display:none;"></div>'
                    + '<div class="msg-actions" style="display:none;">'
                    + '<button type="button" class="act-btn act-regen" title="重新生成"><i class="fa fa-redo"></i></button>'
                    + '<button type="button" class="act-btn act-copy" title="复制"><i class="fa fa-copy"></i></button>'
                    + '<button type="button" class="act-btn act-speak" title="朗读"><i class="fa fa-volume-up"></i></button>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
                var $el = $(html);
                $el.data('startTs', Date.now()).data('raw', '');
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
                $row.find('.msg-actions').show();
                scrollBottom();
            }

            // 发送请求并流式渲染到新的 AI 气泡
            function requestAi() {
                var aiId = addAiPlaceholder();
                var full = '';
                sending = true;
                updateSendState();

                fetch(apiBase, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'messages=' + encodeURIComponent(JSON.stringify(messages))
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
                    function finish(skipPush) {
                        if (!skipPush && full) messages.push({ role: 'assistant', content: full });
                        sending = false;
                        updateSendState();
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
                    pump();
                }).catch(function () {
                    setAi(aiId, renderMd('网络错误，请稍后重试'));
                    finish(true);
                });
            }

            // 会话开始：隐藏欢迎区与推荐问题，并以首条提问作为会话主题
            function startSession(text) {
                $('#welcomeBox, #suggestWrap').hide();
                var $t = $('#chatTopic');
                if (!$t.is(':visible')) {
                    $t.text(text.length > 40 ? text.slice(0, 40) + '…' : text).show();
                }
            }

            function send() {
                if (sending) return;
                var text = $.trim($('#msgInput').val());
                if (!text) return;

                startSession(text);
                addUser(text);
                messages.push({ role: 'user', content: text });
                $('#msgInput').val('');
                autoGrow();
                requestAi();
            }

            updateSendState();
            autoGrow();

            $('#sendBtn').on('click', send);
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

            // 模型选择下拉（当前仅默认模型，UI 占位）
            $('#modelBtn').on('click', function (e) {
                e.stopPropagation();
                $('#modelMenu').toggleClass('show');
            });
            $('#modelMenu').on('click', '.aic-menu-item', function () {
                $('#modelMenu .aic-menu-item').removeClass('active');
                $(this).addClass('active');
                $('#modelMenu').removeClass('show');
            });
            $(document).on('click', function () { $('#modelMenu').removeClass('show'); });

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
                        var raw = $r.data('raw') || '';
                        if (raw) rebuilt.push({ role: 'assistant', content: raw });
                    }
                });
                messages = rebuilt;
            }

            // 复制
            $(document).on('click', '.act-copy', function () {
                copyText(rowText($(this).closest('.msg-row')));
            });

            // 编辑：回填输入框并清除该消息及其后内容，供重新发送
            $(document).on('click', '.act-edit', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                var text = $row.data('text') || '';
                $row.nextAll('.msg-row').remove();
                $row.remove();
                rebuildMessages();
                if ($('#chatList .msg-row').length === 0) {
                    $('#chatTopic').hide();
                    $('#welcomeBox, #suggestWrap').show();
                }
                $('#msgInput').val(text).trigger('input').focus();
            });

            // 重新生成
            $(document).on('click', '.act-regen', function () {
                if (sending) return;
                var $row = $(this).closest('.msg-row');
                $row.nextAll('.msg-row').remove();
                $row.remove();
                if (messages.length && messages[messages.length - 1].role === 'assistant') messages.pop();
                requestAi();
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
                $('#chatTopic').hide();
                $('#welcomeBox, #suggestWrap').show();
                $('#msgInput').val('');
                autoGrow();
                updateSendState();
            });
        });
        </script>
<?php include('./foot.php'); ?>
