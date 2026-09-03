/**
 * 功能维护页面
 * 
 * 当系统时间在 23:00 - 次日 6:00 时，自动跳转到此页面
 */

const feature = require('../../utils/feature.js');

Page({
  data: {
    currentHour: 0,
    maintenanceTime: '23:00 - 06:00',
    notice: null, // 维护公告
    loading: true
  },

  onLoad() {
    // 获取当前时间
    const now = new Date();
    const hour = now.getHours();
    
    this.setData({
      currentHour: hour
    });

    // 获取维护公告
    this.loadMaintenanceNotice();
  },

  /**
   * 加载维护公告
   * 使用 feature 模块获取维护公告
   */
  async loadMaintenanceNotice() {
    try {
      this.setData({ loading: true });
      
      // 使用 feature.checkFeature 获取维护公告（标识为 weihu_open）
      const result = await feature.checkFeature('weihu_open');
      
      if (result && result.announcement) {
        this.setData({
          notice: {
            title: '维护公告',
            content: result.announcement.content,
            type: result.announcement.type || 'info'
          },
          loading: false
        });
      } else {
        // 如果没有配置公告，显示默认公告
        this.setData({
          notice: {
            title: '维护公告',
            content: '系统正在进行日常维护，维护期间暂停所有服务。请稍后再试，感谢您的理解与支持！',
            type: 'info'
          },
          loading: false
        });
      }
    } catch (error) {
      // 出错时也显示默认公告
      this.setData({
        notice: {
          title: '维护公告',
          content: '系统正在进行日常维护，维护期间暂停所有服务。请稍后再试，感谢您的理解与支持！',
          type: 'info'
        },
        loading: false
      });
    }
  }
});
