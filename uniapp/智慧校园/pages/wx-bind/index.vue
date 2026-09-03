<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">微信绑定</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<block v-if="loaded">
				<view class="bind-card">
					<view class="card-header">
						<image class="card-logo" src="/static/assets/wxmp-wechat.svg" mode="widthFix"></image>
						<view class="card-title">微信账号绑定</view>
						<view class="card-desc">绑定微信后可使用微信快捷登录</view>
					</view>

					<view class="status-section">
						<view class="status-icon">
							<image v-if="hasWxOpenid" class="status-icon-img" src="/static/assets/wxmp-check.svg" mode="aspectFit"></image>
							<image v-else class="status-icon-img" src="/static/assets/wxmp-error.svg" mode="aspectFit"></image>
						</view>
						<view class="status-text">{{ hasWxOpenid ? '已绑定微信账号' : '未绑定微信账号' }}</view>
						<view class="status-desc" v-if="hasWxOpenid">您可以使用微信快捷登录</view>
						<view class="status-desc" v-else>绑定后可使用微信快捷登录</view>
					</view>

					<view v-if="hasWxOpenid" class="info-section">
						<view class="info-item">
							<view class="info-label">OpenID</view>
							<view class="info-value openid">{{ openidDisplay }}</view>
						</view>
					</view>

					<button v-if="hasWxOpenid" class="unbind-btn" @click="onUnbind" :loading="loading">解除绑定</button>
					<button v-else class="bind-btn" @click="onBind" :loading="loading">立即绑定</button>
				</view>

				<view class="tips-card">
					<view class="tips-title">温馨提示</view>
					<view class="tips-list">
						<view class="tips-item">• 绑定微信后，可使用微信快捷登录</view>
						<view class="tips-item">• 解除绑定后将无法使用微信登录</view>
						<view class="tips-item">• 每个微信账号只能绑定一个账号</view>
					</view>
				</view>
			</block>

			<block v-else>
				<view class="bind-card">
					<view class="card-header">
						<view class="wxb-sk wxb-sk-logo wxb-sk-anim"></view>
						<view class="wxb-sk wxb-sk-title wxb-sk-anim"></view>
						<view class="wxb-sk wxb-sk-desc wxb-sk-anim"></view>
					</view>

					<view class="status-section">
						<view class="wxb-sk wxb-sk-status-icon wxb-sk-anim"></view>
						<view class="wxb-sk wxb-sk-status-text wxb-sk-anim"></view>
						<view class="wxb-sk wxb-sk-status-desc wxb-sk-anim"></view>
					</view>

					<view class="wxb-sk wxb-sk-info wxb-sk-anim"></view>
					<view class="wxb-sk wxb-sk-btn wxb-sk-anim"></view>
				</view>

				<view class="tips-card">
					<view class="wxb-sk wxb-sk-tips-title wxb-sk-anim"></view>
					<view class="wxb-sk wxb-sk-line wxb-sk-anim"></view>
					<view class="wxb-sk wxb-sk-line wxb-sk-anim"></view>
					<view class="wxb-sk wxb-sk-line wxb-sk-line-short wxb-sk-anim"></view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
