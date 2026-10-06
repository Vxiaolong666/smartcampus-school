<template>
	<view v-if="show" class="splash-mask" @touchmove.stop.prevent="noop">
		<!-- 同图模糊背景：铺满整屏填充留白；前面仍完整展示广告原图（不裁切） -->
		<image class="splash-bg" :src="image" mode="aspectFill"></image>
		<image class="splash-image" :src="image" mode="aspectFit" @click="onTap"></image>
		<view class="splash-skip" @click="close">跳过 {{ countdown }}s</view>
		<view class="splash-footer" @click="onTap">
			<view class="splash-brand">
				<image class="splash-logo" src="/static/assets/wxmp-logo.svg" mode="aspectFit"></image>
				<text class="splash-name">{{ name }}</text>
			</view>
			<view v-if="url" class="splash-hint">点击跳转到详情页</view>
		</view>
	</view>
</template>

<script>
/**
 * 开屏广告
 * 启动时全屏展示（图片铺满 + 右上角倒计时跳过），倒计时结束或点击自动关闭；
 * 点击图片跳转后台配置的链接（站内页面 / 站外 http(s)）。
 * 展示期间锁定背景页面滚动（H5 用 scroll-lock，App/小程序用遮罩 catch touchmove）。
 */
import { lockPageScroll, unlockPageScroll } from '../../utils/scroll-lock.js';

export default {
	name: 'splash-ad',
	props: {
		show: { type: Boolean, default: false },
		image: { type: String, default: '' },
		url: { type: String, default: '' },
		duration: { type: [Number, String], default: 3 },
		name: { type: String, default: '智慧校园' }
	},
	data() {
		return {
			countdown: 3,
			timer: null
		};
	},
	watch: {
		show(val) {
			if (val) {
				this.startCountdown();
				this.setScrollLock(true);
				this.setTabBarHidden(true);
			} else {
				this.stopCountdown();
				this.setScrollLock(false);
				this.setTabBarHidden(false);
			}
		}
	},
	beforeDestroy() {
		this.stopCountdown();
		this.setScrollLock(false);
		this.setTabBarHidden(false);
	},
	methods: {
		// 展示时锁定背景滚动，关闭时还原（避免滚动穿透 / 露出滚动条）
		setScrollLock(on) {
			if (on && !this._scrollLocked) {
				this._scrollLocked = true;
				lockPageScroll();
			} else if (!on && this._scrollLocked) {
				this._scrollLocked = false;
				unlockPageScroll();
			}
		},
		// 隐藏 / 恢复底部 tabBar（原生 tabBar 会盖在浮层之上，覆盖不到）
		setTabBarHidden(hidden) {
			const opts = { animation: false, fail: () => {} };
			try {
				if (hidden) {
					uni.hideTabBar(opts);
				} else {
					uni.showTabBar(opts);
				}
			} catch (e) {}
		},
		noop() {},
		startCountdown() {
			this.stopCountdown();
			let sec = parseInt(this.duration, 10);
			if (isNaN(sec) || sec < 1) sec = 3;
			this.countdown = sec;
			this.timer = setInterval(() => {
				this.countdown--;
				if (this.countdown <= 0) {
					this.stopCountdown();
					this.$emit('close');
				}
			}, 1000);
		},
		stopCountdown() {
			if (this.timer) {
				clearInterval(this.timer);
				this.timer = null;
			}
		},
		close() {
			this.stopCountdown();
			this.$emit('close');
		},
		onTap() {
			const url = this.url;
			this.close();
			if (!url) return;
			// 站外链接
			if (/^https?:\/\//i.test(url)) {
				// #ifdef APP-PLUS
				plus.runtime.openURL(url);
				// #endif
				// #ifndef APP-PLUS
				try {
					window.open(url, '_blank');
				} catch (e) {}
				// #endif
				return;
			}
			// 站内页面：优先 navigateTo，失败（如 tabBar 页）再 switchTab
			uni.navigateTo({
				url,
				fail: () => {
					uni.switchTab({ url });
				}
			});
		}
	}
};
</script>

<style scoped>
.splash-mask {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	z-index: 9998;
	background: #000000;
	overflow: hidden;
}

/* 同图模糊背景：铺满整屏填充留白（放大 1.2 倍避免模糊边缘露白） */
.splash-bg {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	filter: blur(24px);
	transform: scale(1.2);
	opacity: 0.7;
	z-index: 0;
}

/* 广告原图：完整展示（contain），叠在模糊背景之上 */
.splash-image {
	position: relative;
	z-index: 1;
	width: 100%;
	height: 100%;
	display: block;
}

.splash-skip {
	position: absolute;
	top: calc(var(--status-bar-height) + 40rpx);
	right: 40rpx;
	padding: 10rpx 28rpx;
	font-size: 26rpx;
	color: #ffffff;
	background: rgba(0, 0, 0, 0.45);
	border: 1rpx solid rgba(255, 255, 255, 0.4);
	border-radius: 999rpx;
	z-index: 2;
}

/* 底部品牌栏：应用 logo + 名称 + 跳转提示 */
.splash-footer {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 2;
	padding: 80rpx 40rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.55) 100%);
}

.splash-brand {
	display: flex;
	align-items: center;
	margin-bottom: 16rpx;
}

.splash-logo {
	width: 60rpx;
	height: 60rpx;
	border-radius: 14rpx;
	margin-right: 16rpx;
	background: #ffffff;
	padding: 6rpx;
	box-sizing: border-box;
}

.splash-name {
	font-size: 32rpx;
	font-weight: bold;
	color: #ffffff;
	letter-spacing: 2rpx;
}

.splash-hint {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.85);
}
</style>
