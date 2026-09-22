/**
 * 注册页面
 * 处理用户注册流程
 */
const api = require('../../utils/api.js');
const validate = require('../../utils/validate.js');

Page({
    data: {
        username: '',           // 用户名
        password: '',           // 密码
        confirmPassword: '',    // 确认密码
        email: '',              // 邮箱
        loading: false          // 注册加载状态
    },

    //用户名输入
    onUsernameInput(e) {
        this.setData({ username: e.detail.value });
    },

    //密码输入
    onPasswordInput(e) {
        this.setData({ password: e.detail.value });
    },

    //确认密码输入
    onConfirmPasswordInput(e) {
        this.setData({ confirmPassword: e.detail.value });
    },

    //邮箱输入
    onEmailInput(e) {
        this.setData({ email: e.detail.value });
    },

    //用户注册
    onRegister() {
        const { username, password, confirmPassword, email, loading } = this.data;

        // 防止重复提交
        if (loading) return;

        // 验证用户名
        if (!username.trim()) {
            wx.showToast({ title: '请输入用户名', icon: 'none' });
            return;
        }

        // 验证用户名格式（与后端一致：4-20 位字母、数字或下划线）
        if (!validate.isUsername(username)) {
            wx.showToast({ title: '用户名需为4-20位字母、数字或下划线', icon: 'none' });
            return;
        }

        // 验证邮箱
        if (!email.trim()) {
            wx.showToast({ title: '请输入邮箱', icon: 'none' });
            return;
        }

        // 验证邮箱格式（避免把必然失败的请求发到后端）
        if (!validate.isEmail(email)) {
            wx.showToast({ title: '邮箱格式不正确', icon: 'none' });
            return;
        }

        // 验证密码
        if (!password.trim()) {
            wx.showToast({ title: '请输入密码', icon: 'none' });
            return;
        }

        // 验证密码长度
        if (password.length < 6) {
            wx.showToast({ title: '密码长度至少6位', icon: 'none' });
            return;
        }

        // 验证两次密码是否一致
        if (password !== confirmPassword) {
            wx.showToast({ title: '两次密码不一致', icon: 'none' });
            return;
        }

        // 图片验证码校验（本地先拦，避免无效请求）
        const captchaComp = this.selectComponent('#captchaImg');
        const captchaCheck = captchaComp ? captchaComp.check() : { ok: true };
        if (!captchaCheck.ok) {
            wx.showToast({ title: captchaCheck.msg || '请输入图片验证码', icon: 'none' });
            return;
        }

        // 开始注册
        this.setData({ loading: true });
        wx.showLoading({ title: '注册中...' });

        // 调用注册API（验证码随表单一起提交）
        api.register(username, password, email, confirmPassword, { captcha: captchaComp ? captchaComp.getCode() : '' })
            .then(res => {
                // 注册成功
                wx.hideLoading();
                this.setData({ loading: false });

                // 显示成功提示
                wx.showModal({
                    title: '注册成功',
                    content: res.msg,
                    showCancel: false,
                    success: () => {
                        // 返回登录页
                        wx.navigateBack();
                    }
                });
            })
            .catch(err => {
                // 注册失败
                wx.hideLoading();
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '注册失败', icon: 'none' });
            });
    },

    //返回登录页
    onLogin() {
        wx.navigateBack();
    }
});