/**
 * 微信绑定页面
 * 处理用户微信账号的绑定与解绑操作
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			hasWxOpenid: false,
			openid: '',
			openidDisplay: '',
			loaded: false,
			loading: false
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			this.loaded = true;
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}
		this.loadBindStatus();
	},

	onShow() {
		this.loadBindStatus();
	},

	methods: {
		async loadBindStatus() {
			try {
				const res = await api.getUserInfo();
				const userInfo = res.data;

				const hasWxOpenid = !!userInfo.wx_openid;
				const openid = userInfo.wx_openid || '';

				const openidDisplay = openid
					? openid.substring(0, 8) + '...' + openid.substring(openid.length - 8)
					: '';

				this.hasWxOpenid = hasWxOpenid;
				this.openid = openid;
				this.openidDisplay = openidDisplay;
			} catch (err) {
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			} finally {
				this.loaded = true;
			}
		},

		async onBind() {
			// #ifdef MP-WEIXIN
			this.loading = true;

			try {
				const loginRes = await uni.login();

				if (!loginRes.code) {
					throw new Error('获取登录凭证失败');
				}

				await api.request({
					act: 'bind_wx',
					method: 'POST',
					data: { code: loginRes.code }
				});

				uni.showToast({ title: '绑定成功', icon: 'success' });
				this.loadBindStatus();
			} catch (err) {
				uni.showToast({ title: err.msg || '绑定失败', icon: 'none' });
			} finally {
				this.loading = false;
			}
			// #endif
			// #ifndef MP-WEIXIN
			uni.showToast({ title: '当前平台暂不支持微信绑定', icon: 'none' });
			// #endif
		},

		async onUnbind() {
			const modalRes = await new Promise(resolve => {
				uni.showModal({
					title: '确认解绑',
					content: '解绑后将无法使用微信快捷登录，确定要解绑吗？',
					success: resolve
				});
			});

			if (!modalRes.confirm) return;

			this.loading = true;

			try {
				await api.request({
					act: 'unbind_wx',
					method: 'POST'
				});

				uni.showToast({ title: '解绑成功', icon: 'success' });
				this.loadBindStatus();
			} catch (err) {
				uni.showToast({ title: err.msg || '解绑失败', icon: 'none' });
			} finally {
				this.loading = false;
			}
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
}

.wxb-sk {
	background: linear-gradient(90deg, #e9ecf5 25%, #f7f9fe 37%, #e9ecf5 63%);
	background-size: 400% 100%;
	border-radius: 8rpx;
}

.wxb-sk-anim {
	animation: wxbind-skeleton-shimmer 1.4s ease infinite;
}

.wxb-sk-logo {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
}

.wxb-sk-title {
	width: 280rpx;
	height: 36rpx;
	margin-top: 24rpx;
}

.wxb-sk-desc {
	width: 360rpx;
	height: 24rpx;
	margin-top: 16rpx;
}

.wxb-sk-status-icon {
	width: 92rpx;
	height: 92rpx;
	border-radius: 50%;
}

.wxb-sk-status-text {
	width: 300rpx;
	height: 32rpx;
	margin-top: 24rpx;
}

.wxb-sk-status-desc {
	width: 340rpx;
	height: 24rpx;
	margin-top: 16rpx;
}

.wxb-sk-info {
	height: 100rpx;
	border-radius: 16rpx;
	margin-bottom: 30rpx;
}

.wxb-sk-btn {
	height: 96rpx;
	border-radius: 20rpx;
}

.wxb-sk-tips-title {
	width: 180rpx;
	height: 28rpx;
	margin-bottom: 24rpx;
}

.wxb-sk-line {
	height: 24rpx;
	margin-bottom: 12rpx;
}

.wxb-sk-line-short {
	width: 60%;
}

@keyframes wxbind-skeleton-shimmer {
	0% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0 50%;
	}
}
</style>

<style scoped>
.nav-title {
	height: 100%;
	display: flex;
	align-items: center;
	font-size: 32rpx;
	font-weight: bold;
	position: relative;
	z-index: 1;
	color: white;
	letter-spacing: 6rpx;
}

.background-wrapper {
	position: fixed;
	width: 100%;
	top: 0;
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
	background: #eff1ff;
	top: 30rpx;
	min-height: calc(100vh - 200rpx);
	border-top-left-radius: 60rpx;
	border-top-right-radius: 60rpx;
	box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
	padding: 30rpx;
	padding-bottom: 100rpx;
}

.bind-card {
	background: white;
	border-radius: 30rpx;
	padding: 50rpx 40rpx;
	margin-top: 25rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.card-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 50rpx;
	padding-bottom: 40rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.card-logo {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 20rpx;
}

.card-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 10rpx;
}

.card-desc {
	font-size: 24rpx;
	color: #948eb3;
}

.status-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 40rpx;
}

.status-icon {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 20rpx;
}

.status-icon-img {
	width: 92rpx;
	height: 92rpx;
}

.status-text {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 8rpx;
}

.status-desc {
	font-size: 24rpx;
	color: #948eb3;
}

.info-section {
	background: #f7f8fc;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 30rpx;
}

.info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #e5e5e5;
}

.info-item:last-child {
	border-bottom: none;
}

.info-label {
	font-size: 26rpx;
	color: #948eb3;
}

.info-value {
	font-size: 26rpx;
	color: #48416f;
}

.info-value.openid {
	font-size: 22rpx;
	color: #948eb3;
	max-width: 300rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.bind-btn {
	width: 100%;
	height: 96rpx;
	background: #13c2c2;
	color: #fff;
	font-size: 30rpx;
	border-radius: 20rpx;
	font-weight: bold;
	box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
	display: flex;
	align-items: center;
	justify-content: center;
}

.bind-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.unbind-btn {
	width: 100%;
	height: 96rpx;
	background: white;
	color: #ff4d4f;
	font-size: 30rpx;
	border-radius: 20rpx;
	font-weight: bold;
	border: 2rpx solid #ff4d4f;
	display: flex;
	align-items: center;
	justify-content: center;
}

.unbind-btn:active {
	opacity: 0.9;
}

.tips-card {
	background: white;
	border-radius: 30rpx;
	padding: 30rpx 40rpx;
	margin-top: 30rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.tips-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
}

.tips-list {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.tips-item {
	font-size: 24rpx;
	color: #948eb3;
	line-height: 1.6;
}
</style>
