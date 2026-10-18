<template>
	<view v-if="visible" class="cb-load-fail" @touchmove.stop.prevent="noop">
		<view class="cb-load-fail__card">
			<image class="cb-load-fail__icon" src="/static/assets/wxmp-network-error.svg" mode="widthFix"></image>
			<view class="cb-load-fail__title">加载失败</view>
			<view class="cb-load-fail__desc">{{ message }}</view>
			<view class="cb-load-fail__btn" @click="onRetry">重新加载</view>
		</view>
	</view>
</template>

<script>
/**
 * 统一「加载失败」遮罩
 * 风格与「未绑定宿舍 / 未绑定教务账号」提示一致：图标 + 标题 + 说明 + 按钮
 *
 * 用法：在二级页面根节点放一个 <cb-load-fail /> 即可，无需传参：
 * - api.js 网络层失败时自动出现（仅对发生失败的页面生效）
 * - 请求成功或点击「重新加载」后自动收起
 * - 遮罩覆盖内容区（保留顶部导航，可正常返回）
 * 与原生小程序端（components/cb-load-fail）保持完全一致。
 */
import loadFail from '../../utils/load-fail.js';

export default {
	name: 'cb-load-fail',

	data() {
		return {
			visible: false,
			message: ''
		};
	},

	created() {
		this._route = loadFail.getRoute();
		// 只按路由匹配展示，避免返回上一页时误显示；状态由请求成功时清除
		this.sync(loadFail.get());
		this._unsubscribe = loadFail.subscribe((state) => this.sync(state));
	},

	beforeDestroy() {
		this.cleanup();
	},

	beforeUnmount() {
		this.cleanup();
	},

	methods: {
		cleanup() {
			if (this._unsubscribe) {
				this._unsubscribe();
				this._unsubscribe = null;
			}
		},

		sync(state) {
			// 只在「发生失败的页面」展示，避免返回上一页时误显示
			const isMine = !state.route || !this._route || state.route === this._route;
			this.visible = !!state.failed && isMine;
			this.message = state.message || '网络异常，请稍后重试';
		},

		onRetry() {
			loadFail.clear();
			this.reloadPage();
		},

		// 重新进入当前页，触发页面重新拉取数据
		reloadPage() {
			let url = '';
			try {
				const pages = getCurrentPages();
				const page = pages[pages.length - 1];
				const route = (page && (page.route || (page.$page && page.$page.route))) || this._route;
				const options = (page && (page.options || (page.$page && page.$page.options))) || {};
				const query = Object.keys(options)
					.map((k) => k + '=' + encodeURIComponent(options[k]))
					.join('&');
				url = '/' + route + (query ? '?' + query : '');
			} catch (e) {
				url = '';
			}
			if (!url || url === '/') {
				this.visible = false;
				return;
			}
			uni.reLaunch({
				url,
				fail: () => {
					uni.redirectTo({ url });
				}
			});
		},

		noop() {}
	}
};
</script>

<style scoped>
.cb-load-fail {
	/* 只覆盖所在的内容区容器（.content-wrapper 为定位参照），不涉及导航栏 */
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 900;
	display: flex;
	/* 横向居中即可，不做垂直居中 */
	align-items: flex-start;
	justify-content: center;
	padding: 100rpx 60rpx 0;
	/* 不透明页面底色：彻底盖住原内容 */
	background: #eff1ff;
	/* 与内容区保持一致的上圆角，避免把原有圆角/顶部背景图盖成直角 */
	border-radius: 60rpx 60rpx 0 0;
	overflow: hidden;
	/* 不做整体淡入：网络失败时要立即完全遮住内容区，避免原内容闪现 */
}

.cb-load-fail__card {
	display: flex;
	flex-direction: column;
	align-items: center;
	width: 100%;
	padding: 0 40rpx;
}

.cb-load-fail__icon {
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 24rpx;
	opacity: 0.85;
	animation: clf-pop-in 0.4s cubic-bezier(0.34, 1.4, 0.64, 1) 0.04s both;
}

.cb-load-fail__title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
	animation: clf-rise-in 0.32s ease-out 0.1s both;
}

.cb-load-fail__desc {
	font-size: 26rpx;
	color: #948eb3;
	text-align: center;
	line-height: 1.6;
	animation: clf-rise-in 0.32s ease-out 0.16s both;
}

.cb-load-fail__btn {
	margin-top: 32rpx;
	height: 72rpx;
	line-height: 72rpx;
	padding: 0 60rpx;
	background: #13c2c2;
	color: #ffffff;
	font-size: 28rpx;
	border-radius: 36rpx;
	font-weight: bold;
	animation: clf-rise-in 0.32s ease-out 0.22s both;
}

.cb-load-fail__btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

@keyframes clf-fade-in {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@keyframes clf-pop-in {
	from {
		opacity: 0;
		transform: scale(0.8) translateY(8rpx);
	}
	to {
		opacity: 1;
		transform: scale(1) translateY(0);
	}
}

@keyframes clf-rise-in {
	from {
		opacity: 0;
		transform: translateY(12rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>
