/**
 * 功能维护页面
 *
 * 两种进入场景（可能同时成立）：
 * 1. 系统每日例行维护：每日 23:00 - 次日 06:00（受学校教务系统每晚关闭维护影响）
 * 2. 管理员手动开启维护：后台「维护」功能开关 weihu_open 启用（可配置维护公告）
 * 页面会区分展示具体的维护原因、维护时段与预计恢复时间
 */

const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

// 内置兜底时段（后台未配置时使用）：23:00 - 次日 06:00
const DEFAULT_START = '23:00';
const DEFAULT_END = '06:00';

// 维护图标（直接绘制 SVG，避免使用 emoji 或不相关的资源图标）
const svgDataUri = (inner, color) => {
  const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' +
    color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg>';
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
};

// 每日例行维护：时钟（主题青色）
const ICON_CLOCK = svgDataUri(
  '<circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15.5 14"></polyline>',
  '#13c2c2'
);
// 管理员维护：盾牌（警示橙色）
const ICON_SHIELD = svgDataUri(
  '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path>',
  '#fa8c16'
);

// 时间字符串（HH:mm）转当日分钟数
const toMinutes = (hhmm) => {
  const parts = String(hhmm || '').split(':');
  const h = parseInt(parts[0] || '0', 10);
  const m = parseInt(parts[1] || '0', 10);
  return (isNaN(h) ? 0 : h) * 60 + (isNaN(m) ? 0 : m);
};

// 判断当前是否处于维护时段（支持跨天，如 22:00 - 次日 08:00）
const inWindow = (nowMin, startMin, endMin) => {
  if (startMin === endMin) return false;
  if (startMin < endMin) return nowMin >= startMin && nowMin < endMin;
  return nowMin >= startMin || nowMin < endMin;
};

Page({
  data: {
    loading: true,
    dailyActive: false,     // 是否处于每日例行维护时段
    manualActive: false,    // 管理员是否开启了维护
    typeLabel: '',          // 维护类型标签
    maintenanceTime: '',    // 维护时间说明
    countdown: '',          // 预计恢复倒计时
    notice: null,           // 管理员维护公告 {content, type}
    featureName: '',        // 后台功能名称
    currentTime: '',        // 当前时间 HH:mm
    startText: DEFAULT_START, // 后台配置的维护开始时间
    endText: DEFAULT_END,     // 后台配置的维护结束时间
    iconClock: ICON_CLOCK,    // 每日维护图标（SVG）
    iconShield: ICON_SHIELD   // 管理员维护图标（SVG）
  },

  onLoad() {
    this.loadMaintenanceConfig();
    this.detectStatus();
  },

  //读取后台「服务停止时段」配置（失败时沿用内置默认值）
  async loadMaintenanceConfig() {
    try {
      const res = await api.getMaintenanceConfig();
      const start = (res.data && res.data.start) || DEFAULT_START;
      const end = (res.data && res.data.end) || DEFAULT_END;
      this.setData({ startText: start, endText: end });
      // 配置到达后重新判定一次
      this.detectStatus();
    } catch (e) {
      // 保持默认值
    }
  },

  onUnload() {
    this.clearTimer();
  },

  onHide() {
    this.clearTimer();
  },

  onShow() {
    // 回到前台时重新检测，并恢复倒计时
    this.detectStatus();
  },

  //检测当前维护状态
  async detectStatus() {
    const now = new Date();
    const { startText, endText } = this.data;
    const startMin = toMinutes(startText);
    const endMin = toMinutes(endText);
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const dailyActive = inWindow(nowMin, startMin, endMin);

    // 管理员维护开关（weihu_open 启用即代表管理员开启了维护）
    let manualActive = false;
    let notice = null;
    let featureName = '';
    try {
      const result = await feature.checkFeature('weihu_open');
      manualActive = !!(result && result.enabled === true);
      if (result && result.announcement) {
        notice = {
          content: result.announcement.content,
          type: result.announcement.type || 'info'
        };
      }
      featureName = (result && result.featureName) || '';
    } catch (e) {
      // 拉取失败时按每日维护状态展示
    }

    const typeLabel = dailyActive && manualActive
      ? '每日维护 + 管理员维护'
      : (dailyActive ? '每日例行维护' : (manualActive ? '管理员维护' : '维护结束'));

    // 跨天时段显示“次日”，同日时段直接显示
    const windowText = startMin < endMin
      ? ('每日 ' + startText + ' - ' + endText)
      : ('每日 ' + startText + ' - 次日 ' + endText);
    const maintenanceTime = dailyActive
      ? windowText
      : (manualActive ? '时间待定（以公告为准）' : '—');

    this.setData({
      loading: false,
      dailyActive,
      manualActive,
      typeLabel,
      maintenanceTime,
      notice,
      featureName,
      currentTime: this.formatTime(now)
    });

    if (dailyActive) {
      this.startCountdown();
    } else {
      this.clearTimer();
      this.setData({ countdown: '' });
    }

    // 已不在任何维护状态：返回首页
    if (!dailyActive && !manualActive) {
      wx.showToast({ title: '维护已结束', icon: 'none' });
      setTimeout(() => {
        wx.switchTab({ url: '/pages/white-home/index' });
      }, 800);
    }
  },

  //启动恢复倒计时（每秒刷新展示）
  startCountdown() {
    this.clearTimer();
    this.updateCountdown();
    this._timer = setInterval(() => this.updateCountdown(), 1000);
  },

  clearTimer() {
    if (this._timer) {
      clearInterval(this._timer);
      this._timer = null;
    }
  },

  //计算距离维护结束时间（后台配置）的剩余时间
  updateCountdown() {
    const now = new Date();
    const endMin = toMinutes(this.data.endText);
    const end = new Date(now.getTime());
    end.setHours(Math.floor(endMin / 60), endMin % 60, 0, 0);
    if (end.getTime() <= now.getTime()) {
      end.setDate(end.getDate() + 1);
    }
    const diff = end.getTime() - now.getTime();
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    const pad = n => (n < 10 ? '0' + n : '' + n);
    this.setData({
      countdown: pad(h) + ':' + pad(m) + ':' + pad(s)
    });
  },

  formatTime(date) {
    const pad = n => (n < 10 ? '0' + n : '' + n);
    return pad(date.getHours()) + ':' + pad(date.getMinutes());
  },

  //重新检测维护状态
  onRecheck() {
    wx.showLoading({ title: '检测中...' });
    this.detectStatus();
    setTimeout(() => wx.hideLoading(), 600);
  }
});
