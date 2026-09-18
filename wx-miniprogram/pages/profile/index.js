const api = require('../../utils/api.js');

Page({
    // 页面数据
    data: {
        userInfo: null,         // 用户信息
        nickname: '',           // 昵称
        avatar: '',             // 头像URL
        hasWxOpenid: false      // 是否已绑定微信
    },

    // 页面加载时触发
    onLoad() {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            wx.showToast({ title: '请先登录', icon: 'none' });
            setTimeout(() => {
                wx.navigateTo({ url: '/pages/login/index' });
            }, 1000);
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
                hasWxOpenid: !!userInfo.wx_openid
            });
        } catch (err) {
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
