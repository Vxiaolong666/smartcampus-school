<template>
  <view class="page-container">
    <!-- 后台「功能设置」关闭 UNIAPP 端 AI 对话开关（uniapp_ai_chat）时的遮罩：
         顶部导航一并盖住，覆盖范围到 tabBar 上方，仅底部 Tab 保持可用（可切换其它 Tab） -->
    <view v-if="featureDisabled" class="feature-disabled-mask">
      <!-- 图标与文案动态取自后台公告；未配置公告时用内置红叉 + 兜底文案 -->
      <view v-if="featureIcon" class="feature-disabled-icon feature-disabled-icon-plain">
        <image class="feature-disabled-icon-image" :src="featureIcon" mode="aspectFit"></image>
      </view>
      <view v-else class="feature-disabled-icon">
        <view class="feature-disabled-cross"></view>
      </view>
      <view class="feature-disabled-text">{{ featureTip }}</view>
    </view>

    <cb-nav-bar compact>
      <view class="nav-title">AI 助手</view>
      <view slot="right" class="nav-action" @click="clearChat">
        <text class="action-text">清空</text>
      </view>
    </cb-nav-bar>

    <view class="background-wrapper">
      <image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
      <view class="background-mask"></view>
    </view>

    <view class="content-wrapper">
      <!-- 功能开关校验中：顶部导航保留，其余内容统一用 loading 占位，避免先渲染对话再被遮罩覆盖 -->
      <view v-if="!featureReady" class="chat-loading">
        <cb-loading />
      </view>

      <scroll-view
        v-if="featureReady"
        class="chat-body"
        scroll-y
        :scroll-into-view="scrollTarget"
        :scroll-with-animation="true"
      >
        <!-- 欢迎区（未开始对话时显示） -->
        <view v-if="!hasConversation" class="welcome">
          <view class="welcome-avatar">AI</view>
          <view class="welcome-text">
            <view class="welcome-title">你好，我是智能助手</view>
            <view class="welcome-sub">我可以帮助你解决使用中遇到的问题~</view>
          </view>
        </view>

        <!-- 推荐问题 -->
        <view v-if="!hasConversation" class="suggest">
          <view class="suggest-label">你可以这样问我：</view>
          <view
            class="suggest-chip"
            v-for="(q, i) in suggestions"
            :key="i"
            @click="onSuggestTap(q)"
          >{{ q }}</view>
        </view>

        <!-- 消息列表 -->
        <view
          v-for="m in chatList"
          :key="m._uid"
          :id="'msg-' + m._uid"
          class="msg-row"
          :class="m.role === 'user' ? 'is-user' : 'is-ai'"
        >
          <!-- 用户消息 -->
          <template v-if="m.role === 'user'">
            <view class="msg-col">
              <view class="bubble bubble-user" :class="{ editing: editingUid === m._uid }">
                <view v-if="editingUid === m._uid" class="edit-card">
                  <textarea
                    class="edit-area"
                    v-model="editText"
                    :auto-height="true"
                    maxlength="-1"
                  ></textarea>
                  <view class="edit-actions">
                    <view class="edit-btn edit-btn-cancel" @click="cancelEdit">取消</view>
                    <view class="edit-btn edit-btn-save" @click="confirmEdit(m)">发送</view>
                  </view>
                </view>
                <text v-else class="bubble-text">{{ m.content }}</text>
              </view>
              <view class="msg-actions" v-if="editingUid !== m._uid">
                <view class="act-btn" @click="copyText(m.content)">
                  <image class="act-icon" src="/static/assets/wxmp-copy.svg" mode="aspectFit"></image>
                </view>
                <view class="act-btn" @click="startEdit(m)">
                  <image class="act-icon" src="/static/assets/wxmp-edit.svg" mode="aspectFit"></image>
                </view>
              </view>
            </view>
          </template>

          <!-- AI 消息 -->
          <template v-else>
            <view class="msg-avatar ai-avatar">AI</view>
            <view class="msg-col">
              <view class="ai-name">智能助手</view>

              <!-- 思考过程框 -->
              <view
                v-if="m.thinkState"
                class="think-box"
                :class="{ open: m.thinkOpen }"
                :data-state="m.thinkState"
                @click="toggleThink(m)"
              >
                <view class="think-head">
                  <view class="think-icon" :class="{ spinning: m.thinkState === 'thinking' }">AI</view>
                  <text class="think-text">{{ m.thinkState === 'thinking' ? '正在思考' : '思考已完成' }}</text>
                  <view v-if="m.thinkState === 'thinking'" class="think-dots">
                    <view class="dot"></view>
                    <view class="dot"></view>
                    <view class="dot"></view>
                  </view>
                  <text v-if="m.thinkState === 'done'" class="think-caret">›</text>
                </view>
                <view v-if="m.thinkState === 'done' && m.thinkOpen" class="think-body">
                  已完成对本问题的思考（用时 {{ m.thinkSeconds }} 秒）。
                </view>
              </view>

              <!-- 回复气泡 -->
              <msg-bubble v-if="m.content" :role="'assistant'" :content="m.content" />
              <view v-if="m.canceled" class="canceled-tip">已取消回答</view>

              <!-- 气泡操作 -->
              <view class="msg-actions" v-if="!m.generating">
                <template v-if="m.versions.length > 1">
                  <view class="act-btn" :class="{ disabled: m.verIdx <= 0 }" @click="switchVersion(m, -1)">
                    <image class="act-icon" src="/static/assets/wxmp-arrow-left.svg" mode="aspectFit"></image>
                  </view>
                  <text class="ver-indicator">{{ m.verIdx + 1 }}/{{ m.versions.length }}</text>
                  <view class="act-btn" :class="{ disabled: m.verIdx >= m.versions.length - 1 }" @click="switchVersion(m, 1)">
                    <image class="act-icon" src="/static/assets/wxmp-arrow-right.svg" mode="aspectFit"></image>
                  </view>
                </template>
                <view class="act-btn" @click="regen(m)">
                  <image class="act-icon" src="/static/assets/wxmp-regenerate.svg" mode="aspectFit"></image>
                </view>
                <view class="act-btn" @click="copyText(m.content)">
                  <image class="act-icon" src="/static/assets/wxmp-copy.svg" mode="aspectFit"></image>
                </view>
              </view>
              <view class="msg-actions" v-else>
                <view class="act-btn" @click="stop">
                  <image class="act-icon" src="/static/assets/wxmp-stop.svg" mode="aspectFit"></image>
                </view>
              </view>
            </view>
          </template>
        </view>

        <view class="chat-body-tail" id="msg-tail"></view>
      </scroll-view>

      <!-- 输入区 -->
      <view class="chat-input" v-if="featureReady">
        <view class="input-box">
          <textarea
            class="msg-input"
            v-model="inputText"
            :disabled="false"
            placeholder="问问 AI 关于课程、成绩、用电…"
            :auto-height="true"
            maxlength="-1"
            :adjust-position="true"
            confirm-type="send"
            @confirm="send"
          ></textarea>
          <view class="aic-toolbar">
            <view class="toolbar-left">
              <view class="aic-pill" :class="{ active: showModelMenu }" @click="openModelMenu">
                <text class="pill-text">{{ currentModel || '默认模型' }}</text>
                <text class="aic-caret">▾</text>
              </view>
              <view class="aic-pill" :class="{ active: deepThink }" @click="deepThink = !deepThink">
                <text>深度思考</text>
              </view>
            </view>
          </view>
        </view>
        <!-- 发送/终止按钮：终止时圆环在按钮外侧作边框旋转，按钮内部只留方块 -->
        <view class="send-btn-wrap">
          <view v-if="streaming" class="send-ring"></view>
          <button
            class="send-btn"
            :class="{ disabled: !streaming && !inputText.trim(), streaming }"
            @click="onSendBtnTap"
          >
            <image v-if="!streaming" class="send-icon" src="/static/assets/wxmp-send-white.svg" mode="aspectFit"></image>
            <view v-else class="send-stop"></view>
          </button>
        </view>

        <!-- 模型选择菜单 -->
        <view v-if="showModelMenu" class="model-mask" @click="showModelMenu = false">
          <view class="model-menu" :style="{ bottom: modelMenuBottom }" @click.stop>
            <view
              class="model-item"
              :class="{ active: m === currentModel }"
              v-for="m in models"
              :key="m"
              @click="selectModel(m)"
            >{{ m }}</view>
            <view v-if="!models.length" class="model-item active">默认模型</view>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from '../../utils/api';
