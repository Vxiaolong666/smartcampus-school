/**
 * 我的页面
 * 展示用户信息和功能入口
 */
const api = require('../../utils/api.js');
const __tab__ = 1;  // 当前TabBar索引
const API_BASE = 'https://mc.xzwmc.com/csgocs';

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
        wxBound: false              // 是否已绑定微信
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
                let userAvatar = '';
                
                // 处理头像URL
                if (userInfo.avatar) {
                    userAvatar = userInfo.avatar.startsWith('http') ? userInfo.avatar : API_BASE + userInfo.avatar;
                }
                
                // 更新数据
                this.setData({
                    apiUserInfo: userInfo,
                    userAvatar: userAvatar
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

    //打开隐私协议
    handleAgreement() {
        wx.openPrivacyContract();
    },

    //跳转到关于页
    handleAbout() {
        wx.navigateTo({
            url: '/pages/about/index',
        });
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
    handleAcademicPushSettings() {
        wx.navigateTo({
            url: '/pages/academic-push-settings/index',
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

    //页面显示
    onShow() {
        // 更新TabBar状态
        if (typeof this.getTabBar === 'function' && this.getTabBar() && __tab__ !== -1) {
            this.getTabBar().switchTabByIndex(__tab__);
        }
        
        // 刷新所有状态
        this.checkLoginStatus();
        this.loadUserInfo();
        this.checkAcademicStatus();
        this.checkDormitoryStatus();
        this.checkSubscriptionStatus();
    },

  
})
