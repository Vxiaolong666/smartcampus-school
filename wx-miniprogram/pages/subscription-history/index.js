/**
 * 订阅历史记录页面
 * 展示当前用户的订阅授权记录与消息推送记录（电费推送 / 教务推送）
 * 与 uniapp 端 pages/subscription-history/index.vue 保持一致
 */
const api = require('../../utils/api.js');

// 记录来源对应的图标
const ICON_MAP = {
  subscription: '/assets/wxmp-subscribe-teal.svg',
  power_push: '/assets/wxmp-bolt.svg',
  academic_push: '/assets/wxmp-calendar.svg'
};

// 状态码 -> 样式类
function statusClassOf(status) {
  if (status === 1) return 'success';
  if (status === 2) return 'fail';
  return 'pending';
}

Page({
  data: {
    tabs: [
      { label: '全部', value: 'all' },
      { label: '订阅记录', value: 'subscribe' },
      { label: '推送记录', value: 'push' }
    ],
    activeTab: 'all',

    list: [],
    loading: false,
    page: 1,
    size: 10,
    total: 0,
    hasMore: true,
    showBackTop: false
  },

  //页面加载
  onLoad() {
    // 检查登录状态
    if (!api.isLoggedIn()) {
      // 先说明登录用途（合规），再跳转登录页
      api.goLoginWithTip();
      return;
    }
    this.loadHistory(true);
  },

  //触底加载更多
  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadHistory(false);
    }
  },

  //滚动时显示/隐藏回到顶部按钮
  onPageScroll(e) {
    const show = e.scrollTop > 200;
    if (show !== this.data.showBackTop) {
      this.setData({ showBackTop: show });
    }
  },

  //切换选项卡
  onChangeTab(e) {
    const value = e.currentTarget.dataset.value;
    if (value === this.data.activeTab) return;
    this.setData({ activeTab: value });
    this.loadHistory(true);
  },

  //回到顶部
  onBackTop() {
    wx.pageScrollTo({ scrollTop: 0, duration: 300 });
  },

  //刷新
  onRefresh() {
    this.loadHistory(true);
  },

  //加载历史记录（reset=true 时重置列表，此时即便正在加载也允许发起）
  async loadHistory(reset) {
    if (this.data.loading && !reset) return;

    let page = this.data.page;
    let list = this.data.list;
    let hasMore = this.data.hasMore;

    if (reset) {
      page = 1;
      list = [];
      hasMore = true;
      this.setData({ list: [], total: 0, page: 1, hasMore: true });
    }

    this.setData({ loading: true });

    try {
      const res = await api.getSubscriptionHistory(page, this.data.size, this.data.activeTab);
      const data = res.data || {};

      const newList = (data.list || []).map(item => {
        const status = parseInt(item.status, 10) || 0;
        return {
          id: item.id,
          source: item.source,
          category: item.category,
          title: item.title,
          content: item.content || '',
          status: status,
          status_text: item.status_text || '',
          error_msg: item.error_msg || '',
          time: item.time || '',
          statusClass: statusClassOf(status),
          icon: ICON_MAP[item.source] || '/assets/wxmp-list.svg'
        };
      });

      const merged = list.concat(newList);
      const total = parseInt(data.total || 0, 10);

      this.setData({
        list: merged,
        total: total,
        page: page + 1,
        hasMore: merged.length < total,
        loading: false
      });
    } catch (err) {
      console.error('加载订阅历史失败', err);
      this.setData({ loading: false });
      wx.showToast({ title: (err && err.msg) || '加载失败', icon: 'none' });
    }
  }
});
