<template>
	<view v-if="value" class="ai-mask" @click="$emit('input', false)">
		<view class="ai-modal" :style="{ height: sheetHeight + 'px' }" @click.stop>
			<view class="ai-modal-handle"
				@touchstart="onDragStart"
				@touchmove="onDragMove"
				@touchend="onDragEnd"></view>
			<view class="ai-modal-header">
				<text class="ai-modal-title">{{ title }}</text>
				<text class="ai-modal-close" @click="$emit('input', false)">✕</text>
			</view>
			<scroll-view scroll-y="true" class="ai-modal-body">
				<view class="ai-modal-body-inner">
					<rich-text :nodes="html" class="ai-modal-content"></rich-text>
				</view>
			</scroll-view>
		</view>
	</view>
</template>

<script>
import markdownToHtml from '../../utils/markdown.js';

export default {
	props: {
		// v-model 控制显隐
		value: {
			type: Boolean,
			default: false
		},
		title: {
			type: String,
			default: 'AI 智能分析'
		},
		// markdown 原始内容
		content: {
			type: String,
			default: ''
		}
	},

	data() {
		return {
			windowHeight: 0,
			sheetHeight: 0,
			dragStartY: 0,
			dragStartHeight: 0,
			dragging: false
		};
	},

	computed: {
		html() {
			return markdownToHtml(this.content);
		}
	},

	mounted() {
		try {
			this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
		} catch (e) {
			this.windowHeight = 0;
		}
		this.sheetHeight = Math.round(this.windowHeight * 0.8);
	},

	methods: {
		onDragStart(e) {
			this.dragging = true;
			this.dragStartY = e.touches[0].clientY;
			this.dragStartHeight = this.sheetHeight;
		},

		onDragMove(e) {
			if (!this.dragging) return;
			const deltaY = e.touches[0].clientY - this.dragStartY;
			let newH = this.dragStartHeight - deltaY;
			const maxH = this.windowHeight || 99999;
			const minH = Math.round((this.windowHeight || 0) * 0.25);
			if (newH > maxH) newH = maxH;
			if (newH < minH) newH = minH;
			this.sheetHeight = newH;
		},

		onDragEnd() {
			this.dragging = false;
		}
	}
};
</script>

<style scoped>
.ai-mask {
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.45);
	z-index: 9999;
	display: flex;
	flex-direction: column;
	justify-content: flex-end;
	animation: aiMaskFade 0.25s ease;
}

@keyframes aiMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.ai-modal {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.18);
	animation: aiModalUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes aiModalUp {
	from { transform: translateY(100%); }
	to { transform: translateY(0); }
}

.ai-modal-handle {
	width: 72rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #d8d4e8;
	margin: 18rpx auto 2rpx;
	transition: background 0.2s ease;
}

.ai-modal-handle:active {
	background: #b9b2d6;
}

.ai-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 14rpx 44rpx 22rpx;
	border-bottom: 1rpx solid #f0f0f4;
}

.ai-modal-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #322c54;
}

.ai-modal-close {
	width: 56rpx;
	height: 56rpx;
	line-height: 56rpx;
	text-align: center;
	font-size: 30rpx;
	color: #948eb3;
	background: #f3f2f8;
	border-radius: 50%;
}

.ai-modal-body {
	flex: 1;
	min-height: 0;
}

.ai-modal-body-inner {
	padding: 30rpx 44rpx;
}

.ai-modal-content {
	display: block;
	font-size: 28rpx;
	color: #48416f;
	line-height: 1.75;
	word-break: break-word;
}
</style>