import feature from '../../utils/feature.js';
import MsgBubble from './msg-bubble.vue';

const SYSTEM_PROMPT = '你是“智慧校园”AI 助手，帮助学生解答校园生活、学习规划、教务查询（成绩/课表）、宿舍用电等方面的问题。回答简洁友好、条理清晰，可适当使用 Markdown。展示数据时使用友好的中文名称（如宿舍名称、“第X节/上午第1节/下午第2节”等），不要展示系统内部的编码或编号（如宿舍 ID、classTime 等）。';

const SUGGESTIONS = [
  '我最近的成绩怎么样？',
  '我的宿舍用电情况如何？',
  '怎么查询我的课程安排？',
  '校园卡怎么充值？'
];

let uidSeed = 1;

export default {
  components: { MsgBubble },
  data() {
    return {
      messages: [
        { role: 'system', content: SYSTEM_PROMPT, _uid: 0 }
      ],
      inputText: '',
      streaming: false,
      scrollTarget: 'msg-tail',
      // 思考 / 版本 / 编辑状态
      streamAbort: null,
      pageAborted: false,
      editingUid: -1,
      editText: '',
      // 模型
      models: [],
      currentModel: '',
      showModelMenu: false,
      modelMenuBottom: '260rpx',
      deepThink: true,
      suggestions: SUGGESTIONS,
      // 后台「功能设置」开关（uniapp_ai_chat）：关闭时展示整页遮罩，文案 / 图标取后台公告
      featureDisabled: false,
      featureTip: '',
      featureIcon: '',
      // 功能开关是否已校验完成（未完成时整块内容用 loading 占位，避免闪现对话界面）
      featureReady: false
    };
  },

  computed: {
    // 过滤 system（仅提示词，不渲染）
    chatList() {
      return this.messages.filter(m => m.role !== 'system');
    },
    hasConversation() {
      return this.messages.some(m => m.role === 'user');
    }
  },

  onLoad() {
    this.checkAiFeature();
  },

  // 页面每次显示都重新校验开关（后台调整后返回本页即时生效；首次显示由 onLoad 校验）
  onShow() {
    if (this._entered) {
      this.checkAiFeature();
    } else {
      this._entered = true;
    }
  },

  methods: {
    // 校验后台「UNIAPP 端 AI 对话」开关（uniapp_ai_chat）：
    // 开关关闭时展示整页遮罩（与小程序端一致），开启时才加载模型列表；
    // 后台尚未创建该标识时 feature.checkFeature 默认返回启用，不影响老环境。
    checkAiFeature() {
      feature.checkFeature('uniapp_ai_chat').then((res) => {
        const disabled = !!(res && res.enabled === false);
        const ann = res && res.announcement;
        const hasAnn = !!(ann && ann.content);
        this.featureDisabled = disabled;
        this.featureTip = hasAnn ? ann.content : 'AI 对话暂未开放，请稍后再试';
        this.featureIcon = hasAnn ? feature.getAnnouncementIcon(ann.type) : '';
        if (disabled) return;
        // 功能可用但未登录：与「课程」Tab 一致，先说明登录用途再跳登录页。
        // tab 页 onLoad 只执行一次，这里每次进入都会校验，保证再次点「AI 助手」仍会提示。
        if (!api.isLoggedIn()) {
          api.goLoginWithTip({ mode: 'redirect' });
          return;
        }
        if (!this._modelsLoaded) {
          this._modelsLoaded = true;
          this.loadModels();
        }
      }).catch(() => {}).finally(() => {
        this.featureReady = true;
      });
    },

    toggleThink(m) {
      if (m.thinkState !== 'done') return;
      m.thinkOpen = !m.thinkOpen;
    },

    scrollToBottom() {
      this.$nextTick(() => {
        this.scrollTarget = '';
        setTimeout(() => { this.scrollTarget = 'msg-tail'; }, 60);
      });
    },

    // ===== 模型 =====
    async loadModels() {
      try {
        const res = await api.getAiModels();
        const list = (res.data && res.data.models) || [];
        const def = (res.data && res.data.default) || '';
        if (def && list.indexOf(def) === -1) list.unshift(def);
        this.models = list;
        this.currentModel = def || (list[0] || '');
      } catch (e) {
        this.models = [];
        this.currentModel = '';
      }
    },
    selectModel(m) {
      this.currentModel = m;
      this.showModelMenu = false;
    },

    // 打开模型菜单：实测输入区顶部位置，把菜单精确钉在其上方
    openModelMenu() {
      if (this.showModelMenu) {
        this.showModelMenu = false;
        return;
      }
      try {
        const sys = uni.getSystemInfoSync();
        const query = uni.createSelectorQuery().in(this);
        query.select('.chat-input').boundingClientRect(rect => {
          if (rect && typeof rect.top === 'number') {
            // 菜单底部 = 视口高度 - 输入区顶部 + 12px 间距
            this.modelMenuBottom = Math.max(120, sys.windowHeight - rect.top + 12) + 'px';
          }
          this.showModelMenu = true;
        }).exec();
      } catch (e) {
        this.showModelMenu = true;
      }
    },

    // ===== 发送 =====
    onSendBtnTap() {
      if (this.streaming) {
        this.stop();
      } else {
        this.send();
      }
    },

    onSuggestTap(q) {
      if (this.streaming) return;
      this.inputText = q;
      this.send();
    },

    send() {
      const text = (this.inputText || '').trim();
      if (!text || this.streaming) return;

      this.messages.push({ role: 'user', content: text, _uid: uidSeed++ });
      this.inputText = '';
      this.requestAi();
    },

    // 构建 OpenAI 格式上下文（canceled / 空回复 / local 不计入）
    buildContext() {
      return this.messages
        .filter(m => (m.role === 'user' || m.role === 'assistant') && m.content && !m.canceled)
        .map(m => ({ role: m.role, content: m.content }));
    },

    // 请求 AI 回复。reuseMsg 传入已有 assistant 消息用于重新生成（保留历史版本）
    requestAi(reuseMsg) {
      let m = reuseMsg;
      if (!m) {
        m = {
          role: 'assistant',
          content: '',
          _uid: uidSeed++,
          versions: [],
          verIdx: -1,
          thinkState: 'thinking',
          thinkSeconds: 0,
          thinkOpen: false,
          canceled: false,
          generating: true
        };
        this.messages.push(m);
      } else {
        m.content = '';
        m.canceled = false;
        m.thinkState = 'thinking';
        m.generating = true;
        m.thinkStart = Date.now();
      }
      m.thinkStart = Date.now();
      this.pageAborted = false;
      this.streaming = true;
      this.editingUid = -1;
      this.showModelMenu = false;
      this.scrollToBottom();

      const watchdog = setTimeout(() => {
        if (this.streaming) {
          this.pageAborted = true;
          if (this.streamAbort) this.streamAbort.abort();
        }
      }, 300000);

      const p = api.aiChatStream({
        messages: this.buildContext(),
        model: this.currentModel,
        onChunk: (delta) => {
          if (m.thinkState === 'thinking') {
            m.thinkState = 'done';
            m.thinkSeconds = Math.max(1, Math.round((Date.now() - m.thinkStart) / 1000));
          }
          m.content += delta;
          if (m.verIdx >= 0 && m.versions[m.verIdx] !== undefined) {
            m.versions[m.verIdx] = m.content;
          }
          this.scrollToBottom();
        },
        onDone: () => {
          clearTimeout(watchdog);
          this.streaming = false;
          m.generating = false;
          if (this.pageAborted) {
            m.canceled = true;
          } else if (m.content) {
            // 记录为新版本
            m.versions.push(m.content);
            m.verIdx = m.versions.length - 1;
          }
          m.thinkState = m.content ? 'done' : '';
          if (!m.thinkSeconds && m.thinkStart) {
            m.thinkSeconds = Math.max(1, Math.round((Date.now() - m.thinkStart) / 1000));
          }
          this.scrollToBottom();
        },
        onError: (msg) => {
          if (!m.content) m.content = '出错了：' + msg;
        }
      });
      // 保存整个 promise（其上挂载了 abort 方法），终止时调用 .abort()
      this.streamAbort = p;
    },

    // 终止思考：保留已生成内容
    stop() {
      if (!this.streaming) return;
      this.pageAborted = true;
      if (this.streamAbort) this.streamAbort.abort();
    },

    // ===== 重新生成 / 版本 =====
    regen(m) {
      if (this.streaming) return;
      const idx = this.messages.indexOf(m);
      if (idx < 0) return;
      // 截断该回复之后的所有消息
      this.messages = this.messages.slice(0, idx + 1);
      this.requestAi(m);
    },

    switchVersion(m, dir) {
      const next = m.verIdx + dir;
      if (next < 0 || next >= m.versions.length) return;
      m.verIdx = next;
      m.content = m.versions[next];
      this.scrollToBottom();
    },

    // ===== 编辑用户消息 =====
    startEdit(m) {
      if (this.streaming) return;
      this.editingUid = m._uid;
      this.editText = m.content;
    },
    cancelEdit() {
      this.editingUid = -1;
      this.editText = '';
    },
    confirmEdit(m) {
      const text = (this.editText || '').trim();
      if (!text) {
        uni.showToast({ title: '内容不能为空', icon: 'none' });
        return;
      }
      if (this.streaming) return;
      const idx = this.messages.indexOf(m);
      m.content = text;
      // 移除该消息之后的所有消息（原 AI 回复作废），并重新生成
      this.messages = this.messages.slice(0, idx + 1);
      this.editingUid = -1;
      this.editText = '';
      this.requestAi();
    },

    // ===== 工具 =====
    copyText(text) {
      if (!text) return;
      uni.setClipboardData({
        data: text,
        success: () => uni.showToast({ title: '已复制', icon: 'none' }),
        fail: (err) => {
          const msg = (err && err.errMsg) || '';
          if (msg.indexOf('privacy') > -1 || msg.indexOf('auth') > -1) {
            uni.showToast({ title: '未授权，暂时无法复制', icon: 'none' });
          } else {
            uni.showToast({ title: '复制失败，请检查剪贴板权限', icon: 'none' });
          }
        }
      });
    },

    clearChat() {
      if (this.streaming) return;
      uni.showModal({
        title: '提示',
        content: '确定要清空当前对话吗？',
        confirmText: '清空',
        cancelText: '取消',
        success: (res) => {
          if (!res.confirm) return;
          this.messages = [{ role: 'system', content: SYSTEM_PROMPT, _uid: 0 }];
          this.editingUid = -1;
          this.scrollToBottom();
          uni.showToast({ title: '已清空', icon: 'none' });
        }
      });
    }
  }
};
</script>

