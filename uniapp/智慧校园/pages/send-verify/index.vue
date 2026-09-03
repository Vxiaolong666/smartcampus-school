<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">发送验证邮件</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="logo-section">
				<image class="logo" src="/static/assets/wxmp-email.svg" mode="widthFix"></image>
				<view class="app-name">验证邮箱</view>
				<view class="app-desc">重新发送注册验证邮件</view>
			</view>

			<view class="form-card">
				<view class="card-header">
					<image class="card-icon" src="/static/assets/wxmp-email.svg" mode="widthFix"></image>
					<text class="card-title">输入注册邮箱</text>
				</view>

				<view class="form-item">
					<view class="input-wrapper">
						<image class="input-icon" src="/static/assets/wxmp-email.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="text"
							placeholder="请输入注册时的邮箱"
							:value="email"
							@input="onEmailInput"
						/>
					</view>
				</view>

				<view class="tips">
					<text>如果您的账号尚未验证邮箱，输入注册邮箱后我们将重新发送验证邮件。 </text>
				</view>

				<button class="submit-btn" @click="onSubmit" :loading="loading">
					<text>发送验证邮件</text>
				</button>

				<view class="bottom-links">
					<text class="link" @click="onLogin">返回登录</text>
					<text class="divider-text">|</text>
					<text class="link" @click="onForgot">忘记密码</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 发送验证邮件页面
 * 处理验证邮件发送流程
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			email: '',
			loading: false
		};
	},

	methods: {
		onEmailInput(e) {
			this.email = e.detail.value;
		},

		onSubmit() {
			const { email, loading } = this;

			if (loading) return;

			if (!email.trim()) {
				uni.showToast({ title: '请输入邮箱', icon: 'none' });
				return;
			}

			this.loading = true;
			uni.showLoading({ title: '发送中...' });

			api.sendVerifyEmail(email)
				.then(res => {
					uni.hideLoading();
					this.loading = false;

					uni.showModal({
						title: '发送成功',
						content: res.msg,
						showCancel: false,
						success: () => {
							uni.navigateBack();
						}
					});
				})
				.catch(err => {
					uni.hideLoading();
					this.loading = false;
					uni.showToast({ title: err.msg || '发送失败', icon: 'none' });
				});
		},

		onLogin() {
			uni.navigateBack();
		},

		onForgot() {
			uni.navigateTo({ url: '/pages/forgot/index' });
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

.tips {
	padding: 20rpx 30rpx;
	font-size: 24rpx;
	color: #948eb3;
	line-height: 1.6;
}

.submit-btn {
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

.submit-btn:active {
	opacity: 0.9;
}

.bottom-links {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 30rpx;
	font-size: 26rpx;
}

.bottom-links .link {
	color: #13c2c2;
}

.bottom-links .divider-text {
	color: #e5e5e5;
	margin: 0 20rpx;
}
</style>
