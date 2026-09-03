/**
 * 自定义导航栏组件
 * 适配不同设备的状态栏高度，支持自定义返回按钮
 */
Component({
  // 组件属性定义
  properties: {
    showBack: {
      type: Boolean,
      value: false,      // 是否显示返回按钮
      description: '是否显示返回按钮'
    },
    backColor: {
      type: String,
      value: '#fff',     // 返回按钮图标颜色
      description: '返回按钮图标颜色'
    }
  },

  // 组件内部数据
  data: {
    statusBarHeight: 0,   // 状态栏高度
    menuBarHeight: 0      // 导航栏内容区域高度
  },

  // 组件方法定义
  methods: {
    // 处理返回按钮点击事件
    // 如果有上一页则返回，否则跳转到首页
    handleBack() {
      const pages = getCurrentPages();
      if (pages.length > 1) {
        wx.navigateBack();
      } else {
        wx.switchTab({
          url: '/pages/white-home/index'
        });
      }
    }
  },

  // 组件生命周期钩子
  lifetimes: {
    // 组件挂载时执行
    // 获取系统状态栏和菜单按钮信息，计算导航栏高度
    attached() {
      const windowInfo = wx.getWindowInfo();
      const menuBouding = wx.getMenuButtonBoundingClientRect();
      this.setData({
        statusBarHeight: windowInfo.statusBarHeight,
        menuBarHeight: menuBouding.height + (menuBouding.top - windowInfo.statusBarHeight) * 2
      });
    }
  }
});