<style scoped>
page {
  background: #eff1ff;
  /* 锁定页面本身滚动，避免对话区被继续下滑出空白 */
  height: 100%;
  overflow: hidden;
}

.page-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background: #eff1ff;
}

/* #ifdef H5 */
/* uni-app H5 的页面内容包裹层默认高度 auto，会被内容撑高导致整页可下滑 */
uni-page-body {
  height: 100%;
  overflow: hidden;
}
/* #endif */

.nav-title {
  display: flex;
  align-items: center;
  font-size: 32rpx;
  font-weight: bold;
  color: #fff;
  letter-spacing: 6rpx;
  position: relative;
  z-index: 1;
}

.nav-action {
  color: #fff;
  padding: 0 30rpx;
}

.action-text {
  font-size: 26rpx;
}

/* banner + 遮罩，与系统其他页面一致 */
.background-wrapper {
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 0;
}

.background {
  width: 100%;
  position: absolute;
  top: 0;
  height: 800rpx;
}

.background-mask {
  background-image: linear-gradient(rgba(0, 0, 0, 0.8), transparent);
  height: 600rpx;
  position: absolute;
  width: 100%;
  top: 0;
}

.content-wrapper {
  position: relative;
  z-index: 1;
  flex: 1;
  /* 关键：flex 子项默认 min-height:auto 不收缩，会被内容撑高导致整页下滑 */
  min-height: 0;
  margin-top: 30rpx;
  display: flex;
  flex-direction: column;
  background: #eff1ff;
  border-top-left-radius: 60rpx;
  border-top-right-radius: 60rpx;
  box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

/* 功能开关校验中的加载占位（横向居中，不做垂直居中，与其他页面加载态一致） */
.chat-loading {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}

/* ===== 聊天主体 ===== */
.chat-body {
  flex: 1;
  /* 允许收缩，消息再多也只在内部滚动 */
  min-height: 0;
  height: 0;
  overflow: hidden;
  padding: 24rpx 24rpx 0;
  box-sizing: border-box;
}

.chat-body-tail {
  height: 20rpx;
}

/* 欢迎区 */
.welcome {
  display: flex;
  align-items: center;
  gap: 24rpx;
  padding: 32rpx 36rpx;
  border-radius: 28rpx;
  background: linear-gradient(90deg, #e8f9f9 0%, #e6f2ff 60%, #eaf4ff 100%);
  margin-bottom: 32rpx;
}

.welcome-avatar {
  width: 96rpx;
  height: 96rpx;
  border-radius: 28rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 40rpx;
  font-weight: bold;
  background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
  box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.35);
  flex-shrink: 0;
}

.welcome-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #1f2937;
}

