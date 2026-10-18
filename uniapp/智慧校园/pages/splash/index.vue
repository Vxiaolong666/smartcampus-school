<template>
	<view class="splash-page" @touchmove.stop.prevent="noop">
		<!-- 广告图已就绪：铺满整屏 + 跳过 + 底部品牌栏 -->
		<template v-if="image">
			<view class="splash-bg">
				<image class="splash-bg-img" :src="image" mode="aspectFill"></image>
			</view>
			<image class="splash-image" :src="image" mode="aspectFit" @click="onTap"></image>
			<view class="splash-skip" @click="goHome">
				<text class="splash-skip-label">跳过</text>
				<text class="splash-skip-num">{{ countdown }}s</text>
			</view>
			<view class="splash-footer" @click="onTap">
				<view class="splash-brand">
					<image class="splash-logo" src="/static/assets/wxmp-logo.svg" mode="aspectFit"></image>
					<text class="splash-name">{{ name }}</text>
					<text class="splash-ad-tag">广告</text>
				</view>
				<view v-if="url" class="splash-hint">点击跳转到详情页</view>
				<view class="splash-disclaimer" @click.stop="noop">广告内容由广告主提供，本平台仅提供展示，请注意甄别</view>
			</view>

			<!-- 底部倒计时进度条：随展示时长线性收缩 -->
			<view class="splash-progress">
				<view class="splash-progress-bar" :style="'animation-duration: ' + duration + 's'"></view>
			</view>
		</template>

		<!-- 广告未就绪 / 无广告：品牌启动页（首页内容不会提前露出） -->
		<view v-else class="splash-loading">
			<view class="splash-loading-glow"></view>
			<view class="splash-loading-card">
				<image class="splash-loading-logo" src="/static/assets/wxmp-logo.svg" mode="aspectFit"></image>
			</view>
			<text class="splash-loading-name">{{ name }}</text>
			<text class="splash-loading-sub">一站式智慧校园生活助手</text>
			<view class="splash-loading-dots">
				<view class="splash-loading-dot"></view>
				<view class="splash-loading-dot"></view>
				<view class="splash-loading-dot"></view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 独立开屏页（应用启动入口）
 *
 * 流程：
 * 1. 首帧显示品牌启动页，避免首页内容先渲染再被广告盖住；
 * 2. 并行拉取开屏广告（带缓存 / 冷却 / 1.2s 超时）；
 * 3. 有广告 → 图片就绪后开始倒计时，可点击跳过或点击跳转；
 * 4. 无广告 / 冷却中 / 超时 / 请求失败无缓存 → 立即 reLaunch 到首页。
 *
 * 首页不再承担广告逻辑，版本更新检查也回到首页 onLoad，两个弹层不会互相冲突。
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

const CACHE_KEY = 'splash_ad_cache';
const SHOWN_KEY = 'splash_ad_shown_at';
const COOLDOWN = 30 * 60 * 1000; // 同会话刷新 30 分钟内不重复展示
const CACHE_TTL = 3 * 24 * 60 * 60 * 1000;

// 会话标记：区分「首次进入」与「同一会话内刷新」
function splashSessionHandled() {
	let handled = false;
	// #ifdef H5
	try { handled = window.sessionStorage.getItem('splash_session') === '1'; } catch (e) {}
	// #endif
	// #ifndef H5
	try {
		const app = getApp();
		handled = !!(app && app.globalData && app.globalData.__splashSession);
	} catch (e) {}
	// #endif
	return handled;
}

function markSplashSession() {
	// #ifdef H5
	try { window.sessionStorage.setItem('splash_session', '1'); } catch (e) {}
	// #endif
	// #ifndef H5
	try {
		const app = getApp();
		if (app) {
			app.globalData = app.globalData || {};
			app.globalData.__splashSession = true;
		}
	} catch (e) {}
	// #endif
}

