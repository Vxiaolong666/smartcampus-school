const api = require('../../utils/api.js');

/**
 * 意见反馈
 * ------------------------------------------------------------------
 * 提交反馈（act=submit_feedback）与查看自己的反馈记录（act=get_my_feedbacks）。
 * 处理状态：0 待处理、1 处理中、2 已解决、3 已驳回、4 已忽略；
 * 管理员填写的处理备注（remark）会在此展示，列表只显示摘要，点击卡片弹窗查看完整详情。
 * 联系人信息由服务端自动取当前账号，客户端只提交主题与内容。
 */
Page({
  data: {
    isLoggedIn: false,
    formOpen: true,
    // 提交前人机验证开关（后台「反馈配置」，接口返回 form_captcha）
    formCaptcha: true,
    subject: '',
    content: '',
    contactSummary: '',
    submitting: false,
    loading: false,
    list: [],
    total: 0,
    page: 1,
    size: 10,
    hasMore: false,
    detail: null,
    detailShow: false
  },

  onLoad() {
    const logged = api.isLoggedIn();
    this.setData({ isLoggedIn: logged });
    if (!logged) {
      // 与其它二级页面一致：未登录先说明用途再跳登录页
      api.goLoginWithTip();
      return;
    }
    this.fillContactSummary();
    this.loadList(true);
  },

  // 展示当前账号用于反馈的联系方式（服务端也会按账号自动补全）
  fillContactSummary() {
    const info = (typeof api.getUserInfoCache === 'function' ? api.getUserInfoCache() : null) || {};
    const parts = [];
    const name = info.nickname || info.username || '';
    if (name) parts.push(name);
    if (info.email) parts.push(info.email);
    this.setData({ contactSummary: parts.join(' / ') });
  },

  onSubjectInput(e) {
    this.setData({ subject: e.detail.value });
  },

  onContentInput(e) {
    this.setData({ content: e.detail.value });
  },

  goLogin() {
    api.goLoginWithTip();
  },

  // 处理状态 → 徽章配色（WXML 内不能调用方法，先算好类名）
  statusClass(status) {
    const s = Number(status);
    if (s === 1) return 'fb-badge-doing';
    if (s === 2) return 'fb-badge-done';
    if (s === 3) return 'fb-badge-reject';
    if (s === 4) return 'fb-badge-ignore';
    return 'fb-badge-pending';
  },

  // 查看单条反馈详情（列表只展示摘要）
  openDetail(e) {
    const index = Number(e.currentTarget.dataset.index);
    const item = this.data.list[index];
    if (!item) return;
    this.setData({ detail: item, detailShow: true });
  },

  closeDetail() {
    this.setData({ detailShow: false });
  },

  // 弹窗内部点击不关闭（配合 catchtap 阻止冒泡）
  noop() {},

  // 联系客服：在线客服 / 其他联系方式（复用统一 contact-guide 组件）
  openContactGuide() {
    const guide = this.selectComponent('#contactGuide');
    if (guide) guide.open();
  },

  loadList(reset) {
    if (this.data.loading) return;
    if (reset) {
      this.setData({ page: 1, hasMore: false });
    }
    this.setData({ loading: true });
    api.getMyFeedbacks({ page: this.data.page, size: this.data.size })
      .then((res) => {
        const data = (res && res.data) || {};
        const rows = (Array.isArray(data.list) ? data.list : []).map((item) => {
          item.statusClass = this.statusClass(item.status);
          return item;
        });
        this.setData({
          list: reset ? rows : this.data.list.concat(rows),
          total: Number(data.total) || 0,
          hasMore: !!data.has_more,
          formOpen: data.form_open === undefined ? this.data.formOpen : Number(data.form_open) === 1,
          // 后台「反馈配置」的「提交前人机验证」开关
          formCaptcha: data.form_captcha === undefined ? this.data.formCaptcha : Number(data.form_captcha) === 1,
          loading: false
        });
      })
      .catch((err) => {
        this.setData({ loading: false });
        wx.showToast({ title: (err && err.msg) || '记录加载失败', icon: 'none' });
      });
  },

  loadMore() {
    if (this.data.loading || !this.data.hasMore) return;
    this.setData({ page: this.data.page + 1 });
    this.loadList(false);
  },

  // 提交前校验图片验证码（后台「反馈配置」开启人机验证时必填）
  checkCaptcha() {
    if (!this.data.formCaptcha) return { ok: true, msg: '', code: '' };
    const cap = this.selectComponent('#captchaImg');
    if (!cap) return { ok: true, msg: '', code: '' };
    const res = cap.check();
    return { ok: !!(res && res.ok), msg: (res && res.msg) || '', code: cap.getCode() };
  },

  // 重置图片验证码：验证码为一次性，提交后需重新获取
  resetCaptcha() {
    if (!this.data.formCaptcha) return;
    const cap = this.selectComponent('#captchaImg');
    if (cap) cap.refresh();
  },

  onSubmit() {
    const content = (this.data.content || '').trim();
    if (!content) {
      wx.showToast({ title: '请填写反馈内容', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;

    // 人机验证（后台「反馈配置」开启时必填）：与网页端一致，验证不通过不发请求
    const cap = this.checkCaptcha();
    if (!cap.ok) {
      wx.showToast({ title: cap.msg || '请先完成人机验证', icon: 'none' });
      return;
    }

    this.setData({ submitting: true });
    wx.showLoading({ title: '提交中...' });
    api.submitFeedback({
      subject: (this.data.subject || '').trim(),
      content: content,
      captcha: cap.code
    })
      .then((res) => {
        wx.hideLoading();
        this.setData({ submitting: false, subject: '', content: '' });
        this.resetCaptcha();
        wx.showToast({ title: (res && res.msg) || '提交成功', icon: 'success' });
        this.loadList(true);
      })
      .catch((err) => {
        wx.hideLoading();
        this.setData({ submitting: false });
        // 验证码已被后端消费，需重新获取后再提交
        this.resetCaptcha();
        wx.showToast({ title: (err && err.msg) || '提交失败，请稍后重试', icon: 'none' });
      });
  }
});
