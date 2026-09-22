<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">{{ pageTitle }}</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<announcement v-if="announcement" :content="announcement.content" type="info" />

			<view v-if="loading" class="loading-section">
				<view class="loading-spinner"></view>
				<text class="loading-text">加载中...</text>
			</view>

			<view v-else-if="!hasAcademicAccount" class="form-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/048-presentation.svg" mode="widthFix"></image>
					<view class="card-title">{{ pageTitle }}</view>
					<view class="card-desc">{{ pageDesc }}</view>
				</view>
				<view class="empty-tip">
					<text>请先绑定教务账号后再设置学术推送</text>
				</view>
				<button class="submit-btn" @click="goToBind">前往绑定</button>
			</view>

			<block v-else>
				<view class="form-card">
					<view class="card-header">
						<image class="card-logo" src="/static/assets/048-presentation.svg" mode="widthFix"></image>
						<view class="card-title">{{ pageTitle }}</view>
						<view class="card-desc">{{ pageDesc }}</view>
					</view>

					<view class="bound-info">
						<view class="bound-item">
							<view class="bound-label">当前账号</view>
							<picker v-if="academicAccounts.length > 1" :range="academicAccounts" range-key="student_id" :value="accountIndex" @change="onAccountChange">
								<view class="bound-value">{{ studentId }} <text class="switch-caret">▾</text></view>
							</picker>
							<view v-else class="bound-value">{{ studentId }}</view>
						</view>
						<view class="bound-item">
							<view class="bound-label">推送状态</view>
							<switch :checked="isEnabled" @change="togglePush" color="#13c2c2" />
						</view>
					</view>

					<block v-if="isEnabled">
						<view class="form-item">
							<view class="form-label">推送方式</view>
							<checkbox-group class="checkbox-group" @change="onPushTypeChange">
								<label v-if="globalConfig.wechat_push_enabled == 1" class="checkbox-item">
									<checkbox
										value="wechat"
										:checked="pushWechat"
										color="#13c2c2"
									/>
									<text>微信订阅消息</text>
								</label>
								<label v-if="globalConfig.email_push_enabled == 1" class="checkbox-item">
									<checkbox
										value="email"
										:checked="pushEmail"
										color="#13c2c2"
									/>
									<text>邮件推送</text>
								</label>
							</checkbox-group>
						</view>

						<view v-if="globalConfig.schedule_reminder_enabled == 1" class="form-item">
							<view class="form-label">明日课程提醒</view>
							<view class="switch-row">
								<switch :checked="scheduleReminderEnabled" @change="onScheduleReminderChange" color="#13c2c2" />
								<picker v-if="scheduleReminderEnabled" mode="time" :value="scheduleReminderTime" @change="onTimeChange">
									<view class="time-picker">{{ scheduleReminderTime }}</view>
								</picker>
							</view>
							<view class="form-tip">每天定时推送明天的课程安排</view>
						</view>

						<view v-if="globalConfig.grade_notify_enabled == 1" class="form-item">
							<view class="form-label">成绩发布通知</view>
							<view class="switch-row">
								<switch :checked="gradeNotifyEnabled" @change="onGradeNotifyChange" color="#13c2c2" />
							</view>
							<view class="form-tip">当有新成绩发布时自动推送通知</view>
						</view>

						<button class="submit-btn" @click="onSubmit" :loading="saving">保存设置</button>
					</block>
				</view>

				<view class="menu-card" v-if="logs.length > 0">
					<view class="menu-title">推送记录</view>
					<view v-for="item in logs" :key="item.id" class="log-item">
						<view class="log-header">
							<view class="log-type">
								<text>{{ item.notify_type === 'schedule_reminder' ? '课程提醒' : '成绩通知' }}</text>
							</view>
							<view class="log-status" :class="item.status === 1 ? 'success' : 'failed'">
								{{ item.status === 1 ? '已发送' : '失败' }}
							</view>
						</view>
						<view class="log-title">{{ item.title }}</view>
						<view class="log-time">{{ item.sent_at }}</view>
					</view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
