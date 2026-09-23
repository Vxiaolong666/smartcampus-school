/**
 * AI 助手页面（与 uniapp 端 pages/ai-chat/index 完全一致的交互）
 * 真流式：enableChunked 分块接收 NDJSON 行协议，边收边渲染 Markdown
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const markdownToHtml = require('../../utils/markdown.js');

const SYSTEM_PROMPT = '你是“智慧校园”AI 助手，帮助学生解答校园生活、学习规划、教务查询（成绩/课表）、宿舍用电等方面的问题。回答简洁友好、条理清晰，可适当使用 Markdown。展示数据时使用友好的中文名称（如宿舍名称、“第X节/上午第1节/下午第2节”等），不要展示系统内部的编码或编号（如宿舍 ID、classTime 等）。';

const SUGGESTIONS = [
  '我最近的成绩怎么样？',
  '我的宿舍用电情况如何？',
  '怎么查询我的课程安排？',
  '校园卡怎么充值？'
];

let uidSeed = 1;

Page({
  data: {
    messages: [
      { role: 'system', content: SYSTEM_PROMPT, _uid: 0 }
    ],
    inputText: '',
    streaming: false,
    scrollTarget: 'msg-tail',
    hasConversation: false,
    // 思考 / 版本 / 编辑状态
    editingUid: -1,
    editText: '',
    // 模型
    models: [],
    currentModel: '',
    showModelMenu: false,
    modelMenuBottom: '260rpx',
    deepThink: true,
    suggestions: SUGGESTIONS,
    // 后台「微信小程序端AI功能」开关：关闭时整页遮罩提示且不发起 AI 请求
    featureDisabled: false,
    // 兜底文案：后台未配置公告时展示
    featureTip: '微信小程序不支持该功能，请前往网页或APP端使用',
    // 动态图标：后台公告类型对应的图标（见 feature.getAnnouncementIcon）；为空时用内置红叉兜底
    featureIcon: ''
  },

  onLoad() {
    this._streamAbort = null;
    this._pageAborted = false;
    this._modelsLoaded = false;
    this._entered = false;
    this.checkAiFeature();
  },

  // 页面每次显示都重新校验开关（后台调整后返回本页即时生效）
  onShow() {
    if (this._entered) {
      this.checkAiFeature();
    } else {
      this._entered = true; // 首次显示由 onLoad 负责校验
    }
  },

  // 校验后台「微信小程序端AI功能」开关：关闭时展示遮罩
  checkAiFeature() {
    feature.checkFeature('wx_ai_chat')
      .then((res) => {
        const disabled = !!(res && res.enabled === false);
        // 文案与图标都动态取自后台公告：
        //  - 文案：公告内容，未配置时兜底为内置提示
        //  - 图标：公告类型对应的图标（feature.getAnnouncementIcon），未配置公告时置空 → 用内置红叉兜底
        const ann = res && res.announcement;
        const hasAnn = !!(ann && ann.content);
        this.setData({
          featureDisabled: disabled,
          featureTip: hasAnn ? ann.content : '微信小程序不支持该功能，请前往网页或APP端使用',
          featureIcon: hasAnn ? feature.getAnnouncementIcon(ann.type) : ''
        });
        if (!disabled) this.loadModelsOnce();
      })
      .catch(() => {
        // 开关接口异常不阻断功能（与其它功能开关的默认行为一致）
        this.loadModelsOnce();
      });
  },

  loadModelsOnce() {
    if (this._modelsLoaded) return;
    this._modelsLoaded = true;
    this.loadModels();
  },

  // ===== 工具 =====
  findMessage(uid) {
    return this.data.messages.find(m => m._uid === uid);
  },

  findIndex(uid) {
    return this.data.messages.findIndex(m => m._uid === uid);
  },

  updateMessage(uid, patch) {
    const idx = this.findIndex(uid);
    if (idx < 0) return;
    const data = {};
    Object.keys(patch).forEach(k => {
      data['messages[' + idx + '].' + k] = patch[k];
    });
    this.setData(data);
  },

  scrollToBottom() {
    // 先置空再赋值，保证相同目标也能再次触发滚动
    this.setData({ scrollTarget: '' }, () => {
      setTimeout(() => this.setData({ scrollTarget: 'msg-tail' }), 60);
    });
  },

  // ===== 模型 =====
  loadModels() {
    api.getAiModels()
      .then(res => {
        const list = (res.data && res.data.models) || [];
        const def = (res.data && res.data.default) || '';
        if (def && list.indexOf(def) === -1) list.unshift(def);
        this.setData({ models: list, currentModel: def || (list[0] || '') });
      })
      .catch(() => {
        this.setData({ models: [], currentModel: '' });
      });
  },

  openModelMenu() {
    if (this.data.showModelMenu) {
      this.setData({ showModelMenu: false });
      return;
    }
    try {
      const sys = wx.getSystemInfoSync();
      wx.createSelectorQuery().in(this)
        .select('.chat-input')
        .boundingClientRect(rect => {
          const patch = {};
          if (rect && typeof rect.top === 'number') {
            // 菜单底部 = 视口高度 - 输入区顶部 + 12px 间距
            patch.modelMenuBottom = Math.max(120, sys.windowHeight - rect.top + 12) + 'px';
          }
          patch.showModelMenu = true;
          this.setData(patch);
        })
        .exec();
    } catch (e) {
      this.setData({ showModelMenu: true });
    }
  },

  closeModelMenu() {
    this.setData({ showModelMenu: false });
  },

  selectModel(e) {
    this.setData({ currentModel: e.currentTarget.dataset.m, showModelMenu: false });
  },

  toggleDeep() {
    this.setData({ deepThink: !this.data.deepThink });
  },

  noop() {},

  // ===== 发送 =====
  onSendBtnTap() {
    if (this.data.streaming) {
      this.stop();
    } else {
      this.send();
    }
  },

  onInput(e) {
    this.setData({ inputText: e.detail.value });
  },

  onSuggestTap(e) {
    if (this.data.streaming) return;
    this.setData({ inputText: e.currentTarget.dataset.q }, () => this.send());
  },

  send() {
    const text = (this.data.inputText || '').trim();
    if (!text || this.data.streaming) return;

    const messages = this.data.messages.concat([
      { role: 'user', content: text, _uid: uidSeed++ }
    ]);
    this.setData({ messages, inputText: '', hasConversation: true });
    this.requestAi();
  },

  // 构建 OpenAI 格式上下文（canceled / 空回复 / system 不计入）
  buildContext() {
    return this.data.messages
      .filter(m => (m.role === 'user' || m.role === 'assistant') && m.content && !m.canceled)
      .map(m => ({ role: m.role, content: m.content }));
  },

  // 请求 AI 回复。reuseUid 传入已有 assistant 消息 uid 用于重新生成（保留历史版本）
  requestAi(reuseUid) {
    // 开关关闭时不发起任何 AI 请求（页面已被遮罩覆盖，此处为兜底）
    if (this.data.featureDisabled) return;
    let m = reuseUid ? this.findMessage(reuseUid) : null;
    if (m) {
      this.updateMessage(m._uid, {
        content: '',
        contentHtml: '',
        canceled: false,
        thinkState: 'thinking',
        generating: true
      });
    } else {
      m = {
        role: 'assistant',
        content: '',
        contentHtml: '',
        _uid: uidSeed++,
        versions: [],
        verIdx: -1,
        thinkState: 'thinking',
        thinkSeconds: 0,
        thinkOpen: false,
        canceled: false,
        generating: true
      };
      this.setData({ messages: this.data.messages.concat([m]) });
    }
    m.thinkStart = Date.now();
    this._pageAborted = false;
    this.setData({ streaming: true, editingUid: -1, showModelMenu: false });
    this.scrollToBottom();

    const watchdog = setTimeout(() => {
      if (this.data.streaming) {
        this._pageAborted = true;
        if (this._streamAbort) this._streamAbort.abort();
      }
    }, 300000);

    const p = api.aiChatStream({
      messages: this.buildContext(),
      model: this.data.currentModel,
      onChunk: (delta) => {
        const cur = this.findMessage(m._uid);
        if (!cur) return;
        const patch = {};
        if (cur.thinkState === 'thinking') {
          patch.thinkState = 'done';
          patch.thinkSeconds = Math.max(1, Math.round((Date.now() - m.thinkStart) / 1000));
        }
        patch.content = (cur.content || '') + delta;
        patch.contentHtml = markdownToHtml(patch.content);
        if (cur.verIdx >= 0 && cur.versions && cur.versions[cur.verIdx] !== undefined) {
          const versions = cur.versions.slice();
          versions[cur.verIdx] = patch.content;
          patch.versions = versions;
        }
        this.updateMessage(m._uid, patch);
        this.scrollToBottom();
      },
      onDone: () => {
        clearTimeout(watchdog);
        const cur = this.findMessage(m._uid);
        const patch = { generating: false };
        this.setData({ streaming: false });
        if (this._pageAborted) {
          patch.canceled = true;
          patch.thinkState = cur && cur.content ? 'done' : '';
        } else if (cur && cur.content) {
          const versions = (cur.versions || []).concat([cur.content]);
          patch.versions = versions;
          patch.verIdx = versions.length - 1;
          patch.thinkState = 'done';
        } else {
          patch.thinkState = '';
        }
        if (cur && !cur.thinkSeconds && m.thinkStart) {
          patch.thinkSeconds = Math.max(1, Math.round((Date.now() - m.thinkStart) / 1000));
        }
        this.updateMessage(m._uid, patch);
        this.scrollToBottom();
      },
      onError: (msg) => {
        const cur = this.findMessage(m._uid);
        if (cur && !cur.content) {
          this.updateMessage(m._uid, {
            content: '出错了：' + msg,
            contentHtml: markdownToHtml('出错了：' + msg)
          });
        }
      }
    });
    this._streamAbort = p;
  },

  // 终止思考：保留已生成内容
  stop() {
    if (!this.data.streaming) return;
    this._pageAborted = true;
    if (this._streamAbort) this._streamAbort.abort();
  },

  // ===== 重新生成 / 版本 =====
  regen(e) {
    if (this.data.streaming) return;
    const uid = e.currentTarget.dataset.uid;
    const idx = this.findIndex(uid);
    if (idx < 0) return;
    // 截断该回复之后的所有消息
    const messages = this.data.messages.slice(0, idx + 1);
    this.setData({ messages });
    this.requestAi(uid);
  },

  switchVersion(e) {
    if (this.data.streaming) return;
    const { uid, dir } = e.currentTarget.dataset;
    const m = this.findMessage(uid);
    if (!m || !m.versions || !m.versions.length) return;
    const next = (m.verIdx || 0) + Number(dir);
    if (next < 0 || next >= m.versions.length) return;
    this.updateMessage(uid, { verIdx: next, content: m.versions[next], contentHtml: markdownToHtml(m.versions[next]) });
    this.scrollToBottom();
  },

  // ===== 编辑用户消息 =====
  startEdit(e) {
    if (this.data.streaming) return;
    const m = this.findMessage(e.currentTarget.dataset.uid);
    if (!m) return;
    this.setData({ editingUid: m._uid, editText: m.content });
  },

  onEditInput(e) {
    this.setData({ editText: e.detail.value });
  },

  cancelEdit() {
    this.setData({ editingUid: -1, editText: '' });
  },

  confirmEdit(e) {
    const text = (this.data.editText || '').trim();
    if (!text) {
      wx.showToast({ title: '内容不能为空', icon: 'none' });
      return;
    }
    if (this.data.streaming) return;
    const uid = e.currentTarget.dataset.uid;
    const idx = this.findIndex(uid);
    if (idx < 0) return;
    const messages = this.data.messages.slice(0, idx + 1);
    messages[idx].content = text;
    // 移除该消息之后的所有消息（原 AI 回复作废），并重新生成
    this.setData({ messages, editingUid: -1, editText: '' });
    this.requestAi();
  },

  // ===== 工具 =====
  copyText(e) {
    const m = this.findMessage(e.currentTarget.dataset.uid);
    if (!m || !m.content) return;
    wx.setClipboardData({
      data: m.content,
      success: () => wx.showToast({ title: '已复制', icon: 'none' }),
      fail: (err) => {
        const msg = (err && err.errMsg) || '';
        if (msg.indexOf('privacy') > -1 || msg.indexOf('auth') > -1) {
          wx.showToast({ title: '未授权，暂时无法复制', icon: 'none' });
        } else {
          wx.showToast({ title: '复制失败，请检查剪贴板权限', icon: 'none' });
        }
      }
    });
  },

  toggleThink(e) {
    const m = this.findMessage(e.currentTarget.dataset.uid);
    if (!m || m.thinkState !== 'done') return;
    this.updateMessage(m._uid, { thinkOpen: !m.thinkOpen });
  },

  clearChat() {
    if (this.data.streaming) return;
    wx.showModal({
      title: '提示',
      content: '确定要清空当前对话吗？',
      confirmText: '清空',
      cancelText: '取消',
      success: (res) => {
        if (!res.confirm) return;
        this.setData({
          messages: [{ role: 'system', content: SYSTEM_PROMPT, _uid: 0 }],
          hasConversation: false,
          editingUid: -1
        });
        this.scrollToBottom();
        wx.showToast({ title: '已清空', icon: 'none' });
      }
    });
  }
});
