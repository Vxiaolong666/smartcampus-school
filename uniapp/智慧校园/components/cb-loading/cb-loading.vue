<template>
	<view class="cb-loading" :class="{ 'cb-loading--compact': compact }">
		<view class="cb-loading__spinner">
			<view class="cb-loading__ring"></view>
		</view>

		<view class="cb-loading__texts">
			<text
				class="cb-loading__text"
				:class="animate ? (flipA ? 'cb-loading__text--a' : 'cb-loading__text--b') : ''"
			>{{ displayText }}</text>
			<text
				v-if="showSub && displaySub"
				class="cb-loading__sub"
				:class="animate ? (flipA ? 'cb-loading__sub--a' : 'cb-loading__sub--b') : ''"
			>{{ displaySub }}</text>
		</view>
	</view>
</template>

<script>
/**
 * 统一加载占位组件
 * - 圆环 1s/圈旋转，每转完一圈自动换色（青 → 蓝 → 紫）
 * - 主文案 + 副标题成对轮换，切换时错峰入场，观感更灵动
 * - 加载超时后切换为「加载较慢」提示
 * 与原生小程序端（components/cb-loading）保持完全一致
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

export default {
	name: 'cb-loading',
	props: {
		// 指定后固定展示该文案，不再自动轮换
		text: {
			type: String,
			default: ''
		},
		// 副标题（配合 text 使用；不传则跟随轮换文案）
		subText: {
			type: String,
			default: ''
		},
		// 是否展示副标题
		showSub: {
			type: Boolean,
			default: true
		},
		// 内嵌在卡片内时使用更紧凑的间距与字号
		compact: {
			type: Boolean,
			default: false
		},
		// 是否自动轮换文案
		rotate: {
			type: Boolean,
			default: true
		},
		// 超过该时长（毫秒）后提示加载较慢，传 0 关闭
		slowAfter: {
			type: Number,
			default: 8000
		},
		slowText: {
			type: String,
			default: ''
		},
		slowSubText: {
			type: String,
			default: ''
		}
	},

	data() {
		return {
			index: 0,
			displayText: '',
			displaySub: '',
			flipA: true,
			// 首个文案直接展示，不做入场动画
			animate: false,
			slow: false
		};
	},

	created() {
		this.startTextLoop();
	},

	beforeDestroy() {
		this.stopTextLoop();
	},

	beforeUnmount() {
		this.stopTextLoop();
	},

	methods: {
		startTextLoop() {
			// 指定了固定文案：不再轮换，副标题取 subText
			if (this.text) {
				this.displayText = this.text;
				this.displaySub = this.subText;
				return;
			}

			this.displayText = LOADING_TIPS[0].text;
			this.displaySub = LOADING_TIPS[0].sub;

			if (this.rotate) {
				this._textTimer = setInterval(() => {
					if (this.slow) return;
					this.index = (this.index + 1) % LOADING_TIPS.length;
					const tip = LOADING_TIPS[this.index];
					this.switchText(tip.text, tip.sub);
				}, 1600);
			}

			if (this.slowAfter > 0) {
				this._slowTimer = setTimeout(() => {
					this.slow = true;
					this.switchText(this.slowText || SLOW_TIP.text, this.slowSubText || SLOW_TIP.sub);
				}, this.slowAfter);
			}
		},

		// 换文案时来回切换动画类名，保证每次切换都有入场动画
		switchText(text, sub) {
			this.displayText = text;
			this.displaySub = sub || '';
			this.flipA = !this.flipA;
			this.animate = true;
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
};
</script>

<style scoped>
.cb-loading {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 0;
	/* 遮罩入场：与内容淡入配套，切换时不突兀 */
	animation: cb-loading-veil-in 0.2s ease-out both;
}

.cb-loading--compact {
	padding: 40rpx 0;
}

.cb-loading__spinner {
	position: relative;
	width: 96rpx;
	height: 96rpx;
	animation: cb-loading-breathe 2.4s ease-in-out infinite;
}

.cb-loading--compact .cb-loading__spinner {
	width: 76rpx;
	height: 76rpx;
}

