/**
 * 账号注销页面（分步表单 + 步骤指示器）
 * 与 Web 端 / uniapp 端流程一致：协议 → 风险告知 → 身份核验 → 确认提交
 * 第三步点击「下一步」即向服务端核验身份，通过后才进入第四步。
 */
const api = require('../../utils/api.js');
const markdownToHtml = require('../../utils/markdown.js');

const STEP_LABELS = ['阅读协议', '风险告知', '身份核验', '确认提交'];
const TOTAL_STEPS = 4;
const CONFIRM_PHRASE_DEFAULT = '我已确认注销后用户数据无法找回并确认注销';

const DEFAULT_SUMMARY = {
  dorm_count: 0,
  academic_bound: 0,
  academic_student_id: '',
  oauth_count: 0,
  sub_count: 0,
  push_count: 0
};

Page({
  data: {
    loading: true,
    enabled: false,
    needPassword: false,
    needEmail: false,
    coolingEnable: false,
    coolingDays: 10,
    agreement: '',
    agreementHtml: '',
    confirmPhrase: CONFIRM_PHRASE_DEFAULT,
    isCancelling: false,
    expireTimeText: '',
    summary: Object.assign({}, DEFAULT_SUMMARY),

    steps: [],
    currentStep: 1,

    agreeAgreement: false,
    agreeRisk: false,
    password: '',
    emailCode: '',
    confirmText: '',

    sending: false,
    countdown: 0,
    submitting: false,
    verifying: false,
    identityPassed: false,
    verifyPass: '',

    nextEnabled: false,
    submitEnabled: false
  },

  //页面加载
  onLoad() {
    // 检查登录状态
    if (!api.isLoggedIn()) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      setTimeout(() => {
        wx.navigateBack();
      }, 800);
      return;
    }
    this.setData({ steps: this.buildSteps(1) });
    this.loadConfig();
  },

  //页面卸载：清理倒计时
  onUnload() {
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = null;
    }
  },

  /* ==================== 内部方法 ==================== */

  // 生成步骤指示器数据
  buildSteps(currentStep) {
    return STEP_LABELS.map((label, i) => {
      const step = i + 1;
      let dotClass = '';
      let labelClass = '';
      let done = false;
      if (step < currentStep) {
        dotClass = 'done';
        labelClass = 'label-done';
        done = true;
      } else if (step === currentStep) {
        dotClass = 'active';
        labelClass = 'label-active';
      }
      return {
        label: label,
        dotClass: dotClass,
        labelClass: labelClass,
        done: done,
        lineDone: (step + 1) < currentStep
      };
    });
  },

  // 「下一步」是否可点击
  calcNextEnabled() {
    const d = this.data;
    if (d.currentStep === 1) return d.agreeAgreement;
    if (d.currentStep === 2) return d.agreeRisk;
    if (d.currentStep === 3) {
      if (d.needPassword && !String(d.password).trim()) return false;
      if (d.needEmail && !String(d.emailCode).trim()) return false;
      return true;
    }
    return true;
  },

  // 重新计算步骤条与按钮状态
  refresh() {
    const d = this.data;
    this.setData({
      steps: this.buildSteps(d.currentStep),
      nextEnabled: this.calcNextEnabled(),
      submitEnabled: !!(d.agreeAgreement && d.agreeRisk && d.identityPassed && String(d.confirmText).trim() === d.confirmPhrase)
    });
  },

  // 切换步骤
  goToStep(step) {
    this.setData({ currentStep: step });
    this.refresh();
  },

  /* ==================== 数据加载 ==================== */

  loadConfig() {
    this.setData({ loading: true });
    api.getCancelConfig()
      .then(res => {
        const d = res.data || {};
        const summary = Object.assign({}, DEFAULT_SUMMARY, d.summary || {});
        this.setData({
          enabled: parseInt(d.enabled, 10) === 1,
          needPassword: !!d.need_password,
          needEmail: !!d.need_email,
          coolingEnable: parseInt(d.cooling_enable, 10) === 1,
          coolingDays: parseInt(d.cooling_days, 10) || 10,
          agreement: d.agreement || '',
          agreementHtml: markdownToHtml(d.agreement || ''),
          confirmPhrase: d.confirm_phrase ? d.confirm_phrase : CONFIRM_PHRASE_DEFAULT,
          isCancelling: parseInt(d.is_cancelling, 10) === 1,
          expireTimeText: d.expire_time ? String(d.expire_time).substring(0, 10) : '',
          summary: summary,
          loading: false
        });
        this.refresh();
      })
      .catch(err => {
        this.setData({ loading: false });
        wx.showToast({ title: (err && err.msg) || '加载失败', icon: 'none' });
      });
  },

  /* ==================== 表单交互 ==================== */

  //勾选/取消：已阅读协议
  toggleAgreement() {
    this.setData({ agreeAgreement: !this.data.agreeAgreement });
    this.refresh();
  },

  //勾选/取消：已知晓风险
  toggleRisk() {
    this.setData({ agreeRisk: !this.data.agreeRisk });
    this.refresh();
  },

  //密码输入
  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
    this.refresh();
  },

  //邮箱验证码输入
  onEmailCodeInput(e) {
    this.setData({ emailCode: e.detail.value });
    this.refresh();
  },

  //确认短语输入
  onConfirmTextInput(e) {
    this.setData({ confirmText: e.detail.value });
    this.refresh();
  },

  //发送邮箱验证码
  handleSendCode() {
    if (this.data.sending || this.data.countdown > 0) return;
    this.setData({ sending: true });
    api.sendCancelCode()
      .then(res => {
        this.setData({ sending: false });
        wx.showToast({ title: res.msg || '验证码已发送', icon: 'none' });
        this.startCountdown();
      })
      .catch(err => {
        this.setData({ sending: false });
        wx.showToast({ title: (err && err.msg) || '发送失败', icon: 'none' });
      });
  },

  //发送冷却倒计时
  startCountdown() {
    if (this.timer) clearInterval(this.timer);
    this.setData({ countdown: 60 });
    this.timer = setInterval(() => {
      const left = this.data.countdown - 1;
      if (left <= 0) {
        clearInterval(this.timer);
        this.timer = null;
        this.setData({ countdown: 0 });
      } else {
        this.setData({ countdown: left });
      }
    }, 1000);
  },

  /* ==================== 步骤导航 ==================== */

  //下一步（第三步先核验身份）
  goNext() {
    const d = this.data;

    if (d.currentStep === 3) {
      if (d.identityPassed) {
        this.goToStep(4);
        return;
      }
      if (!d.nextEnabled) {
        wx.showToast({ title: '请先完成当前步骤', icon: 'none' });
        return;
      }
      this.verifyIdentity();
      return;
    }

    if (!d.nextEnabled) {
      wx.showToast({ title: '请先完成当前步骤', icon: 'none' });
      return;
    }
    if (d.currentStep < TOTAL_STEPS) {
      this.goToStep(d.currentStep + 1);
    }
  },

  //上一步
  goPrev() {
    const step = this.data.currentStep;
    if (step > 1) {
      this.goToStep(step - 1);
    }
  },

  //取消注销流程
  goCancel() {
    wx.showModal({
      title: '提示',
      content: '确定要取消注销操作吗？',
      success: (res) => {
        if (!res.confirm) return;
        const pages = getCurrentPages();
        if (pages && pages.length > 1) {
          wx.navigateBack();
        } else {
          wx.switchTab({ url: '/pages/white-home/index' });
        }
      }
    });
  },

  //身份核验（第三步「下一步」触发）
  verifyIdentity() {
    if (this.data.verifying) return;
    this.setData({ verifying: true });
    wx.showLoading({ title: '核验中...' });

    api.verifyCancelIdentity({
      password: this.data.password,
      email_code: this.data.emailCode
    })
      .then(res => {
        wx.hideLoading();
        this.setData({
          verifying: false,
          identityPassed: true,
          verifyPass: (res.data && res.data.pass) ? res.data.pass : ''
        });
        this.goToStep(4);
        wx.showToast({ title: '身份核验通过', icon: 'none' });
      })
      .catch(err => {
        wx.hideLoading();
        this.setData({ verifying: false });
        wx.showToast({ title: (err && err.msg) || '身份核验失败', icon: 'none' });
      });
  },

  /* ==================== 提交注销 ==================== */

  handleSubmit() {
    const d = this.data;
    if (!d.submitEnabled || d.submitting) return;

    const warn = d.coolingEnable
      ? '确定要提交注销申请吗？提交后将进入冷静期，到期账号将被永久删除。'
      : '确定要注销账号吗？账号及全部个人数据将被立即永久删除，且无法恢复！';

    wx.showModal({
      title: '提示',
      content: warn,
      success: (res) => {
        if (!res.confirm) return;
        this.doSubmit();
      }
    });
  },

  doSubmit() {
    this.setData({ submitting: true });
    wx.showLoading({ title: '处理中...' });

    api.cancelAccount({
      agree: 1,
      password: this.data.password,
      email_code: this.data.emailCode,
      confirm_text: String(this.data.confirmText).trim(),
      pass: this.data.verifyPass
    })
      .then(res => {
        wx.hideLoading();
        this.setData({ submitting: false });
        api.clearAuth();
        wx.showModal({
          title: '操作成功',
          content: res.msg || '账号已处理',
          showCancel: false,
          success: () => {
            wx.reLaunch({ url: '/pages/login/index' });
          }
        });
      })
      .catch(err => {
        wx.hideLoading();
        const patch = { submitting: false };
        // 核验凭据已失效：退回第三步重新核验
        if (err && err.code === -3) {
          patch.identityPassed = false;
          patch.verifyPass = '';
          patch.currentStep = 3;
        }
        this.setData(patch);
        this.refresh();
        wx.showToast({ title: (err && err.msg) || '注销失败', icon: 'none' });
      });
  }
});
