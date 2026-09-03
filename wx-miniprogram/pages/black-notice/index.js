const api = require('../../utils/api.js');

const API_BASE = 'https://mc.xzwmc.com/csgocs';

Page({
    // 页面数据
    data: {
        notices: [],       // 通知列表
        loading: false,    // 加载状态
        page: 1,           // 当前页码
        size: 10,          // 每页数量
        total: 0,          // 总数量
        hasMore: true      // 是否有更多数据
    },

    // 页面加载时触发
    onLoad(options) {
        this.loadNotices();
    },

    // 加载通知列表
    async loadNotices() {
        // 如果正在加载或没有更多数据，则返回
        if (this.data.loading || !this.data.hasMore) return;
        
        this.setData({ loading: true });
        
        try {
            const res = await api.getNotices(this.data.page, this.data.size);
            
            // 处理通知列表数据
            const list = res.data.list.map(item => ({
                ...item,
                cover: item.cover ? API_BASE + item.cover : ''
            }));
            
            // 追加新数据到列表
            this.setData({
                notices: [...this.data.notices, ...list],
                total: res.data.total,
                page: this.data.page + 1,
                hasMore: this.data.notices.length + list.length < res.data.total,
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

    // 跳转通知详情
    handleNoticeDetail(e) {
        const id = e.currentTarget.dataset.id;
        wx.navigateTo({
            url: '/pages/black-notice-info/index?id=' + id,
        });
    },




})