.welcome-sub {
  font-size: 24rpx;
  color: #6b7280;
  margin-top: 4rpx;
}

/* 推荐问题 */
.suggest {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 18rpx;
  margin-bottom: 20rpx;
}

.suggest-label {
  font-size: 27rpx;
  font-weight: 600;
  color: #374151;
}

.suggest-chip {
  background: #fff;
  border: 1rpx solid #e6e8ef;
  color: #374151;
  border-radius: 22rpx;
  padding: 18rpx 28rpx;
  font-size: 27rpx;
  text-align: left;
}

.suggest-chip:active {
  border-color: #8adcdc;
  background: #f0fbfb;
  color: #0e8c8c;
}

/* ===== 消息行 ===== */
.msg-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 30rpx;
  animation: bubble-in 0.3s ease both;
}

.msg-row.is-user {
  flex-direction: row-reverse;
}

@keyframes bubble-in {
  from { opacity: 0; transform: translateY(14rpx); }
  to { opacity: 1; transform: translateY(0); }
}

.msg-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  color: #fff;
  font-size: 26rpx;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 4rpx;
}

.ai-avatar {
  background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
  box-shadow: 0 6rpx 16rpx rgba(19, 194, 194, 0.3);
}

.msg-col {
  display: flex;
  flex-direction: column;
  max-width: 82%;
  margin: 0 14rpx;
}

