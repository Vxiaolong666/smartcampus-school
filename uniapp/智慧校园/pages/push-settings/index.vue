<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">电费信息推送设置</view>
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

			<view v-else-if="dormitories.length === 0" class="empty-section">
				<image class="empty-icon" src="/static/assets/wxmp-home2-gray.svg" mode="widthFix"></image>
				<text class="empty-text">未绑定宿舍，请先绑定宿舍</text>
				<button class="bind-btn" @click="goToBind">前往绑定</button>
			</view>

			<view v-else class="settings-list">
				<view class="action-card">
					<view class="action-title">
						<image class="action-icon" src="/static/assets/wxmp-lightbulb.svg" mode="widthFix"></image>
						<text>推送说明</text>
					</view>
					<view class="action-desc">开启推送后，系统会根据您的设置自动推送电费相关提醒消息</view>
				</view>

				<view v-for="(item, index) in dormitories" :key="item.id" class="dorm-card">
					<view class="dorm-header">
						<view class="dorm-name">
							<image class="dorm-icon" src="/static/assets/wxmp-home2.svg" mode="widthFix"></image>
							<text>{{ item.displayName }}</text>
							<text v-if="item.is_default == 1" class="default-tag">默认</text>
						</view>
						<switch :checked="item.isEnabled" @change="togglePush" :data-index="index" color="#13c2c2" />
					</view>

					<view v-if="item.isEnabled" class="dorm-content">
						<view class="form-section">
							<view class="section-title">推送方式</view>
							<view class="checkbox-group">
								<label class="checkbox-item">
									<checkbox
										value="wechat"
										:checked="item.pushWechat"
										@change="onPushTypeChange"
										:data-index="index"
										data-type="wechat"
										color="#13c2c2"
									/>
									<text>微信订阅消息</text>
								</label>
								<label class="checkbox-item">
									<checkbox
										value="email"
										:checked="item.pushEmail"
										@change="onPushTypeChange"
										:data-index="index"
										data-type="email"
										color="#13c2c2"
									/>
									<text>邮件推送</text>
								</label>
							</view>
						</view>

						<view class="form-section">
							<view class="section-title">推送消息类型</view>
							<view class="checkbox-group vertical">
								<label class="checkbox-item">
									<checkbox
										value="daily_summary"
										:checked="item.notifyDaily"
										@change="onNotifyTypeChange"
										:data-index="index"
										data-type="daily_summary"
										color="#13c2c2"
										:disabled="!item.dailySummaryEnabled"
									/>
									<text :class="{ disabled: !item.dailySummaryEnabled }">每日用电汇总</text>
								</label>
								<label class="checkbox-item">
									<checkbox
										value="balance_low"
										:checked="item.notifyLow"
										@change="onNotifyTypeChange"
										:data-index="index"
										data-type="balance_low"
										color="#13c2c2"
										:disabled="!item.lowBalanceEnabled"
									/>
									<text :class="{ disabled: !item.lowBalanceEnabled }">余额不足提醒</text>
								</label>
								<label class="checkbox-item">
									<checkbox
										value="balance_critical"
										:checked="item.notifyCritical"
										@change="onNotifyTypeChange"
										:data-index="index"
										data-type="balance_critical"
										color="#13c2c2"
										:disabled="!item.criticalBalanceEnabled"
									/>
									<text :class="{ disabled: !item.criticalBalanceEnabled }">余额即将耗尽</text>
								</label>
								<label class="checkbox-item">
									<checkbox
										value="arrears"
										:checked="item.notifyArrears"
										@change="onNotifyTypeChange"
										:data-index="index"
										data-type="arrears"
										color="#13c2c2"
										:disabled="!item.arrearsEnabled"
									/>
									<text :class="{ disabled: !item.arrearsEnabled }">欠费提醒</text>
								</label>
							</view>
						</view>

						<view class="form-section">
							<view class="section-title">阈值设置</view>
							<view class="input-row">
								<text class="input-label">余额不足阈值(元)</text>
								<input
									type="digit"
									:value="item.balanceLowThreshold"
									@input="onThresholdChange"
									:data-index="index"
									data-type="low"
									placeholder="30"
								/>
							</view>
							<view class="input-row">
								<text class="input-label">即将耗尽阈值(元)</text>
								<input
									type="digit"
									:value="item.balanceCriticalThreshold"
									@input="onThresholdChange"
									:data-index="index"
									data-type="critical"
									placeholder="10"
								/>
							</view>
						</view>

						<button class="save-btn" @click="onSubmit" :data-index="index">保存设置</button>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 电费推送设置页面
 * 管理宿舍电费推送通知设置
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

