/**
 * 统一「加载失败」遮罩
 * 风格与「未绑定宿舍 / 未绑定教务账号」提示一致：图标 + 标题 + 说明 + 按钮
 *
 * 用法：在二级页面根节点放一个 <cb-load-fail /> 即可，无需传参：
 * - api.js 网络层失败时自动出现（仅对发生失败的页面生效）
 * - 请求成功或点击「重新加载」后自动收起
 * - 遮罩覆盖内容区（保留顶部导航，可正常返回）
 */
const loadFail = require('../../utils/load-fail.js');

Component({
  data: {
    visible: false,
    message: ''
  },

  lifetimes: {
    attached() {
      this._route = loadFail.getRoute();
      // 进入新页面时清掉上一页遗留的失败状态，避免误显示
      loadFail.clear();
      this.sync(loadFail.get());
      this._unsubscribe = loadFail.subscribe((state) => this.sync(state));
    },
    detached() {
      this.cleanup();
    }
  },

  methods: {
    cleanup() {
      if (this._unsubscribe) {
        this._unsubscribe();
        this._unsubscribe = null;
      }
    },

    sync(state) {
      // 只在「发生失败的页面」展示，避免返回上一页时误显示
      const isMine = !state.route || !this._route || state.route === this._route;
      this.setData({
        visible: !!state.failed && isMine,
        message: state.message || '网络异常，请稍后重试'
      });
    },

    onRetry() {
      loadFail.clear();
      this.reloadPage();
    },

    // 重新进入当前页，触发页面重新拉取数据
    reloadPage() {
      let url = '';
      try {
        const pages = getCurrentPages();
        const page = pages[pages.length - 1];
        const route = (page && page.route) || this._route;
        const options = (page && page.options) || {};
        const query = Object.keys(options)
          .map((k) => k + '=' + encodeURIComponent(options[k]))
          .join('&');
        url = '/' + route + (query ? '?' + query : '');
      } catch (e) {
        url = '';
      }
      if (!url || url === '/') {
        this.setData({ visible: false });
        return;
      }
      wx.reLaunch({
        url,
        fail: () => {
          wx.redirectTo({ url });
        }
      });
    },

    noop() {}
  }
});
