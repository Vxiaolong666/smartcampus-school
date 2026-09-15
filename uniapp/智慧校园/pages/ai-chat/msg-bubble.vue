<template>
  <view class="msg-bubble" :class="role === 'user' ? 'bubble-user' : 'bubble-ai'">
    <!-- H5 用 v-html，小程序/App 用 rich-text。每个气泡独立成组件，隔离 rich-text 实例，避免列表内重复渲染 -->
    <!-- #ifdef H5 -->
    <view v-if="content" class="msg-content" v-html="html"></view>
    <!-- #endif -->
    <!-- #ifndef H5 -->
    <rich-text v-if="content" :nodes="html" class="msg-content"></rich-text>
    <!-- #endif -->
    <view v-else class="msg-typing">
      <view class="dot"></view>
      <view class="dot"></view>
      <view class="dot"></view>
    </view>
  </view>
</template>

<script>
import markdownToHtml from '../../utils/markdown.js';

export default {
  name: 'MsgBubble',
  props: {
    role: { type: String, default: '' },
    content: { type: String, default: '' }
  },
  computed: {
    html() {
      return markdownToHtml(this.content || '');
    }
  }
};
</script>

<style scoped>
.msg-bubble {
  position: relative;
  max-width: 76%;
  margin: 0 14rpx;
  padding: 22rpx 26rpx;
  border-radius: 24rpx;
  font-size: 28rpx;
  line-height: 1.7;
  word-break: break-word;
}

.bubble-ai {
  background: #fff;
  color: #48416f;
  border: 1rpx solid #eceaf5;
  box-shadow: 0 6rpx 20rpx rgba(72, 65, 111, 0.07);
}

.bubble-ai::before {
  content: '';
  position: absolute;
  left: -12rpx;
  top: 28rpx;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 12rpx 14rpx 12rpx 0;
  border-color: transparent #fff transparent transparent;
}

.bubble-user {
  background: linear-gradient(135deg, #15c9c9 0%, #13c2c2 100%);
  color: #fff;
  box-shadow: 0 6rpx 20rpx rgba(19, 194, 194, 0.28);
}

.bubble-user::before {
  content: '';
  position: absolute;
  right: -12rpx;
  top: 28rpx;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 12rpx 0 12rpx 14rpx;
  border-color: transparent transparent transparent #13c2c2;
}

.msg-content {
  display: block;
}

.msg-typing {
  display: flex;
  align-items: center;
  gap: 10rpx;
  padding: 8rpx 0;
}

.msg-typing .dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #13c2c2;
  animation: typing-blink 1.2s infinite ease-in-out;
}

.msg-typing .dot:nth-child(2) {
  animation-delay: 0.2s;
}

.msg-typing .dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing-blink {
  0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
  30% { opacity: 1; transform: translateY(-6rpx); }
}
</style>
