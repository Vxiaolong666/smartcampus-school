/**
 * 登录页面
 * 处理账号密码登录和微信登录
 */
const api = require('../../utils/api.js');

Page({
    data: {
        username: '',       // 用户名
        password: '',       // 密码
        loading: false,     // 登录加载状态
        wxLoading: false    // 微信登录加载状态
    },

    //页面加载
    onLoad(options) {
        // 如果已登录，直接跳转到首页
        if (api.isLoggedIn()) {
            wx.switchTab({ url: '/pages/white-home/index' });
        }
    },

    //用户名输入
    onUsernameInput(e) {
        this.setData({ username: e.detail.value });
    },

    //密码输入
    onPasswordInput(e) {
        this.setData({ password: e.detail.value });
    },

    //账号密码登录
    onLogin() {
        const { username, password, loading } = this.data;

        // 防止重复提交
        if (loading) return;

        // 验证用户名
        if (!username.trim()) {
            wx.showToast({ title: '请输入用户名', icon: 'none' });
            return;
        }

        // 验证密码
        if (!password.trim()) {
            wx.showToast({ title: '请输入密码', icon: 'none' });
            return;
        }

        // 开始登录
        this.setData({ loading: true });
        wx.showLoading({ title: '登录中...' });

        // 调用登录API
        api.login(username, password)
            .then(res => {
                // 登录成功
                wx.hideLoading();
                this.setData({ loading: false });

                // 保存token和用户信息
                api.setToken(res.data.token);
                api.setUserInfoCache(res.data);

                wx.showToast({ title: '登录成功', icon: 'success' });

                // 延迟1秒后跳转到首页
                setTimeout(() => {
                    wx.switchTab({ url: '/pages/white-home/index' });
                }, 1000);
            })
            .catch(err => {
                // 登录失败
                wx.hideLoading();
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '登录失败', icon: 'none' });
            });
    },

    //微信一键登录
    async onWxLogin() {
        // 防止重复提交
        if (this.data.wxLoading) return;

        this.setData({ wxLoading: true });
        wx.showLoading({ title: '登录中...' });

        try {
            // 获取微信登录凭证
            const loginRes = await wx.login();
            
            // 调用微信登录API
            const res = await api.request({
                act: 'wx_login',
                method: 'POST',
                data: { code: loginRes.code }
            });

            // 登录成功
            wx.hideLoading();
            this.setData({ wxLoading: false });

            // 保存token和用户信息
            api.setToken(res.data.token);
            api.setUserInfoCache(res.data);

            wx.showToast({ title: '登录成功', icon: 'success' });

            // 延迟1秒后跳转到首页
            setTimeout(() => {
                wx.switchTab({ url: '/pages/white-home/index' });
            }, 1000);
        } catch (err) {
            // 登录失败
            wx.hideLoading();
            this.setData({ wxLoading: false });
            wx.showToast({ title: err.msg || '微信登录失败', icon: 'none' });
        }
    },

    //跳转到注册页
    onRegister() {
        wx.navigateTo({ url: '/pages/register/index' });
    },

    //跳转到忘记密码页
    onForgot() {
        wx.navigateTo({ url: '/pages/forgot/index' });
    },

    //跳转到发送验证邮件页
    onSendVerify() {
        wx.navigateTo({ url: '/pages/send-verify/index' });
    }
});
