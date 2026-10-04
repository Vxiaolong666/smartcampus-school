/**
 * 协议中心：协议清单与内容（Markdown）
 * ------------------------------------------------------------------
 * 「协议中心」列表页与通用协议详情页共用本模块：
 *   - resolvePath  统一跳转到通用详情页 /pages/agreement/detail/index?key=xxx
 *   - pageConfig   通用详情页的 Page 配置（正文图文块渲染、加载/失败/未发布状态）
 *
 * 说明：本模块与各协议页面均不内置任何协议清单、标题、说明或正文等预留信息，
 *       协议清单与内容一律来自后台「协议管理」：
 *         - api.php?act=get_agreements  协议清单
 *         - api.php?act=get_agreement   单份协议内容
 *       后台未配置/已停用 → 「当前协议未发布」；
 *       拉取失败（网络异常、协议表未初始化等）→ 「加载失败 + 重新加载」。
 */

const { formatDate } = require('./common.js');

/**
 * 协议详情统一路径
 * ------------------------------------------------------------------
 * 所有协议（后台新建的）都走同一个通用详情页：detail/index?key=xxx，
 * 页面内按 key 从接口取内容。好处：新增协议无需再改页面/路由，一处维护。
 */
function resolvePath(key) {
  if (!key) return '';
  return '/pages/agreement/detail/index?key=' + encodeURIComponent(key);
}

/**
 * 生成协议详情页的 Page 配置（所有协议共用同一个通用详情页）
 * 内容始终取后台「协议管理」（接口下发 Markdown）：
 *   - 正文拆成「文本块 + 图片块」：文本交 rich-text，图片交原生 image（各端稳定显示、可点击放大）；
 *   - 后台已停用 / 未配置（not_published）：展示「当前协议未发布」；
 *   - 拉取失败（网络异常、协议表未初始化等）：展示「加载失败」+「重新加载」。
 * @param {string} key 协议标识；留空时从页面参数 ?key= 读取（通用详情页）
 * @param {string} defaultTitle 加载中的兜底标题
 */
function agreementPageConfig(key, defaultTitle) {
  return {
    data: {
      title: defaultTitle || '协议内容',
      updatedAt: '',
      blocks: [],           // 正文块：{type:'html',html} 或 {type:'image',src,alt}
      loading: true,        // 正文始终从接口获取
      failed: false,        // 拉取失败（网络异常 / 协议表未初始化等）
      notPublished: false   // 后台未配置 / 已停用
    },
    onLoad: function (options) {
      this.__akey = key || (options && options.key) || '';
      this.loadAgreementFromServer();
    },
    // Markdown → 块序列（图片相对路径自动补全为完整地址）
    toBlocks: function (md) {
      var markdownToHtml = require('./markdown.js');
      var api = require('./api.js');
      return markdownToHtml.markdownToBlocks(md || '', { resolveUrl: api.getFileUrl });
    },
    // 正文图片点击放大（md-blocks 组件事件 / 原生 image 均可）
    previewImage: function (e) {
      var src = (e && e.detail && e.detail.src) || (e && e.currentTarget && e.currentTarget.dataset.src);
      if (src) wx.previewImage({ current: src, urls: [src] });
    },
    // 关于我们
    goAbout: function () {
      wx.navigateTo({ url: '/pages/about/index' });
    },
    // 联系客服：先询问「在线客服 / 其他联系方式」
    openContactGuide: function () {
      var guide = this.selectComponent('#contactGuide');
      if (guide) guide.open();
    },
    // 拉取失败后「重新加载」
    retryLoad: function () {
      this.loadAgreementFromServer();
    },
    // 始终从接口获取协议内容（本地不留存任何协议预留信息）
    loadAgreementFromServer: function () {
      var that = this;
      var k = this.__akey;
      var api = require('./api.js');
      if (!k) {
        this.setData({
          loading: false,
          failed: true,
          notPublished: false,
          blocks: [],
          updatedAt: ''
        });
        return;
      }
      this.setData({ loading: true, failed: false, notPublished: false });
      api.getAgreement(k).then(function (res) {
        var d = (res && res.data) || {};
        that.setData({
          title: d.title || that.data.title,
          updatedAt: d.updated_at ? formatDate(d.updated_at) : '',
          blocks: d.content ? that.toBlocks(d.content) : [],
          loading: false,
          failed: false,
          // 接口成功但正文为空：按「未发布」处理，避免出现空白页
          notPublished: !d.content
        });
      }).catch(function (err) {
        // 后台明确「未发布」（未配置/已停用）：展示「当前协议未发布」
        // 其它错误（网络异常、协议表未初始化等）：展示「加载失败」，不再回退任何本地内容
        var notPublished = !!(err && err.data && err.data.not_published);
        that.setData({
          loading: false,
          notPublished: notPublished,
          failed: !notPublished,
          blocks: [],
          updatedAt: ''
        });
      });
    }
  };
}

module.exports = {
  resolvePath: resolvePath,
  pageConfig: agreementPageConfig
};