.msg-row.is-user .msg-col {
  align-items: flex-end;
}

.ai-name {
  font-size: 22rpx;
  color: #9ca3af;
  margin-bottom: 10rpx;
}

/* 用户气泡（AI 气泡由 msg-bubble 组件渲染） */
.bubble {
  max-width: 100%;
  padding: 22rpx 26rpx;
  border-radius: 24rpx;
  font-size: 28rpx;
  line-height: 1.7;
  word-break: break-word;
  box-sizing: border-box;
}

.bubble-user {
  background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
  color: #fff;
  border-bottom-right-radius: 8rpx;
  box-shadow: 0 6rpx 20rpx rgba(19, 194, 194, 0.28);
}

.bubble-text {
  display: block;
  white-space: pre-wrap;
}

/* 用户编辑态气泡 */
.msg-row .bubble.editing {
  background: #fff;
  color: #1f2937;
  border: 2rpx solid #8adcdc;
  border-bottom-right-radius: 24rpx;
}

/* ===== 思考过程框 ===== */
.think-box {
  border: 1rpx solid #ececf1;
  background: #fafafc;
  border-radius: 20rpx;
  margin-bottom: 14rpx;
  overflow: hidden;
  width: 100%;
  box-sizing: border-box;
}

.think-head {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 16rpx 24rpx;
  font-size: 24rpx;
  color: #6b7280;
}

