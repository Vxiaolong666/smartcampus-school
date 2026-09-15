<?php
$title = 'AI 对话';
$requireLogin = true;
include('./head.php');
?>
    <div id="pjax-container" class="content animated fadeIn">
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
            <div class="block-content">
                <div class="ai-chat">
                    <div class="ai-chat-list" id="chatList">
                        <div class="msg-row ai">
                            <div class="bubble">你好！我是智慧校园 AI 助手 🤖 可以帮你解答课程安排、成绩分析、宿舍用电、校园生活等问题，试着问我吧～</div>
                        </div>
                    </div>
                    <div class="ai-chat-input">
                        <textarea id="msgInput" class="form-control" rows="2" placeholder="输入你的问题，Enter 发送，Shift+Enter 换行"></textarea>
                        <button id="sendBtn" class="btn btn-primary">
                            <i class="fa fa-paper-plane me-1"></i>发送
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <style>
            .ai-chat { display: flex; flex-direction: column; height: 65vh; min-height: 420px; }
            .ai-chat-list { flex: 1; overflow-y: auto; padding: 12px; background: #f8f9fa; border-radius: 8px; }
            .msg-row { display: flex; margin-bottom: 14px; }
            .msg-row.user { justify-content: flex-end; }
            .msg-row.ai { justify-content: flex-start; }
            .bubble {
                max-width: 76%;
                padding: 10px 14px;
                border-radius: 14px;
                line-height: 1.6;
                word-break: break-word;
                font-size: 14px;
            }
            .msg-row.user .bubble { background: #1296db; color: #fff; border-top-right-radius: 4px; }
            .msg-row.ai .bubble { background: #fff; border: 1px solid #e9ecef; border-top-left-radius: 4px; }
            .ai-typing { color: #999; }
            .ai-chat-input { display: flex; gap: 10px; padding-top: 12px; align-items: flex-end; }
            .ai-chat-input textarea { flex: 1; }
            .markdown-body pre {
                background: #282c34; color: #abb2bf; padding: .75rem;
                border-radius: .25rem; overflow-x: auto; margin-bottom: .6rem;
            }
            .markdown-body code { background: #f1f3f5; padding: .1rem .3rem; border-radius: .2rem; font-size: .9em; }
            .markdown-body pre code { background: transparent; padding: 0; }
            .markdown-body table { display: block; width: 100%; overflow-x: auto; }
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
            function addUser(text) {
                $('#chatList').append('<div class="msg-row user"><div class="bubble">' + esc(text) + '</div></div>');
                scrollBottom();
            }
            function addAiPlaceholder() {
                var id = 'ai-' + Date.now();
                $('#chatList').append('<div class="msg-row ai" id="' + id + '"><div class="bubble"><span class="ai-typing">正在思考…</span></div></div>');
                scrollBottom();
                return id;
            }
            function setAi(id, html) {
                $('#' + id + ' .bubble').html(html);
                scrollBottom();
            }

            function send() {
                if (sending) return;
                var text = $('#msgInput').val().trim();
                if (!text) return;

                addUser(text);
                messages.push({ role: 'user', content: text });
                $('#msgInput').val('');
                var aiId = addAiPlaceholder();
                var full = '';
                sending = true;

                fetch(apiBase, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'messages=' + encodeURIComponent(JSON.stringify(messages))
                }).then(function (res) {
                    if (!res.ok) {
                        setAi(aiId, renderMd('请求失败（HTTP ' + res.status + '）'));
                        sending = false;
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
                        if (typeof obj.content === 'string') { full += obj.content; setAi(aiId, renderMd(full)); }
                    }
                    function finish(skipPush) {
                        if (!skipPush && full) messages.push({ role: 'assistant', content: full });
                        sending = false;
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
                    sending = false;
                });
            }

            $('#sendBtn').on('click', send);
            $('#msgInput').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
            });
            $('#clearBtn').on('click', function () {
                if (sending) return;
                messages = [messages[0]];
                $('#chatList').html('<div class="msg-row ai"><div class="bubble">对话已清空，有什么可以帮你的？</div></div>');
            });
        });
        </script>
    </div>
<?php include('./foot.php'); ?>
