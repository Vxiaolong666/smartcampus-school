/**
 * 微信绑定页面
 * 处理用户微信账号的绑定与解绑操作
 */
const api = require('../../utils/api.js');

Page({
    data: {
        hasWxOpenid: false,  // 是否已绑定微信
        openid: '',          // 微信openid
        openidDisplay: '',   // 脱敏显示的openid
        loading: false       // 加载状态
    },

    //页面加载
    onLoad() {
        // 检查用户是否已登录
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        // 加载当前微信绑定状态
        this.loadBindStatus();
    },

    //页面显示
    onShow() {
        this.loadBindStatus();
    },

    //加载绑定状态
    async loadBindStatus() {
        try {
            // 调用API获取用户信息
            const res = await api.getUserInfo();
            const userInfo = res.data;
            
            // 判断是否已绑定微信
            const hasWxOpenid = !!userInfo.wx_openid;
            const openid = userInfo.wx_openid || '';
            
            // 脱敏处理：显示前8位+...+后8位
            const openidDisplay = openid 
                ? openid.substring(0, 8) + '...' + openid.substring(openid.length - 8) 
                : '';
            
            // 更新页面数据
            this.setData({ hasWxOpenid, openid, openidDisplay });
        } catch (err) {
            wx.showToast({ title: err.msg || '加载失败', icon: 'none' });
        }
    },

    //绑定微信账号
    async onBind() {
        this.setData({ loading: true });
        
        try {
            // 调用微信登录API获取code
            const loginRes = await new Promise((resolve, reject) => {
                wx.login({ success: resolve, fail: reject });
            });

            // 校验code是否获取成功
            if (!loginRes.code) {
                throw new Error('获取登录凭证失败');
            }

            // 调用后端绑定接口
            await api.request({
                act: 'bind_wx',
                method: 'POST',
                data: { code: loginRes.code }
            });

            // 绑定成功提示并刷新状态
            wx.showToast({ title: '绑定成功', icon: 'success' });
            this.loadBindStatus();
        } catch (err) {
            // 绑定失败提示
            wx.showToast({ title: err.msg || '绑定失败', icon: 'none' });
        } finally {
            // 重置加载状态
            this.setData({ loading: false });
        }
    },

    //解绑微信账号
    async onUnbind() {
        // 弹出确认对话框
        const modalRes = await new Promise((resolve) => {
            wx.showModal({
                title: '确认解绑',
                content: '解绑后将无法使用微信快捷登录，确定要解绑吗？',
                success: resolve
            });
        });

        // 用户取消则直接返回
        if (!modalRes.confirm) return;

        this.setData({ loading: true });

        try {
            // 调用后端解绑接口
            await api.request({
                act: 'unbind_wx',
                method: 'POST'
            });

            // 解绑成功提示并刷新状态
            wx.showToast({ title: '解绑成功', icon: 'success' });
            this.loadBindStatus();
        } catch (err) {
            // 解绑失败提示
            wx.showToast({ title: err.msg || '解绑失败', icon: 'none' });
        } finally {
            // 重置加载状态
            this.setData({ loading: false });
        }
    }
});
