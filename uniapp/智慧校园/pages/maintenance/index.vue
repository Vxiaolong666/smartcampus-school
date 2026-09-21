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
					<view v-if="typeLabel" class="type-badge">{{ typeLabel }}</view>
				</view>

				<view class="maintenance-content">
					<view class="maintenance-icon-wrapper">
						<image class="maintenance-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
					</view>

					<!-- 每日例行维护 -->
					<view v-if="dailyActive" class="reason-card">
						<view class="reason-head">
							<image class="reason-icon-img" src="/static/assets/wxmp-maintenance-clock.svg" mode="aspectFit"></image>
							<view class="reason-title-wrap">
								<view class="reason-title">每日例行维护</view>
								<view class="reason-sub">受学校教务系统每晚关闭维护影响</view>
							</view>
						</view>
						<view class="reason-body">
							<view class="reason-row">
								<text class="reason-label">维护时段</text>
								<text class="reason-value">{{ maintenanceTime }}</text>
							</view>
							<view class="reason-row">
								<text class="reason-label">当前时间</text>
								<text class="reason-value">{{ currentTime }}</text>
							</view>
							<view v-if="countdown" class="reason-row">
								<text class="reason-label">预计恢复</text>
								<text class="reason-value countdown">{{ countdown }} 后</text>
							</view>
							<view class="reason-desc">
								维护期间课表、成绩、今日课程等教务相关功能暂不可用，宿舍电费等功能不受影响，06:00 后将自动恢复。
							</view>
						</view>
					</view>

					<!-- 管理员开启维护 -->
					<view v-if="manualActive" class="reason-card manual">
						<view class="reason-head">
							<image class="reason-icon-img" src="/static/assets/wxmp-maintenance-shield.svg" mode="aspectFit"></image>
							<view class="reason-title-wrap">
								<view class="reason-title">管理员维护{{ featureName ? '（' + featureName + '）' : '' }}</view>
								<view class="reason-sub">系统维护已由管理员手动开启</view>
							</view>
						</view>
						<view class="reason-body">
							<view class="reason-row">
								<text class="reason-label">维护时段</text>
								<text class="reason-value">{{ maintenanceTime }}</text>
							</view>
							<!-- 后台配置的实际维护信息 -->
							<view v-if="notice" class="notice-box">
								<view class="notice-title">维护公告</view>
								<view class="notice-content">{{ notice.content }}</view>
							</view>
							<view v-else class="notice-box">
								<view class="notice-content">系统正在维护，恢复时间待定，请留意公告或稍后再试。</view>
							</view>
						</view>
					</view>

					<view class="maintenance-section">
						<view class="section-title">温馨提示</view>
						<view class="section-text">
							如需紧急帮助，请通过「我的」页面中的「联系客服」与我们取得联系。系统恢复后重新进入即可正常使用。
						</view>
					</view>
				</view>

				<view class="maintenance-footer">
					<!-- 一行两个按钮：联系客服（主）+ 重新检测（次） -->
					<view class="footer-actions">
						<view class="action-btn action-primary" @click="onContact">联系客服</view>
						<view class="action-btn action-plain" @click="onRecheck">重新检测</view>
					</view>
					<view class="footer-text">感谢您的耐心等待</view>
				</view>
			</view>
		</view>
		<!-- 联系客服自定义弹窗（参考更新弹窗样式） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
