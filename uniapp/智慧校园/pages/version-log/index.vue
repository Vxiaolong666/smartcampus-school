<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">更新日志</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<!-- 说明卡片 -->
			<view class="intro-card">
				<view class="intro-icon">
					<image class="intro-icon-img" src="/static/assets/wxmp-info.svg" mode="aspectFit"></image>
				</view>
				<view class="intro-main">
					<view class="intro-title">版本更新说明</view>
					<view class="intro-desc">这里汇总了本应用每一次版本的迭代记录，包含新增功能、体验优化与问题修复，帮助你快速了解最新变化。</view>
				</view>
			</view>

			<!-- 统计卡片 -->
			<view class="stat-card">
				<view class="stat-item">
					<view class="stat-value">{{ logs.length }}</view>
					<view class="stat-label">累计版本</view>
				</view>
				<view class="stat-divider"></view>
				<view class="stat-item">
					<view class="stat-value">v{{ latestVersion }}</view>
					<view class="stat-label">最新版本</view>
				</view>
				<view class="stat-divider"></view>
				<view class="stat-item">
					<view class="stat-value stat-value-date">{{ latestDate }}</view>
					<view class="stat-label">最近更新</view>
				</view>
			</view>

			<!-- 更新日志列表 -->
			<view class="timeline" v-if="logs.length">
				<!-- 高亮优先「最新」：同一条既是最新又是当前版本时，只保留最新样式 -->
				<view class="log-card" :class="{ 'log-card-latest': idx === 0, 'log-card-current': idx !== 0 && isCurrentVersion(item) }" v-for="(item, idx) in logs" :key="idx" @click="openDetail(item)">
					<view class="log-top">
						<view class="log-version-wrap">
							<text class="log-version">v{{ item.version }}</text>
							<text class="log-plat" :class="'plat-' + (item.platform || 'all')">{{ platformText(item.platform) }}</text>
							<text v-if="idx === 0" class="log-badge">最新</text>
							<text v-if="isCurrentVersion(item)" class="log-badge-current">当前版本</text>
						</view>
						<text class="log-date">{{ fmtDateTime(item.created_at) }}</text>
					</view>
					<view class="log-list">
						<view class="log-list-item" v-for="(line, i) in splitChangelog(item.changelog)" :key="i">
							<view class="log-dot" :class="{ 'log-dot-latest': idx === 0 }"></view>
							<text class="log-list-text">{{ line }}</text>
						</view>
					</view>
				</view>
			</view>

			<cb-loading v-if="loading" />

			<view v-if="!loading && logs.length === 0" class="empty">
				<image class="empty-icon-img" src="/static/assets/wxmp-empty.svg" mode="aspectFit"></image>
				<view class="empty-text">暂无更新日志</view>
			</view>
		</view>

		<!-- 版本详情弹窗 -->
		<view class="detail-mask" v-if="detailVisible" @click="closeDetail">
			<view class="detail-modal" @click.stop>
				<view class="detail-modal-header">
					<view class="detail-title-wrap">
						<view class="detail-title">v{{ detailItem.version }}</view>
						<view class="detail-sub">
							<text class="log-plat" :class="'plat-' + (detailItem.platform || 'all')">{{ platformText(detailItem.platform) }}</text>
							<text v-if="isLatestDetail" class="log-badge">最新</text>
							<text v-if="isCurrentVersion(detailItem)" class="log-badge-current">当前版本</text>
						</view>
					</view>
					<view class="detail-close" @click="closeDetail">✕</view>
				</view>
				<view class="detail-modal-body">
					<view class="detail-date-row">
						<text class="detail-date-label">更新时间</text>
						<text class="detail-date-value">{{ fmtDateTime(detailItem.created_at) }}</text>
					</view>
					<!-- 下载密码（后台按「链接末尾追加 &pwd=密码」配置时由服务端解析返回，展示与复制同更新弹窗） -->
					<view v-if="detailPwd" class="detail-pwd">
						<text class="detail-pwd-label">下载密码</text>
						<text class="detail-pwd-value">{{ detailPwd }}</text>
						<text class="detail-pwd-copy" :class="{ 'detail-pwd-copy-done': pwdCopied }" @click="copyPwd">{{ pwdCopied ? '已复制' : '复制' }}</text>
					</view>
					<view class="detail-divider"></view>
					<view class="detail-list-title">更新内容</view>
					<scroll-view scroll-y class="detail-scroll">
						<view class="detail-list">
							<view class="detail-list-item" v-for="(line, i) in splitChangelog(detailItem.changelog)" :key="i">
								<view class="detail-dot"></view>
								<text class="detail-list-text">{{ line }}</text>
							</view>
						</view>
					</scroll-view>
					<view class="detail-actions">
						<view class="detail-btn-cancel" @click="closeDetail">关闭</view>
						<view v-if="detailDownloadUrl" class="detail-btn" @click="openDownload">
							<image class="detail-btn-icon" src="/static/assets/wxmp-download-white.svg" mode="aspectFit"></image>
							<text class="detail-btn-text">{{ downloadBtnText }}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 更新日志
 * 拉取后台「版本管理」中已上架版本的更新日志列表
 */
