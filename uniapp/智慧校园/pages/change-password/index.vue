<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">修改密码</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="form-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/048-presentation.svg" mode="widthFix"></image>
					<view class="card-title">修改密码</view>
					<view class="card-desc">请输入原密码和新密码</view>
				</view>

				<view class="form-item">
					<view class="form-label">原密码</view>
					<input
						class="form-input"
						type="password"
						placeholder="请输入原密码"
						:value="oldPassword"
						@input="onOldPasswordInput"
					/>
				</view>

				<view class="form-item">
					<view class="form-label">新密码</view>
					<input
						class="form-input"
						type="password"
						placeholder="请输入新密码（至少6位）"
						:value="newPassword"
						@input="onNewPasswordInput"
					/>
				</view>

				<view class="form-item">
					<view class="form-label">确认新密码</view>
					<input
						class="form-input"
						type="password"
						placeholder="请再次输入新密码"
						:value="confirmPassword"
						@input="onConfirmPasswordInput"
					/>
				</view>

				<button class="submit-btn" @click="onChangePassword" :loading="loading">确认修改</button>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 修改密码页面
 * 处理密码修改流程
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			oldPassword: '',
			newPassword: '',
			confirmPassword: '',
			loading: false
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip();
		}
	},

	methods: {
		onOldPasswordInput(e) {
			this.oldPassword = e.detail.value;
		},

		onNewPasswordInput(e) {
			this.newPassword = e.detail.value;
		},

		onConfirmPasswordInput(e) {
			this.confirmPassword = e.detail.value;
		},

		onChangePassword() {
			const { oldPassword, newPassword, confirmPassword, loading } = this;

			if (loading) return;

			if (!oldPassword.trim()) {
				uni.showToast({ title: '请输入原密码', icon: 'none' });
				return;
			}

			if (!newPassword.trim()) {
				uni.showToast({ title: '请输入新密码', icon: 'none' });
				return;
			}

			if (newPassword.length < 6) {
				uni.showToast({ title: '新密码至少6位', icon: 'none' });
				return;
			}

			if (newPassword !== confirmPassword) {
				uni.showToast({ title: '两次密码不一致', icon: 'none' });
				return;
			}

			if (oldPassword === newPassword) {
				uni.showToast({ title: '新密码不能与原密码相同', icon: 'none' });
				return;
			}

			this.loading = true;
			uni.showLoading({ title: '修改中...' });

			api.changePassword(oldPassword, newPassword)
				.then(res => {
					uni.hideLoading();
					this.loading = false;

					uni.showToast({ title: '修改成功', icon: 'success' });

					api.clearAuth();

					setTimeout(() => {
						uni.reLaunch({ url: '/pages/login/index' });
					}, 1500);
				})
				.catch(err => {
					uni.hideLoading();
					this.loading = false;
					uni.showToast({ title: err.msg || '修改失败', icon: 'none' });
				});
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
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
	padding: 40rpx 30rpx;
}

.form-card {
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

.form-item {
	margin-bottom: 36rpx;
}

.form-label {
	font-size: 28rpx;
	color: #48416f;
	margin-bottom: 16rpx;
	font-weight: bold;
}

.form-input {
	width: 100%;
	height: 88rpx;
	padding: 0 30rpx;
	border: 2rpx solid #e5e5e5;
	border-radius: 20rpx;
	font-size: 28rpx;
	box-sizing: border-box;
	background: #f7f8fc;
	color: #48416f;
}

.form-input:focus {
	border-color: #13c2c2;
	background: white;
}

.submit-btn {
	width: 100%;
	height: 96rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	color: #fff;
	font-size: 30rpx;
	border-radius: 20rpx;
	font-weight: bold;
	margin-top: 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
}

.submit-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.submit-btn::after {
	border: none;
}
</style>
