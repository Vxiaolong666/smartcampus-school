<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">注册</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="logo-section">
				<image class="logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
				<view class="app-name">注册账号</view>
				<view class="app-desc">创建账号，享受校园服务</view>
			</view>

			<view class="form-card">
				<view class="card-header">
					<image class="card-icon" src="/static/assets/wxmp-edit.svg" mode="widthFix"></image>
					<text class="card-title">填写信息</text>
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
						<image class="input-icon" src="/static/assets/wxmp-email.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="text"
							placeholder="请输入邮箱"
							:value="email"
							@input="onEmailInput"
						/>
					</view>
				</view>

				<view class="form-item">
					<view class="input-wrapper">
						<image class="input-icon" src="/static/assets/wxmp-lock.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="password"
							placeholder="请输入密码（至少6位）"
							:value="password"
							@input="onPasswordInput"
						/>
					</view>
				</view>

				<view class="form-item">
					<view class="input-wrapper">
						<image class="input-icon" src="/static/assets/wxmp-lock.svg" mode="widthFix"></image>
						<input
							class="form-input"
							type="password"
							placeholder="请再次输入密码"
							:value="confirmPassword"
							@input="onConfirmPasswordInput"
						/>
					</view>
				</view>

				<button
					class="register-btn"
					@click="onRegister"
					:loading="loading"
					style="height: 95rpx; display: flex; box-sizing: border-box; left: -1rpx; top: 4rpx; width: 351rpx; position: relative"
				>
					<text>注 册</text>
				</button>

				<view class="login-link">
					<text>已有账号？</text>
					<text class="link" @click="onLogin">立即登录</text>
				</view>

				<view class="agreement-link">
					<text>注册即代表同意</text>
					<text class="link" @click="onPrivacy">《隐私协议》</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 注册页面
 * 处理用户注册流程
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			username: '',
			password: '',
			confirmPassword: '',
			email: '',
			loading: false
		};
	},

	methods: {
		onUsernameInput(e) {
			this.username = e.detail.value;
		},

		onPasswordInput(e) {
			this.password = e.detail.value;
		},

		onConfirmPasswordInput(e) {
			this.confirmPassword = e.detail.value;
		},

		onEmailInput(e) {
			this.email = e.detail.value;
		},

		onRegister() {
			const { username, password, confirmPassword, email, loading } = this;

			if (loading) return;

			if (!username.trim()) {
				uni.showToast({ title: '请输入用户名', icon: 'none' });
				return;
			}

			if (!email.trim()) {
				uni.showToast({ title: '请输入邮箱', icon: 'none' });
				return;
			}

			if (!password.trim()) {
				uni.showToast({ title: '请输入密码', icon: 'none' });
				return;
			}

			if (password.length < 6) {
				uni.showToast({ title: '密码长度至少6位', icon: 'none' });
				return;
			}

			if (password !== confirmPassword) {
				uni.showToast({ title: '两次密码不一致', icon: 'none' });
				return;
			}

			this.loading = true;
			uni.showLoading({ title: '注册中...' });

			api.register(username, password, email, confirmPassword)
				.then(res => {
					uni.hideLoading();
					this.loading = false;

					uni.showModal({
						title: '注册成功',
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
					uni.showToast({ title: err.msg || '注册失败', icon: 'none' });
				});
		},

		onLogin() {
			uni.navigateBack();
		},

		onPrivacy() {
			// 统一走接口驱动的协议详情页（内容由后台「协议管理」维护）
			uni.navigateTo({ url: '/pages/agreement/detail/index?key=privacy' });
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

.register-btn {
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

.register-btn:active {
	opacity: 0.9;
}

.login-link {
	text-align: center;
	padding: 30rpx;
	font-size: 26rpx;
	color: #948eb3;
}

.login-link .link {
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
