<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">登录</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="logo-section">
				<image class="logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
				<view class="app-name">校园服务</view>
				<view class="app-desc">便捷的校园生活助手</view>
			</view>

			<view class="form-card">
				<view class="card-header">
					<image class="card-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
					<text class="card-title">账号登录</text>
				</view>

				<view class="form-item">
					<view class="input-wrapper">
						<image class="input-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="text"
							placeholder="请输入用户名"
							:value="username"
							@input="onUsernameInput"
						/>
					</view>
				</view>

				<view class="form-item">
					<view class="input-wrapper">
						<image class="input-icon" src="/static/assets/wxmp-lock.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="password"
							placeholder="请输入密码"
							:value="password"
							@input="onPasswordInput"
						/>
					</view>
				</view>

				<view class="forgot-link" @click="onForgot">忘记密码？</view>
				<view class="verify-link" @click="onSendVerify">未验证邮箱？</view>

				<button class="login-btn" @click="onLogin" :loading="loading">
					<text>登 录</text>
				</button>

				<view class="divider">
					<view class="divider-line"></view>
					<text class="divider-text">其他登录方式</text>
					<view class="divider-line"></view>
				</view>

				<!-- 第三方账号登录（聚合登录） -->
				<view v-if="oauthChannels.length > 0" class="oauth-area">
					<view
						v-for="(c, i) in oauthChannels"
						:key="i"
						class="oauth-btn"
						@click="onOauthLogin(c)"
					>
						<image v-if="c.icon" class="oauth-logo" :src="c.icon" mode="aspectFit"></image>
						<view v-else class="oauth-badge" :style="{ backgroundColor: c.color }">{{ c.short }}</view>
						<text class="oauth-name">{{ c.name }}</text>
					</view>
				</view>

				<!-- #ifdef MP-WEIXIN -->
				<button class="wx-login-btn" @click="onWxLogin" :loading="wxLoading">
					<image class="wx-icon" src="/static/assets/wxmp-wechat.svg" mode="widthFix"></image>
					<text>微信一键登录</text>
				</button>
				<!-- #endif -->

				<view class="register-link">
					<text>还没有账号？</text>
					<text class="link" @click="onRegister">立即注册</text>
				</view>

				<view class="agreement-link">
					<text>登录即代表同意</text>
					<text class="link" @click="onPrivacy">《隐私协议》</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 登录页面
 * 处理账号密码登录、微信一键登录与第三方账号（聚合登录）登录
 */
import api from '../../utils/api.js';

// 渠道品牌图标（static/logo 下已提供 SVG 的渠道优先使用图标，其余回退为文字徽标）
const CHANNEL_ICONS = {
	qq: '/static/logo/qq.svg',
	wx: '/static/logo/weixin.svg',
	alipay: '/static/logo/zifubao.svg',
	douyin: '/static/logo/douyin.svg',
	huawei: '/static/logo/huawei.svg',
	xiaomi: '/static/logo/xiaomi.svg',
	google: '/static/logo/google.svg',
	twitter: '/static/logo/twitter.svg',
	feishu: '/static/logo/feishu.svg'
};

// 渠道配色覆盖（QQ 品牌以黑色为主，文字/文字徽标统一用黑色）
const COLOR_OVERRIDES = {
	qq: '#000000'
};

// 渠道短标识（无图标资源时用文字徽标区分渠道）
const SHORT_NAMES = {
	qq: 'QQ',
	wx: '微信',
	alipay: '支',
	sina: '博',
	baidu: '百',
	douyin: '抖',
	huawei: '华',
	xiaomi: '米',
	google: 'G',
	microsoft: 'MS',
	facebook: 'f',
	twitter: 'T',
	feishu: '飞',
	wework: '企',
	dingtalk: '钉',
	gitee: 'Gt',
	github: 'GH',
	telegram: 'Tg'
};

