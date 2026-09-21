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
    startText: DEFAULT_START, // 后台配置的维护开始时间（未取到时为兜底值，不直接展示）
    endText: DEFAULT_END,     // 后台配置的维护结束时间（未取到时为兜底值，不直接展示）
    configLoaded: false,      // 后台时段配置是否已成功加载（未加载时不展示兜底时段）
    recoverDesc: '',          // 「预计恢复」说明文案（随后台时段动态生成）
    manualOnly: false,        // 是否仅为「管理员手动开启维护」（用于切换主图标颜色）
    iconClock: ICON_CLOCK,    // 每日维护图标（SVG）
    iconShield: ICON_SHIELD   // 管理员维护图标（SVG）
  },

  onLoad() {
    this.loadMaintenanceConfig();
    this.detectStatus();
  },

  //读取后台「服务停止时段」配置（失败时沿用内置默认值），并返回本次判定结果
  async loadMaintenanceConfig(options) {
    try {
      const res = await api.getMaintenanceConfig();
      const start = (res.data && res.data.start) || DEFAULT_START;
      const end = (res.data && res.data.end) || DEFAULT_END;
      this.setData({ startText: start, endText: end, configLoaded: true });
      // 配置已就绪，允许据此判定“维护已结束”
      this._configLoaded = true;
    } catch (e) {
      // 拉取失败：保留默认值，并标记为“未就绪”，避免据此误判为维护已结束
      this._configLoaded = false;
      this.setData({ configLoaded: false });
    }
    // 配置（或失败）到达后重新判定一次，并把结果返回给调用方
    return await this.detectStatus(options);
  },

  onUnload() {
    this.clearTimer();
    this.clearExitTimer();
  },

  onHide() {
    this.clearTimer();
    this.clearExitTimer();
  },

  onShow() {
    // 回到前台时重新检测，并恢复倒计时
    this.detectStatus();
  },

  /**
   * 检测当前维护状态
   * @param {Object} [options] { notifyEnded:false } 时不在此弹「维护已结束」提示，由调用方提示
   * @returns {Object} { active, dailyActive, manualActive, reliable }
   *   active   是否处于维护中；reliable 结果是否可信（配置已加载且功能开关判定成功）
   */
  async detectStatus(options) {
    // 取消上一次可能存在的「回首页」定时器，避免配置到达后仍被跳走
    this.clearExitTimer();

    // 管理员维护开关（weihu_open 启用即代表管理员开启了维护）
    let manualActive = false;
    let notice = null;
    let featureName = '';
    let featureChecked = false; // 是否成功读到维护开关配置（失败时不能据此判定“维护已结束”）
    try {
      const sw = await feature.checkMaintenanceSwitch();
      manualActive = !!(sw && sw.enabled === true);
      if (sw && sw.announcement) {
        notice = {
          content: sw.announcement.content,
          type: sw.announcement.type || 'info'
        };
      }
      featureName = (sw && sw.featureName) || '';
      // known=false 表示开关配置读取失败，此时不能判定“维护已结束”
      featureChecked = !!(sw && sw.known);
    } catch (e) {
      // 拉取失败时无法确认维护是否结束，保持维护页展示
      featureChecked = false;
    }

    // 关键：时段必须在上面 await 之后「重新读取」。
    // 若在 await 之前读取，等待期间 loadMaintenanceConfig 可能已更新时段并置 _configLoaded=true，
    // 就会出现「dailyActive 用旧（默认）时段算出 false、reliable 却已是 true」的误判，
    // 导致每日时段维护时刚进页面就被判定“维护已结束”跳回首页（管理员手动维护不受影响）。
    const now = new Date();
    const startText = this.data.startText;
    const endText = this.data.endText;
    const startMin = toMinutes(startText);
    const endMin = toMinutes(endText);
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const dailyActive = inWindow(nowMin, startMin, endMin);

    const typeLabel = dailyActive && manualActive
      ? '教务系统维护'
      : (dailyActive ? '教务系统维护' : (manualActive ? '系统维护' : '维护结束'));

    // 跨天时段显示“次日”，同日时段直接显示
    const windowText = startMin < endMin
      ? ('每日 ' + startText + ' - ' + endText)
      : ('每日 ' + startText + ' - 次日 ' + endText);
    // 「维护时段」仅用于每日例行维护卡片（管理员维护卡片不展示该行）；
    // 并且未成功加载后台时段时不展示兜底时段（避免把默认 23:00-06:00 误当成真实时段）
    const maintenanceTime = dailyActive
      ? (this._configLoaded ? windowText : '时间待确认（以公告为准）')
      : '—';
    // 「预计恢复」文案同样随后台时段动态生成，不写死结束时间
    const recoverDesc = this._configLoaded
      ? ('维护期间课表、成绩、今日课程、宿舍电费等教务相关功能暂不可用，基础功能不受影响，' + endText + ' 后将自动恢复。')
      : '维护期间课表、成绩、今日课程、宿舍电费等教务相关功能暂不可用，基础功能不受影响，恢复后将自动可用。';

    this.setData({
      loading: false,
      dailyActive,
      manualActive,
      // 仅「管理员手动开启维护」（不处于每日维护时段）时主图标用橙色
      manualOnly: manualActive && !dailyActive,
      typeLabel,
      maintenanceTime,
      recoverDesc,
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

    // 结果是否可信：配置已加载且功能开关判定成功；不可信时不应判定为“维护已结束”
    const reliable = this._configLoaded && featureChecked;
    const active = dailyActive || manualActive;

    // 仅当结果可信且不在维护状态时，才判定维护已结束并返回首页；
    // 否则（例如后台时段配置尚未返回、仍在使用默认时段）不跳转，
    // 避免刚进入维护页就被误判为“维护已结束”而立刻跳回首页
    if (!active && reliable) {
      this.scheduleExitHome(!(options && options.notifyEnded === false));
    }

    return { active, dailyActive, manualActive, reliable };
  },

  //维护已结束时返回首页（可被后续检测取消）
  //notify=false 时不在此提示，交由调用方在关闭 loading 后提示，避免提示被 hideLoading 覆盖
  scheduleExitHome(notify) {
    if (this._exitTimer) return;
    if (notify !== false) {
      wx.showToast({ title: '维护已结束', icon: 'none' });
    }
    this._exitTimer = setTimeout(() => {
      this._exitTimer = null;
      wx.switchTab({ url: '/pages/white-home/index' });
    }, 800);
  },

  //取消「回首页」定时器（重新检测到维护中 / 页面隐藏时调用）
  clearExitTimer() {
    if (this._exitTimer) {
      clearTimeout(this._exitTimer);
      this._exitTimer = null;
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

  //重新检测维护状态：等待检测真正完成后关闭 loading，并给出明确结果提示
  async onRecheck() {
    wx.showLoading({ title: '检测中...', mask: true });

    let result = null;
    try {
      // 交由本方法提示结果，避免 detectStatus 内的提示被随后的 hideLoading 覆盖
      result = await this.loadMaintenanceConfig({ notifyEnded: false });
    } catch (e) {
      result = null;
    }
    wx.hideLoading();

    if (!result) {
      wx.showToast({ title: '网络不佳，请检查网络后重试', icon: 'none' });
      return;
    }
    if (result.active) {
      wx.showToast({ title: '系统仍在维护中，请稍后再试。带来的不便我们深表歉意，请您谅解', icon: 'none' });
      return;
    }
    if (!result.reliable) {
      wx.showToast({ title: '网络不佳，请检查网络后重试', icon: 'none' });
      return;
    }
    // 已不在维护状态：detectStatus 已安排好返回首页，这里给出提示
    wx.showToast({ title: '维护已结束，感谢您的理解', icon: 'none' });
  },

  //联系客服：先询问「在线客服 / 其他联系方式」
  openContactGuide() {
    const guide = this.selectComponent('#contactGuide');
    if (guide) guide.open();
  }
});