export default {
	data() {
		return {
			image: '',
			url: '',
			name: '智慧校园',
			countdown: 3,
			// 广告总时长（秒）：用于底部进度条动画时长
			duration: 3
		};
	},

	onLoad() {
		this._closed = false;
		this.loadAd();
	},

	onUnload() {
		this.stopCountdown();
	},

	methods: {
		async loadAd() {
			// 维护状态优先：维护中直接进入维护页，不展示开屏广告
			const maintaining = await this.checkMaintenance();
			if (maintaining) return;

			// 同一会话内刷新且未过冷却期 → 不再展示
			if (splashSessionHandled()) {
				let lastShown = 0;
				try { lastShown = uni.getStorageSync(SHOWN_KEY) || 0; } catch (e) {}
				if (lastShown && (Date.now() - lastShown) < COOLDOWN) {
					this.goHome();
					return;
				}
			}

			let cached = null;
			try { cached = uni.getStorageSync(CACHE_KEY) || null; } catch (e) {}

			try {
				// 最多等 1.2s：接口过慢时不再让用户干等，直接进首页
				const res = await this.withTimeout(api.getSplashAd('app'), 1200);
				const d = res && res.data;
				if (!d || !(d.image_url || d.image)) {
					// 后台明确无广告 / 已关闭 → 清缓存
					try { uni.removeStorageSync(CACHE_KEY); } catch (e) {}
					this.goHome();
					return;
				}
				const config = {
					image: d.image_url || d.image,
					localImage: '',
					url: d.url || '',
					duration: parseInt(d.duration, 10) || 3,
					name: d.site_name || '智慧校园',
					ts: Date.now()
				};
				this.showAd(config);
				try { uni.setStorageSync(CACHE_KEY, config); } catch (e) {}
				this.cacheImage(config);
			} catch (e) {
				if (this._closed) return;
				// 接口失败（非超时）→ 用缓存兜底；超时则直接进首页
				const timedOut = e && e.message === 'splash timeout';
				if (!timedOut && this.isCacheValid(cached)) {
					this.showAd(cached);
				} else {
					this.goHome();
				}
			}
		},

		// 维护开关查询：开启时直接跳维护页（查询失败/超时视为未维护，不阻塞启动）
		async checkMaintenance() {
			try {
				const res = await this.withTimeout(feature.checkMaintenanceSwitch(), 1000);
				if (res && res.enabled) {
					this.goMaintenance();
					return true;
				}
			} catch (e) {}
			return false;
		},

		goMaintenance() {
			if (this._closed) return;
			this._closed = true;
			this.stopCountdown();
			setTimeout(() => {
				uni.reLaunch({ url: '/pages/maintenance/index' });
			}, 0);
		},

		showAd(config) {
			if (this._closed) return;
			this.image = config.localImage || config.image;
			this.url = config.url || '';
			this.name = config.name || '智慧校园';
			const sec = parseInt(config.duration, 10);
			const duration = isNaN(sec) || sec < 1 ? 3 : sec;
			this.duration = duration;
			this.startCountdown(duration);
			try { uni.setStorageSync(SHOWN_KEY, Date.now()); } catch (e) {}
			markSplashSession();
		},

		// 缓存是否可用（有图片且未过期）
		isCacheValid(c) {
			if (!c || !(c.localImage || c.image)) return false;
			if (c.ts && (Date.now() - c.ts) > CACHE_TTL) return false;
			return true;
		},

		// 把开屏图片保存到本地，供离线/加速展示（best effort）
		cacheImage(config) {
			// #ifdef APP-PLUS || MP-WEIXIN
			try {
				uni.downloadFile({
					url: config.image,
					success: (res) => {
						if (!res || res.statusCode !== 200 || !res.tempFilePath) return;
						uni.saveFile({
							tempFilePath: res.tempFilePath,
							success: (s) => {
								if (!s || !s.savedFilePath) return;
								config.localImage = s.savedFilePath;
								try { uni.setStorageSync(CACHE_KEY, config); } catch (e) {}
							}
						});
					}
				});
			} catch (e) {}
			// #endif
		},

		withTimeout(promise, ms) {
			return Promise.race([
				promise,
				new Promise((resolve, reject) => {
					setTimeout(() => reject(new Error('splash timeout')), ms);
				})
			]);
		},

		startCountdown(sec) {
			this.stopCountdown();
			this.countdown = sec;
			this.timer = setInterval(() => {
				this.countdown--;
				if (this.countdown <= 0) {
					this.goHome();
				}
			}, 1000);
		},

		stopCountdown() {
			if (this.timer) {
				clearInterval(this.timer);
				this.timer = null;
			}
		},

		// 进入首页（reLaunch：开屏页不留在页面栈，返回键不会回到广告）
		goHome() {
			if (this._closed) return;
			this._closed = true;
			this.stopCountdown();
			// 延迟一帧再跳转，避免在 onLoad 同步阶段直接 reLaunch
			setTimeout(() => {
				uni.reLaunch({ url: '/pages/white-home/index' });
			}, 0);
		},

		// 点击广告：先回首页，再跳转目标
		onTap() {
			const url = this.url;
			if (!url) {
				this.goHome();
				return;
			}
			if (this._closed) return;
			this._closed = true;
			this.stopCountdown();
			uni.reLaunch({
				url: '/pages/white-home/index',
				success: () => {
					if (/^https?:\/\//i.test(url)) {
						// #ifdef APP-PLUS
						plus.runtime.openURL(url);
						// #endif
						// #ifdef H5
						try { window.open(url, '_blank'); } catch (e) {}
						// #endif
						// #ifdef MP-WEIXIN
						uni.setClipboardData({
							data: url,
							success: () => uni.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' })
						});
						// #endif
						return;
					}
					uni.navigateTo({ url, fail: () => uni.switchTab({ url }) });
				}
			});
		},

		noop() {}
	}
};
</script>