import api from '../../utils/api.js';
import { withPwd, copyText, openDownloadUrl } from '../../utils/download.js';

export default {
	data() {
		return {
			logs: [],
			loading: true,
			detailVisible: false,
			detailItem: {},
			pwdCopied: false,
			// 当前运行版本（由 initVersion() 从 manifest.json 自动读取，用于给对应日志加「当前版本」标识）
			currentVersion: '',
			currentVersionCode: 0
		};
	},

	computed: {
		// 最新版本号（列表按 version_code 倒序，取第一条）
		latestVersion() {
			return this.logs.length ? this.logs[0].version : '—';
		},
		// 最近更新时间（仅取日期部分，避免过长）
		latestDate() {
			if (!this.logs.length) return '暂无';
			return this.fmtDate(this.logs[0].created_at) || '暂无';
		},
		// 当前详情是否为最新版本
		isLatestDetail() {
			return this.logs.length > 0 && this.detailItem && this.detailItem.version_code === this.logs[0].version_code;
		},
		// 下载密码（蓝奏云等带密码的分享链接才有，服务端解析返回）
		detailPwd() {
			return (this.detailItem && this.detailItem.download_pwd) || '';
		},
		// 详情弹窗使用的下载地址：优先直链，兜底原分享页，并注入下载密码
		detailDownloadUrl() {
			const item = this.detailItem || {};
			return withPwd(item.download_url || item.download_page || '', item.download_pwd);
		},
		// 按钮文案：小程序端无法唤起外部浏览器，改为复制链接
		downloadBtnText() {
			// #ifdef MP
			return '复制下载链接';
			// #endif
			// #ifndef MP
			return '浏览器打开';
			// #endif
		}
	},

	onLoad() {
		this.initVersion();
		this.loadLogs();
	},

	methods: {
		/**
		 * 自动读取当前应用版本（取自 manifest.json 的 versionName / versionCode）
		 * - uni.getAppBaseInfo()：App / H5 / 微信小程序 均支持（H5 需 HBuilderX 3.4.13+）
		 * - App 端再以 plus.runtime 的实际打包版本为准
		 * 与「关于我们」页保持一致，升级版本只需修改 manifest.json
		 */
		initVersion() {
			try {
				const info = typeof uni.getAppBaseInfo === 'function' ? uni.getAppBaseInfo() : null;
				if (info) {
					if (info.appVersion) {
						this.currentVersion = String(info.appVersion);
					}
					if (info.appVersionCode !== undefined && info.appVersionCode !== null) {
						const code = parseInt(info.appVersionCode, 10);
						if (!isNaN(code)) this.currentVersionCode = code;
					}
				}
			} catch (e) {}

			// App 端以实际打包版本为准（离线打包 / 热更新后更准确）
			// #ifdef APP-PLUS
			try {
				const vn = plus.runtime.version;
				const vc = plus.runtime.versionCode;
				if (vn) this.currentVersion = String(vn);
				if (vc) {
					const code = parseInt(vc, 10);
					if (!isNaN(code)) this.currentVersionCode = code;
				}
			} catch (e) {}
			// #endif
		},

		// 该条日志是否为「当前版本」：优先比对数字版本号，取不到时回退比对版本号字符串
		isCurrentVersion(item) {
			if (!item) return false;
			const code = parseInt(item.version_code, 10) || 0;
			if (this.currentVersionCode > 0 && code > 0) {
				return code === this.currentVersionCode;
			}
			return !!this.currentVersion && String(item.version || '') === this.currentVersion;
		},

		loadLogs() {
			this.loading = true;
			api.getAppVersionLog()
				.then(res => {
					this.logs = (res && res.data) || [];
				})
				.catch(() => {
					this.logs = [];
				})
				.finally(() => {
					this.loading = false;
				});
		},

		// 打开版本详情弹窗
		openDetail(item) {
			this.detailItem = item || {};
			this.pwdCopied = false;
			this.detailVisible = true;
		},

		// 关闭版本详情弹窗
		closeDetail() {
			this.detailVisible = false;
		},

		// 复制下载密码（内联反馈 + toast 双保险，与更新弹窗表现一致）
		copyPwd() {
			if (!this.detailPwd) return;
			copyText(this.detailPwd, () => {
				this.pwdCopied = true;
				if (this._pwdTimer) clearTimeout(this._pwdTimer);
				this._pwdTimer = setTimeout(() => { this.pwdCopied = false; }, 1600);
				uni.showToast({ title: '密码已复制', icon: 'none' });
			});
		},

		// 打开下载地址：不在应用内直接下载，统一交给浏览器（小程序端复制链接并提示到浏览器打开）
		openDownload() {
			const url = this.detailDownloadUrl;
			if (!url) {
				uni.showToast({ title: '暂无下载地址', icon: 'none' });
				return;
			}
			openDownloadUrl(url, { pwd: this.detailPwd });
		},

		// 平台标识转显示文案
		platformText(platform) {
			const map = { all: '全平台', android: 'Android', ios: 'iOS', harmony: '鸿蒙' };
			return map[platform] || '全平台';
		},

		// 将更新日志文本按行拆分为条目，去掉行首的符号标记，便于列表化展示
		splitChangelog(text) {
			if (!text) return ['本次更新优化了多项体验，建议更新以获得更好体验'];
			return String(text)
				.split('\n')
				.map(line => line.replace(/^\s*[-•*·]\s*/, '').replace(/^\s*\d+[.、)]\s*/, '').trim())
				.filter(line => line.length > 0);
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

/* ===== 说明卡片 ===== */
.intro-card {
	display: flex;
	align-items: center;
	padding: 32rpx;
	margin-bottom: 24rpx;
	border-radius: 24rpx;
	background: #ffffff;
	box-shadow: 0 14rpx 40rpx rgba(72, 65, 111, 0.08);
}

.intro-icon {
	width: 40rpx;
	height: 40rpx;
	flex-shrink: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
}

.intro-icon-img {
	width: 40rpx;
	height: 40rpx;
}

.intro-main {
	flex: 1;
	min-width: 0;
}

.intro-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 10rpx;
}

.intro-desc {
	font-size: 24rpx;
	color: #948eb3;
	line-height: 1.6;
}

/* ===== 统计卡片 ===== */
.stat-card {
	display: flex;
	align-items: center;
	background: #ffffff;
	border-radius: 24rpx;
	padding: 34rpx 0;
	margin-bottom: 28rpx;
	box-shadow: 0 14rpx 40rpx rgba(72, 65, 111, 0.08);
}

.stat-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-width: 0;
}

