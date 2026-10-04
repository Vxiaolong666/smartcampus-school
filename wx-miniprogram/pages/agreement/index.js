/**
 * 协议中心
 * 清单始终取后台「协议管理」（接口下发），本地不内置任何协议清单/正文等预留信息：
 *   - 后台未发布任何协议 → 展示「暂无协议」；
 *   - 请求失败（网络异常 / 协议表未初始化）→ 展示「协议加载失败」+ 重新加载。
 * 每项协议统一跳转到通用详情页。
 */
const api = require('../../utils/api.js');
const agreements = require('../../utils/agreements.js');
const { formatDate } = require('../../utils/common.js');

Page({
  data: {
    list: [],
    loading: true,   // 清单始终从接口获取
    failed: false    // 拉取失败（网络异常 / 协议表未初始化）
  },

  onLoad() {
    this.loadFromServer();
  },

  loadFromServer() {
    this.setData({ loading: true, failed: false });
    api.getAgreements().then(res => {
      const rows = (res && Array.isArray(res.data)) ? res.data : [];
      // 以服务端清单为准（可能为空：后台把协议全部停用 → 展示「暂无协议」）
      this.setData({
        loading: false,
        failed: false,
        list: rows.map(r => ({
          key: r.key,
          title: r.title,
          desc: r.updated_at ? ('最近更新：' + formatDate(r.updated_at)) : '',
          route: agreements.resolvePath(r.key)
        }))
      });
    }).catch(() => {
      // 拉取失败（网络异常 / 协议表未初始化）：展示失败态，不回退本地内置清单
      this.setData({ loading: false, failed: true, list: [] });
    });
  },

  // 失败后重新加载
  retryLoad() {
    this.loadFromServer();
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
