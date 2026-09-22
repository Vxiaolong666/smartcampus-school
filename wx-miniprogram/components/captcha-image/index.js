const api = require('../../utils/api.js');

/**
 * 图片验证码（小程序端）
 * ------------------------------------------------------------------
 * 图形由服务端 server-submit/captcha.php 生成（与官网登录的「图片验证码」兜底同一张图），
 * 点击图片可刷新。
 * 页面在提交前调用 check() 做本地校验，并用 getCode() 取值随表单一起提交。
 * 说明：腾讯云滑块（TCaptcha.js）依赖浏览器 DOM，小程序无法直接使用，故小程序端采用图片验证码。
 */
Component({
  properties: {
    /**
     * 表单风格：
     *  - 'default'：适配注册/忘记密码等页面（.form-item 自带左右内边距、输入框无边框）
     *  - 'form'   ：适配注销等页面（.form-item 无左右内边距、输入框为带边框的 .form-input）
     */
    variant: {
      type: String,
      value: 'default'
    }
  },

  data: {
    imgUrl: '',
    code: ''
  },

  lifetimes: {
    attached() {
      this.refresh();
    }
  },

  methods: {
    // 刷新验证码（加时间戳，避免图片被缓存复用）
    refresh() {
      this.setData({
        code: '',
        imgUrl: api.getFileUrl('/server-submit/captcha.php?t=' + Date.now())
      });
    },

    // 输入
    onInput(e) {
      this.setData({ code: e.detail.value });
    },

    // 提交前校验：由页面调用（4 位字母或数字，与后端出图规则一致）
    check() {
      const code = String(this.data.code || '').trim();
      if (!code) return { ok: false, msg: '请输入图片验证码' };
      if (!/^[A-Za-z0-9]{4}$/.test(code)) return { ok: false, msg: '图片验证码为4位字母或数字' };
      return { ok: true, msg: '' };
    },

    // 取验证码（提交表单时一并发送）
    getCode() {
      return String(this.data.code || '').trim();
    }
  }
});
