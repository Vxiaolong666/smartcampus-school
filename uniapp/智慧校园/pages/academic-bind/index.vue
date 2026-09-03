<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">教务账号</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="form-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/058-video-conference.svg" mode="widthFix"></image>
					<view class="card-title">教务账号绑定</view>
					<view class="card-desc">绑定后可查询成绩、课表等信息</view>
				</view>

				<block v-if="!isBound">
					<view class="form-item">
						<view class="form-label">学工号</view>
						<input
							class="form-input"
							type="text"
							placeholder="请输入学工号"
							:value="studentId"
							@input="onStudentIdInput"
						/>
					</view>

					<view class="form-item">
						<view class="form-label">教务密码</view>
						<input
							class="form-input"
							type="password"
							placeholder="请输入教务密码"
							:value="password"
							@input="onPasswordInput"
						/>
					</view>

					<view class="form-item">
						<view class="form-label">真实姓名（选填）</view>
						<input
							class="form-input"
							type="text"
							placeholder="请输入真实姓名"
							:value="realName"
							@input="onRealNameInput"
						/>
					</view>

					<button class="submit-btn" @click="onBind" :loading="loading">绑定账号</button>
				</block>

				<block v-else>
					<view class="bound-info">
						<view class="bound-item">
							<view class="bound-label">学工号</view>
							<view class="bound-value">{{ academicInfo.student_id }}</view>
						</view>
						<view class="bound-item">
							<view class="bound-label">真实姓名</view>
							<view class="bound-value">{{ academicInfo.real_name || '未设置' }}</view>
						</view>
						<view class="bound-item">
							<view class="bound-label">绑定时间</view>
							<view class="bound-value">{{ academicInfo.bind_time }}</view>
						</view>
					</view>

					<view class="action-btns">
						<button class="unbind-btn" @click="onUnbind">解绑账号</button>
					</view>
				</block>
			</view>

			<view class="menu-card" v-if="isBound">
				<view class="menu-title">教务功能</view>
				<view class="menu-item" @click="goToGrade">
					<image class="menu-icon" src="/static/assets/wxmp-read.svg" mode="widthFix"></image>
					<view class="menu-text">成绩查询</view>
					<image class="menu-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
				<view class="menu-item" @click="goToSchedule">
					<image class="menu-icon" src="/static/assets/wxmp-calendar-gray.svg" mode="widthFix"></image>
					<view class="menu-text">课程表查询</view>
					<image class="menu-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
				<view class="menu-item" @click="goToStudentInfo">
					<image class="menu-icon" src="/static/assets/wxmp-about.svg" mode="widthFix"></image>
					<view class="menu-text">学籍信息</view>
					<image class="menu-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 教务绑定页面
 * 处理教务账号绑定和解绑
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			studentId: '',
			password: '',
			realName: '',
			loading: false,
			isBound: false,
			academicInfo: {}
		};
	},

	onLoad(options) {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}
		this.loadAcademicInfo();
	},

	onShow() {
		if (api.isLoggedIn()) {
			this.loadAcademicInfo();
		}
	},

	methods: {
		loadAcademicInfo() {
			api.getAcademic()
				.then(res => {
					this.isBound = true;
					this.academicInfo = res.data;
				})
				.catch(() => {
					this.isBound = false;
					this.academicInfo = null;
				});
		},

		onStudentIdInput(e) {
			this.studentId = e.detail.value;
		},

		onPasswordInput(e) {
			this.password = e.detail.value;
		},

		onRealNameInput(e) {
			this.realName = e.detail.value;
		},

		onBind() {
			const { studentId, password, realName, loading } = this;

			if (loading) return;

			if (!studentId.trim()) {
				uni.showToast({ title: '请输入学工号', icon: 'none' });
				return;
			}

			if (!password.trim()) {
				uni.showToast({ title: '请输入教务密码', icon: 'none' });
				return;
			}

			this.loading = true;
			uni.showLoading({ title: '绑定中...' });

			api.bindAcademic(studentId, password, realName)
				.then(() => {
					uni.hideLoading();
					this.loading = false;

					uni.showToast({ title: '绑定成功', icon: 'success' });

					setTimeout(() => {
						this.loadAcademicInfo();
					}, 1000);
				})
				.catch(err => {
					uni.hideLoading();
					this.loading = false;
					uni.showToast({ title: err.msg || '绑定失败', icon: 'none' });
				});
		},

		onUnbind() {
			uni.showModal({
				title: '提示',
				content: '确定要解绑教务账号吗？',
				success: res => {
					if (res.confirm) {
						uni.showLoading({ title: '解绑中...' });

						api.unbindAcademic()
							.then(() => {
								uni.hideLoading();
								uni.showToast({ title: '解绑成功', icon: 'success' });
								this.isBound = false;
								this.academicInfo = null;
							})
							.catch(err => {
								uni.hideLoading();
								uni.showToast({ title: err.msg || '解绑失败', icon: 'none' });
							});
					}
				}
			});
		},

		goToGrade() {
			uni.navigateTo({ url: '/pages/academic-grade/index' });
		},

		goToSchedule() {
			uni.navigateTo({ url: '/pages/academic-schedule/index' });
		},

		goToStudentInfo() {
			uni.navigateTo({ url: '/pages/academic-info/index' });
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
	line-height: 96rpx;
	background: #13c2c2;
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

.bound-info {
	padding: 20rpx 0;
}

.bound-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 24rpx 0;
	border-bottom: 2rpx solid #f0f0f0;
}

.bound-item:last-child {
	border-bottom: none;
}

.bound-label {
	font-size: 28rpx;
	color: #948eb3;
}

.bound-value {
	font-size: 28rpx;
	color: #48416f;
	font-weight: bold;
}

.action-btns {
	margin-top: 40rpx;
}

.unbind-btn {
	width: 100%;
	height: 88rpx;
	background: #ff7b7b;
	color: #fff;
	font-size: 28rpx;
	border-radius: 20rpx;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
}

.unbind-btn:active {
	opacity: 0.9;
}

.menu-card {
	background: white;
	border-radius: 30rpx;
	margin-top: 30rpx;
	padding: 30rpx 0;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.menu-title {
	font-size: 28rpx;
	color: #948eb3;
	padding: 0 40rpx 20rpx;
	font-weight: bold;
}

.menu-item {
	display: flex;
	align-items: center;
	padding: 30rpx 40rpx;
	border-bottom: 2rpx solid #f7f8fc;
}

.menu-item:last-child {
	border-bottom: none;
}

.menu-icon {
	width: 44rpx;
	height: 44rpx;
}

.menu-text {
	flex: 1;
	font-size: 30rpx;
	color: #48416f;
	margin-left: 24rpx;
}

.menu-arrow {
	width: 28rpx;
	height: 28rpx;
	opacity: 0.5;
}
</style>
