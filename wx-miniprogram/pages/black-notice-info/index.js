const api = require('../../utils/api.js');
const markdownToHtml = require('../../utils/markdown.js');

Page({
    // 页面数据
    data: {
        notice: null,        // 通知详情
        loading: true,       // 加载状态
        aiLoading: false,    // AI 总结加载状态
        aiTime: '',          // AI 总结生成时间
        activeTab: 'summary',// 当前页签：summary / detail
        summaryHtml: '',     // 总结（概括文段，Markdown 渲染后 HTML）
        detailHtml: ''       // 要点（列表/说明，Markdown 渲染后 HTML）
    },

    // 页面加载时触发
    async onLoad(options) {
        try {
            // 获取通知详情
            const res = await api.getNoticeDetail(options.id);
            
            // 处理数据，图片输出完整 URL（由 getFileUrl 自动拼接站点地址）
            this.setData({
                notice: {
                    ...res.data,
                    cover: api.getFileUrl(res.data.cover),
                    image: api.getFileUrl(res.data.image)
                },
                loading: false
            });
            // 通知加载完成后自动异步获取 AI 总结（命中历史缓存不重复消耗额度）
            this.loadAiSummary(options.id);
        } catch (err) {
            this.setData({ loading: false });
            wx.showToast({
                title: err.msg || '加载失败',
                icon: 'none'
            });
        }
    },

    // 预览封面图片
    onPreviewCover() {
        if (this.data.notice?.cover) {
            wx.previewImage({
                current: this.data.notice.cover,
                urls: [this.data.notice.cover]
            });
        }
    },

    // 预览详情图片
    onPreviewImage() {
        if (this.data.notice?.image) {
            wx.previewImage({
                current: this.data.notice.image,
                urls: [this.data.notice.image]
            });
        }
    },

    // 分享给好友
    onShareAppMessage() {
        const notice = this.data.notice;
        return {
            title: notice?.title || '通知详情',
            path: `/pages/black-notice-info/index?id=${notice?.id}`,
            imageUrl: notice?.cover || ''
        };
    },

    // 自动异步拉取 AI 对本文的总结，命中历史缓存则标记
    async loadAiSummary(id) {
        if (!id) return;
        this.setData({ aiLoading: true });
        try {
            const res = await api.getNoticeAiSummary(id);
            const now = new Date();
            const p = (n) => (n < 10 ? '0' : '') + n;
            const aiTime = now.getFullYear() + '-' + p(now.getMonth() + 1) + '-' + p(now.getDate())
                + ' ' + p(now.getHours()) + ':' + p(now.getMinutes());
            const parts = this.parseAiSummary(res.data.content);
            this.setData({
                aiTime,
                summaryHtml: markdownToHtml(parts.summary || res.data.content),
                detailHtml: parts.detail ? markdownToHtml(parts.detail) : '',
                activeTab: 'summary',
                aiLoading: false
            });
        } catch (e) {
            // 总结失败不影响正文浏览
            this.setData({ aiLoading: false });
        }
    },

    // 总结 / 要点 页签切换
    switchTab(e) {
        this.setData({ activeTab: e.currentTarget.dataset.tab });
    },

    // 将 AI 返回内容拆分为「总结」（第一段概括）与「要点」（其余列表/说明），返回 Markdown 文本
    parseAiSummary(text) {
        const lines = (text || '').split(/\r?\n/);
        const summary = [];
        const detail = [];
        let inDetail = false;
        let started = false;
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!inDetail) {
                if (!line) {
                    if (started) inDetail = true;
                    continue;
                }
                const isItem = /^([-*•]|\d+[.、)])\s*/.test(line);
                const isHeader = /^\*\*[^*]+\*\*:?$/.test(line) || /^【[^】]+】$/.test(line);
                if (isItem || isHeader) {
                    inDetail = true;
                    detail.push(line);
                    continue;
                }
                summary.push(line);
                started = true;
            } else if (line) {
                detail.push(line);
            }
        }
        return { summary: summary.join('\n'), detail: detail.join('\n') };
    }
})
