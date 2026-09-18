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
 * 处理账号密码登录和微信登录
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			username: '',
			password: '',
			loading: false,
			wxLoading: false
		};
	},

	onLoad(options) {
		// 如果已登录，直接跳转到首页
		if (api.isLoggedIn()) {
			uni.switchTab({ url: '/pages/white-home/index' });
		}
	},

	methods: {
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

					api.setToken(res.data.token);
					api.setUserInfoCache(res.data);

					// 冷静期内登录已自动撤销注销，给出提示
					if (res.data && parseInt(res.data.cancel_revoked) === 1) {
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

				api.setToken(res.data.token);
				api.setUserInfoCache(res.data);

				// 冷静期内登录已自动撤销注销，给出提示
				if (res.data && parseInt(res.data.cancel_revoked) === 1) {
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
</style>