.stat-value {
	font-size: 38rpx;
	font-weight: bold;
	color: #48416f;
	line-height: 1.2;
}

.stat-value-date {
	font-size: 28rpx;
}

.stat-label {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 12rpx;
}

.stat-divider {
	width: 1rpx;
	height: 56rpx;
	background: #edeef6;
}

/* ===== 更新日志卡片 ===== */
.timeline {
	position: relative;
}

.log-card {
	position: relative;
	background: #ffffff;
	border-radius: 24rpx;
	padding: 30rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 14rpx 40rpx rgba(72, 65, 111, 0.08);
	overflow: hidden;
}

.log-card:last-child {
	margin-bottom: 0;
}

/* 最新版本卡片高亮 */
.log-card-latest {
	background: linear-gradient(180deg, #eaf9fb 0%, #ffffff 45%);
	box-shadow: 0 18rpx 48rpx rgba(19, 194, 194, 0.18);
}

/* 当前运行版本卡片高亮（紫色浅色渐变，效果对齐「最新」卡片） */
.log-card-current {
	background: linear-gradient(180deg, #f8f3fe 0%, #ffffff 45%);
	box-shadow: 0 18rpx 48rpx rgba(114, 46, 209, 0.18);
}

.log-top {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 22rpx;
}

.log-version-wrap {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	flex: 1;
	min-width: 0;
}

.log-version {
	font-size: 34rpx;
	font-weight: bold;
	color: #48416f;
	letter-spacing: 1rpx;
}

.log-plat {
	margin-left: 16rpx;
	padding: 4rpx 18rpx;
	font-size: 20rpx;
	border-radius: 20rpx;
	line-height: 1.6;
	background: #e8f9f9;
	color: #13c2c2;
}

.plat-all {
	background: #e8f9f9;
	color: #13c2c2;
}

.plat-android {
	background: #e6f7ff;
	color: #1890ff;
}

.plat-ios {
	background: #e6fffb;
	color: #13c2c2;
}

.plat-harmony {
	background: #f0f5ff;
	color: #2f54eb;
}

.log-badge {
	margin-left: 16rpx;
	padding: 4rpx 16rpx;
	font-size: 20rpx;
	color: #ffffff;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	border-radius: 20rpx;
	line-height: 1.6;
}

/* 当前运行版本标识（紫色） */
.log-badge-current {
	margin-left: 16rpx;
	padding: 4rpx 16rpx;
	font-size: 20rpx;
	color: #ffffff;
	background: linear-gradient(135deg, #9254de 0%, #722ed1 100%);
	border-radius: 20rpx;
	line-height: 1.6;
}

.log-date {
	font-size: 22rpx;
	color: #948eb3;
	flex-shrink: 0;
	margin-left: 16rpx;
}

.log-list {
	display: flex;
	flex-direction: column;
}

.log-list-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 16rpx;
}

.log-list-item:last-child {
	margin-bottom: 0;
}

.log-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #c9cbe0;
	margin-top: 14rpx;
	margin-right: 18rpx;
	flex-shrink: 0;
}

.log-dot-latest {
	background: #13c2c2;
}

.log-list-text {
	flex: 1;
	font-size: 26rpx;
	color: #767676;
	line-height: 1.7;
	word-break: break-word;
}

/* ===== 加载 / 空状态 ===== */
.loading {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 0;
}

.loading-spinner {
	width: 52rpx;
	height: 52rpx;
	border: 6rpx solid #e4e5f2;
	border-top-color: #13c2c2;
	border-radius: 50%;
	animation: log-spin 0.8s linear infinite;
}

@keyframes log-spin {
	to {
		transform: rotate(360deg);
	}
}

.loading-text {
	font-size: 24rpx;
	color: #948eb3;
	margin-top: 20rpx;
}

.empty {
	text-align: center;
	color: #948eb3;
	padding: 80rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.empty-icon-img {
	width: 120rpx;
	height: 120rpx;
	opacity: 0.9;
}

.empty-text {
	font-size: 26rpx;
	margin-top: 20rpx;
}

/* ===== 版本详情弹窗 ===== */
.log-card:active {
	opacity: 0.92;
}

.detail-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(50, 44, 84, 0.45);
	backdrop-filter: blur(4px);
	-webkit-backdrop-filter: blur(4px);
	z-index: 998;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 60rpx;
}

.detail-modal {
	width: 100%;
	max-width: 620rpx;
	background: #ffffff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 24rpx 80rpx rgba(50, 44, 84, 0.25);
}

.detail-modal-header {
	display: flex;
	align-items: flex-start;
	padding: 36rpx 32rpx 24rpx;
}

.detail-title-wrap {
	flex: 1;
	min-width: 0;
}

.detail-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #48416f;
	line-height: 1.35;
	letter-spacing: 1rpx;
	word-break: break-all;
}

