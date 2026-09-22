/**
 * 协议中心
 * 清单优先取后台「协议管理」（接口下发）：后台未发布任何协议时展示「暂无协议」；
 * 请求失败（网络异常 / 协议表未初始化）时回退内置清单，保证离线可读；
 * 每项协议跳转到各自的独立页面（内置协议）或通用详情页。
 */
const api = require('../../utils/api.js');
const agreements = require('../../utils/agreements.js');

Page({
  data: {
    // 先用内置清单渲染，避免等待网络
    list: agreements.list.map(item => ({
      key: item.key,
      title: item.title,
      desc: item.desc,
      route: agreements.resolvePath(item.key)
    }))
  },

  onLoad() {
    this.loadFromServer();
  },

  loadFromServer() {
    api.getAgreements().then(res => {
      const rows = (res && Array.isArray(res.data)) ? res.data : null;
      // 响应异常（非数组）：沿用内置清单
      if (!rows) return;
      const descMap = {};
      agreements.list.forEach(item => { descMap[item.key] = item.desc; });
      // 以服务端清单为准（可能为空：后台把协议全部停用 → 展示「暂无协议」）
      this.setData({
        list: rows.map(r => ({
          key: r.key,
          title: r.title,
          desc: descMap[r.key] || (r.updated_at ? ('最近更新：' + String(r.updated_at).slice(0, 10)) : ''),
          route: agreements.resolvePath(r.key)
        }))
      });
    }).catch(() => {
      // 拉取失败（网络异常 / 协议表未初始化）：沿用内置清单
    });
  },

  // 打开某份协议（统一走通用详情页）
  openAgreement(e) {
    const item = e.currentTarget.dataset.item;
    const url = (item && item.route) || agreements.resolvePath(item && item.key);
    if (!url) return;
    wx.navigateTo({ url });
  },

  // 关于我们
  goAbout() {
    wx.navigateTo({ url: '/pages/about/index' });
  },

  // 联系客服：先询问「在线客服 / 其他联系方式」
  openContactGuide() {
    const guide = this.selectComponent('#contactGuide');
    if (guide) guide.open();
  }
});
