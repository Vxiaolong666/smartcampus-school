<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">订阅消息管理</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="card">
				<view class="card-title">订阅消息管理</view>
				<view class="card-desc">管理您的订阅消息授权和剩余次数</view>
			</view>

			<view class="action-card">
				<view class="action-title">
					<image class="action-icon" src="/static/assets/wxmp-lightbulb.svg" mode="widthFix"></image>
					<text>订阅说明</text>
				</view>
				<view class="action-desc">
					订阅消息需要您主动授权，每次授权可增加一次订阅次数。当订阅次数用完后，将无法收到对应类型的推送通知。
				</view>
			</view>

			<view class="action-card">
				<view class="action-title">
					<image class="action-icon" src="/static/assets/wxmp-question.svg" mode="widthFix"></image>
					<text>为什么需要一次一次订阅？</text>
				</view>
				<view class="action-desc">
					这是微信小程序的规定，订阅消息必须由用户主动授权，且每次授权只能发送一条消息。这是为了保护用户不被骚扰，确保每条推送都是用户知情同意的。
				</view>
			</view>

			<view v-if="loading" class="loading-wrapper">
				<view class="loading-spinner"></view>
				<view class="loading-text">加载中...</view>
			</view>

			<view v-else-if="subscriptions.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
				<view class="empty-text">暂无可用的订阅消息模板</view>
			</view>

			<view v-else class="subscription-list">
				<view
					v-for="item in subscriptions"
					:key="item.id"
					class="subscription-item"
					:class="item.subscribe_count >= 2 ? 'status-ok' : item.subscribe_count > 0 ? 'status-low' : 'status-empty'"
				>
					<view class="subscription-header">
						<view class="subscription-name">{{ item.name }}</view>
						<view class="subscription-scene">{{ item.sceneName }}</view>
					</view>

					<view class="subscription-desc" v-if="item.description">{{ item.description }}</view>

					<view class="subscription-footer">
						<view class="subscription-count">
							<text class="count-label">剩余次数</text>
							<text class="count-value">{{ item.subscribe_count }}</text>
						</view>

						<view class="subscription-actions">
							<button
								class="subscribe-btn"
								:class="item.subscribe_count >= 2 ? 'secondary' : 'primary'"
								:data-template-id="item.template_id"
								@click="onRequestSubscribe"
							>
								<image
									class="btn-icon"
									:src="item.subscribe_count >= 2 ? '/static/assets/wxmp-subscribe-teal.svg' : '/static/assets/wxmp-subscribe.svg'"
									mode="widthFix"
								></image>
								<text>{{ item.subscribe_count >= 2 ? '增加订阅' : '立即订阅' }}</text>
							</button>
						</view>
					</view>

					<view v-if="item.subscribe_time" class="subscription-time">最后订阅: {{ item.subscribe_time }}</view>
				</view>
			</view>

			<view class="action-card">
				<view class="action-title">如何增加订阅次数？</view>
				<view class="action-steps">
					<view class="step-item">
						<view class="step-num">1</view>
						<view class="step-text">点击对应模板的"立即订阅"按钮</view>
					</view>
					<view class="step-item">
						<view class="step-num">2</view>
						<view class="step-text">在弹出的授权窗口中点击"允许"</view>
					</view>
					<view class="step-item">
						<view class="step-num">3</view>
						<view class="step-text">每次授权可增加一次订阅次数</view>
					</view>
				</view>
			</view>

			<view class="bottom-actions">
				<button class="action-btn secondary" @click="goToSubscriptionHistory">
					<image class="btn-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
					<text>订阅历史</text>
				</button>
				<button class="action-btn primary" @click="onRefresh">
					<image class="btn-icon" src="/static/assets/wxmp-refresh.svg" mode="widthFix"></image>
					<text>刷新数据</text>
				</button>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 订阅管理页面
 * 管理微信订阅消息设置
 */
import api from '../../utils/api.js';
import { sceneTypeNames } from '../../utils/common.js';

