</main>
    <footer id="page-footer">
        <div class="content py-3">
            <div class="row fs-sm">
                <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                    <a class="fw-semibold" href="https://beian.miit.gov.cn/" target="_blank"><?php echo conf('ICP');?></a>
                </div>
                <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                    &copy; <span data-toggle="year-copy"></span> <a class="fw-semibold" href="//<?php echo conf('Url');?>" target="_blank"><?php echo conf('Name');?></a>
                </div>
            </div>
        </div>
    </footer>
</div>

<script src="./assets/admin/js/time.js"></script>

<style>
/* ===== 右侧浮动 AI 分析按钮（与 uniapp 端统一样式） ===== */
.ai-fab {
    position: fixed !important;
    right: 26px !important;
    bottom: 140px !important;
    top: auto !important;
    left: auto !important;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #13c2c2, #1890ff);
    box-shadow: 0 8px 20px rgba(19, 194, 194, 0.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1030;
    cursor: pointer;
    color: #fff;
    text-align: center;
    line-height: 1.05;
    animation: aiFabPulse 2.4s ease-out infinite;
    user-select: none;
}
.ai-fab:hover { text-decoration: none; color: #fff; }
.ai-fab-icon { font-size: 16px; font-weight: bold; }
.ai-fab-label { font-size: 11px; opacity: 0.92; margin-top: 1px; }
.ai-fab:active { transform: scale(0.92); }
.ai-fab.ai-loading { opacity: 0.75; }
@keyframes aiFabPulse {
    0%   { box-shadow: 0 8px 20px rgba(19,194,194,0.45), 0 0 0 0 rgba(19,194,194,0.4); }
    70%  { box-shadow: 0 8px 20px rgba(19,194,194,0.45), 0 0 0 16px rgba(19,194,194,0); }
    100% { box-shadow: 0 8px 20px rgba(19,194,194,0.45), 0 0 0 0 rgba(19,194,194,0); }
}
@media (max-width: 768px) {
    .ai-fab { right: 18px !important; bottom: 120px !important; width: 56px !important; height: 56px !important; }
}
</style>

<script>
/* ===== Markdown → HTML 统一入口（公告 / 协议 / AI 对话 / AI 分析共用）=====
   修复 marked v4 的已知偏差：紧跟在段落、引用、列表后的 `---`（或 *** / ___）
   会被当成"上文的下划线式标题"，于是它上方的内容（连同 # / > 等原始符号）
   被整体吞进一个 <h2>，页面上看起来就像"Markdown 没渲染"。
   本项目的约定是：这类分隔线一律按分隔线（<hr>）处理，
   因此在其前面补一个空行，消除歧义。 */
window.mdToHtml = function (md) {
    var text = String(md === null || md === undefined ? '' : md).replace(/\r\n?/g, '\n');
    var lines = text.split('\n');
    var out = [];
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        // 仅由 -、*、_ 组成（≥3 个，允许空格分隔）的行 = 分隔线
        var isDivider = /^[ \t]{0,3}(?:(?:-[ \t]*){3,}|(?:\*[ \t]*){3,}|(?:_[ \t]*){3,})[ \t]*$/.test(line);
        if (isDivider && out.length && out[out.length - 1].trim() !== '') {
            out.push('');
        }
        out.push(line);
    }
    var normalized = out.join('\n');
    return window.marked ? window.marked.parse(normalized) : $('<div>').text(normalized).html();
};

/* 统一的 AI 分析调用封装：页面不刷新（AJAX）、不重复发起请求、
   命中页面缓存即刻展示，并优先由后端查询历史以节省 AI 额度 */
window.AiAnalyze = {
    _loading: false,
    _cache: {},
    _raw: {},
    run: function (o) {
        var self = this;
        if (o.validate && !o.validate()) return;
        if (self._loading) {
            self._openModal(o.modalId);
            if (window.layer) layer.msg('AI 正在分析中，请稍候…', { icon: 0 });
            return;
        }
        var key = o.cacheKey ? o.cacheKey() : '';
        if (key && self._cache[key]) {
            self._openModal(o.modalId);
            self._render(o.resultId, self._cache[key]);
            if (o.onDone) o.onDone({ code: 0, content: self._raw[key] || '', cached: 1 });
            return;
        }
        self._loading = true;
        self._setFab(o.fabSel, true);
        self._openModal(o.modalId);
        self._render(o.resultId, '<div class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin me-2"></i>AI 正在分析，请稍候…</div>');
        var data = o.payload ? o.payload() : {};
        $.ajax({
            url: o.url,
            type: 'POST',
            data: data,
            dataType: 'json',
            timeout: 30000,
            success: function (res) {
                if (res.code == 0) {
                    var html = window.mdToHtml
                        ? '<div class="markdown-body">' + window.mdToHtml(res.content) + '</div>'
                        : $('<div>').text(res.content).html();
                    if (key) { self._cache[key] = html; self._raw[key] = res.content; }
                    self._render(o.resultId, html);
                } else {
                    self._render(o.resultId, '<div class="alert alert-danger">' + (res.msg || '分析失败') + '</div>');
                }
                if (o.onDone) o.onDone(res);
            },
            error: function () {
                self._render(o.resultId, '<div class="alert alert-danger">请求失败，请稍后重试</div>');
            },
            complete: function () {
                self._loading = false;
                self._setFab(o.fabSel, false);
            }
        });
    },
    _openModal: function (id) {
        if (!id) return;
        var el = document.getElementById(id);
        if (!el) return;
        if (window.bootstrap && bootstrap.Modal) {
            (new bootstrap.Modal(el)).show();
        } else if (window.jQuery) {
            $('#' + id).modal('show');
        }
    },
    _render: function (id, html) { if (id) $('#' + id).html(html); },
    _setFab: function (sel, on) {
        if (!sel) return;
        var f = document.querySelector(sel);
        if (f) f.classList.toggle('ai-loading', !!on);
    }
};

/* 将浮动按钮挂到 <body> 下，并以内联 !important 写死定位：
   确保 position:fixed + bottom 真正相对视口生效，压过任何框架/主题样式覆盖
   （也顺带规避任何祖先 transform 带来的定位上下文问题） */
(function () {
    function ensureFabInBody() {
        // 优先取新页面（PJAX 容器）内的按钮，其次任意 .ai-fab
        var f = document.querySelector('#pjax-container .ai-fab') || document.querySelector('.ai-fab');
        if (!f) return;
        // 移除已挂在 body 上的旧按钮，避免 PJAX 切换后出现重复
        var existing = document.querySelectorAll('body > .ai-fab');
        for (var i = 0; i < existing.length; i++) {
            if (existing[i] !== f) existing[i].parentNode.removeChild(existing[i]);
        }
        if (f.parentNode !== document.body) {
            document.body.appendChild(f);
        }
        // 直接以内联 !important 写死定位，彻底压过任何框架/主题样式覆盖，
        // 并相对视口固定（已挂到 body，无祖先 transform 干扰）
        var isMobile = window.innerWidth <= 768;
        var s = f.style;
        s.setProperty('position', 'fixed', 'important');
        s.setProperty('right', (isMobile ? '18px' : '26px'), 'important');
        s.setProperty('bottom', (isMobile ? '120px' : '140px'), 'important');
        s.setProperty('top', 'auto', 'important');
        s.setProperty('left', 'auto', 'important');
        s.setProperty('z-index', '1030', 'important');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureFabInBody);
    } else {
        ensureFabInBody();
    }
    // PJAX 局部刷新后新页面的按钮需重新挂到 body
    if (window.jQuery) {
        window.jQuery(document).on('pjax:end pjax:complete', ensureFabInBody);
    }
})();
</script>
</body>
</html>
