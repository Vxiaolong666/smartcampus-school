/**
 * 忘记密码页面
 * 处理密码重置流程
 */
const api = require('../../utils/api.js');
const validate = require('../../utils/validate.js');

Page({
  data: {
    email: '',          // 邮箱
    loading: false      // 发送加载状态
  },

  //邮箱输入
  onEmailInput(e) {
    this.setData({ email: e.detail.value });
  },

  //发送重置密码邮件
  onSubmit() {
    const { email, loading } = this.data;
    
    // 防止重复提交
    if (loading) return;
    
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

    // 图片验证码校验（本地先拦，避免无效请求）
    const captchaComp = this.selectComponent('#captchaImg');
    const captchaCheck = captchaComp ? captchaComp.check() : { ok: true };
    if (!captchaCheck.ok) {
      wx.showToast({ title: captchaCheck.msg || '请输入图片验证码', icon: 'none' });
      return;
    }

    // 开始发送
    this.setData({ loading: true });
    wx.showLoading({ title: '发送中...' });

    // 调用忘记密码API（验证码随表单一起提交）
    api.forgotPassword(email, { captcha: captchaComp ? captchaComp.getCode() : '' })
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
  }
});
