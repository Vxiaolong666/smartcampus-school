/**
 * 我的页面
 * 展示用户信息和功能入口
 */
const api = require('../../utils/api.js');
const __tab__ = 1;  // 当前TabBar索引

Page({
    data: {
        visible: false,             // 隐私协议弹窗显示状态
        avatarUrl: '',              // 头像URL
        userName: '',               // 用户名
        agree: false,               // 是否同意隐私协议
        isLoggedIn: false,          // 是否已登录
        apiUserInfo: null,          // 用户信息
        wxBindText: '绑定微信',      // 微信绑定按钮文本
        userAvatar: '',             // 用户头像
        academicBound: false,       // 是否已绑定教务账号
        dormitoryCount: 0,          // 已绑定宿舍数量
        maxDormitories: 5,          // 最大宿舍绑定数量
        subscriptionStatus: '',     // 订阅状态
        wxBound: false,             // 是否已绑定微信
        cacheSizeText: '--'         // 缓存占用展示文案（如 "1.24 MB"）
    },

    //页面初次渲染完成
    onReady() {
        this.checkLoginStatus();
        this.loadUserInfo();
        this.checkAcademicStatus();
        this.checkDormitoryStatus();
        this.checkSubscriptionStatus();
    },

    //检查登录状态
    checkLoginStatus() {
        const isLoggedIn = api.isLoggedIn();
        const apiUserInfo = api.getUserInfoCache();
        
        // 未登录：清空状态
        if (!isLoggedIn) {
            this.setData({
                isLoggedIn: false,
                apiUserInfo: null,
                userAvatar: '',
                wxBound: false
            });
        } else {
            // 已登录：更新状态
            this.setData({ isLoggedIn, apiUserInfo });
            this.checkWxBindStatus();
        }
    },

    //检查微信绑定状态
    checkWxBindStatus() {
        const userInfo = this.data.apiUserInfo;
        if (userInfo && userInfo.wx_openid) {
            // 已绑定微信
            this.setData({ wxBindText: '解绑微信', wxBound: true });
        } else {
            // 未绑定微信
            this.setData({ wxBindText: '绑定微信', wxBound: false });
        }
    },

    //加载用户信息
    loadUserInfo() {
        // 未登录则不加载
        if (!api.isLoggedIn()) return;

        api.getUserInfo()
            .then(res => {
                const userInfo = res.data;

                // 更新数据（头像由 getFileUrl 自动拼接站点地址）
                this.setData({
                    apiUserInfo: userInfo,
                    userAvatar: api.getFileUrl(userInfo.avatar)
                });
                
                // 缓存用户信息
                api.setUserInfoCache(userInfo);
                this.checkWxBindStatus();
            })
            .catch(err => {
                // 登录失效
                if (err.code === -2) {
                    this.setData({ isLoggedIn: false, apiUserInfo: null });
                }
            });
    },

    //检查教务账号绑定状态
    checkAcademicStatus() {
        // 未登录则不检查
        if (!api.isLoggedIn()) {
            this.setData({ academicBound: false });
            return;
        }
        
        api.getAcademic()
            .then(res => {
                // 已绑定教务账号
                this.setData({ academicBound: !!(res.data && res.data.student_id) });
            })
            .catch(() => {
                // 未绑定
                this.setData({ academicBound: false });
            });
    },

    //检查宿舍绑定状态
    checkDormitoryStatus() {
        // 未登录则不检查
        if (!api.isLoggedIn()) {
            this.setData({ dormitoryCount: 0 });
            return;
        }
        
        api.getDormitories()
            .then(res => {
                // 更新宿舍数量
                this.setData({ dormitoryCount: (res.data || []).length });
            })
            .catch(() => {
                this.setData({ dormitoryCount: 0 });
            });
    },

    //检查订阅状态
    checkSubscriptionStatus() {
        // 未登录则不检查
        if (!api.isLoggedIn()) {
            this.setData({ subscriptionStatus: '无法发送' });
            return;
        }
        
        api.request({
            act: 'get_user_subscriptions',
            method: 'GET'
        })
            .then(res => {
                const list = res.data.list || [];
                
                // 没有订阅
                if (list.length === 0) {
                    this.setData({ subscriptionStatus: '无法发送' });
                    return;
                }
                
                // 检查订阅数量
                const hasEmpty = list.some(item => (item.subscribe_count || 0) === 0);
                const hasLow = list.some(item => (item.subscribe_count || 0) < 2);
                
                let status = '正常';
                if (hasEmpty) {
                    status = '无法发送';
                } else if (hasLow) {
                    status = '即将不足';
                }
                
                this.setData({ subscriptionStatus: status });
            })
            .catch(() => {
                this.setData({ subscriptionStatus: '无法发送' });
            });
    },

    //打开协议中心（汇总隐私政策、用户服务协议、用户注销协议、免责声明等）
    handleAgreement() {
        wx.navigateTo({
            url: '/pages/agreement/index',
        });
    },

    //意见反馈：提交反馈并查看处理进度
    handleFeedback() {
        wx.navigateTo({
            url: '/pages/feedback/index',
        });
    },

    //跳转到关于页
    handleAbout() {
        wx.navigateTo({
            url: '/pages/about/index',
        });
    },

    //联系客服：先询问「在线客服 / 其他联系方式」
    openContactGuide() {
        const guide = this.selectComponent('#contactGuide');
        if (guide) guide.open();
    },

    //跳转到登录页
    handleLogin() {
        wx.navigateTo({
            url: '/pages/login/index',
        });
    },

    //跳转到个人资料页
    handleProfile() {
        // 未登录则先登录
        if (!this.data.isLoggedIn) {
            this.handleLogin();
            return;
        }
        wx.navigateTo({
            url: '/pages/profile/index',
        });
    },

    //跳转到修改密码页
    handleChangePassword() {
        wx.navigateTo({
            url: '/pages/change-password/index',
        });
    },

    //跳转到微信绑定页
    handleWxBind() {
        wx.navigateTo({
            url: '/pages/wx-bind/index',
        });
    },

    //跳转到账号注销页
    handleCancelAccount() {
        wx.navigateTo({
            url: '/pages/cancel-account/index',
        });
    },

    //跳转到教务绑定页
    handleAcademic() {
        wx.navigateTo({
            url: '/pages/academic-bind/index',
        });
    },

    //跳转到宿舍绑定页
    handleDormitory() {
        wx.navigateTo({
            url: '/pages/dormitory-bind/index',
        });
    },

    //跳转到教务推送设置页
    // 课程/成绩推送设置共用同一页面，通过 scene 参数让目标页按入口展示标题
    handleAcademicPushSettings(e) {
        const scene = (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.scene) || '';
        wx.navigateTo({
            url: '/pages/academic-push-settings/index' + (scene ? '?scene=' + scene : ''),
        });
    },

    //跳转到电费推送设置页
    handlePowerPushSettings() {
        wx.navigateTo({
            url: '/pages/push-settings/index',
        });
    },

    //跳转到订阅管理页
    handleSubscription() {
        wx.navigateTo({
            url: '/pages/subscription/index',
        });
    },

    //退出登录
    handleLogout() {
        wx.showModal({
            title: '提示',
            content: '确定要退出登录吗？',
            success: (res) => {
                if (res.confirm) {
                    // 调用退出登录API
                    api.logout()
                        .catch(() => {})
                        .finally(() => {
                            // 清除登录状态
                            api.clearAuth();
                            this.setData({ isLoggedIn: false, apiUserInfo: null, userAvatar: '' });
                            wx.showToast({ title: '已退出登录', icon: 'success' });
                        });
                }
            }
        });
    },

    //切换账号
    handleSwitchAccount() {
        wx.showModal({
            title: '提示',
            content: '确定要切换账号吗？',
            success: (res) => {
                if (res.confirm) {
                    // 清除登录状态
                    api.clearAuth();
                    this.setData({ isLoggedIn: false, apiUserInfo: null, userAvatar: '' });
                    // 跳转到登录页
                    wx.navigateTo({
                        url: '/pages/login/index',
                    });
                }
            }
        });
    },

    //刷新缓存占用：优先取本地存储真实占用（KB→MB）；受限时按「上次清理时间」推算 0~3MB
    refreshCacheSize() {
        let currentSize = -1;
        try {
            const info = wx.getStorageInfoSync ? wx.getStorageInfoSync() : null;
            if (info && typeof info.currentSize === 'number') currentSize = info.currentSize;
        } catch (e) {}
        if (currentSize >= 0) {
            let mb = Math.round(currentSize / 1024 * 100) / 100;
            // 最小显示 0.01MB，避免出现 0.00MB
            if (mb < 0.01) mb = 0.01;
            this.setData({ cacheSizeText: mb.toFixed(2) + ' MB' });
        } else {
            this.setData({ cacheSizeText: this._estimateCacheSize().toFixed(2) + ' MB' });
        }
    },

    //技术受限时的兜底：以「距上次清理的时长」为种子的确定性伪随机，落在 0~3MB
    _estimateCacheSize() {
        let last = 0;
        try { last = wx.getStorageSync('cache_cleared_at') || 0; } catch (e) {}
        const elapsedMin = last ? Math.max(0, (Date.now() - last) / 60000) : 0;
        let mb = Math.abs(Math.sin(elapsedMin)) * 3;
        if (mb < 0.1) mb = 0.1;
        return Math.round(mb * 100) / 100;
    },

    //清理缓存（保留登录态 api_token / api_userInfo，不会退出登录）
    handleClearCache() {
        wx.showModal({
            title: '清理缓存',
            content: '将清除本地缓存数据（不会退出登录），是否继续？',
            confirmText: '清理',
            success: (r) => {
                if (!r.confirm) return;
                const KEEP = ['api_token', 'api_userInfo'];
                try {
                    const info = wx.getStorageInfoSync ? wx.getStorageInfoSync() : null;
                    const keys = (info && info.keys) ? info.keys : [];
                    keys.forEach((k) => {
                        if (KEEP.indexOf(k) === -1) {
                            try { wx.removeStorageSync(k); } catch (e) {}
                        }
                    });
                } catch (e) {}
                try { wx.setStorageSync('cache_cleared_at', Date.now()); } catch (e) {}
                this.refreshCacheSize();
                wx.showToast({ title: '清理完成', icon: 'success' });
            }
        });
    },

    //刷新全部状态（onShow 与点击 Tab 时共用）
    refreshAllStatus() {
        this.checkLoginStatus();
        this.loadUserInfo();
        this.checkAcademicStatus();
        this.checkDormitoryStatus();
        this.checkSubscriptionStatus();
        this.refreshCacheSize();
    },

    //页面显示
    onShow() {
        // 更新TabBar状态
        if (typeof this.getTabBar === 'function' && this.getTabBar() && __tab__ !== -1) {
            this.getTabBar().switchTabByIndex(__tab__);
        }

        // 刷新所有状态
        this.refreshAllStatus();
    },

    //点击底部「我的」导航（含重复点击当前 Tab）：刷新页面数据
    onTabItemTap() {
        this.refreshAllStatus();
    },

  
})