<style scoped>
.splash-page {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: #000000;
	overflow: hidden;
}

/* 同图模糊背景：填充留白，缓慢流动 */
.splash-bg {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	filter: blur(24px);
	transform: scale(1.4);
	opacity: 0.85;
	z-index: 0;
	will-change: transform, filter;
	animation: splash-bg-flow 4s ease-in-out infinite alternate;
}

/* 模糊背景漂移 + 缩放（保持放大，避免边缘露出） */
@keyframes splash-bg-flow {
	0% {
		transform: scale(1.4) translate3d(-7%, -5%, 0) rotate(-1deg);
		filter: blur(18px);
	}
	33% {
		transform: scale(1.52) translate3d(6%, 5%, 0) rotate(1.2deg);
		filter: blur(26px);
	}
	66% {
		transform: scale(1.44) translate3d(-5%, 7%, 0) rotate(-0.8deg);
		filter: blur(20px);
	}
	100% {
		transform: scale(1.52) translate3d(7%, -5%, 0) rotate(1deg);
		filter: blur(22px);
	}
}

.splash-bg-img {
	width: 100%;
	height: 100%;
}

.splash-image {
	position: relative;
	z-index: 1;
	width: 100%;
	height: 100%;
	display: block;
	animation: splash-image-in 0.36s ease-out both;
}

/* 广告图淡入 + 轻微放大，避免生硬切换 */
@keyframes splash-image-in {
	from {
		opacity: 0;
		transform: scale(1.04);
	}
	to {
		opacity: 1;
		transform: scale(1);
	}
}

.splash-skip {
	position: absolute;
	top: calc(var(--status-bar-height, 0px) + 40rpx);
	right: 40rpx;
	display: flex;
	align-items: center;
	padding: 10rpx 24rpx;
	font-size: 26rpx;
	letter-spacing: 1rpx;
	color: #ffffff;
	background: rgba(0, 0, 0, 0.38);
	border: 1rpx solid rgba(255, 255, 255, 0.35);
	border-radius: 999rpx;
	box-shadow: 0 6rpx 18rpx rgba(0, 0, 0, 0.18);
	backdrop-filter: blur(10px);
	z-index: 3;
}

.splash-skip-label {
	opacity: 0.85;
}

/* 倒计时数字：加粗提亮，突出可跳过时间 */
.splash-skip-num {
	margin-left: 6rpx;
	font-weight: 600;
	font-variant-numeric: tabular-nums;
}

.splash-footer {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 3;
	padding: 160rpx 48rpx 40rpx;
	padding-bottom: calc(40rpx + constant(safe-area-inset-bottom));
	padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
	display: flex;
	flex-direction: column;
	align-items: center;
	background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.28) 45%, rgba(0, 0, 0, 0.72) 100%);
	animation: splash-footer-in 0.5s ease-out both;
}

