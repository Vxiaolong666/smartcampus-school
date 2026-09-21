/**
 * 小程序入口文件
 * 
 * 在启动时检查：
 * 1. 当前时间是否在维护时间段（后台「服务停止时段」配置，默认 23:00 - 次日 6:00）
 * 2. 维护页面功能开关是否禁用
 * 满足任一条件则跳转到维护页面
 */

const api = require('./utils/api.js');
const feature = require('./utils/feature.js');

// 内置兜底时段
const DEFAULT_START = '23:00';
const DEFAULT_END = '06:00';

const toMinutes = (hhmm) => {
  const parts = String(hhmm || '').split(':');
  const h = parseInt(parts[0] || '0', 10);
  const m = parseInt(parts[1] || '0', 10);
  return (isNaN(h) ? 0 : h) * 60 + (isNaN(m) ? 0 : m);
};

// 支持跨天时段（如 22:00 - 次日 08:00）
const inWindow = (nowMin, startMin, endMin) => {
  if (startMin === endMin) return false;
  if (startMin < endMin) return nowMin >= startMin && nowMin < endMin;
  return nowMin >= startMin || nowMin < endMin;
};

App({
  onLaunch() {
    // 检查是否需要跳转到维护页面
    this.checkMaintenanceTime();
  },

  onShow() {
    // 小程序从后台进入前台时也检查
    this.checkMaintenanceTime();
  },

  globalData: {
    // 全局数据
  },

  /**
   * 读取后台「服务停止时段」配置（带缓存，失败时用内置默认值）
   */
  async getMaintenanceWindow() {
    if (this._maintWindow) return this._maintWindow;
    let start = DEFAULT_START;
    let end = DEFAULT_END;
    try {
      const res = await api.getMaintenanceConfig();
      start = (res.data && res.data.start) || DEFAULT_START;
      end = (res.data && res.data.end) || DEFAULT_END;
    } catch (e) {
      // 沿用默认值
    }
    this._maintWindow = { start, end };
    return this._maintWindow;
  },

  /**
   * 检查是否需要跳转到维护页面
   * 条件1：当前时间在维护时间段（后台配置的服务停止时段）
   * 条件2：维护页面功能开关设置为启用（is_enabled = 1）
   */
  async checkMaintenanceTime() {
    // 去重：onLaunch 与 onShow 会几乎同时触发，避免并发检查与并发导航互相取消
    if (this._maintChecking) return;
    this._maintChecking = true;
    try {
      // 条件1：检查时间（使用后台配置的时段；getMaintenanceWindow 内部已做兜底，不会抛错）
      const win = await this.getMaintenanceWindow();
      const now = new Date();
      const nowMin = now.getHours() * 60 + now.getMinutes();
      const isMaintenanceTime = inWindow(nowMin, toMinutes(win.start), toMinutes(win.end));

      // 条件2：管理员是否手动开启维护
      // 必须「明确读到 weihu_open 且 is_enabled=1」才算维护中；
      // 接口失败 / 未配置该开关一律按未开启处理，避免误跳维护页
      let isFeatureEnabled = false;
      try {
        const sw = await feature.checkMaintenanceSwitch();
        isFeatureEnabled = !!(sw && sw.enabled === true);
      } catch (e) {
        isFeatureEnabled = false;
      }

      // 满足任一条件则跳转
      if (isMaintenanceTime || isFeatureEnabled) {
        this.navigateToMaintenance();
      }
    } finally {
      this._maintChecking = false;
    }
  },

  /**
   * 跳转到系统维护页
   * 维护页需要接管整个应用，直接使用 reLaunch 清空页面栈、一次性完成，
   * 避免 redirectTo 失败后再 reLaunch 造成的「二次导航」与被取消的导航竞态
   */
  navigateToMaintenance() {
    const pages = getCurrentPages();
    const currentPage = pages[pages.length - 1];
    const currentRoute = currentPage ? (currentPage.route || '') : '';
    if (currentRoute === 'pages/maintenance/index') return; // 已在维护页
    if (this._maintNavigating) return; // 已有跳转进行中

    this._maintNavigating = true;
    let attempts = 0;

    const go = () => {
      attempts++;
      wx.reLaunch({
        url: '/pages/maintenance/index',
        success: () => {
          this._maintNavigating = false;
        },
        fail: () => {
          // 导航被新导航取消（如首屏路由切换）→ 稍后重试，最多 3 次
          if (attempts < 3) {
            setTimeout(go, 300);
          } else {
            this._maintNavigating = false;
          }
        }
      });
    };

    go();
  }
})