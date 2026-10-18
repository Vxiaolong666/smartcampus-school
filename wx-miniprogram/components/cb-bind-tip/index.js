/**
 * 统一的「未绑定宿舍 / 未绑定教务账号」提示遮罩
 * 规范：图标 + 标题 + 说明 + 前往绑定按钮，与 uniapp 端保持一致
 */
Component({
  properties: {
    // academic=教务账号，dorm=宿舍
    type: {
      type: String,
      value: 'academic'
    },
    // 自定义标题/说明，不传则使用统一默认文案
    title: {
      type: String,
      value: ''
    },
    desc: {
      type: String,
      value: ''
    },
    btnText: {
      type: String,
      value: '前往绑定'
    },
    // 是否展示前往绑定按钮
    showBtn: {
      type: Boolean,
      value: true
    },
    compact: {
      type: Boolean,
      value: false
    }
  },

  data: {
    tipIcon: '/assets/wxmp-book-gray.svg',
    tipTitle: '请先绑定教务账号',
    tipDesc: '绑定后可查看课程、成绩与学籍信息'
  },

  lifetimes: {
    attached() {
      this.updateTip();
    }
  },

  observers: {
    'type, title, desc': function () {
      this.updateTip();
    }
  },

  methods: {
    updateTip() {
      const isDorm = this.data.type === 'dorm';
      const title = this.data.title;
      const desc = this.data.desc;

      this.setData({
        tipIcon: isDorm ? '/assets/wxmp-home2-gray.svg' : '/assets/wxmp-book-gray.svg',
        tipTitle: title || (isDorm ? '请先绑定宿舍' : '请先绑定教务账号'),
        tipDesc: desc || (isDorm ? '绑定宿舍后可查看电费余额与用电统计' : '绑定后可查看课程、成绩与学籍信息')
      });
    },

    onBind() {
      const url = this.data.type === 'dorm' ? '/pages/dormitory-bind/index' : '/pages/academic-bind/index';
      wx.navigateTo({ url });
      this.triggerEvent('bind');
    }
  }
});