/* 底部信息栏上滑淡入，与广告图淡入错开 */
@keyframes splash-footer-in {
	from {
		opacity: 0;
		transform: translateY(24rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

/* 品牌胶囊：半透明底 + 细边框，提升层次 */
.splash-brand {
	display: flex;
	align-items: center;
	margin-bottom: 14rpx;
	padding: 10rpx 28rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.16);
	border: 1rpx solid rgba(255, 255, 255, 0.22);
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.2);
}

.splash-logo {
	width: 58rpx;
	height: 58rpx;
	border-radius: 14rpx;
	margin-right: 16rpx;
	background: #ffffff;
	padding: 5rpx;
	box-sizing: border-box;
}

.splash-name {
	font-size: 30rpx;
	font-weight: bold;
	line-height: 1.2;
	color: #ffffff;
	letter-spacing: 1rpx;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.35);
}

/* 广告标识 */
.splash-ad-tag {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	margin-left: 18rpx;
	padding: 4rpx 12rpx;
	font-size: 20rpx;
	line-height: 1.2;
	color: rgba(255, 255, 255, 0.85);
	border: 1rpx solid rgba(255, 255, 255, 0.45);
	border-radius: 6rpx;
}

.splash-hint {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.85);
}

/* 广告免责声明 */
.splash-disclaimer {
	margin-top: 16rpx;
	padding: 0 20rpx;
	font-size: 20rpx;
	line-height: 1.6;
	color: rgba(255, 255, 255, 0.55);
	text-align: center;
	letter-spacing: 0.5rpx;
}

/* 底部倒计时进度条 */
.splash-progress {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	height: 4rpx;
	background: rgba(255, 255, 255, 0.18);
	z-index: 4;
}

.splash-progress-bar {
	height: 100%;
	width: 100%;
	background: linear-gradient(90deg, rgba(255, 255, 255, 0.6) 0%, #ffffff 100%);
	transform-origin: left center;
	animation-name: splash-progress-shrink;
	animation-timing-function: linear;
	animation-fill-mode: forwards;
}

@keyframes splash-progress-shrink {
	from {
		transform: scaleX(1);
	}
	to {
		transform: scaleX(0);
	}
}

/* 品牌启动页：无广告时清爽白底（渐变 + 主题色光晕） */
.splash-loading {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	background: linear-gradient(180deg, #ffffff 0%, #f4f8ff 100%);
	z-index: 1;
}

/* 主题色光晕：避免大片纯白显得空 */
.splash-loading-glow {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 620rpx;
	height: 620rpx;
	margin-left: -310rpx;
	margin-top: -400rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(19, 194, 194, 0.16) 0%, rgba(24, 144, 255, 0.1) 45%, rgba(24, 144, 255, 0) 72%);
}

/* logo 卡片：白底 + 柔和投影 */
.splash-loading-card {
	position: relative;
	width: 168rpx;
	height: 168rpx;
	border-radius: 44rpx;
	background: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 16rpx 44rpx rgba(24, 144, 255, 0.18);
}

.splash-loading-logo {
	width: 112rpx;
	height: 112rpx;
}

.splash-loading-name {
	position: relative;
	margin-top: 40rpx;
	font-size: 38rpx;
	font-weight: 600;
	color: #1f2d3d;
	letter-spacing: 6rpx;
}

.splash-loading-sub {
	position: relative;
	margin-top: 18rpx;
	font-size: 26rpx;
	color: #8a94a6;
	letter-spacing: 2rpx;
}

/* 底部三点加载动画 */
.splash-loading-dots {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 14vh;
	display: flex;
	align-items: center;
	justify-content: center;
}

.splash-loading-dot {
	width: 14rpx;
	height: 14rpx;
	margin: 0 10rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	opacity: 0.25;
	animation: splash-dot-pulse 1.2s ease-in-out infinite;
}

.splash-loading-dot:nth-child(2) {
	animation-delay: 0.16s;
}

.splash-loading-dot:nth-child(3) {
	animation-delay: 0.32s;
}

@keyframes splash-dot-pulse {
	0%, 100% { opacity: 0.25; transform: scale(0.75); }
	50% { opacity: 1; transform: scale(1); }
}
</style>