.detail-sub {
	margin-top: 16rpx;
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 12rpx;
}

/* 弹窗内复用列表页标签样式，去掉其默认左间距避免与 gap 叠加 */
.detail-sub .log-plat,
.detail-sub .log-badge,
.detail-sub .log-badge-current {
	margin-left: 0;
}

.detail-close {
	width: 52rpx;
	height: 52rpx;
	line-height: 50rpx;
	text-align: center;
	font-size: 26rpx;
	color: #948eb3;
	background: #f4f5fb;
	border-radius: 50%;
	flex-shrink: 0;
	margin-left: 16rpx;
}

.detail-close:active {
	background: #e8eaf5;
	transform: scale(0.92);
}

.detail-modal-body {
	padding: 0 36rpx 36rpx;
}

.detail-date-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 4rpx 0 22rpx;
}

.detail-date-label {
	font-size: 24rpx;
	color: #948eb3;
}

.detail-date-value {
	font-size: 24rpx;
	color: #48416f;
}

.detail-divider {
	height: 1rpx;
	background: #eef0f8;
}

/* 下载密码行（样式与版本更新弹窗一致） */
.detail-pwd {
	display: flex;
	align-items: center;
	margin-top: 24rpx;
	padding: 16rpx 20rpx;
	background: #eef9fb;
	border: 1rpx solid #cdeef2;
	border-radius: 16rpx;
}