/**
 * 功能维护页面
 *
 * 两种进入场景（可能同时成立）：
 * 1. 系统每日例行维护：每日 23:00 - 次日 06:00（受学校教务系统每晚关闭维护影响）
 * 2. 管理员手动开启维护：后台「维护」功能开关 weihu_open 启用（可配置维护公告）
 * 页面会区分展示具体的维护原因、维护时段与预计恢复时间
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

// 内置兜底时段（后台未配置时使用）：23:00 - 次日 06:00
const DEFAULT_START = '23:00';
const DEFAULT_END = '06:00';

// 时间字符串（HH:mm）转当日分钟数
const toMinutes = (hhmm) => {
	const parts = String(hhmm || '').split(':');
	const h = parseInt(parts[0] || '0', 10);
	const m = parseInt(parts[1] || '0', 10);
	return (isNaN(h) ? 0 : h) * 60 + (isNaN(m) ? 0 : m);
};

// 判断当前是否处于维护时段（支持跨天，如 22:00 - 次日 08:00）
const inWindow = (nowMin, startMin, endMin) => {
	if (startMin === endMin) return false;
	if (startMin < endMin) return nowMin >= startMin && nowMin < endMin;
	return nowMin >= startMin || nowMin < endMin;
};

export default {
	data() {
		return {
			loading: true,
			dailyActive: false,     // 是否处于每日例行维护时段
			manualActive: false,    // 管理员是否开启了维护
			typeLabel: '',          // 维护类型标签
			maintenanceTime: '',    // 维护时间说明
			countdown: '',          // 预计恢复倒计时
			notice: null,           // 管理员维护公告 {content, type}
			featureName: '',        // 后台功能名称
			currentTime: '',        // 当前时间 HH:mm
			startText: DEFAULT_START, // 后台配置的维护开始时间
			endText: DEFAULT_END,     // 后台配置的维护结束时间
			contactShow: false,
			contactLines: []
		};
	},

	onLoad() {
		this.loadMaintenanceConfig();
		this.detectStatus();
	},

	onShow() {
		// 回到前台时重新检测，并恢复倒计时
		this.detectStatus();
	},

	onHide() {
		this.clearTimer();
	},

	onUnload() {
		this.clearTimer();
	},

	methods: {
		//读取后台「服务停止时段」配置（失败时沿用内置默认值）
		async loadMaintenanceConfig() {
			try {
				const res = await api.getMaintenanceConfig();
				this.startText = (res.data && res.data.start) || DEFAULT_START;
				this.endText = (res.data && res.data.end) || DEFAULT_END;
				// 配置到达后重新判定一次
				this.detectStatus();
			} catch (e) {
				// 保持默认值
			}
		},

		//检测当前维护状态
		async detectStatus() {
			const now = new Date();
			const startMin = toMinutes(this.startText);
			const endMin = toMinutes(this.endText);
			const nowMin = now.getHours() * 60 + now.getMinutes();
			const dailyActive = inWindow(nowMin, startMin, endMin);

			// 管理员维护开关（weihu_open 启用即代表管理员开启了维护）
			let manualActive = false;
			let notice = null;
			let featureName = '';
			try {
				const result = await feature.checkFeature('weihu_open');
				manualActive = !!(result && result.enabled === true);
				if (result && result.announcement) {
					notice = {
						content: result.announcement.content,
						type: result.announcement.type || 'info'
					};
				}
				featureName = (result && result.featureName) || '';
			} catch (e) {
				// 拉取失败时按每日维护状态展示
			}

			const typeLabel = dailyActive && manualActive
				? '每日维护 + 管理员维护'
				: (dailyActive ? '每日例行维护' : (manualActive ? '管理员维护' : '维护结束'));

			// 跨天时段显示“次日”，同日时段直接显示
			const windowText = startMin < endMin
				? ('每日 ' + this.startText + ' - ' + this.endText)
				: ('每日 ' + this.startText + ' - 次日 ' + this.endText);
			const maintenanceTime = dailyActive
				? windowText
				: (manualActive ? '时间待定（以公告为准）' : '—');

			this.loading = false;
			this.dailyActive = dailyActive;
			this.manualActive = manualActive;
			this.typeLabel = typeLabel;
			this.maintenanceTime = maintenanceTime;
			this.notice = notice;
			this.featureName = featureName;
			this.currentTime = this.formatTime(now);

			if (dailyActive) {
				this.startCountdown();
			} else {
				this.clearTimer();
				this.countdown = '';
			}

			// 已不在任何维护状态：返回首页
			if (!dailyActive && !manualActive) {
				uni.showToast({ title: '维护已结束', icon: 'none' });
				setTimeout(() => {
					uni.switchTab({ url: '/pages/white-home/index' });
				}, 800);
			}
		},

		//启动恢复倒计时（每秒刷新展示）
		startCountdown() {
			this.clearTimer();
			this.updateCountdown();
			this._timer = setInterval(() => this.updateCountdown(), 1000);
		},

		clearTimer() {
			if (this._timer) {
				clearInterval(this._timer);
				this._timer = null;
			}
		},

		//计算距离维护结束时间（后台配置）的剩余时间
		updateCountdown() {
			const now = new Date();
			const endMin = toMinutes(this.endText);
			const end = new Date(now.getTime());
			end.setHours(Math.floor(endMin / 60), endMin % 60, 0, 0);
			if (end.getTime() <= now.getTime()) {
				end.setDate(end.getDate() + 1);
			}
			const diff = end.getTime() - now.getTime();
			const h = Math.floor(diff / 3600000);
			const m = Math.floor((diff % 3600000) / 60000);
			const s = Math.floor((diff % 60000) / 1000);
			this.countdown = this.pad(h) + ':' + this.pad(m) + ':' + this.pad(s);
		},

		pad(n) {
			return n < 10 ? '0' + n : '' + n;
		},

		formatTime(date) {
			return this.pad(date.getHours()) + ':' + this.pad(date.getMinutes());
		},

		//重新检测维护状态
		onRecheck() {
			uni.showLoading({ title: '检测中...' });
			this.detectStatus();
			setTimeout(() => uni.hideLoading(), 600);
		},

		//联系客服：弹窗展示后台「首页配置」中维护的联系方式
		async onContact() {
			uni.showLoading({ title: '加载中...' });
			let info = null;
			try {
				const res = await api.getContactInfo();
				info = (res && res.data) || null;
			} catch (e) {
				info = null;
			}
			uni.hideLoading();

			const lines = [];
			if (info) {
				if (info.phone) lines.push('联系电话：' + info.phone);
				if (info.email) lines.push('电子邮箱：' + info.email);
				if (info.qq) lines.push('QQ：' + info.qq);
				if (info.worktime) lines.push('工作时间：' + info.worktime);
				if (info.address) lines.push('联系地址：' + info.address);
				if (info.desc) lines.push('说明：' + info.desc);
			}
			if (!lines.length) {
				lines.push('暂未配置联系方式，请在「我的」页面通过「联系客服」功能与我们取得联系。');
			}

			this.contactLines = lines;
			this.contactShow = true;
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
}

.section-text {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	text-indent: 2em;
}

.maintenance-footer {
	padding: 30rpx;
	text-align: center;
	border-top: 1rpx solid #f0f0f0;
}

.footer-text {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 24rpx;
}

/* ===== 维护类型标签 ===== */
.type-badge {
	margin-left: auto;
	padding: 8rpx 20rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	color: #fff;
	font-size: 22rpx;
	font-weight: 500;
	flex-shrink: 0;
}

