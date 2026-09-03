const api = require('../../utils/api.js');
const API_BASE = 'https://mc.xzwmc.com/csgocs';

Page({
    // 页面数据
    data: {
        notice: null,      // 通知详情
        loading: true      // 加载状态
    },

    // 页面加载时触发
    async onLoad(options) {
        try {
            // 获取通知详情
            const res = await api.getNoticeDetail(options.id);
            
            // 处理数据，拼接图片URL
            this.setData({
                notice: {
                    ...res.data,
                    cover: res.data.cover ? API_BASE + res.data.cover : '',
                    image: res.data.image ? API_BASE + res.data.image : ''
                },
                loading: false
            });
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
    }
})