/**
 * 教务推送设置页面
 * 管理课程提醒和成绩通知设置
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import validate from '../../utils/validate.js';

// 入口场景 → 页面标题 / 卡片副标题
// 「我的」页的「课程推送设置」与「成绩推送设置」共用本页，按入口动态展示标题与说明
const PAGE_META = {
    default: { title: '学术推送设置', desc: '开启后可自动推送课程提醒和成绩通知' },
    course: { title: '课程推送设置', desc: '开启后可自动推送课程提醒' },
    grade: { title: '成绩推送设置', desc: '开启后可自动推送成绩通知' }
};

export default {
	data() {
		return {
			featureEnabled: true,
			announcement: null,
			scene: '',                            // 入口场景：course=课程推送，grade=成绩推送，空=学术推送
			pageTitle: PAGE_META.default.title,    // 页面标题（按入口动态展示）
			pageDesc: PAGE_META.default.desc,      // 卡片副标题（按入口动态展示）
			studentId: '',
			hasAcademicAccount: false,
			isEnabled: false,
			pushWechat: true,
			pushEmail: false,
			scheduleReminderEnabled: true,
			scheduleReminderTime: '20:00',
			gradeNotifyEnabled: true,
			logs: [],
			loading: true,
			saving: false,
			globalConfig: {
				schedule_reminder_enabled: 1,
				grade_notify_enabled: 1,
				wechat_push_enabled: 1,
				email_push_enabled: 1
			},
			academicAccounts: [],
			academicAccountId: 0,
			accountIndex: 0
		};
	},

	onLoad(options) {
		// 按入口动态展示标题与副标题：「我的」页的课程/成绩推送设置均跳转到本页
		const scene = (options && options.scene) ? String(options.scene) : 'default';
		const meta = PAGE_META[scene] || PAGE_META.default;
		this.scene = scene;
		this.pageTitle = meta.title;
		this.pageDesc = meta.desc;
		try {
			uni.setNavigationBarTitle({ title: this.pageTitle });
		} catch (e) {}

		this.checkFeature().then(canProceed => {
			if (canProceed) {
				this.loadSettings();
			}
		});
	},

	methods: {
		async checkFeature() {
			const result = await feature.checkFeature('academic_push');
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

		loadSettings(accountId) {
			this.loading = true;

			api.getAcademicPushSettings({ academic_account_id: accountId || this.academicAccountId || undefined })
				.then(res => {
					const data = res.data || {};
					const settings = data.settings || {};
					const globalConfig = data.global_config || {
						schedule_reminder_enabled: 1,
						grade_notify_enabled: 1,
						wechat_push_enabled: 1,
						email_push_enabled: 1
					};

					const pushTypes = settings.push_types || 'wechat';

					this.academicAccounts = data.academic_accounts || [];
					this.academicAccountId = data.current_account_id || 0;
					this.accountIndex = this.academicAccounts.findIndex(a => a.id === this.academicAccountId);
					if (this.accountIndex < 0) this.accountIndex = 0;

					this.studentId = data.student_id || '';
					this.hasAcademicAccount = data.has_academic_account || false;
					this.isEnabled = settings.is_enabled == 1;
					this.pushWechat = pushTypes.includes('wechat');
					this.pushEmail = pushTypes.includes('email');
					this.scheduleReminderEnabled =
						settings.schedule_reminder_enabled == 1 || settings.schedule_reminder_enabled === null;
					this.scheduleReminderTime = (settings.schedule_reminder_time || '20:00:00').substring(0, 5);
					this.gradeNotifyEnabled = settings.grade_notify_enabled == 1 || settings.grade_notify_enabled === null;
					this.globalConfig = globalConfig;
					this.loading = false;

					this.loadLogs();
				})
				.catch(err => {
					if (err.code === -2) {
						uni.showToast({ title: '请先登录', icon: 'none' });
						setTimeout(() => {
							uni.redirectTo({ url: '/pages/login/index' });
						}, 1500);
					} else {
						uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
						this.loading = false;
					}
				});
		},

		loadLogs() {
			api.getAcademicPushLogs()
				.then(res => {
					this.logs = res.data || [];
					this.loading = false;
				})
				.catch(() => {});
		},

		togglePush(e) {
			const isEnabled = e.detail.value;
			this.isEnabled = isEnabled;

			if (!isEnabled) {
				this.saveSettings(true);
			}
		},

		onPushTypeChange(e) {
			const vals = e.detail.value || [];
			this.pushWechat = vals.includes('wechat');
			this.pushEmail = vals.includes('email');
		},

		onScheduleReminderChange(e) {
			this.scheduleReminderEnabled = e.detail.value;
		},

		onGradeNotifyChange(e) {
			this.gradeNotifyEnabled = e.detail.value;
		},

		onTimeChange(e) {
			this.scheduleReminderTime = e.detail.value;
		},

		onAccountChange(e) {
			const idx = e.detail.value;
			if (this.academicAccounts[idx]) {
				this.academicAccountId = this.academicAccounts[idx].id;
				this.accountIndex = idx;
				this.loadSettings();
			}
		},

		saveSettings(disableOnly = false) {
			const pushTypes = [];
			if (this.pushWechat) pushTypes.push('wechat');
			if (this.pushEmail) pushTypes.push('email');

			// 表单校验：开启推送时必须至少选择一种推送方式，否则本次请求没有意义
			if (!disableOnly && this.isEnabled && pushTypes.length === 0) {
				uni.showToast({ title: '请至少选择一种推送方式', icon: 'none' });
				return;
			}

			this.saving = true;

			const data = {
				academic_account_id: this.academicAccountId,
				push_types: pushTypes.join(','),
				schedule_reminder_enabled: this.scheduleReminderEnabled ? 1 : 0,
				schedule_reminder_time: this.scheduleReminderTime,
				grade_notify_enabled: this.gradeNotifyEnabled ? 1 : 0,
				is_enabled: disableOnly ? 0 : this.isEnabled ? 1 : 0
			};

			api.saveAcademicPushSettings(data)
				.then(() => {
					this.saving = false;
					uni.showToast({ title: '保存成功', icon: 'success' });
				})
				.catch(err => {
					this.saving = false;
					if (err.code === -2) {
						uni.showToast({ title: '请先登录', icon: 'none' });
						setTimeout(() => {
							uni.redirectTo({ url: '/pages/login/index' });
						}, 1500);
					} else {
						uni.showToast({ title: err.msg || '保存失败', icon: 'none' });
					}
				});
		},

		onSubmit() {
			this.saveSettings();
		},

		goToBind() {
			uni.navigateTo({ url: '/pages/academic-bind/index' });
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

.loading-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 0;
}

.loading-spinner {
	width: 60rpx;
	height: 60rpx;
	border: 4rpx solid #e0e0e0;
	border-top-color: #13c2c2;
	border-radius: 50%;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}

.loading-text {
	margin-top: 20rpx;
	color: #666;
	font-size: 28rpx;
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

.empty-tip {
	text-align: center;
	padding: 40rpx 0;
}

.empty-tip text {
	font-size: 28rpx;
	color: #948eb3;
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

.form-item {
	margin-bottom: 36rpx;
}

.form-label {
	font-size: 28rpx;
	color: #48416f;
	margin-bottom: 16rpx;
	font-weight: bold;
}

.form-tip {
	font-size: 24rpx;
	color: #948eb3;
	margin-top: 12rpx;
}

.checkbox-group {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.checkbox-item {
	display: flex;
	align-items: center;
	font-size: 28rpx;
	color: #48416f;
}

.checkbox-item checkbox {
	margin-right: 16rpx;
}

.switch-row {
	display: flex;
	align-items: center;
	gap: 30rpx;
}

.time-picker {
	background: #f7f8fc;
	padding: 16rpx 30rpx;
	border-radius: 16rpx;
	font-size: 28rpx;
	color: #13c2c2;
	border: 2rpx solid #13c2c2;
}

.submit-btn {
	width: 100%;
	height: 96rpx;
	background: #13c2c2;
	color: #fff;
	font-size: 30rpx;
	border-radius: 20rpx;
	font-weight: bold;
	margin-top: 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
	display: flex;
	align-items: center;
	justify-content: center;
}

.submit-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.menu-card {
	background: white;
	border-radius: 30rpx;
	margin-top: 30rpx;
	padding: 30rpx 40rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.menu-title {
	font-size: 28rpx;
	color: #948eb3;
	padding-bottom: 20rpx;
	font-weight: bold;
}

.log-item {
	padding: 24rpx 0;
	border-bottom: 2rpx solid #f0f0f0;
}

.log-item:last-child {
	border-bottom: none;
}

.log-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.log-type text {
	font-size: 26rpx;
	color: #48416f;
}

.log-status {
	font-size: 22rpx;
	padding: 4rpx 16rpx;
	border-radius: 20rpx;
}

.log-status.success {
	background: #f6ffed;
	color: #52c41a;
}

.log-status.failed {
	background: #fff2f0;
	color: #ff4d4f;
}

.log-title {
	font-size: 28rpx;
	color: #48416f;
	margin-bottom: 8rpx;
}

.log-time {
	font-size: 24rpx;
	color: #948eb3;
}
</style>
