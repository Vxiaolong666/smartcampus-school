/**
 * 推送测试页面
 * 用于测试微信订阅消息功能，包括模板选择、订阅请求、测试消息发送
 */
const api = require('../../utils/api.js');

Page({
  // 页面数据
  data: {
    loading: false,               // 加载状态
    subscribed: false,           // 是否已订阅
    openid: '',                  // 用户OpenID
    templateId: '',              // 模板消息ID
    messageData: '',             // 消息数据JSON
    // 默认数据示例
    defaultDataPlaceholder: '{"thing8": {"value": "高等数学"}, "thing4": {"value": "教学楼A101"}, "thing5": {"value": "请准时上课"}, "time15": {"value": "2024-01-01 08:00"}, "time3": {"value": "2024-01-01 08:00"}}',
    sending: false,              // 发送中状态
    logs: [],                    // 日志记录
    templateList: [],            // 模板列表
    selectedTemplate: null,      // 选中的模板
    selectedTemplateName: '',    // 选中的模板名称
    subscribeCount: null,       // 可用订阅次数
    wxVersion: '',               // 微信版本
    sdkVersion: ''               // SDK版本
  },

  // 页面加载
  onLoad() {
    // 检查用户是否已登录
    if (!api.isLoggedIn()) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      setTimeout(() => {
        wx.navigateTo({ url: '/pages/login/index' });
      }, 1000);
      return;
    }
    // 获取系统信息和OpenID
    this.getSystemInfo();
    this.loadOpenid();
    // 加载模板列表
    this.loadTemplates();
  },

  // 获取系统信息
  getSystemInfo() {
    try {
      const systemInfo = wx.getSystemInfoSync();
      const wxVersion = systemInfo.version || '未知';
      const sdkVersion = systemInfo.SDKVersion || '未知';
      
      this.setData({ 
        wxVersion, 
        sdkVersion
      });
    } catch (err) {
      console.error('获取系统信息失败', err);
    }
  },

  // 页面显示
  onShow() {
    // 如果已有模板ID，刷新订阅次数
    if (this.data.templateId) {
      this.getSubscribeCount();
    }
  },

  // 从本地存储加载OpenID
  loadOpenid() {
    const openid = wx.getStorageSync('wx_openid') || '';
    this.setData({ openid });
  },

  // 加载模板列表
  async loadTemplates() {
    try {
      const res = await api.request({
        act: 'get_wx_templates',
        method: 'GET'
      });
      this.setData({ templateList: res.data.list || [] });
    } catch (err) {
      console.error('加载模板列表失败', err);
    }
  },

  // 获取当前模板的可用订阅次数
  async getSubscribeCount() {
    const { templateId } = this.data;
    if (!templateId) return;

    try {
      const res = await api.request({
        act: 'get_subscription_count',
        method: 'GET',
        data: { template_id: templateId }
      });
      const count = res.data.subscribe_count;
      this.setData({ subscribeCount: count });
      this.addLog('当前模板可用订阅次数: ' + count, 'info');
    } catch (err) {
      console.error('获取订阅次数失败', err);
      this.setData({ subscribeCount: 0 });
      this.addLog('获取订阅次数失败: ' + (err.msg || '未知错误'), 'warning');
    }
  },

  // 模板选择器变化
  onTemplatePickerChange(e) {
    const index = e.detail.value;
    const template = this.data.templateList[index];
    if (template) {
      this.setData({
        selectedTemplate: template,
        selectedTemplateName: template.name,
        templateId: template.template_id,
        messageData: JSON.stringify(template.default_data, null, 2)
      });
      // 切换模板后刷新订阅次数
      this.getSubscribeCount();
    }
  },

  // 模板ID输入变化
  onTemplateInput(e) {
    this.setData({ 
      templateId: e.detail.value,
      selectedTemplate: null,
      selectedTemplateName: ''
    });
    // 手动输入后刷新订阅次数
    this.getSubscribeCount();
  },

  // 消息数据输入变化
  onMessageDataInput(e) {
    this.setData({ messageData: e.detail.value });
  },

  // 请求订阅消息
  async onRequestSubscribe() {
    const { templateId } = this.data;
    
    if (!templateId) {
      wx.showToast({ title: '请输入模板ID', icon: 'none' });
      return;
    }

    try {
      const res = await wx.requestSubscribeMessage({
        tmplIds: [templateId]
      });

      const result = res[templateId];
      
      if (result === 'accept') {
        // 用户同意订阅
        this.setData({ subscribed: true });
        this.addLog('用户同意订阅消息', 'success');
        // 保存订阅信息到服务器
        await this.saveSubscription(templateId);
        // 刷新订阅次数
        this.getSubscribeCount();
      } else if (result === 'reject') {
        // 用户拒绝订阅
        this.addLog('用户拒绝订阅消息', 'warning');
      } else if (result === 'ban') {
        // 模板被封禁
        this.addLog('该模板已被封禁', 'error');
      } else {
        this.addLog('订阅结果: ' + result, 'info');
      }
    } catch (err) {
      this.addLog('订阅失败: ' + (err.errMsg || JSON.stringify(err)), 'error');
    }
  },

  // 保存订阅信息到服务器
  async saveSubscription(templateId) {
    try {
      await api.request({
        act: 'save_subscription',
        method: 'POST',
        data: {
          template_id: templateId
        }
      });
      this.addLog('订阅信息已保存到服务器', 'success');
    } catch (err) {
      this.addLog('保存订阅信息失败: ' + (err.msg || '未知错误'), 'error');
    }
  },

  // 发送测试消息
  async onSendTest() {
    const { templateId, openid, messageData } = this.data;
    
    if (!templateId) {
      wx.showToast({ title: '请输入模板ID', icon: 'none' });
      return;
    }

    if (!openid) {
      wx.showToast({ title: '请先获取OpenID', icon: 'none' });
      return;
    }

    // 解析消息数据JSON
    let dataObj = {};
    if (messageData) {
      try {
        dataObj = JSON.parse(messageData);
      } catch (e) {
        wx.showToast({ title: '消息数据JSON格式错误', icon: 'none' });
        return;
      }
    }

    this.setData({ sending: true });
    this.addLog('正在发送测试消息...', 'info');

    try {
      const res = await api.request({
        act: 'send_subscribe_message',
        method: 'POST',
        data: {
          template_id: templateId,
          openid: openid,
          data: JSON.stringify(dataObj)
        }
      });

      this.addLog('消息发送成功', 'success');
      // 刷新订阅次数
      this.getSubscribeCount();
    } catch (err) {
      this.addLog('消息发送失败: ' + (err.msg || '未知错误'), 'error');
    }

    this.setData({ sending: false });
  },

  // 获取用户OpenID
  async onGetOpenid() {
    this.setData({ loading: true });

    try {
      // 调用微信登录获取code
      const loginRes = await wx.login();
      // 通过code获取OpenID
      const res = await api.request({
        act: 'get_openid',
        method: 'POST',
        data: {
          code: loginRes.code
        }
      });

      if (res.data && res.data.openid) {
        // 保存到本地存储
        wx.setStorageSync('wx_openid', res.data.openid);
        this.setData({ openid: res.data.openid });
        this.addLog('获取OpenID成功: ' + res.data.openid, 'success');
      }
    } catch (err) {
      this.addLog('获取OpenID失败: ' + (err.msg || '未知错误'), 'error');
    }

    this.setData({ loading: false });
  },

  // 添加日志记录
  addLog(message, type) {
    const logs = this.data.logs;
    // 在数组头部插入新日志
    logs.unshift({
      time: new Date().toLocaleTimeString(),
      message,
      type
    });
    this.setData({ logs });
  },

  // 清空日志
  clearLogs() {
    this.setData({ logs: [] });
  },

  // 复制日志内容
  onCopyLog(e) {
    const message = e.currentTarget.dataset.message;
    wx.setClipboardData({
      data: message,
      success: () => {
        wx.showToast({ title: '已复制', icon: 'success' });
      }
    });
  }
});