const api = require('../../utils/api.js');
const markdownToHtml = require('../../utils/markdown.js');

Page({
    // 页面数据
    data: {
        notice: null,        // 通知详情
        loading: true,       // 加载状态
        aiLoading: false,    // AI 总结加载状态
        summaryHtml: '',     // AI 总结（Markdown 渲染后 HTML）
        contentBlocks: []    // 正文拆分块：{type:'html',html} 或 {type:'image',src,alt}
    },

    // 页面加载时触发
    async onLoad(options) {
        try {
            // 获取通知详情
            const res = await api.getNoticeDetail(options.id);
            
            // 处理数据，图片输出完整 URL（由 getFileUrl 自动拼接站点地址）
            // 正文拆成「文本块 + 图片块」：文本块交给 rich-text 解析 Markdown，
            // 图片块交给原生 <image mode="widthFix"> 渲染，保证图片在各端稳定显示
            this.setData({
                notice: {
                    ...res.data,
                    cover: api.getFileUrl(res.data.cover),
                    image: api.getFileUrl(res.data.image)
                },
                contentBlocks: markdownToHtml.markdownToBlocks(res.data.content || '', { resolveUrl: api.getFileUrl }),
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

    // 预览正文中的图片（md-blocks 组件事件 / 原生 image 均可）
    onPreviewContentImage(e) {
        const src = (e && e.detail && e.detail.src) || (e && e.currentTarget && e.currentTarget.dataset.src);
        if (src) {
            wx.previewImage({
                current: src,
                urls: [src]
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
            this.setData({
                summaryHtml: markdownToHtml(res.data.content),
                aiLoading: false
            });
        } catch (e) {
            // 总结失败不影响正文浏览
            this.setData({ aiLoading: false });
        }
    },

})
