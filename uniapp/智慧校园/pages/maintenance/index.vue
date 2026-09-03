<template>
	<view class="container">
		<cb-nav-bar :show-back="false">
			<view class="nav-title">系统维护</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="maintenance-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
					<view class="card-title">系统维护中</view>
				</view>

				<view class="maintenance-content">
					<view class="maintenance-icon-wrapper">
						<image class="maintenance-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
					</view>

					<view class="maintenance-section">
						<view class="section-title">维护时间</view>
						<view class="time-display">
							<text class="time-text">{{ maintenanceTime }}</text>
						</view>
					</view>

					<view v-if="notice" class="maintenance-section notice-section">
						<view class="section-title">维护公告</view>
						<view class="section-card">
							<view class="section-text">{{ notice.content }}</view>
						</view>
					</view>

					<view class="maintenance-section">
						<view class="section-title">温馨提示</view>
						<view class="section-text">
							受学校教务系统每晚关闭维护影响，相关系统功能在每日23时至次日上午6时不可用。如需紧急帮助，请通过"我的"页面中的"联系客服"功能与我们取得联系。
						</view>
					</view>
				</view>

				<view class="maintenance-footer">
					<text class="footer-text">感谢您的耐心等待</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 功能维护页面
 * 当系统时间在 23:00 - 次日 6:00 时，自动跳转到此页面
 */
import feature from '../../utils/feature.js';

export default {
	data() {
		return {
			currentHour: 0,
			maintenanceTime: '23:00 - 06:00',
			notice: null,
			loading: true
		};
	},

	onLoad() {
		const now = new Date();
		const hour = now.getHours();

		this.currentHour = hour;
		this.loadMaintenanceNotice();
	},

	methods: {
		async loadMaintenanceNotice() {
			try {
				this.loading = true;

				const result = await feature.checkFeature('weihu_open');

				if (result && result.announcement) {
					this.notice = {
						title: '维护公告',
						content: result.announcement.content,
						type: result.announcement.type || 'info'
					};
				} else {
					this.notice = {
						title: '维护公告',
						content: '系统正在进行日常维护，维护期间暂停所有服务。请稍后再试，感谢您的理解与支持！',
						type: 'info'
					};
				}
				this.loading = false;
			} catch (error) {
				this.notice = {
					title: '维护公告',
					content: '系统正在进行日常维护，维护期间暂停所有服务。请稍后再试，感谢您的理解与支持！',
					type: 'info'
				};
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

.maintenance-card {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.card-header {
	display: flex;
	align-items: center;
	padding: 40rpx 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.card-logo {
	width: 60rpx;
	height: 60rpx;
	margin-right: 20rpx;
}

.card-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
}

.maintenance-content {
	padding: 30rpx;
}

.maintenance-icon-wrapper {
	display: flex;
	justify-content: center;
	padding: 40rpx 0;
}

.maintenance-icon {
	width: 120rpx;
	height: 120rpx;
}

.time-display {
	display: flex;
	justify-content: center;
	align-items: center;
	padding: 30rpx;
	background: #f7f8fc;
	border-radius: 20rpx;
	margin: 20rpx 0;
}

.time-text {
	font-size: 48rpx;
	font-weight: bold;
	color: #48416f;
	letter-spacing: 4rpx;
}

.maintenance-section {
	margin-bottom: 40rpx;
}

.maintenance-section:last-child {
	margin-bottom: 0;
}

.section-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	padding-left: 16rpx;
	border-left: 6rpx solid #13c2c2;
}

.section-text {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	text-indent: 2em;
}

.notice-section {
	margin-top: 20rpx;
}

.notice-card {
	background: #f7f8fc;
	border-radius: 16rpx;
	padding: 24rpx;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.notice-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 16rpx;
	padding-bottom: 12rpx;
	border-bottom: 1rpx solid #e0e0e0;
}

.notice-content {
	font-size: 26rpx;
	color: #595959;
	line-height: 1.8;
}

.feature-list {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	width: 100%;
}

.feature-item {
	display: flex;
	align-items: center;
	width: 48%;
	padding: 20rpx;
	background: #f7f8fc;
	border-radius: 16rpx;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
	margin-bottom: 20rpx;
	box-sizing: border-box;
}

.feature-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 12rpx;
	flex-shrink: 0;
}

.feature-item text {
	font-size: 26rpx;
	color: #48416f;
	font-weight: 500;
	flex: 1;
}

.maintenance-footer {
	padding: 30rpx;
	text-align: center;
	border-top: 1rpx solid #f0f0f0;
}

.footer-text {
	font-size: 22rpx;
	color: #948eb3;
}
</style>