.detail-pwd-label {
	font-size: 24rpx;
	color: #6b7280;
	flex-shrink: 0;
}

.detail-pwd-value {
	flex: 1;
	margin-left: 16rpx;
	font-size: 28rpx;
	font-weight: bold;
	color: #0e8c8c;
	letter-spacing: 2rpx;
	word-break: break-all;
}

.detail-pwd-copy {
	flex-shrink: 0;
	margin-left: 16rpx;
	padding: 6rpx 20rpx;
	font-size: 22rpx;
	color: #0e8c8c;
	background: #ffffff;
	border-radius: 999rpx;
}

.detail-pwd-copy:active {
	opacity: 0.7;
}

/* 复制成功：按钮变主色，提供明确的内联反馈 */
.detail-pwd-copy-done {
	color: #ffffff;
	background: #13c2c2;
}

.detail-list-title {
	font-size: 26rpx;
	font-weight: bold;
	color: #48416f;
	margin: 26rpx 0 18rpx;
}

.detail-scroll {
	max-height: 620rpx;
}

.detail-list {
	display: flex;
	flex-direction: column;
}

.detail-list-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 16rpx;
}

.detail-list-item:last-child {
	margin-bottom: 0;
}

.detail-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #13c2c2;
	margin-top: 14rpx;
	margin-right: 18rpx;
	flex-shrink: 0;
}

.detail-list-text {
	flex: 1;
	font-size: 26rpx;
	color: #767676;
	line-height: 1.7;
	word-break: break-word;
}

.detail-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 36rpx;
}

.detail-btn-cancel {
	flex: 1;
	height: 88rpx;
	line-height: 88rpx;
	text-align: center;
	font-size: 30rpx;
	color: #948eb3;
	background: #f4f5fb;
	border-radius: 999rpx;
}

.detail-btn-cancel:active {
	background: #e8eaf5;
}

.detail-btn {
	flex: 2;
	height: 88rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 30rpx;
	font-weight: 500;
	color: #ffffff;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	border-radius: 999rpx;
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.3);
}

.detail-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.detail-btn-icon {
	width: 34rpx;
	height: 34rpx;
	margin-right: 10rpx;
}

.detail-btn-text {
	font-size: 30rpx;
	color: #ffffff;
}
</style>
