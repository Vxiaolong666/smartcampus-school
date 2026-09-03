<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">隐私协议</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="policy-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
					<view class="card-title">隐私协议</view>
				</view>

				<view class="policy-content">
					<view class="policy-update">最后更新日期：2026年9月1日</view>

					<view class="policy-section">
						<view class="section-title">一、概述</view>
						<view class="section-text">
							我们非常重视您的隐私和个人信息保护。本协议说明在您使用本小程序（以下简称"本服务"）过程中，我们如何收集、使用、存储与保护您的信息。请您在使用前仔细阅读并充分理解本协议内容。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">二、信息的收集</view>
						<view class="section-text">在您使用本服务时，我们可能收集以下信息：</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>账号信息：您注册或登录时提供的手机号、密码（加密存储）等；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>绑定信息：您主动绑定的教务账号、宿舍校区、楼栋、房间等；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>使用数据：电费、课程、成绩等查询记录及必要的设备与日志信息。</text></view>
					</view>

					<view class="policy-section">
						<view class="section-title">三、信息的使用</view>
						<view class="section-text">
							我们收集的信息仅用于为您提供校园服务查询、用电统计、消息推送及改善产品体验。我们不会将您的个人信息用于任何与上述目的无关的用途。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">四、信息的存储与保护</view>
						<view class="section-text">
							您的个人信息将采用加密方式存储，并采取合理的技术与管理措施防止信息泄露、篡改或丢失。除非法律另有规定，我们仅在为您提供服务所必需的最短期限内保留您的信息。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">五、信息的共享</view>
						<view class="section-text">
							我们不会向第三方出售或非法提供您的个人信息。仅在以下情形下，我们可能共享必要信息：获得您的明确同意；为完成您请求的服务所必需（如向学校公开系统查询）；根据法律法规或有权机关的要求。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">六、您的权利</view>
						<view class="section-text">
							您有权查阅、更正自己的个人信息，并可在"我的"页面中解绑相关账号或注销账户。如您对信息处理有任何疑问，可随时联系我们进行咨询或投诉。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">七、第三方服务</view>
						<view class="section-text">
							本服务可能接入微信登录、消息推送等第三方功能，相关数据的处理受相应第三方隐私政策约束。建议您在使用前了解其隐私条款。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">八、未成年人保护</view>
						<view class="section-text">
							我们重视未成年人个人信息保护。若您未满十八周岁，请在监护人陪同下使用本服务，并确保已获得其同意。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">九、协议更新</view>
						<view class="section-text">
							我们可能适时修订本协议。更新后的协议将在本页面发布，并在重大变更时以适当方式通知您。您继续使用本服务即视为接受修订后的内容。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十、联系我们</view>
						<view class="section-text">
							如您对本协议或个人信息处理有任何疑问，请通过"我的"页面中的"联系客服"功能与我们取得联系，我们将在合理时间内予以回复。
						</view>
					</view>
				</view>

				<view class="policy-footer">
					<text class="footer-text">感谢您信任并使用本服务</text>
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
	padding-bottom: 30rpx;
}

.policy-card {
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

.policy-content {
	padding: 30rpx;
}

.policy-update {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 30rpx;
	text-align: right;
}

.policy-section {
	margin-bottom: 36rpx;
}

.policy-section:last-child {
	margin-bottom: 0;
}

.section-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 16rpx;
	padding-left: 16rpx;
	border-left: 6rpx solid #13c2c2;
}

.section-text {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	text-indent: 2em;
}

.bullet-item {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	display: flex;
	align-items: flex-start;
	padding-left: 2em;
}

.bullet-dot {
	margin-right: 12rpx;
	color: #13c2c2;
	font-weight: bold;
}

.policy-footer {
	padding: 30rpx;
	text-align: center;
	border-top: 1rpx solid #f0f0f0;
}

.footer-text {
	font-size: 22rpx;
	color: #948eb3;
}
</style>
