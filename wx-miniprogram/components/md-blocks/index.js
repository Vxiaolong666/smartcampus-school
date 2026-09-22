/**
 * 正文块渲染组件（公告 / 协议通用）
 * ------------------------------------------------------------------
 * 渲染 utils/markdown.js 的 markdownToBlocks 产出的三类块：
 *  - { type:'html', html }        → rich-text 渲染
 *  - { type:'image', src, alt }   → 原生 image，点击触发 preview 事件（页面负责放大）
 *  - { type:'details', title, titleHtml, open, blocks }
 *        → 原生可折叠区块（rich-text 不支持 <details> 与折叠交互），
 *          内部同样支持 文本 / 图片 块；默认展开状态取 <details open>
 */
Component({
  properties: {
    blocks: {
      type: Array,
      value: [],
      observer: 'initOpen'
    }
  },

  data: {
    openMap: {}   // 折叠块展开状态（块 key → true/false）
  },

  methods: {
    // 内容变化时重置折叠状态（默认取 <details open>）
    initOpen(blocks) {
      const map = {};
      (blocks || []).forEach(function (b) {
        if (b && b.type === 'details') map[b.key] = !!b.open;
      });
      this.setData({ openMap: map });
    },

    // 展开 / 折叠
    toggle(e) {
      const key = e.currentTarget.dataset.key;
      const map = Object.assign({}, this.data.openMap);
      map[key] = !map[key];
      this.setData({ openMap: map });
    },

    // 正文图片点击：交给页面放大预览
    onPreview(e) {
      const src = e.currentTarget.dataset.src;
      if (src) this.triggerEvent('preview', { src: src });
    }
  }
});
