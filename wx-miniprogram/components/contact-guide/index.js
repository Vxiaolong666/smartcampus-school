const api = require('../../utils/api.js');
const contactModalBus = require('../../utils/contact-modal.js');

/**
 * 联系客服弹窗（小程序端）
 * 一级选择使用微信原生 wx.showActionSheet（在线客服 / 其他联系方式）；
 * 选择「在线客服」弹出仅含客服按钮的小弹窗，选择「其他联系方式」弹出联系方式列表（视觉同 uniapp contact-modal）。
 * 页面通过 this.selectComponent('#contactGuide').open() 打开。
 */
Component({
  data: {
    show: false,
    view: 'online',   // online=在线客服按钮  detail=其他联系方式
    loading: false,
    items: [],
    worktime: ''      // 客服在线时间（取自后台「官网配置」）
  },

  // 挂载/卸载时登记到全局表：便于 utils/feature.js 打开「当前页面」的联系客服弹窗
  attached() {
    contactModalBus.registerContactModal(this);
  },
  detached() {
    contactModalBus.unregisterContactModal(this);
  },

  methods: {
    // 一级选择：微信原生 ActionSheet
    // onClosed：联系方式弹窗关闭后的回调（如「功能暂未开放」流程里看完即离开当前页）
    open(onClosed) {
      this._onClosed = typeof onClosed === 'function' ? onClosed : null;
      wx.showActionSheet({
        itemList: ['联系在线客服', '查看其他联系方式'],
        success: (res) => {
          if (res.tapIndex === 0) {
            this.openOnline();
          } else if (res.tapIndex === 1) {
            this.openDetail();
          }
        },
        fail: () => {
          // 用户直接关闭「在线客服 / 查看其他联系方式」选择器：与关闭联系方式弹窗一致，触发返回
          this._fireClosed();
        }
      });
    },

    // 在线客服：弹出含客服按钮的弹窗，并展示客服在线时间
    openOnline() {
      this.setData({ show: true, view: 'online', loading: false, items: [], worktime: '' });
      api.getContactInfo()
        .then((res) => {
          const info = (res && res.data) || null;
          if (info && info.worktime) {
            this.setData({ worktime: info.worktime });
          }
        })
        .catch(() => {});
    },

    // 其他联系方式：拉取后台维护的联系方式
    openDetail() {
      this.setData({ show: true, view: 'detail', loading: true, items: [] });
      api.getContactInfo()
        .then((res) => {
          const info = (res && res.data) || null;
          const lines = [];
          if (info) {
            if (info.phone) lines.push('联系电话：' + info.phone);
            if (info.email) lines.push('电子邮箱：' + info.email);
            if (info.qq) lines.push('QQ：' + info.qq);
            if (info.worktime) lines.push('工作时间：' + info.worktime);
            if (info.address) lines.push('联系地址：' + info.address);
            if (info.desc) lines.push('说明：' + info.desc);
          }
          if (!lines.length) {
            lines.push('暂未配置其他联系方式，请通过在线客服与我们取得联系。');
          }
          this.setData({ items: this.buildItems(lines), loading: false });
        })
        .catch(() => {
          this.setData({
            items: [{ label: '', value: '暂未配置其他联系方式，请通过在线客服与我们取得联系。', labelClass: '' }],
            loading: false
          });
        });
    },

    // 关闭弹窗
    close() {
      this.setData({ show: false });
      this._fireClosed();
    },

    // 触发一次性关闭回调（关闭弹窗 / 直接关闭选择器时调用）
    _fireClosed() {
      const onClosed = this._onClosed;
      this._onClosed = null;
      if (onClosed) onClosed();
    },

    // 阻止事件冒泡/占位
    noop() {},

    // 点击「进入客服会话」：由 open-type="contact" 打开会话，同时收起本弹窗
    onOnline() {
      this.setData({ show: false });
    },

    // 将 "标签：内容" 解析为 { label, value, labelClass }，无冒号的行作为纯提示
    buildItems(lines) {
      return (lines || []).map((line) => {
        const str = String(line == null ? '' : line);
        let idx = str.indexOf('：');
        if (idx === -1) idx = str.indexOf(':');
        if (idx === -1) return { label: '', value: str, labelClass: '' };
        const label = str.slice(0, idx);
        return { label: label, value: str.slice(idx + 1), labelClass: this.labelClass(label) };
      });
    },

    // 根据标签关键字匹配配色
    labelClass(label) {
      const l = String(label || '');
      if (/地址/.test(l)) return 'is-address';
      if (/电话|手机/.test(l)) return 'is-phone';
      if (/邮箱|邮件|email/i.test(l)) return 'is-mail';
      if (/qq/i.test(l)) return 'is-qq';
      if (/微信|wechat/i.test(l)) return 'is-wechat';
      if (/时间/.test(l)) return 'is-time';
      if (/说明|备注/.test(l)) return 'is-desc';
      return 'is-default';
    }
  }
});