/* 主环：旋转 1s/圈，颜色 3s 一循环（33.3% ≈ 每转完一圈换一次色） */
.cb-loading__ring {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	box-sizing: border-box;
	border-radius: 50%;
	border: 7rpx solid rgba(19, 194, 194, 0.12);
	border-top-color: #13c2c2;
	border-right-color: rgba(19, 194, 194, 0.8);
	border-bottom-color: rgba(19, 194, 194, 0.35);
	animation: cb-loading-spin 1s linear infinite, cb-ring-color 3s linear infinite;
}

.cb-loading--compact .cb-loading__ring {
	border-width: 6rpx;
}

.cb-loading__texts {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-top: 28rpx;
	padding: 0 24rpx;
}

.cb-loading--compact .cb-loading__texts {
	margin-top: 22rpx;
}

.cb-loading__text {
	font-size: 32rpx;
	font-weight: 600;
	line-height: 1.4;
	letter-spacing: 1rpx;
	text-align: center;
	color: #48416f;
}

.cb-loading--compact .cb-loading__text {
	font-size: 28rpx;
}

/* 副标题：比主文案轻一档，但保持足够对比度 */
.cb-loading__sub {
	margin-top: 10rpx;
	font-size: 24rpx;
	line-height: 1.4;
	letter-spacing: 1rpx;
	text-align: center;
	color: rgba(72, 65, 111, 0.68);
}

.cb-loading--compact .cb-loading__sub {
	margin-top: 8rpx;
	font-size: 22rpx;
}

/* 主文案 / 副标题错峰入场（a / b 交替，保证每次切换都能重播） */
.cb-loading__text--a {
	animation: cb-loading-text-in-a 0.5s ease-out;
}

.cb-loading__text--b {
	animation: cb-loading-text-in-b 0.5s ease-out;
}

.cb-loading__sub--a {
	animation: cb-loading-sub-in-a 0.5s ease-out 0.1s both;
}

.cb-loading__sub--b {
	animation: cb-loading-sub-in-b 0.5s ease-out 0.1s both;
}

@keyframes cb-loading-veil-in {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@keyframes cb-loading-spin {
	to {
		transform: rotate(360deg);
	}
}

@keyframes cb-ring-color {
	0%,
	33.3% {
		border-top-color: #13c2c2;
		border-right-color: rgba(19, 194, 194, 0.8);
		border-bottom-color: rgba(19, 194, 194, 0.35);
		border-left-color: rgba(19, 194, 194, 0.12);
	}
	33.34%,
	66.6% {
		border-top-color: #4d8bff;
		border-right-color: rgba(77, 139, 255, 0.8);
		border-bottom-color: rgba(77, 139, 255, 0.35);
		border-left-color: rgba(77, 139, 255, 0.12);
	}
	66.67%,
	100% {
		border-top-color: #8b7cf6;
		border-right-color: rgba(139, 124, 246, 0.8);
		border-bottom-color: rgba(139, 124, 246, 0.35);
		border-left-color: rgba(139, 124, 246, 0.12);
	}
}

/* 圆环整体轻微呼吸，避免机械感 */
@keyframes cb-loading-breathe {
	0%,
	100% {
		transform: scale(1);
	}
	50% {
		transform: scale(0.94);
	}
}

@keyframes cb-loading-text-in-a {
	0% {
		opacity: 0;
		transform: translateY(8rpx) scale(0.94);
	}
	60% {
		opacity: 1;
		transform: translateY(0) scale(1.04);
	}
	100% {
		opacity: 1;
		transform: translateY(0) scale(1);
	}
}

@keyframes cb-loading-text-in-b {
	0% {
		opacity: 0;
		transform: translateY(8rpx) scale(0.94);
	}
	60% {
		opacity: 1;
		transform: translateY(0) scale(1.04);
	}
	100% {
		opacity: 1;
		transform: translateY(0) scale(1);
	}
}

@keyframes cb-loading-sub-in-a {
	0% {
		opacity: 0;
		transform: translateY(6rpx);
	}
	100% {
		opacity: 1;
		transform: translateY(0);
	}
}

@keyframes cb-loading-sub-in-b {
	0% {
		opacity: 0;
		transform: translateY(6rpx);
	}
	100% {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>
