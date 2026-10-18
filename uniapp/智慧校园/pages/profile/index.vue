<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">个人资料</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="card">
				<view class="card-title">个人资料</view>
				<view class="card-desc">管理您的个人信息和账号设置</view>
			</view>

			<cb-load-fail />
			<cb-loading v-if="!loaded" />

			<view class="form-card avatar-card" v-if="loaded">
				<view class="avatar-section">
					<image v-if="avatar" class="avatar" :src="avatar" mode="aspectFill"></image>
					<view v-else class="avatar-placeholder">
						<text>{{ (nickname && nickname[0]) || '*' }}</text>
					</view>
					<view class="avatar-actions" style="width: 498rpx; display: flex; box-sizing: border-box">
						<button class="avatar-btn" @click="onChooseAvatar">从相册选择</button>
						<!-- #ifdef MP-WEIXIN -->
						<button class="avatar-btn wx-btn" open-type="chooseAvatar" @chooseavatar="onGetWxAvatar">使用微信头像</button>
						<!-- #endif -->
					</view>
				</view>
			</view>

			<view class="form-card" v-if="loaded">
				<view class="form-item">
					<view class="form-label">昵称</view>
					<input
						class="form-input"
						type="nickname"
						placeholder="请输入昵称"
						:value="nickname"
						@input="onNicknameInput"
						@blur="onNicknameBlur"
						maxlength="20"
					/>
				</view>

				<view class="form-item">
					<view class="form-label">用户名</view>
					<view class="form-value">{{ userInfo.username }}</view>
				</view>

				<view class="form-item">
					<view class="form-label">邮箱</view>
					<view class="form-value">{{ userInfo.email }}</view>
				</view>

				<view class="form-item" @click="onGoToWxBind">
					<view class="form-label">微信绑定</view>
					<view class="form-value link">
						<text>{{ hasWxOpenid ? '已绑定' : '未绑定' }}</text>
						<text class="arrow">›</text>
					</view>
				</view>

				<view class="form-item">
					<view class="form-label">注册时间</view>
					<view class="form-value">{{ fmtDateTime(userInfo.register_time) }}</view>
				</view>

				<button class="save-btn" @click="onSave">保存修改</button>
			</view>
		</view>
	</view>
</template>

<script>
import api from '../../utils/api.js';
import validate from '../../utils/validate.js';

