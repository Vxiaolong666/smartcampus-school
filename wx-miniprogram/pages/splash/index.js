/**
 * 独立开屏页（小程序启动入口）
 *
 * 流程：
 * 1. 首帧显示品牌启动页，避免首页内容先渲染再被广告盖住；
 * 2. 并行拉取开屏广告（带缓存 / 冷却 / 1.2s 超时）；
 * 3. 有广告 → 图片就绪后开始倒计时，可跳过或点击跳转；
 * 4. 无广告 / 冷却中 / 超时 / 请求失败无缓存 → 立即 reLaunch 到首页。
 *
 * 与 UniApp 端 pages/splash/index.vue 逻辑保持一致。
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

const CACHE_KEY = 'splash_ad_cache';
const SHOWN_KEY = 'splash_ad_shown_at';
const COOLDOWN = 30 * 60 * 1000; // 同会话刷新 30 分钟内不重复展示
const CACHE_TTL = 3 * 24 * 60 * 60 * 1000;

function splashSessionHandled() {
  try {
    const app = getApp();
    return !!(app && app.globalData && app.globalData.__splashSession);
  } catch (e) {
    return false;
  }
}

function markSplashSession() {
  try {
    const app = getApp();
    if (app) {
      app.globalData = app.globalData || {};
      app.globalData.__splashSession = true;
    }
  } catch (e) {}
}

Page({
  data: {
    image: '',
    url: '',
    name: '智慧校园',
    countdown: 3,
    // 广告总时长（秒）：用于底部进度条动画时长
    duration: 3,
    // 跳过按钮距顶部距离（px）：按状态栏高度动态设置，适配刘海屏
    skipTop: 60
  },

  onLoad() {
    this._closed = false;
    try {
      const info = wx.getWindowInfo ? wx.getWindowInfo() : wx.getSystemInfoSync();
      this.setData({ skipTop: (info.statusBarHeight || 20) + 12 });
    } catch (e) {}
    this.loadAd();
  },

  onUnload() {
    this.stopCountdown();
  },

  onTap() {
    const url = this.data.url;
    if (!url) {
      this.goHome();
      return;
    }
    if (this._closed) return;
    this._closed = true;
    this.stopCountdown();
    wx.reLaunch({
      url: '/pages/white-home/index',
      success: () => {
        // 站外链接：小程序无法直接打开，复制链接提示
        if (/^https?:\/\//i.test(url)) {
          wx.setClipboardData({
            data: url,
            success: () => wx.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' }),
            fail: () => wx.showToast({ title: '复制失败，请在浏览器中打开链接', icon: 'none' })
          });
          return;
        }
        wx.navigateTo({ url, fail: () => wx.switchTab({ url }) });
      }
    });
  },

  noop() {},

  async loadAd() {
    // 维护状态优先：维护中直接进入维护页，不展示开屏广告
    const maintaining = await this.checkMaintenance();
    if (maintaining) return;

    // 同一会话内刷新且未过冷却期 → 不再展示
    if (splashSessionHandled()) {
      let lastShown = 0;
      try { lastShown = wx.getStorageSync(SHOWN_KEY) || 0; } catch (e) {}
      if (lastShown && (Date.now() - lastShown) < COOLDOWN) {
        this.goHome();
        return;
      }
    }

    let cached = null;
    try { cached = wx.getStorageSync(CACHE_KEY) || null; } catch (e) {}

    try {
      // 最多等 1.2s：接口过慢时不再让用户干等，直接进首页
      const res = await this.withTimeout(api.getSplashAd('wx'), 1200);
      const d = res && res.data;
      if (!d || !(d.image_url || d.image)) {
        try { wx.removeStorageSync(CACHE_KEY); } catch (e) {}
        this.goHome();
        return;
      }
      let sec = parseInt(d.duration, 10);
      if (isNaN(sec) || sec < 1) sec = 3;
      const config = {
        image: d.image_url || d.image,
        localImage: '',
        url: d.url || '',
        duration: sec,
        name: d.site_name || '智慧校园',
        ts: Date.now()
      };
      this.showAd(config);
      try { wx.setStorageSync(CACHE_KEY, config); } catch (e) {}
      this.cacheImage(config);
    } catch (e) {
      if (this._closed) return;
      // 接口失败（非超时）→ 用缓存兜底；超时则直接进首页
      const timedOut = e && e.message === 'splash timeout';
      if (!timedOut && this.isCacheValid(cached)) {
        this.showAd(cached);
      } else {
        this.goHome();
      }
    }
  },

  // 维护开关查询：开启时直接跳维护页（查询失败/超时视为未维护，不阻塞启动）
  async checkMaintenance() {
    try {
      const res = await this.withTimeout(feature.checkMaintenanceSwitch(), 1000);
      if (res && res.enabled) {
        this.goMaintenance();
        return true;
      }
    } catch (e) {}
    return false;
  },

  goMaintenance() {
    if (this._closed) return;
    this._closed = true;
    this.stopCountdown();
    setTimeout(() => {
      wx.reLaunch({ url: '/pages/maintenance/index' });
    }, 0);
  },

  showAd(config) {
    if (this._closed) return;
    let sec = parseInt(config.duration, 10);
    if (isNaN(sec) || sec < 1) sec = 3;
    this.setData({
      image: config.localImage || config.image,
      url: config.url || '',
      name: config.name || '智慧校园',
      countdown: sec,
      duration: sec
    });
    this.startCountdown(sec);
    try { wx.setStorageSync(SHOWN_KEY, Date.now()); } catch (e) {}
    markSplashSession();
  },

  // 缓存是否可用（有图片且未过期）
  isCacheValid(c) {
    if (!c || !(c.localImage || c.image)) return false;
    if (c.ts && (Date.now() - c.ts) > CACHE_TTL) return false;
    return true;
  },

  // 把开屏图片保存到本地，供离线/加速展示（best effort）
  cacheImage(config) {
    try {
      wx.downloadFile({
        url: config.image,
        success: (res) => {
          if (!res || res.statusCode !== 200 || !res.tempFilePath) return;
          wx.saveFile({
            tempFilePath: res.tempFilePath,
            success: (s) => {
              if (!s || !s.savedFilePath) return;
              config.localImage = s.savedFilePath;
              try { wx.setStorageSync(CACHE_KEY, config); } catch (e) {}
            }
          });
        }
      });
    } catch (e) {}
  },

  withTimeout(promise, ms) {
    return Promise.race([
      promise,
      new Promise((resolve, reject) => {
        setTimeout(() => reject(new Error('splash timeout')), ms);
      })
    ]);
  },

  startCountdown(sec) {
    this.stopCountdown();
    this.splashTimer = setInterval(() => {
      const n = (this.data.countdown || 0) - 1;
      if (n <= 0) {
        this.goHome();
      } else {
        this.setData({ countdown: n });
      }
    }, 1000);
  },

  stopCountdown() {
    if (this.splashTimer) {
      clearInterval(this.splashTimer);
      this.splashTimer = null;
    }
  },

  // 进入首页（reLaunch：开屏页不留在页面栈）
  goHome() {
    if (this._closed) return;
    this._closed = true;
    this.stopCountdown();
    // 延迟一帧再跳转，避免在 onLoad 同步阶段直接 reLaunch
    setTimeout(() => {
      wx.reLaunch({ url: '/pages/white-home/index' });
    }, 0);
  }
});
