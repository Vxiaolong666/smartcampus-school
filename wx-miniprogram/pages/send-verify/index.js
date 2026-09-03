/**
 * 发送验证邮件页面
 * 处理验证邮件发送流程
 */
const api = require('../../utils/api.js');

Page({
  data: {
    email: '',          // 邮箱
    loading: false      // 发送加载状态
  },

  //邮箱输入
  onEmailInput(e) {
    this.setData({ email: e.detail.value });
  },

  //发送验证邮件
  onSubmit() {
    const { email, loading } = this.data;
    
    // 防止重复提交
    if (loading) return;
    
    // 验证邮箱
    if (!email.trim()) {
      wx.showToast({ title: '请输入邮箱', icon: 'none' });
      return;
    }

    // 开始发送
    this.setData({ loading: true });
    wx.showLoading({ title: '发送中...' });

    // 调用发送验证邮件API
    api.sendVerifyEmail(email)
      .then(res => {
        // 发送成功
        wx.hideLoading();
        this.setData({ loading: false });
        
        // 显示成功提示
        wx.showModal({
          title: '发送成功',
          content: res.msg,
          showCancel: false,
          success: () => {
            // 返回登录页
            wx.navigateBack();
          }
        });
      })
      .catch(err => {
        // 发送失败
        wx.hideLoading();
        this.setData({ loading: false });
        wx.showToast({ title: err.msg || '发送失败', icon: 'none' });
      });
  },

  //返回登录页
  onLogin() {
    wx.navigateBack();
  },

  //跳转到忘记密码页
  onForgot() {
    wx.navigateTo({ url: '/pages/forgot/index' });
  }
});