.think-icon {
  width: 40rpx;
  height: 40rpx;
  border-radius: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 18rpx;
  font-weight: bold;
  background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
  flex-shrink: 0;
}

.think-icon.spinning {
  animation: think-spin 1.4s ease-in-out infinite;
}

@keyframes think-spin {
  0%, 100% { transform: scale(1); opacity: 0.75; }
  50% { transform: scale(1.18); opacity: 1; }
}

.think-dots {
  display: inline-flex;
  gap: 6rpx;
}

.think-dots .dot {
  width: 10rpx;
  height: 10rpx;
  border-radius: 50%;
  background: #13c2c2;
  animation: think-blink 1.2s infinite ease-in-out;
}

.think-dots .dot:nth-child(2) { animation-delay: 0.2s; }
.think-dots .dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes think-blink {
  0%, 80%, 100% { opacity: 0.25; transform: translateY(0); }
  40% { opacity: 1; transform: translateY(-6rpx); }
}

.think-caret {
  font-size: 22rpx;
  opacity: 0.7;
  transition: transform 0.2s;
  margin-left: 4rpx;
  transform: rotate(90deg);
}

.think-box.open .think-caret {
  transform: rotate(-90deg);
}

.think-body {
  padding: 0 24rpx 18rpx 76rpx;
  font-size: 24rpx;
  color: #6b7280;
}

