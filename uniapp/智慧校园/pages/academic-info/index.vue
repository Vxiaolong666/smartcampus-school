<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">学籍信息</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<announcement v-if="announcement" :content="announcement.content" type="info" />

			<view class="info-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/014-cv.svg" mode="widthFix"></image>
					<view class="card-title">学籍信息</view>
				</view>

				<view v-if="loading" class="loading-wrapper">
					<view class="loading-text">加载中...</view>
				</view>

				<view v-else-if="!studentInfo" class="empty-wrapper">
					<image class="empty-icon" src="/static/assets/wxmp-about.svg" mode="widthFix"></image>
					<view class="empty-text">暂无学籍信息</view>
				</view>

				<view v-else class="info-list">
					<view class="info-item">
						<view class="info-label">姓名</view>
						<view class="info-value">{{ studentInfo.name || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">学号</view>
						<view class="info-value">{{ studentInfo.studentId || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">性别</view>
						<view class="info-value">{{ studentInfo.gender || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">民族</view>
						<view class="info-value">{{ studentInfo.nation || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">学院</view>
						<view class="info-value">{{ studentInfo.college || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">专业</view>
						<view class="info-value">{{ studentInfo.major || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">班级</view>
						<view class="info-value">{{ studentInfo.className || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">年级</view>
						<view class="info-value">{{ studentInfo.grade || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">学制</view>
						<view class="info-value">{{ studentInfo.schooling || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">入学日期</view>
						<view class="info-value">{{ studentInfo.enrollmentDate || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">学籍状态</view>
						<view class="info-value status-normal">{{ studentInfo.status || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">政治面貌</view>
						<view class="info-value">{{ studentInfo.political || '-' }}</view>
					</view>
					<view class="info-item">
						<view class="info-label">生源地</view>
						<view class="info-value">{{ studentInfo.origin || '-' }}</view>
					</view>
				</view>

				<button v-if="!loading" class="refresh-btn" @click="onRefresh">
					<image class="btn-icon" src="/static/assets/wxmp-refresh.svg" mode="widthFix"></image>
					<text>刷新数据</text>
				</button>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 学生信息页面
 * 展示学生个人信息
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

export default {
	data() {
		return {
			featureEnabled: true,
			announcement: null,
			loading: false,
			studentInfo: null
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
		this.checkFeature().then(canProceed => {
			if (canProceed) {
				this.loadStudentInfo();
			}
		});
	},

	methods: {
		async checkFeature() {
			const result = await feature.checkFeature('student_info');
			if (!result.enabled) {
				this.featureEnabled = false;
				this.announcement = null;
				feature.showDisabledAlert(result.featureName, result.announcement);
				return false;
			}
			if (result.announcement) {
				this.announcement = result.announcement;
			}
			return true;
		},

		loadStudentInfo() {
			this.loading = true;

			api.queryStudentInfo()
				.then(res => {
					const rawData = res.data || [];
					let studentInfo = null;

					if (rawData.length > 0) {
						const info = rawData[0];
						studentInfo = {
							name: info.xm || '-',
							studentId: info.xh || '-',
							gender: info.xb || '-',
							nation: info.mz || '-',
							college: info.yx || '-',
							major: info.zymc || '-',
							className: info.bj || '-',
							grade: this.extractGrade(info.xh),
							political: info.zzmm || '-',
							origin: info.jg || '-',
							status: '在读'
						};
					}

					this.studentInfo = studentInfo;
					this.loading = false;
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
				});
		},

		extractGrade(studentId) {
			if (!studentId || studentId.length < 4) return '-';
			const year = studentId.substring(0, 4);
			if (/^\d{4}$/.test(year)) {
				return year + '级';
			}
			return '-';
		},

		onRefresh() {
			this.loadStudentInfo();
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
	padding-bottom: 30rpx;
}

.info-card {
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
	margin-bottom: 40rpx;
	padding-bottom: 30rpx;
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
}

.loading-wrapper {
	display: flex;
	justify-content: center;
	padding: 100rpx 0;
}

.loading-text {
	font-size: 28rpx;
	color: #948eb3;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 100rpx 0;
}

.empty-icon {
	width: 120rpx;
	height: 120rpx;
	opacity: 0.5;
	margin-bottom: 20rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}

.info-list {
	display: flex;
	flex-direction: column;
}

.info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 28rpx 0;
	border-bottom: 2rpx solid #f7f8fc;
}

.info-item:last-child {
	border-bottom: none;
}

.info-label {
	font-size: 28rpx;
	color: #948eb3;
	flex-shrink: 0;
}

.info-value {
	font-size: 28rpx;
	color: #48416f;
	font-weight: bold;
	text-align: right;
	word-break: break-all;
}

.info-value.status-normal {
	color: #13c2c2;
}

.refresh-btn {
	width: 100%;
	height: 88rpx;
	line-height: 88rpx;
	background: #13c2c2;
	color: #fff;
	font-size: 28rpx;
	border-radius: 20rpx;
	font-weight: bold;
	margin-top: 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0;
}

.refresh-btn:active {
	opacity: 0.9;
}

.refresh-btn .btn-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 10rpx;
}
</style>
