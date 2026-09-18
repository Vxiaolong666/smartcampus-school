/**
 * 订阅管理页面
 * 管理微信订阅消息设置
 */
const api = require('../../utils/api.js');
const { sceneTypeNames } = require('../../utils/common.js');

Page({
    data: {
        loading: true,          // 加载状态
        subscriptions: []       // 订阅列表
    },

    //页面加载
    onLoad() {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            wx.showToast({ title: '请先登录', icon: 'none' });
            setTimeout(() => {
                wx.navigateTo({ url: '/pages/login/index' });
            }, 1000);
            return;
        }
        this.loadSubscriptions();
    },

    //页面显示
    onShow() {
        if (api.isLoggedIn()) {
            this.loadSubscriptions();
        }
    },

    //加载订阅列表
    async loadSubscriptions() {
        this.setData({ loading: true });

        try {
            const res = await api.request({
                act: 'get_user_subscriptions',
                method: 'GET'
            });

            // 处理订阅列表数据
            const subscriptions = (res.data.list || []).map(item => ({
                ...item,
                sceneName: sceneTypeNames[item.scene_type] || '未分类',
                hasCount: item.subscribe_count > 0
            }));

            this.setData({
                subscriptions,
                loading: false
            });
        } catch (err) {
            console.error('加载订阅数据失败', err);
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '加载失败', icon: 'none' });
        }
    },

    //请求订阅消息
    async onRequestSubscribe(e) {
        const templateId = e.currentTarget.dataset.templateId;

        // 验证模板ID
        if (!templateId) {
            wx.showToast({ title: '模板ID不存在', icon: 'none' });
            return;
        }

        try {
            // 调用微信订阅消息API
            const res = await wx.requestSubscribeMessage({
                tmplIds: [templateId]
            });

            const result = res[templateId];

            // 处理订阅结果
            if (result === 'accept') {
                wx.showToast({ title: '订阅成功', icon: 'success' });
                await this.saveSubscription(templateId);
                this.loadSubscriptions();
            } else if (result === 'reject') {
                wx.showToast({ title: '您拒绝了订阅', icon: 'none' });
            } else if (result === 'ban') {
                wx.showToast({ title: '该模板已被封禁', icon: 'none' });
            } else {
                wx.showToast({ title: '订阅结果: ' + result, icon: 'none' });
            }
        } catch (err) {
            console.error('订阅失败', err);
            wx.showToast({ title: '订阅失败: ' + (err.errMsg || '未知错误'), icon: 'none' });
        }
    },

    //保存订阅记录
    async saveSubscription(templateId) {
        try {
            await api.request({
                act: 'save_subscription',
                method: 'POST',
                data: {
                    template_id: templateId
                }
            });
        } catch (err) {
            console.error('保存订阅失败', err);
        }
    },

    //刷新订阅列表
    onRefresh() {
        this.loadSubscriptions();
    },

    //跳转到推送测试页
    goToPushTest() {
        wx.navigateTo({ url: '/pages/push-test/index' });
    },

    //跳转到订阅历史记录页
    goToSubscriptionHistory() {
        wx.navigateTo({ url: '/pages/subscription-history/index' });
    }
});
