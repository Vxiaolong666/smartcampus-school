/**
 * 公告提示组件
 * 支持多种类型的提示信息展示，可关闭，点击可查看详情
 */
Component({
  // 组件属性定义
  properties: {
    content: {
      type: String,
      value: '',          // 公告内容
      description: '公告内容文本'
    },
    type: {
      type: String,
      value: 'info',      // 公告类型：info/success/warning/danger
      description: '公告类型'
    },
    closable: {
      type: Boolean,
      value: false,       // 是否可关闭
      description: '是否显示关闭按钮'
    }
  },

  // 组件内部数据
  data: {
    visible: true,                    // 是否显示
    typeClass: 'announcement-info',   // 类型样式类名
    icon: '/assets/wxmp-info.svg'     // 图标路径
  },

  // 数据监听器
  observers: {
    // 监听类型变化，更新样式类和图标
    'type': function(type) {
      const typeClasses = {
        'info': 'announcement-info',
        'success': 'announcement-success',
        'warning': 'announcement-warning',
        'danger': 'announcement-danger'
      };
      
      const icons = {
        'info': '/assets/wxmp-info.svg',
        'success': '/assets/wxmp-check.svg',
        'warning': '/assets/wxmp-warning.svg',
        'danger': '/assets/wxmp-error.svg'
      };
      
      this.setData({
        typeClass: typeClasses[type] || 'announcement-info',
        icon: icons[type] || icons['info']
      });
    }
  },

  // 组件方法定义
  methods: {
    // 关闭公告，隐藏组件并触发close事件
    onClose() {
      this.setData({ visible: false });
      this.triggerEvent('close');
    },

    // 点击公告内容，弹窗展示完整公告内容，提供联系客服入口
    onTap() {
      const content = this.data.content;
      if (!content) return;
      wx.showModal({
        title: '公告',
        content: content,
        showCancel: true,
        cancelText: '联系客服',
        confirmText: '我知道了',
        success: (res) => {
          if (res.cancel) {
            const guide = this.selectComponent('#contactGuide');
            if (guide) guide.open();
          }
        }
      });
    },
    
    // 显示公告
    show() {
      this.setData({ visible: true });
    },
    
    // 隐藏公告
    hide() {
      this.setData({ visible: false });
    }
  }
});