export default {
	data() {
		return {
			username: '',
			password: '',
			loading: false,
			wxLoading: false,

			// 第三方登录
			oauthChannels: [],
			oauthLoading: false,
			oauthTicket: '',
			oauthPolling: false
		};
	},

	onLoad(options) {
		// 如果已登录，直接跳转到首页
		if (api.isLoggedIn()) {
			uni.switchTab({ url: '/pages/white-home/index' });
			return;
		}
		this.loadOauthChannels();
		// 恢复未完成的第三方登录票据（如授权途中页面被重建；超过 10 分钟视为失效）
		const ticket = uni.getStorageSync('oauth_login_ticket') || '';
		const ticketTime = parseInt(uni.getStorageSync('oauth_login_ticket_time') || 0, 10);
		if (ticket && (Date.now() - ticketTime) < 10 * 60 * 1000) {
			this.oauthTicket = ticket;
		} else if (ticket) {
			this.clearOauthTicket();
		}
	},

	onShow() {
		// 从授权页 / 授权窗口返回后，轮询领取登录结果
		if (!api.isLoggedIn() && this.oauthTicket) {
			this.pollOauthLogin();
		}
	},

	methods: {
		//统一登录成功处理（密码 / 微信 / 第三方）
		handleLoginSuccess(data) {
			api.setToken(data.token);
			api.setUserInfoCache(data);

			// 冷静期内登录已自动撤销注销，给出提示
			if (data && parseInt(data.cancel_revoked) === 1) {
				uni.showModal({
					title: '提示',
					content: '您此前提交的账号注销申请已自动撤销，账号已恢复正常。',
					showCancel: false,
					success: () => {
						uni.switchTab({ url: '/pages/white-home/index' });
					}
				});
				return;
			}

			uni.showToast({ title: '登录成功', icon: 'success' });
			setTimeout(() => {
				uni.switchTab({ url: '/pages/white-home/index' });
			}, 1000);
		},

		//加载已启用的第三方登录渠道
		loadOauthChannels() {
			api.getOauthChannels()
				.then(res => {
					const d = res.data || {};
					if (parseInt(d.enabled) !== 1) return;
					const channelMap = d.channels || {};
					this.oauthChannels = Object.keys(channelMap).map(type => {
						const meta = channelMap[type] || {};
						const color = COLOR_OVERRIDES[type] || meta.color || '#6c757d';
						const name = meta.name || type;
						return {
							type: type,
							name: name,
							color: color,
							icon: CHANNEL_ICONS[type] || '',
							short: SHORT_NAMES[type] || String(name).substring(0, 1)
						};
					});
				})
				.catch(() => {
					// 第三方登录不可用时静默降级，不影响账号密码登录
					this.oauthChannels = [];
				});
		},

		//发起第三方登录：取授权地址 → 打开授权页 → 轮询领取令牌
		onOauthLogin(channel) {
			if (this.oauthLoading) return;
			this.oauthLoading = true;
			uni.showLoading({ title: '正在跳转授权...' });

			api.getOauthLoginUrl(channel.type)
				.then(res => {
					uni.hideLoading();
					this.oauthLoading = false;

					const d = res.data || {};
					const url = d.url || '';
					const ticket = d.ticket || '';
					if (!url || !ticket) {
						uni.showToast({ title: '获取授权地址失败', icon: 'none' });
						return;
					}

					this.oauthTicket = ticket;
					uni.setStorageSync('oauth_login_ticket', ticket);
					uni.setStorageSync('oauth_login_ticket_time', Date.now());

					// #ifdef H5
					// H5 端授权页通常禁止 iframe 嵌套，改用新窗口打开，本页后台轮询
					const win = window.open(url, '_blank');
					if (!win) {
						// 弹窗被拦截时当前窗口跳转，返回后本页 onShow 会继续轮询
						window.location.href = url;
						return;
					}
					this.pollOauthLogin();
					// #endif
					// #ifndef H5
					uni.navigateTo({
						url: '/pages/oauth-bind-webview/index?url=' + encodeURIComponent(url)
					});
					// #endif
				})
				.catch(err => {
					uni.hideLoading();
					this.oauthLoading = false;
					uni.showToast({ title: (err && err.msg) || '获取授权地址失败', icon: 'none' });
				});
		},

		//轮询登录结果（授权完成后由服务端签发正式令牌）
		pollOauthLogin() {
			if (this.oauthPolling || !this.oauthTicket) return;
			this.oauthPolling = true;

			let attempts = 0;
			const maxAttempts = 40; // 1.5s × 40 ≈ 60s
			const interval = 1500;

			const tick = () => {
				if (!this.oauthTicket) {
					this.oauthPolling = false;
					return;
				}

				attempts++;

				api.oauthLoginPoll(this.oauthTicket)
					.then(res => {
						const d = res.data || {};

						// 授权完成：拿到正式登录令牌
						if (d.status === 'ok' && d.token) {
							this.oauthPolling = false;
							this.clearOauthTicket();
							this.handleLoginSuccess(d);
							return;
						}

						// 尚未授权完成：静默继续等待，避免遮挡页面
						if (attempts >= maxAttempts) {
							this.oauthPolling = false;
							uni.showToast({ title: '尚未完成授权，可重新点击渠道重试', icon: 'none' });
							return;
						}
						setTimeout(tick, interval);
					})
					.catch(err => {
						this.oauthPolling = false;
						this.clearOauthTicket();
						uni.showToast({ title: (err && err.msg) || '第三方登录失败', icon: 'none' });
					});
			};

			tick();
		},

		clearOauthTicket() {
			this.oauthTicket = '';
			uni.removeStorageSync('oauth_login_ticket');
			uni.removeStorageSync('oauth_login_ticket_time');
		},

		onUsernameInput(e) {
			this.username = e.detail.value;
		},

		onPasswordInput(e) {
			this.password = e.detail.value;
		},

		onLogin() {
			const { username, password, loading } = this;

			if (loading) return;

			if (!username.trim()) {
				uni.showToast({ title: '请输入用户名', icon: 'none' });
				return;
			}

			if (!password.trim()) {
				uni.showToast({ title: '请输入密码', icon: 'none' });
				return;
			}

			this.loading = true;
			uni.showLoading({ title: '登录中...' });

			api.login(username, password)
				.then(res => {
					uni.hideLoading();
					this.loading = false;
					this.handleLoginSuccess(res.data);
				})
				.catch(err => {
					uni.hideLoading();
					this.loading = false;
					uni.showToast({ title: err.msg || '登录失败', icon: 'none' });
				});
		},

		async onWxLogin() {
			// #ifdef MP-WEIXIN
			if (this.wxLoading) return;

			this.wxLoading = true;
			uni.showLoading({ title: '登录中...' });

			try {
				const loginRes = await uni.login();

				const res = await api.request({
					act: 'wx_login',
					method: 'POST',
					data: { code: loginRes.code }
				});

				uni.hideLoading();
				this.wxLoading = false;
				this.handleLoginSuccess(res.data);
			} catch (err) {
				uni.hideLoading();
				this.wxLoading = false;
				uni.showToast({ title: err.msg || '微信登录失败', icon: 'none' });
			}
			// #endif
			// #ifndef MP-WEIXIN
			uni.showToast({ title: '当前平台暂不支持微信登录', icon: 'none' });
			// #endif
		},

		onRegister() {
			uni.navigateTo({ url: '/pages/register/index' });
		},

		onForgot() {
			uni.navigateTo({ url: '/pages/forgot/index' });
		},

		onSendVerify() {
			uni.navigateTo({ url: '/pages/send-verify/index' });
		},

		onPrivacy() {
			uni.navigateTo({ url: '/pages/privacy/index' });
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
	height: 100vh;
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
	background-image: linear-gradient(
		to bottom,
		rgba(0, 0, 0, 0.8) 0%,
		rgba(0, 0, 0, 0.4) 15%,
		transparent 40%,
		transparent 60%,
		rgba(239, 241, 255, 0.3) 75%,
		rgba(239, 241, 255, 0.7) 85%,
		rgba(239, 241, 255, 1) 100%
	);
	height: 800rpx;
	position: absolute;
	width: 100%;
	top: 0;
}

.content-wrapper {
	position: relative;
	z-index: 1;
	padding: 40rpx;
	padding-top: 180rpx;
}

.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 50rpx;
}

.logo {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 16rpx;
}

.app-name {
	font-size: 36rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 8rpx;
}

.app-desc {
	font-size: 24rpx;
	color: #948eb3;
}

.form-card {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.card-header {
	display: flex;
	align-items: center;
	padding: 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.card-icon {
	width: 36rpx;
	height: 36rpx;
	margin-right: 12rpx;
}

.card-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
}

.form-item {
	padding: 0 30rpx;
	margin-top: 30rpx;
}

.input-wrapper {
	display: flex;
	align-items: center;
	background: #f7f8fc;
	border-radius: 16rpx;
	padding: 0 20rpx;
	height: 96rpx;
}

.input-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 16rpx;
	flex-shrink: 0;
}

.form-input {
	flex: 1;
	height: 96rpx;
	font-size: 28rpx;
	color: #48416f;
}

.form-input::placeholder {
	color: #948eb3;
}

.forgot-link {
	text-align: right;
	font-size: 24rpx;
	color: #13c2c2;
	padding: 0 30rpx;
	margin-top: 20rpx;
}

.verify-link {
	text-align: right;
	font-size: 24rpx;
	color: #13c2c2;
	padding: 0 30rpx;
	margin-top: 10rpx;
}

.login-btn {
	width: calc(100% - 60rpx);
	height: 96rpx;
	line-height: 96rpx;
	background: #13c2c2;
	color: #fff;
	font-size: 30rpx;
	border-radius: 16rpx;
	font-weight: bold;
	margin: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.login-btn:active {
	opacity: 0.9;
}

.divider {
	display: flex;
	align-items: center;
	padding: 0 30rpx;
	margin-bottom: 30rpx;
}

.divider-line {
	flex: 1;
	height: 1rpx;
	background: #e5e5e5;
}

.divider-text {
	padding: 0 20rpx;
	font-size: 22rpx;
	color: #948eb3;
}

.wx-login-btn {
	width: calc(100% - 60rpx);
	height: 96rpx;
	line-height: 96rpx;
	background: transparent;
	color: #07c160;
	font-size: 30rpx;
	border-radius: 16rpx;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 30rpx;
	border: 2rpx solid #07c160;
	box-sizing: border-box;
	white-space: nowrap;
}

.wx-login-btn::before {
	display: none;
}

.wx-login-btn:active {
	opacity: 0.9;
}

.wx-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 12rpx;
}

.register-link {
	text-align: center;
	padding: 30rpx;
	font-size: 26rpx;
	color: #948eb3;
}

.register-link .link {
	color: #13c2c2;
	font-weight: bold;
}

.agreement-link {
	text-align: center;
	padding: 0 30rpx 30rpx;
	font-size: 22rpx;
	color: #948eb3;
}

.agreement-link .link {
	color: #13c2c2;
}

/* 第三方账号登录 */
.oauth-area {
	display: flex;
	flex-wrap: wrap;
	/* 渠道数为奇数（如仅一个）时，最后一行居中展示 */
	justify-content: center;
	padding: 0 30rpx;
	margin-bottom: 24rpx;
}

.oauth-btn {
	/* 每行 2 个：宽度 + 左右外边距 ≈ 50%（留 2rpx 余量避免子像素取整换行），多余渠道换行后居中 */
	width: calc(50% - 22rpx);
	height: 92rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	/* 胶囊圆角，与网页版 rounded-pill 一致 */
	border-radius: 46rpx;
	background: #ffffff;
	border: 2rpx solid #eef0f6;
	box-sizing: border-box;
	margin: 0 10rpx 20rpx;
	box-shadow: 0 6rpx 18rpx rgba(72, 65, 111, 0.05);
}

.oauth-btn:active {
	background: #f5fbfb;
	border-color: #c8ecec;
	box-shadow: none;
}

/* 无图标资源时的文字徽标 */
.oauth-badge {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	color: #fff;
	font-size: 22rpx;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 14rpx;
	flex-shrink: 0;
}

/* 渠道品牌图标（SVG） */
.oauth-logo {
	width: 48rpx;
	height: 48rpx;
	margin-right: 14rpx;
	flex-shrink: 0;
}

.oauth-name {
	font-size: 28rpx;
	font-weight: 600;
	letter-spacing: 1rpx;
	color: #000000;
}
</style>
