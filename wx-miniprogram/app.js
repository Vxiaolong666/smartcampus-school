/**
 * 小程序入口文件
 * 
 * 在启动时检查：
 * 1. 当前时间是否在维护时间段（23:00 - 次日 6:00）
 * 2. 维护页面功能开关是否禁用
 * 满足任一条件则跳转到维护页面
 */

const feature = require('./utils/feature.js');

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
   * 检查是否需要跳转到维护页面
   * 条件1：当前时间在维护时间段（23:00 - 次日 6:00）
   * 条件2：维护页面功能开关设置为启用（is_enabled = 1）
   */
  async checkMaintenanceTime() {
    try {
      // 条件1：检查时间
      const now = new Date();
      const hour = now.getHours();
      const isMaintenanceTime = hour >= 23 || hour < 6;

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