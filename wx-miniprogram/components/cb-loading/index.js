/**
 * 统一加载占位组件
 * - 圆环 1s/圈旋转，每转完一圈自动换色（青 → 蓝 → 紫）
 * - 主文案 + 副标题成对轮换，切换时错峰入场，观感更灵动
 * - 加载超时后切换为「加载较慢」提示
 * 与 uniapp 端（components/cb-loading）保持完全一致
 */
const LOADING_TIPS = [
  { text: '加载中...', sub: '正在连接校园服务' },
  { text: '数据飞奔赶来中...', sub: '正在为你整理最新数据' },
  { text: '马上就好...', sub: '只差最后一步啦' },
  { text: '再等一下下...', sub: '网络有点小忙，请稍候' }
];
const SLOW_TIP = {
  text: '当前加载较慢，正在全力加载中...',
  sub: '已等待较久，可检查网络后重试'
};

Component({
  properties: {
    // 指定后固定展示该文案，不再自动轮换
    text: {
      type: String,
      value: ''
    },
    // 副标题（配合 text 使用；不传则跟随轮换文案）
    subText: {
      type: String,
      value: ''
    },
    // 副标题轮换列表（传入后即使固定了 text，副标题也会依次轮换）
    subTexts: {
      type: Array,
      value: []
    },
    // 是否展示副标题
    showSub: {
      type: Boolean,
      value: true
    },
    // 内嵌在卡片内时使用更紧凑的间距与字号
    compact: {
      type: Boolean,
      value: false
    },
    // 强调场景（如 AI 分析弹窗）：圆环与文字整体放大
    large: {
      type: Boolean,
      value: false
    },
    // 是否自动轮换文案
    rotate: {
      type: Boolean,
      value: true
    },
    // 超过该时长（毫秒）后提示加载较慢，传 0 关闭
    slowAfter: {
      type: Number,
      value: 8000
    },
    slowText: {
      type: String,
      value: ''
    },
    slowSubText: {
      type: String,
      value: ''
    }
  },

  data: {
    displayText: '',
    displaySub: '',
    flipA: true,
    flipSubA: true,
    // 首个文案直接展示，不做入场动画
    animate: false,
    subAnimate: false,
    slow: false
  },

  lifetimes: {
    attached() {
      this.startTextLoop();
    },
    detached() {
      this.stopTextLoop();
    }
  },

  methods: {
    startTextLoop() {
      // 指定了固定主文案：主文案保持不动，副标题可按 subTexts 依次轮换
      if (this.data.text) {
        const subs = (this.data.subTexts || []).filter(s => !!s);
        this._subIndex = 0;
        this.setData({
          displayText: this.data.text,
          displaySub: subs.length ? subs[0] : this.data.subText
        });

        if (subs.length > 1) {
          this._textTimer = setInterval(() => {
            if (this.data.slow) return;
            this._subIndex = (this._subIndex + 1) % subs.length;
            this.switchSub(subs[this._subIndex]);
          }, 2400);
        }

        if (this.data.slowAfter > 0) {
          this._slowTimer = setTimeout(() => {
            this.switchText(
              this.data.slowText || SLOW_TIP.text,
              this.data.slowSubText || SLOW_TIP.sub,
              true
            );
          }, this.data.slowAfter);
        }
        return;
      }

      this.setData({
        displayText: LOADING_TIPS[0].text,
        displaySub: LOADING_TIPS[0].sub
      });

      if (this.data.rotate) {
        this._textTimer = setInterval(() => {
          if (this.data.slow) return;
          this._textIndex = ((this._textIndex || 0) + 1) % LOADING_TIPS.length;
          const tip = LOADING_TIPS[this._textIndex];
          this.switchText(tip.text, tip.sub);
        }, 1600);
      }

      if (this.data.slowAfter > 0) {
        this._slowTimer = setTimeout(() => {
          this.switchText(
            this.data.slowText || SLOW_TIP.text,
            this.data.slowSubText || SLOW_TIP.sub,
            true
          );
        }, this.data.slowAfter);
      }
    },

    // 换整组文案（主文案 + 副标题）时来回切换动画类名，保证每次切换都有入场动画
    switchText(text, sub, slow) {
      this.setData({
        displayText: text,
        displaySub: sub || '',
        flipA: !this.data.flipA,
        flipSubA: !this.data.flipSubA,
        animate: true,
        subAnimate: true,
        slow: slow === true ? true : this.data.slow
      });
    },

    // 只轮换副标题：主文案保持不动，避免固定标题反复重播入场动画
    switchSub(sub) {
      this.setData({
        displaySub: sub || '',
        flipSubA: !this.data.flipSubA,
        subAnimate: true
      });
    },

    stopTextLoop() {
      if (this._textTimer) {
        clearInterval(this._textTimer);
        this._textTimer = null;
      }
      if (this._slowTimer) {
        clearTimeout(this._slowTimer);
        this._slowTimer = null;
      }
    }
  }
});
