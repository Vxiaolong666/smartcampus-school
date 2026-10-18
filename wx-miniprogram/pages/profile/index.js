const api = require('../../utils/api.js');
const validate = require('../../utils/validate.js');

Page({
    // 页面数据
    data: {
        userInfo: null,         // 用户信息
        nickname: '',           // 昵称
        avatar: '',             // 头像URL
        hasWxOpenid: false,     // 是否已绑定微信
        loaded: false           // 用户信息是否已返回（用 loading 占位，避免闪现空表单）
    },

    // 页面加载时触发
    onLoad() {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        this.loadUserInfo();
    },

    // 加载用户信息
    async loadUserInfo() {
        try {
            const res = await api.getUserInfo();
            const userInfo = res.data;
            this.setData({
                userInfo,
                nickname: userInfo.nickname || userInfo.username,
                avatar: api.getFileUrl(userInfo.avatar),
                hasWxOpenid: !!userInfo.wx_openid,
                loaded: true
            });
        } catch (err) {
            this.setData({ loaded: true });
            wx.showToast({ title: err.msg || '加载失败', icon: 'none' });
        }
    },

    // 输入昵称
    onNicknameInput(e) {
        this.setData({ nickname: e.detail.value });
    },

    // 昵称输入框失去焦点
    onNicknameBlur(e) {
        if (e.detail.value) {
            this.setData({ nickname: e.detail.value });
        }
    },

    // 选择头像
    onChooseAvatar() {
        wx.chooseMedia({
            count: 1,
            mediaType: ['image'],
            sourceType: ['album', 'camera'],
            success: (res) => {
                this.uploadAvatar(res.tempFiles[0].tempFilePath);
            },
            fail: (err) => {
                const msg = (err && err.errMsg) || '';
                // 用户主动取消不做提示
                if (msg.indexOf('cancel') > -1) return;
                wx.showToast({ title: '无法访问相册，请在设置中授权', icon: 'none' });
            }
        });
    },

    // 获取微信头像
    onGetWxAvatar(e) {
        if (e.detail.avatarUrl) {
            this.uploadAvatar(e.detail.avatarUrl);
        } else {
            wx.showToast({ title: '获取微信头像失败', icon: 'none' });
        }
    },

    // 上传头像
    async uploadAvatar(filePath) {
        wx.showLoading({ title: '上传中...' });

        try {
            const res = await api.uploadAvatar(filePath);
            wx.hideLoading();
            // 后端返回完整头像 URL
            this.setData({ avatar: api.getFileUrl(res.data.avatar) });
            wx.showToast({ title: '上传成功', icon: 'success' });
        } catch (err) {
            wx.hideLoading();
            wx.showToast({ title: err.msg || '上传失败', icon: 'none' });
        }
    },

    // 保存用户资料
    async onSave() {
        const { nickname, avatar } = this.data;

        // 验证昵称
        if (!nickname.trim()) {
            wx.showToast({ title: '请输入昵称', icon: 'none' });
            return;
        }

        // 昵称长度（与输入框 maxlength 保持一致）
        if (validate.trim(nickname).length > 20) {
            wx.showToast({ title: '昵称最多20个字符', icon: 'none' });
            return;
        }

        wx.showLoading({ title: '保存中...' });

        try {
            // 完整 URL 转回 API 站点相对路径后再保存（第三方头像原样保留）
            const avatarPath = api.toApiPath(avatar);
            
            // 调用API保存资料
            await api.request({
                act: 'update_profile',
                method: 'POST',
                data: {
                    nickname: nickname.trim(),
                    avatar: avatarPath
                }
            });

            wx.hideLoading();
            wx.showToast({ title: '保存成功', icon: 'success' });

            // 延迟返回上一页
            setTimeout(() => {
                wx.navigateBack();
            }, 1000);
        } catch (err) {
            wx.hideLoading();
            wx.showToast({ title: err.msg || '保存失败', icon: 'none' });
        }
    },

    // 跳转到微信绑定页面
    onGoToWxBind() {
        wx.navigateTo({ url: '/pages/wx-bind/index' });
    }
});
