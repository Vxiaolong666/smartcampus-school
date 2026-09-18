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
    try {
      // 条件1：检查时间（使用后台配置的时段）
      const win = await this.getMaintenanceWindow();
      const now = new Date();
      const nowMin = now.getHours() * 60 + now.getMinutes();
      const isMaintenanceTime = inWindow(nowMin, toMinutes(win.start), toMinutes(win.end));

      // 条件2：检查功能开关
      const featureStatus = await feature.checkFeature('weihu_open');
      const isFeatureEnabled = featureStatus && featureStatus.enabled === true;

      // 满足任一条件则跳转
      if (isMaintenanceTime || isFeatureEnabled) {
        // 获取当前页面路径
        const pages = getCurrentPages();
        const currentPage = pages[pages.length - 1];
        const currentRoute = currentPage ? currentPage.route : '';

        // 如果当前不在维护页面，则跳转
        if (currentRoute !== 'pages/maintenance/index') {
          wx.redirectTo({
            url: '/pages/maintenance/index',
            fail: () => {
              // 如果 redirectTo 失败（比如是 tabBar 页面），使用 reLaunch
              wx.reLaunch({
                url: '/pages/maintenance/index'
              });
            }
          });
        }
      }
    } catch (error) {
      // 如果检查失败，只根据时间判断
      const now = new Date();
      const hour = now.getHours();
      const isMaintenanceTime = hour >= 23 || hour < 6;

      if (isMaintenanceTime) {
        const pages = getCurrentPages();
        const currentPage = pages[pages.length - 1];
        const currentRoute = currentPage ? currentPage.route : '';

        if (currentRoute !== 'pages/maintenance/index') {
          wx.redirectTo({
            url: '/pages/maintenance/index',
            fail: () => {
              wx.reLaunch({
                url: '/pages/maintenance/index'
              });
            }
          });
        }
      }
    }
  }
})