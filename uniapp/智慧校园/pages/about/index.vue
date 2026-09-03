<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">关于我们</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="about-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
					<view class="card-title">关于我们</view>
				</view>

				<view class="about-content">
					<view class="about-section">
						<view class="section-title">项目介绍</view>
						<view class="section-text">
							本小程序由在校学生自主开发，非学校官方应用。初衷是为同学们提供更便捷、更好用的校园服务体验，仅供学习交流使用。
						</view>
					</view>

					<view class="about-section">
						<view class="section-title">免责声明</view>
						<view class="section-text">
							本小程序为个人学习项目，与学校官方无关。所有数据均来源于学校公开系统，仅供学习参考，请以学校官方信息为准。如有任何问题，请联系我们进行处理。
						</view>
					</view>

					<view class="about-section">
						<view class="section-title">功能特性</view>
						<view class="feature-list">
							<view class="feature-item">
								<image class="feature-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
								<text>教务系统查询</text>
							</view>
							<view class="feature-item">
								<image class="feature-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
								<text>课程表管理</text>
							</view>
							<view class="feature-item">
								<image class="feature-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
								<text>宿舍电费查询</text>
							</view>
							<view class="feature-item">
								<image class="feature-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
								<text>消息推送通知</text>
							</view>
						</view>
					</view>

					<view class="about-section">
						<view class="section-title">联系我们</view>
						<view class="section-text">如有问题或建议，请通过"我的"页面中的"联系客服"功能与我们取得联系。</view>
					</view>
				</view>

				<view class="about-footer">
					<text class="footer-text">版本 1.0.0</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {};
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

.about-card {
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

.about-content {
	padding: 30rpx;
}

.about-section {
	margin-bottom: 40rpx;
}

.about-section:last-child {
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

.feature-list {
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx;
}

.feature-item {
	display: flex;
	align-items: center;
	width: 45%;
	padding: 16rpx 20rpx;
	background: #f7f8fc;
	border-radius: 12rpx;
}

.feature-icon {
	width: 28rpx;
	height: 28rpx;
	margin-right: 12rpx;
}

.feature-item text {
	font-size: 24rpx;
	color: #48416f;
}

.about-footer {
	padding: 30rpx;
	text-align: center;
	border-top: 1rpx solid #f0f0f0;
}

.footer-text {
	font-size: 22rpx;
	color: #948eb3;
}
</style>