/* ===== 气泡操作 ===== */
.msg-actions {
  display: flex;
  align-items: center;
  gap: 4rpx;
  margin-top: 12rpx;
  flex-wrap: wrap;
}

.act-btn {
  min-width: 52rpx;
  height: 52rpx;
  padding: 0 8rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14rpx;
  background: transparent;
  color: #6b7280;
  font-size: 24rpx;
}

.act-btn:active {
  background: #eef0f6;
}

.act-btn.disabled {
  opacity: 0.35;
}

.act-icon {
  width: 30rpx;
  height: 30rpx;
}

.act-txt {
  color: #4b5563;
}

.ver-indicator {
  font-size: 22rpx;
  color: #9ca3af;
  min-width: 48rpx;
  text-align: center;
}

.canceled-tip {
  font-size: 22rpx;
  color: #9ca3af;
  margin-top: 8rpx;
}

/* ===== 输入区 ===== */
.chat-input {
  position: relative;
  display: flex;
  align-items: flex-end;
  padding: 16rpx 20rpx;
  background: #fff;
  border-top: 1rpx solid #e8e5f0;
}

.input-box {
  flex: 1;
  border: 1rpx solid #e6e8ef;
  border-radius: 28rpx;
  padding: 18rpx 24rpx;
  background: #fff;
  box-sizing: border-box;
}

.msg-input {
  width: 100%;
  min-height: 44rpx;
  max-height: 240rpx;
  font-size: 28rpx;
  line-height: 1.6;
  color: #1f2937;
}

.aic-toolbar {
  display: flex;
  align-items: center;
  margin-top: 14rpx;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 14rpx;
}

.aic-pill {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  background: #f4f5f8;
  color: #4b5563;
  border-radius: 999rpx;
  padding: 10rpx 22rpx;
  font-size: 24rpx;
}

.aic-pill.active {
  background: #e8f9f9;
  color: #0e8c8c;
  font-weight: 600;
}

.pill-text {
  max-width: 240rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.aic-caret {
  font-size: 20rpx;
  opacity: 0.7;
}

/* 按钮外层：为 Outside 的加载圆环提供定位参照（按钮自身 overflow 不会裁掉它） */
.send-btn-wrap {
  position: relative;
  margin-left: 16rpx;
  flex-shrink: 0;
  display: flex;
}

/* 终止中：按钮外框的旋转圆环（作为按钮的边框，仅一道弧，避免糊成一圈） */
.send-ring {
  position: absolute;
  left: -9rpx;
  top: -9rpx;
  right: -9rpx;
  bottom: -9rpx;
  box-sizing: border-box;
  border-radius: 50%;
  border: 3rpx solid transparent;
  border-top-color: #13c2c2;
  border-right-color: rgba(19, 194, 194, 0.45);
  border-bottom-color: rgba(19, 194, 194, 0.12);
  animation: send-spin 0.85s linear infinite;
  pointer-events: none;
}

/* 终止时去掉按钮投影，否则与外圈叠在一起会显脏 */
.send-btn.streaming {
  box-shadow: none;
}

.send-btn {
  width: 84rpx;
  height: 84rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 0;
  box-shadow: 0 6rpx 16rpx rgba(19, 194, 194, 0.3);
  transition: opacity 0.2s ease, transform 0.1s ease;
}

.send-btn:active {
  transform: scale(0.92);
}

.send-btn.disabled {
  background: #c5c9e8;
  box-shadow: none;
  opacity: 0.65;
}

.send-icon {
  width: 40rpx;
  height: 40rpx;
}

/* 终止图标：中央方块（约占按钮 1/3，加载圆环在按钮外侧） */
.send-stop {
  width: 26rpx;
  height: 26rpx;
  border-radius: 5rpx;
  background: #fff;
}

@keyframes send-spin {
  to { transform: rotate(360deg); }
}

/* ===== 模型选择菜单 ===== */
.model-mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 30;
}

.model-menu {
  position: fixed;
  left: 20rpx;
  /* bottom 由 openModelMenu 实测输入区位置后动态写入 */
  min-width: 320rpx;
  max-width: 80vw;
  max-height: 480rpx;
  overflow-y: auto;
  background: #fff;
  border: 1rpx solid #eceef4;
  border-radius: 22rpx;
  box-shadow: 0 18rpx 56rpx rgba(15, 23, 42, 0.14);
  padding: 10rpx;
  box-sizing: border-box;
  z-index: 31;
}