/* ===== 维护原因卡片（区分每日维护 / 管理员维护） ===== */
.reason-card {
	background: #f7f8fc;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 30rpx;
}

.reason-card.manual {
	background: #fff8f0;
}

.reason-head {
	display: flex;
	align-items: center;
	gap: 20rpx;
	margin-bottom: 20rpx;
}

.reason-icon {
	width: 64rpx;
	height: 64rpx;
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
}

.reason-icon.manual {
	background: linear-gradient(135deg, #faad14, #fa8c16);
}

.reason-icon-img {
	width: 56rpx;
	height: 56rpx;
	flex-shrink: 0;
}

.reason-title-wrap {
	flex: 1;
	min-width: 0;
}

.reason-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
}

.reason-sub {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 4rpx;
}

.reason-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 10rpx 0;
	font-size: 26rpx;
}

.reason-label {
	color: #948eb3;
	flex-shrink: 0;
}

.reason-value {
	color: #48416f;
	font-weight: 500;
}

.reason-value.countdown {
	color: #13c2c2;
	font-weight: bold;
	font-size: 30rpx;
	letter-spacing: 2rpx;
}

.reason-desc {
	font-size: 24rpx;
	color: #767676;
	line-height: 1.7;
	margin-top: 12rpx;
	padding-top: 12rpx;
	border-top: 1rpx dashed #e5e5ef;
}

/* 后台维护公告 */
.notice-box {
	background: #fff;
	border: 1rpx solid #ffe7c7;
	border-radius: 16rpx;
	padding: 20rpx;
	margin-top: 12rpx;
}

.notice-title {
	font-size: 26rpx;
	font-weight: bold;
	color: #fa8c16;
	margin-bottom: 10rpx;
}

.notice-content {
	font-size: 26rpx;
	color: #595959;
	line-height: 1.8;
	word-break: break-all;
}

/* 底部操作：一行两个按钮 */
.footer-actions {
	display: flex;
	align-items: center;
	gap: 20rpx;
}

.action-btn {
	flex: 1;
	height: 76rpx;
	line-height: 76rpx;
	text-align: center;
	font-size: 28rpx;
	border-radius: 40rpx;
	box-sizing: border-box;
}

.action-btn:active {
	opacity: 0.85;
	transform: scale(0.98);
}

/* 主按钮：联系客服 */
.action-primary {
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	color: #fff;
	box-shadow: 0 8rpx 20rpx rgba(19, 194, 194, 0.28);
}

/* 次按钮：重新检测 */
.action-plain {
	background: #fff;
	color: #13c2c2;
	border: 1rpx solid #13c2c2;
}
</style>
