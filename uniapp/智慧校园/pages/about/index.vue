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
					<text class="footer-text">版本 {{currentVersion}}</text>
				</view>
			</view>

			<view class="menu-card">
				<view class="menu-title">版本服务</view>
				<view class="menu-item" @click="checkUpdate(false)">
					<image class="menu-icon" src="/static/assets/wxmp-about.svg" mode="widthFix"></image>
					<view class="menu-text">检查更新</view>
					<image class="menu-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
				<view class="menu-item" @click="goVersionLog">
					<image class="menu-icon" src="/static/assets/wxmp-read.svg" mode="widthFix"></image>
					<view class="menu-text">更新日志</view>
					<image class="menu-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 关于我们
 * 底部新增「版本服务」：检查更新 + 更新日志（参考教务绑定页下方操作按钮样式）
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			currentVersion: '1.3.25',
			currentVersionCode: 1001
		};
	},

	onLoad() {
		this.initVersion();
	},

	methods: {
		// 优先读取 App 实际版本号，H5/开发时回退到内置常量
		initVersion() {
			// #ifdef APP-PLUS
			try {
				const vn = plus.runtime.version;
				const vc = plus.runtime.versionCode;
				if (vn) this.currentVersion = vn;
				if (vc) this.currentVersionCode = parseInt(vc, 10) || this.currentVersionCode;
			} catch (e) {}
			// #endif
		},

		// 当前平台：android / ios / 其它归 all
		getPlatform() {
			try {
				const platform = uni.getSystemInfoSync().platform;
				if (platform === 'android') return 'android';
				if (platform === 'ios') return 'ios';
			} catch (e) {}
			return 'all';
		},

		// 检查更新；auto=true 时（App 启动）无更新不提示
		checkUpdate(auto = false) {
			uni.showLoading({ title: '检查中...' });
			api.getAppVersion({ version_code: this.currentVersionCode, platform: this.getPlatform() })
				.then(res => {
					const d = (res && res.data) || {};
					if (d.has_update) {
						this.showUpdateModal(d);
					} else if (!auto) {
						uni.showToast({ title: '已是最新版本', icon: 'none' });
					}
				})
				.catch(() => {
					if (!auto) uni.showToast({ title: '检查失败', icon: 'none' });
				})
				.finally(() => {
					uni.hideLoading();
				});
		},

		showUpdateModal(d) {
			const force = parseInt(d.force_update, 10) === 1;
			uni.showModal({
				title: '发现新版本 v' + d.version,
				content: (d.changelog || '修复了一些问题，体验更流畅') + '\n\n建议尽快更新以获得更好体验',
				confirmText: '立即更新',
				showCancel: !force,
				success: (r) => {
					if (r.confirm && d.download_url) {
						this.openUrl(d.download_url);
					}
				}
			});
		},

		openUrl(url) {
			// #ifdef APP-PLUS
			plus.runtime.openURL(url);
			// #endif
			// #ifndef APP-PLUS
			try { window.open(url, '_blank'); } catch (e) {}
			// #endif
		},

		goVersionLog() {
			uni.navigateTo({ url: '/pages/version-log/index' });
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
	padding-bottom: 30rpx;
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