export default {
	data() {
		return {
			userInfo: {},
			nickname: '',
			avatar: '',
			hasWxOpenid: false,
			loaded: false
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip();
			return;
		}
		this.loadUserInfo();
	},

	methods: {
		async loadUserInfo() {
			try {
				const res = await api.getUserInfo();
				const userInfo = res.data;
				this.userInfo = userInfo;
				this.nickname = userInfo.nickname || userInfo.username;
				this.avatar = api.getFileUrl(userInfo.avatar);
				this.hasWxOpenid = !!userInfo.wx_openid;
				this.loaded = true;
			} catch (err) {
				this.loaded = true;
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			}
		},

		onNicknameInput(e) {
			this.nickname = e.detail.value;
		},

		onNicknameBlur(e) {
			if (e.detail.value) {
				this.nickname = e.detail.value;
			}
		},

		onChooseAvatar() {
			uni.chooseImage({
				count: 1,
				sourceType: ['album', 'camera'],
				success: res => {
					this.uploadAvatar(res.tempFilePaths[0]);
				},
				fail: (err) => {
					const msg = (err && err.errMsg) || '';
					// 用户主动取消不做提示
					if (msg.indexOf('cancel') > -1) return;
					uni.showToast({ title: '无法访问相册，请在设置中授权', icon: 'none' });
				}
			});
		},

		onGetWxAvatar(e) {
			// #ifdef MP-WEIXIN
			if (e.detail.avatarUrl) {
				this.uploadAvatar(e.detail.avatarUrl);
			} else {
				uni.showToast({ title: '获取微信头像失败', icon: 'none' });
			}
			// #endif
		},

		async uploadAvatar(filePath) {
			uni.showLoading({ title: '上传中...' });

			try {
				const res = await api.uploadAvatar(filePath);
				uni.hideLoading();
				// 后端返回完整头像 URL
				this.avatar = api.getFileUrl(res.data.avatar);
				uni.showToast({ title: '上传成功', icon: 'success' });
			} catch (err) {
				uni.hideLoading();
				uni.showToast({ title: err.msg || '上传失败', icon: 'none' });
			}
		},

		async onSave() {
			const { nickname, avatar } = this;

			if (!nickname.trim()) {
				uni.showToast({ title: '请输入昵称', icon: 'none' });
				return;
			}

			// 昵称长度（与输入框 maxlength 保持一致）
			if (validate.trim(nickname).length > 20) {
				uni.showToast({ title: '昵称最多20个字符', icon: 'none' });
				return;
			}

			uni.showLoading({ title: '保存中...' });

			try {
				// 完整 URL 转回 API 站点相对路径后再保存（第三方头像原样保留）
				const avatarPath = api.toApiPath(avatar);

				await api.request({
					act: 'update_profile',
					method: 'POST',
					data: {
						nickname: nickname.trim(),
						avatar: avatarPath
					}
				});

				uni.hideLoading();
				uni.showToast({ title: '保存成功', icon: 'success' });

				setTimeout(() => {
					uni.navigateBack();
				}, 1000);
			} catch (err) {
				uni.hideLoading();
				uni.showToast({ title: err.msg || '保存失败', icon: 'none' });
			}
		},

		onGoToWxBind() {
			uni.navigateTo({ url: '/pages/wx-bind/index' });
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
	padding: 30rpx;
}

.card {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	margin-bottom: 24rpx;
	margin-top: 25rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.card-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.card-desc {
	font-size: 26rpx;
	color: #948eb3;
}

.avatar-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 30rpx 0 40rpx 0;
}

.avatar {
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	border: 4rpx solid #f0f0f0;
}

.avatar-placeholder {
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	border: 4rpx solid #f0f0f0;
}

.avatar-placeholder text {
	font-size: 56rpx;
	color: white;
	font-weight: bold;
}

.avatar-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 24rpx;
}

.avatar-btn {
	padding: 16rpx 32rpx;
	font-size: 24rpx;
	border-radius: 40rpx;
	background: transparent;
	color: #48416f;
	line-height: 1.4;
	border: 1rpx solid #bfbfbf;
	transition: all 0.3s ease;
}

.avatar-btn.wx-btn {
	background: transparent;
	color: #13c2c2;
	border: 1rpx solid #13c2c2;
}

.avatar-btn::after {
	border: none;
}

.form-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx 30rpx 28rpx 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.form-card.avatar-card {
	margin-bottom: 24rpx;
}

.form-item {
	padding: 24rpx 0;
	border-bottom: 1rpx solid #f0f0f0;
	display: flex;
	align-items: center;
}

.form-item:last-child {
	border-bottom: none;
}

.form-label {
	font-size: 28rpx;
	color: #948eb3;
	width: 160rpx;
	flex-shrink: 0;
}

.form-input {
	flex: 1;
	font-size: 28rpx;
	color: #48416f;
	text-align: right;
}

.form-value {
	flex: 1;
	font-size: 28rpx;
	color: #48416f;
	text-align: right;
	display: flex;
	align-items: center;
	justify-content: flex-end;
}

.form-value.link {
	color: #13c2c2;
}

.form-value .arrow {
	font-size: 32rpx;
	margin-left: 8rpx;
	color: #c0c0c0;
}

.save-btn {
	width: 100%;
	height: 88rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #13c2c2;
	color: #fff;
	font-size: 28rpx;
	border-radius: 16rpx;
	font-weight: bold;
	margin-top: 30rpx;
	border: none;
}

.save-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.save-btn::before,
.save-btn::after {
	display: none;
}
</style>