export default {
	data() {
		return {
			featureEnabled: true,
			announcement: null,
			dormitories: [],
			config: {},
			loading: true
		};
	},

	onLoad() {
		this.checkFeature().then(canProceed => {
			if (canProceed) {
				this.loadPushSettings();
			}
		});
	},

	methods: {
		async checkFeature() {
			const result = await feature.checkFeature('push_notification');
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

		loadPushSettings() {
			this.loading = true;

			api.getPushSettings()
				.then(res => {
					const dormitories = res.data && res.data.bindings ? res.data.bindings : [];
					const config = res.data && res.data.config ? res.data.config : {};

					dormitories.forEach(dorm => {
						dorm.id = dorm.binding_id;
						dorm.displayName =
							dorm.display_name ||
							(dorm.alias
								? dorm.alias + '（' + dorm.school_area + dorm.building + ' ' + dorm.room + '）'
								: dorm.school_area + dorm.building + ' ' + dorm.room);

						dorm.notifyTypes = dorm.notify_types
							? dorm.notify_types.split(',')
							: ['daily_summary', 'balance_low', 'balance_critical', 'arrears'];
						dorm.pushTypes = dorm.push_types || 'wechat';
						dorm.isEnabled = dorm.is_enabled == 1;

						dorm.balanceLowThreshold = dorm.balance_low_threshold || config.low_balance_threshold || 30;
						dorm.balanceCriticalThreshold = dorm.balance_critical_threshold || config.critical_balance_threshold || 10;

						dorm.notifyDaily = dorm.notifyTypes.includes('daily_summary');
						dorm.notifyLow = dorm.notifyTypes.includes('balance_low');
						dorm.notifyCritical = dorm.notifyTypes.includes('balance_critical');
						dorm.notifyArrears = dorm.notifyTypes.includes('arrears');

						dorm.pushWechat = dorm.pushTypes.includes('wechat');
						dorm.pushEmail = dorm.pushTypes.includes('email');

						dorm.dailySummaryEnabled = config.daily_summary_enabled != 0;
						dorm.lowBalanceEnabled = config.low_balance_enabled != 0;
						dorm.criticalBalanceEnabled = config.critical_balance_enabled != 0;
						dorm.arrearsEnabled = config.arrears_enabled != 0;
					});

					this.dormitories = dormitories;
					this.config = config;
					this.loading = false;
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

		togglePush(e) {
			const index = e.currentTarget.dataset.index;
			const dormitories = this.dormitories;
			dormitories[index].isEnabled = e.detail.value;
			this.dormitories = dormitories;
			this.saveSettings(index, !e.detail.value);
		},

		onPushTypeChange(e) {
			const index = e.currentTarget.dataset.index;
			const type = e.currentTarget.dataset.type;
			const dormitories = this.dormitories;
			const checked = e.detail.value.length > 0;

			if (type === 'wechat') {
				dormitories[index].pushWechat = checked;
			} else if (type === 'email') {
				dormitories[index].pushEmail = checked;
			}

			this.dormitories = dormitories;
		},

		onNotifyTypeChange(e) {
			const index = e.currentTarget.dataset.index;
			const type = e.currentTarget.dataset.type;
			const dormitories = this.dormitories;
			const checked = e.detail.value.length > 0;

			if (type === 'daily_summary') {
				dormitories[index].notifyDaily = checked;
			} else if (type === 'balance_low') {
				dormitories[index].notifyLow = checked;
			} else if (type === 'balance_critical') {
				dormitories[index].notifyCritical = checked;
			} else if (type === 'arrears') {
				dormitories[index].notifyArrears = checked;
			}

			this.dormitories = dormitories;
		},

		onThresholdChange(e) {
			const index = e.currentTarget.dataset.index;
			const type = e.currentTarget.dataset.type;
			const dormitories = this.dormitories;
			const value = parseFloat(e.detail.value) || 0;

			if (type === 'low') {
				dormitories[index].balanceLowThreshold = value;
			} else if (type === 'critical') {
				dormitories[index].balanceCriticalThreshold = value;
			}

			this.dormitories = dormitories;
		},

		saveSettings(index, disableOnly = false) {
			const dorm = this.dormitories[index];

			const pushTypes = [];
			if (dorm.pushWechat) pushTypes.push('wechat');
			if (dorm.pushEmail) pushTypes.push('email');

			const notifyTypes = [];
			if (dorm.notifyDaily) notifyTypes.push('daily_summary');
			if (dorm.notifyLow) notifyTypes.push('balance_low');
			if (dorm.notifyCritical) notifyTypes.push('balance_critical');
			if (dorm.notifyArrears) notifyTypes.push('arrears');

			const data = {
				dormitory_binding_id: dorm.id,
				push_types: pushTypes.join(','),
				notify_types: notifyTypes.join(','),
				balance_low_threshold: dorm.balanceLowThreshold,
				balance_critical_threshold: dorm.balanceCriticalThreshold,
				is_enabled: disableOnly ? 0 : dorm.isEnabled ? 1 : 0
			};

			api.savePushSettings(data)
				.then(() => {
					uni.showToast({ title: '保存成功', icon: 'success' });
				})
				.catch(err => {
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

		onSubmit(e) {
			const index = e.currentTarget.dataset.index;
			this.saveSettings(index);
		},

		goToBind() {
			uni.navigateTo({ url: '/pages/dormitory-bind/index' });
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

.loading-section,
.empty-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 0;
}

.empty-icon {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 20rpx;
}

.loading-spinner {
	width: 60rpx;
	height: 60rpx;
	border: 4rpx solid #e0e0e0;
	border-top-color: #13c2c2;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}

.loading-text,
.empty-text {
	margin-top: 20rpx;
	color: #948eb3;
	font-size: 28rpx;
}

.bind-btn {
	margin-top: 30rpx;
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	color: #fff;
	border-radius: 12rpx;
	font-size: 28rpx;
}

.settings-list {
	margin-top: 25rpx;
}

.action-card {
	background: white;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 24rpx;
}

.action-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	display: flex;
	align-items: center;
}

.action-icon {
	width: 28rpx;
	height: 28rpx;
	margin-right: 10rpx;
}

.action-desc {
	font-size: 26rpx;
	color: #948eb3;
	line-height: 1.6;
}

.dorm-card {
	background: white;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.dorm-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.dorm-name {
	display: flex;
	align-items: center;
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
}

.dorm-icon {
	width: 36rpx;
	height: 36rpx;
	margin-right: 10rpx;
}

.default-tag {
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	color: #fff;
	font-size: 22rpx;
	padding: 4rpx 12rpx;
	border-radius: 8rpx;
	margin-left: 10rpx;
	font-weight: normal;
}

.dorm-content {
	padding: 30rpx;
}

.form-section {
	margin-bottom: 30rpx;
}

.section-title {
	font-size: 28rpx;
	color: #48416f;
	margin-bottom: 20rpx;
	font-weight: bold;
}

.checkbox-group {
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx;
}

.checkbox-group.vertical {
	flex-direction: column;
	gap: 15rpx;
}

.checkbox-item {
	display: flex;
	align-items: center;
	font-size: 28rpx;
	color: #48416f;
}

.checkbox-item text {
	margin-left: 10rpx;
}

.checkbox-item text.disabled {
	color: #c0c0c0;
}

.input-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx;
	background: #f7f8fc;
	border-radius: 12rpx;
	margin-bottom: 15rpx;
}

.input-label {
	font-size: 28rpx;
	color: #48416f;
}

.input-row input {
	width: 200rpx;
	text-align: right;
	font-size: 28rpx;
	color: #48416f;
}

.save-btn {
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	color: #fff;
	border-radius: 12rpx;
	margin-top: 20rpx;
	font-size: 28rpx;
	box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
}
</style>