export default {
	data() {
		return {
			loading: true,
			subscriptions: []
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}
		this.loadSubscriptions();
	},

	onShow() {
		if (api.isLoggedIn()) {
			this.loadSubscriptions();
		}
	},

	methods: {
		async loadSubscriptions() {
			this.loading = true;

			try {
				const res = await api.request({
					act: 'get_user_subscriptions',
					method: 'GET'
				});

				const subscriptions = (res.data.list || []).map(item => ({
					...item,
					sceneName: sceneTypeNames[item.scene_type] || '未分类',
					hasCount: item.subscribe_count > 0
				}));

				this.subscriptions = subscriptions;
				this.loading = false;
			} catch (err) {
				console.error('加载订阅数据失败', err);
				this.loading = false;
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			}
		},

		async onRequestSubscribe(e) {
			const templateId = e.currentTarget.dataset.templateId;

			if (!templateId) {
				uni.showToast({ title: '模板ID不存在', icon: 'none' });
				return;
			}

			// #ifdef MP-WEIXIN
			try {
				const res = await uni.requestSubscribeMessage({
					tmplIds: [templateId]
				});

				const result = res[templateId];

				if (result === 'accept') {
					uni.showToast({ title: '订阅成功', icon: 'success' });
					await this.saveSubscription(templateId);
					this.loadSubscriptions();
				} else if (result === 'reject') {
					uni.showToast({ title: '您拒绝了订阅', icon: 'none' });
				} else if (result === 'ban') {
					uni.showToast({ title: '该模板已被封禁', icon: 'none' });
				} else {
					uni.showToast({ title: '订阅结果: ' + result, icon: 'none' });
				}
			} catch (err) {
				console.error('订阅失败', err);
				uni.showToast({ title: '订阅失败: ' + (err.errMsg || '未知错误'), icon: 'none' });
			}
			// #endif

			// #ifndef MP-WEIXIN
			uni.showToast({ title: '当前平台暂不支持订阅消息，请在微信小程序端完成订阅', icon: 'none' });
			// #endif
		},

		async saveSubscription(templateId) {
			try {
				await api.request({
					act: 'save_subscription',
					method: 'POST',
					data: {
						template_id: templateId
					}
				});
			} catch (err) {
				console.error('保存订阅失败', err);
			}
		},

		onRefresh() {
			this.loadSubscriptions();
		},

		goToSubscriptionHistory() {
			uni.navigateTo({ url: '/pages/subscription-history/index' });
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

.card {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	margin-bottom: 24rpx;
	margin-top: 25rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.card-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.card-desc {
	font-size: 26rpx;
	color: #948eb3;
}

.loading-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 80rpx 0;
}

.loading-spinner {
	width: 60rpx;
	height: 60rpx;
	border: 4rpx solid #e8e8e8;
	border-top-color: #13c2c2;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}

.loading-text {
	font-size: 26rpx;
	color: #948eb3;
	margin-top: 20rpx;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 80rpx 0;
}

.empty-icon {
	width: 80rpx;
	height: 80rpx;
	margin-bottom: 20rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}

.subscription-list {
	margin-bottom: 24rpx;
}

.subscription-item {
	background: white;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
}

.subscription-item.status-low {
	background: #fffbe6;
}

.subscription-item.status-empty {
	background: #fff2f0;
}

.subscription-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.subscription-name {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
}

.subscription-scene {
	font-size: 22rpx;
	color: white;
	background: #13c2c2;
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
}

.subscription-item.status-low .subscription-scene {
	background: #faad14;
}

.subscription-item.status-empty .subscription-scene {
	background: #ff4d4f;
}

.subscription-desc {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 16rpx;
	line-height: 1.5;
}

.subscription-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 16rpx;
	border-top: 1rpx solid #f0f0f0;
}

.subscription-count {
	display: flex;
	flex-direction: column;
}

.count-label {
	font-size: 22rpx;
	color: #948eb3;
}

.subscription-item.status-low .count-label {
	color: #d48806;
}

.subscription-item.status-empty .count-label {
	color: #cf1322;
}

.count-value {
	font-size: 36rpx;
	font-weight: bold;
	color: #52c41a;
}

.subscription-item.status-low .count-value {
	color: #faad14;
}

.subscription-item.status-empty .count-value {
	color: #ff4d4f;
}

.subscription-actions {
	display: flex;
	align-items: center;
	justify-content: flex-end;
}

.subscribe-btn {
	font-size: 26rpx;
	padding: 16rpx 28rpx;
	border-radius: 8rpx;
	margin: 0;
	line-height: 1.4;
	white-space: nowrap;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: auto !important;
	min-width: 0 !important;
	max-width: none !important;
	flex: none;
}

.subscribe-btn::after {
	border: none;
}

.subscribe-btn .btn-icon {
	width: 24rpx;
	height: 24rpx;
	margin-right: 6rpx;
}

.subscribe-btn text {
	color: inherit;
}

.subscribe-btn.primary {
	background: #13c2c2;
	color: white;
}

.subscription-item.status-low .subscribe-btn.primary {
	background: #faad14;
}

.subscription-item.status-empty .subscribe-btn.primary {
	background: #ff4d4f;
}

.subscribe-btn.secondary {
	background: white;
	color: #13c2c2;
	border: 1rpx solid #13c2c2;
}

.subscription-time {
	font-size: 22rpx;
	color: #c0c0c0;
	margin-top: 12rpx;
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

.action-steps {
	padding-left: 8rpx;
}

.step-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 16rpx;
}

.step-item:last-child {
	margin-bottom: 0;
}

.step-num {
	width: 40rpx;
	height: 40rpx;
	background: #13c2c2;
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 22rpx;
	font-weight: bold;
	margin-right: 16rpx;
	flex-shrink: 0;
}

.step-text {
	font-size: 26rpx;
	color: #48416f;
	line-height: 40rpx;
}

.bottom-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 20rpx;
}

.action-btn {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 26rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0;
	border-radius: 12rpx;
	margin: 0;
}

.action-btn.primary {
	background: #13c2c2;
	color: white;
}

.action-btn.secondary {
	background: white;
	color: #13c2c2;
	border: 1rpx solid #13c2c2;
}

.btn-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 8rpx;
}
</style>
