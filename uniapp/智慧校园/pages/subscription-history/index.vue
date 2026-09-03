<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">订阅历史记录</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="info-card">
				<image class="info-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
				<view class="info-content">
					<view class="info-text">共 {{ total }} 条历史记录</view>
					<view class="info-sub">包含订阅授权与消息推送记录</view>
				</view>
			</view>

			<view class="tab-bar">
				<view
					class="tab-item"
					v-for="tab in tabs"
					:key="tab.value"
					:class="activeTab === tab.value ? 'active' : ''"
					:data-value="tab.value"
					@click="onChangeTab"
				>
					{{ tab.label }}
				</view>
			</view>

			<view v-if="loading && list.length === 0" class="loading-wrapper">
				<view class="loading-spinner"></view>
				<view class="loading-text">加载中...</view>
			</view>

			<view v-else-if="list.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
				<view class="empty-text">暂无历史记录</view>
			</view>

			<view v-else class="history-list">
				<view
					v-for="item in list"
					:key="item.id"
					class="history-item"
					:class="item.source"
				>
					<image class="history-icon" :src="iconOf(item.source)" mode="widthFix"></image>
					<view class="history-body">
						<view class="history-header">
							<view class="history-category">{{ item.category }}</view>
							<view class="history-status" :class="statusClass(item.status)">{{ item.status_text }}</view>
						</view>
						<view class="history-title">{{ item.title }}</view>
						<view v-if="item.content" class="history-content">{{ item.content }}</view>
						<view v-if="item.status === 2 && item.error_msg" class="history-error">失败原因：{{ item.error_msg }}</view>
						<view class="history-time">{{ item.time }}</view>
					</view>
				</view>
			</view>

			<view v-if="loading && list.length > 0" class="loading-wrapper">
				<view class="loading-text">加载中...</view>
			</view>

			<view v-if="!hasMore && list.length > 0" class="no-more">
				<view class="no-more-text">没有更多了</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 订阅历史记录页面
 * 展示当前用户的订阅授权记录与消息推送记录（电费推送 / 教务推送）
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			activeTab: 'all',
			tabs: [
				{ label: '全部', value: 'all' },
				{ label: '订阅记录', value: 'subscribe' },
				{ label: '推送记录', value: 'push' }
			],
			list: [],
			loading: false,
			page: 1,
			size: 10,
			total: 0,
			hasMore: true
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
		this.loadHistory(true);
	},

	onReachBottom() {
		if (this.hasMore && !this.loading) {
			this.loadHistory(false);
		}
	},

	methods: {
		iconOf(source) {
			const map = {
				subscription: '/static/assets/wxmp-subscribe-teal.svg',
				power_push: '/static/assets/wxmp-bolt.svg',
				academic_push: '/static/assets/wxmp-calendar.svg'
			};
			return map[source] || '/static/assets/wxmp-list.svg';
		},

		statusClass(status) {
			if (status === 1) return 'success';
			if (status === 2) return 'fail';
			return 'pending';
		},

		onChangeTab(e) {
			const value = e.currentTarget.dataset.value;
			if (value === this.activeTab) return;
			this.activeTab = value;
			this.loadHistory(true);
		},

		async loadHistory(reset) {
			if (this.loading) return;

			if (reset) {
				this.page = 1;
				this.list = [];
				this.hasMore = true;
			}

			this.loading = true;

			try {
				const res = await api.getSubscriptionHistory(this.page, this.size, this.activeTab);
				const data = res.data || {};
				const list = (data.list || []).map(item => ({
					...item,
					time: item.time || ''
				}));

				this.list = [...this.list, ...list];
				this.total = parseInt(data.total || 0, 10);
				this.page = this.page + 1;
				this.hasMore = this.list.length < this.total;
			} catch (err) {
				console.error('加载订阅历史失败', err);
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			}

			this.loading = false;
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

.info-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-top: 25rpx;
	margin-bottom: 24rpx;
	display: flex;
	align-items: center;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.info-icon {
	width: 48rpx;
	height: 48rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.info-content {
	flex: 1;
}

.info-text {
	font-size: 28rpx;
	color: #48416f;
	font-weight: bold;
}

.info-sub {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 6rpx;
}

.tab-bar {
	display: flex;
	background: white;
	border-radius: 16rpx;
	padding: 8rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.tab-item {
	flex: 1;
	text-align: center;
	font-size: 26rpx;
	color: #948eb3;
	padding: 16rpx 0;
	border-radius: 12rpx;
	transition: all 0.2s;
}

.tab-item.active {
	background: #13c2c2;
	color: white;
	font-weight: bold;
}

.loading-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 60rpx 0;
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
	padding: 100rpx 0;
}

.empty-icon {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 20rpx;
}

.empty-text {
	color: #948eb3;
	font-size: 28rpx;
}

.history-list {
	margin-top: 6rpx;
}

.history-item {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	display: flex;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.history-icon {
	width: 56rpx;
	height: 56rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.history-body {
	flex: 1;
	min-width: 0;
}

.history-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.history-category {
	font-size: 22rpx;
	color: white;
	background: #13c2c2;
	padding: 4rpx 14rpx;
	border-radius: 6rpx;
	flex-shrink: 0;
}

.history-item.power_push .history-category {
	background: #ff9f43;
}

.history-item.academic_push .history-category {
	background: #667eea;
}

.history-status {
	font-size: 22rpx;
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	flex-shrink: 0;
}

.history-status.success {
	background: #f6ffed;
	color: #52c41a;
}

.history-status.fail {
	background: #fff2f0;
	color: #ff4d4f;
}

.history-status.pending {
	background: #f4f4f5;
	color: #9494a8;
}

.history-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	line-height: 1.4;
	margin-bottom: 10rpx;
}

.history-content {
	font-size: 24rpx;
	color: #6b6585;
	line-height: 1.5;
	white-space: pre-wrap;
	word-break: break-all;
	margin-bottom: 10rpx;
}

.history-error {
	font-size: 22rpx;
	color: #ff4d4f;
	background: #fff2f0;
	border-radius: 8rpx;
	padding: 12rpx;
	margin-bottom: 10rpx;
}

.history-time {
	font-size: 22rpx;
	color: #c0c0c0;
	text-align: right;
}

.no-more {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 30rpx 0;
}

.no-more-text {
	color: #948eb3;
	font-size: 26rpx;
}
</style>
