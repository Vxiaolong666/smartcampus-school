<template>
  <view class="page-container">
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
      <scroll-view class="chat-body" scroll-y :scroll-into-view="scrollTarget" scroll-with-animation>
        <view
          v-for="m in chatList"
          :key="m._i"
          :id="'msg-' + m._i"
          class="msg-row"
          :class="m.role === 'user' ? 'is-user' : 'is-ai'"
        >
          <view class="msg-avatar ai-avatar" v-if="m.role === 'assistant'">AI</view>
          <msg-bubble :role="m.role" :content="m.content" />
          <view class="msg-avatar user-avatar" v-if="m.role === 'user'">我</view>
        </view>
      </scroll-view>

      <view class="chat-input">
        <textarea
          class="input-box"
          v-model="inputText"
          :disabled="streaming"
          placeholder="问问 AI 关于课程、成绩、用电…"
          :auto-height="true"
          maxlength="-1"
          @confirm="send"
        />
        <button class="send-btn" :class="{ disabled: streaming || !inputText.trim() }" @click="send">
          <image v-if="!streaming" class="send-icon" src="/static/assets/wxmp-send-white.svg" mode="aspectFit"></image>
          <view v-else class="send-spinner"></view>
        </button>
      </view>
    </view>
  </view>
</template>

<script>
import api from '../../utils/api';
import MsgBubble from './msg-bubble.vue';

export default {
  components: { MsgBubble },
  data() {
    return {
      messages: [
        {
          role: 'system',
          content: '你是“智慧校园”AI 助手，帮助学生解答校园生活、学习规划、教务查询、宿舍用电等方面的问题。回答简洁友好、条理清晰，可适当使用 Markdown。'
        },
        {
          role: 'assistant',
          content: '你好！我是智慧校园 AI 助手 🤖\n可以帮你解答课程安排、成绩分析、宿舍用电、校园生活等问题，试着问我吧～',
          local: true
        }
      ],
      inputText: '',
      streaming: false,
      scrollTarget: 'msg-1'
    };
  },

  computed: {
    // 过滤掉 system 消息（仅作提示词，不在对话流中展示），并保留原始下标用于滚动定位与稳定 key
    chatList() {
      return this.messages
        .map((m, i) => ({ ...m, _i: i }))
        .filter(m => m.role !== 'system');
    }
  },

  methods: {
    scrollToBottom() {
      this.$nextTick(() => {
        this.scrollTarget = 'msg-' + (this.messages.length - 1);
      });
    },

    clearChat() {
      if (this.streaming) return;
      this.messages = [
        this.messages[0],
        { role: 'assistant', content: '对话已清空，有什么可以帮你的？', local: true }
      ];
      this.scrollToBottom();
    },

    async send() {
      const text = this.inputText.trim();
      if (!text || this.streaming) return;

      this.messages.push({ role: 'user', content: text });
      this.messages.push({ role: 'assistant', content: '' });
      const aiIndex = this.messages.length - 1;
      this.inputText = '';
      this.streaming = true;
      this.scrollToBottom();

      // 看门狗：若接口在 120s 内仍未返回（如 AI 上游缓慢/网络挂起），强制结束“加载中”，
      // 避免 Promise 未 resolve 导致气泡永久停留在“正在输入”
      const watchdog = setTimeout(() => {
        if (this.streaming) {
          this.streaming = false;
          if (!this.messages[aiIndex].content) {
            this.messages[aiIndex].content = '（请求超时，AI 暂无响应，请稍后重试）';
          }
          this.scrollToBottom();
        }
      }, 120000);

      try {
        await api.aiChatStream({
          messages: this.messages.filter(m => !m.local),
          onChunk: (chunk) => {
            this.messages[aiIndex].content += chunk;
            this.scrollToBottom();
          },
          onDone: () => {
            this.streaming = false;
          },
          onError: (msg) => {
            if (!this.messages[aiIndex].content) this.messages[aiIndex].content = '出错了：' + msg;
          }
        });
      } catch (e) {
        if (!this.messages[aiIndex].content) this.messages[aiIndex].content = '出错了：' + ((e && e.msg) || '请求失败');
      } finally {
        clearTimeout(watchdog);
        this.streaming = false;
        // 兜底：若整轮对话既无内容也无错误，填充提示，避免气泡永久停留在“正在输入”
        if (!this.messages[aiIndex].content) {
          this.messages[aiIndex].content = '（AI 暂无回复，请稍后再试）';
        }
        this.scrollToBottom();
      }
    }
  }
};
</script>

<style scoped>
page {
  background: #eff1ff;
}

.page-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background: #eff1ff;
}

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

/* 与首页 / 我的 等页完全一致的 banner + 遮罩 */
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

/* 圆角上拉卡片，与系统二级页 content-wrapper 一致 */
.content-wrapper {
  position: relative;
  z-index: 1;
  flex: 1;
  margin-top: 30rpx;
  display: flex;
  flex-direction: column;
  background: #eff1ff;
  border-top-left-radius: 60rpx;
  border-top-right-radius: 60rpx;
  box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.chat-body {
  flex: 1;
  overflow: hidden;
  padding: 24rpx 24rpx 8rpx;
  box-sizing: border-box;
}

.msg-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 28rpx;
  animation: bubble-in 0.3s ease both;
}

.msg-row.is-user {
  flex-direction: row-reverse;
}

.msg-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  color: #fff;
  font-size: 26rpx;
  font-weight: bold;
  letter-spacing: 1rpx;
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

.user-avatar {
  background: linear-gradient(135deg, #8b7fd6 0%, #5a5477 100%);
  box-shadow: 0 6rpx 16rpx rgba(90, 84, 119, 0.3);
}

@keyframes bubble-in {
  from { opacity: 0; transform: translateY(14rpx); }
  to { opacity: 1; transform: translateY(0); }
}

.chat-input {
  display: flex;
  align-items: flex-end;
  padding: 16rpx 20rpx;
  background: #fff;
  border-top: 1rpx solid #e8e5f0;
}

.input-box {
  flex: 1;
  min-height: 72rpx;
  max-height: 200rpx;
  background: #f2f3f5;
  border-radius: 16rpx;
  padding: 16rpx 20rpx;
  font-size: 28rpx;
  box-sizing: border-box;
}

.send-btn {
  width: 76rpx;
  height: 76rpx;
  margin-left: 16rpx;
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
  background: #cfe8e8;
  box-shadow: none;
  opacity: 0.65;
}

.send-icon {
  width: 40rpx;
  height: 40rpx;
}

.send-spinner {
  width: 34rpx;
  height: 34rpx;
  border: 4rpx solid rgba(255, 255, 255, 0.45);
  border-top-color: #fff;
  border-radius: 50%;
  animation: send-spin 0.8s linear infinite;
}

@keyframes send-spin {
  to { transform: rotate(360deg); }
}

/* H5 端 tabBar 为 fixed 覆盖底部，需为输入框预留出 tabBar 高度，避免被遮挡 */
/* #ifdef H5 */
.chat-input {
  padding-bottom: calc(56px + env(safe-area-inset-bottom));
}
/* #endif */
</style>