.model-item {
  padding: 18rpx 22rpx;
  border-radius: 14rpx;
  font-size: 26rpx;
  color: #374151;
  word-break: break-all;
}

.model-item.active {
  background: #e8f9f9;
  color: #0e8c8c;
  font-weight: 600;
}

/* ===== 编辑卡片 ===== */
.edit-card {
  width: 100%;
}

.edit-area {
  width: 100%;
  min-height: 60rpx;
  font-size: 28rpx;
  line-height: 1.6;
  color: #1f2937;
}

.edit-actions {
  display: flex;
  gap: 14rpx;
  margin-top: 14rpx;
  justify-content: flex-end;
}

.edit-btn {
  border-radius: 999rpx;
  padding: 8rpx 26rpx;
  font-size: 24rpx;
}

.edit-btn-cancel {
  background: #fff;
  border: 1rpx solid #e5e7eb;
  color: #4b5563;
}

.edit-btn-save {
  background: #13c2c2;
  border: 0;
  color: #fff;
}

/* H5 端 tabBar 为 fixed 覆盖底部，需为输入框预留 tabBar 高度 */
/* #ifdef H5 */
.chat-input {
  padding-bottom: calc(56px + env(safe-area-inset-bottom));
}
/* #endif */

/* ===== 后台「功能设置」关闭 AI 对话（uniapp_ai_chat）：遮罩提示（与小程序端样式一致） ===== */
/* 覆盖范围：从顶部一直盖到 tabBar 上方（顶部导航一并盖住，仅保留底部 Tab 可切换）；
   App 端页面本身在原生 tabBar 之上，bottom: 0 即等于 tabBar 上方；H5 / 小程序端由下方变量与条件编译兜底 */
.feature-disabled-mask {
  position: fixed;
  left: 0;
  top: 0;
  right: 0;
  bottom: var(--window-bottom, 0px);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0 80rpx;
  text-align: center;
  background: rgba(239, 241, 255, 0.97);
}

/* H5 端 tabBar 为 fixed 覆盖底部，与 .chat-input 的预留高度保持一致，避免遮罩盖住底部 Tab */
/* #ifdef H5 */
.feature-disabled-mask {
  bottom: calc(56px + env(safe-area-inset-bottom));
}
/* #endif */

.feature-disabled-icon {
  width: 132rpx;
  height: 132rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 36rpx;
  background: rgba(255, 77, 79, 0.12);
}

/* 动态图标（后台公告类型对应的图标）：白底 + 轻投影，避免与红叉的浅红底冲突 */
.feature-disabled-icon-plain {
  background: #ffffff;
  box-shadow: 0 8rpx 24rpx rgba(72, 65, 111, 0.12);
}

.feature-disabled-icon-image {
  width: 72rpx;
  height: 72rpx;
}

/* 兜底图标（后台未配置公告）：红叉，两条红杠旋转 ±45° 叠加 */
.feature-disabled-cross {
  position: relative;
  width: 72rpx;
  height: 72rpx;
}

.feature-disabled-cross::before,
.feature-disabled-cross::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  width: 72rpx;
  height: 12rpx;
  margin: -6rpx 0 0 -36rpx;
  border-radius: 8rpx;
  background: #ff4d4f;
}

.feature-disabled-cross::before {
  transform: rotate(45deg);
}

.feature-disabled-cross::after {
  transform: rotate(-45deg);
}

.feature-disabled-text {
  max-width: 520rpx;
  font-size: 30rpx;
  line-height: 1.8;
  color: #4b5563;
  animation: fd-text-in 0.32s ease-out 0.14s both;
}

/* 遮罩入场：整体淡入 + 图标回弹 + 文案上浮，退出时由内容淡入衔接 */
.feature-disabled-mask {
  animation: fd-mask-in 0.22s ease-out both;
}

.feature-disabled-icon {
  animation: fd-icon-in 0.42s cubic-bezier(0.34, 1.4, 0.64, 1) 0.06s both;
}

@keyframes fd-mask-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes fd-icon-in {
  from {
    opacity: 0;
    transform: scale(0.84);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes fd-text-in {
  from {
    opacity: 0;
    transform: translateY(10rpx);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
