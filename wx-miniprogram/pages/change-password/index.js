/**
 * 修改密码页面
 * 处理密码修改流程
 */
const api = require('../../utils/api.js');

Page({
  data: {
    oldPassword: '',        // 原密码
    newPassword: '',        // 新密码
    confirmPassword: '',    // 确认新密码
    loading: false          // 修改加载状态
  },

  //页面加载
  onLoad(options) {
    // 检查用户是否已登录
    if (!api.isLoggedIn()) {
      // 先说明登录用途（合规），再跳转登录页
      api.goLoginWithTip();
    }
  },

  //原密码输入
  onOldPasswordInput(e) {
    this.setData({ oldPassword: e.detail.value });
  },

  //新密码输入
  onNewPasswordInput(e) {
    this.setData({ newPassword: e.detail.value });
  },

  //确认密码输入
  onConfirmPasswordInput(e) {
    this.setData({ confirmPassword: e.detail.value });
  },

  //修改密码
  onChangePassword() {
    const { oldPassword, newPassword, confirmPassword, loading } = this.data;
    
    // 防止重复提交
    if (loading) return;
    
    // 验证原密码
    if (!oldPassword.trim()) {
      wx.showToast({ title: '请输入原密码', icon: 'none' });
      return;
    }
    
    // 验证新密码
    if (!newPassword.trim()) {
      wx.showToast({ title: '请输入新密码', icon: 'none' });
      return;
    }

    // 验证新密码长度
    if (newPassword.length < 6) {
      wx.showToast({ title: '新密码至少6位', icon: 'none' });
      return;
    }

    // 验证两次密码是否一致
    if (newPassword !== confirmPassword) {
      wx.showToast({ title: '两次密码不一致', icon: 'none' });
      return;
    }

    // 验证新密码不能与原密码相同
    if (oldPassword === newPassword) {
      wx.showToast({ title: '新密码不能与原密码相同', icon: 'none' });
      return;
    }

    // 开始修改
    this.setData({ loading: true });
    wx.showLoading({ title: '修改中...' });

    // 调用修改密码API
    api.changePassword(oldPassword, newPassword)
      .then(res => {
        // 修改成功
        wx.hideLoading();
        this.setData({ loading: false });
        
        wx.showToast({ title: '修改成功', icon: 'success' });
        
        // 清除登录状态
        api.clearAuth();
        
        // 延迟1.5秒后跳转到登录页
        setTimeout(() => {
          wx.reLaunch({ url: '/pages/login/index' });
        }, 1500);
      })
      .catch(err => {
        // 修改失败
        wx.hideLoading();
        this.setData({ loading: false });
        wx.showToast({ title: err.msg || '修改失败', icon: 'none' });
      });
  }
});
