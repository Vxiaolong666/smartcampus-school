<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">通知公告</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="info-card">
				<image class="info-icon" src="/static/assets/wxmp-notice.svg" mode="widthFix"></image>
				<view class="info-content">
					<view class="info-text">共有{{ notices.length }}条通知</view>
				</view>
			</view>

			<view v-if="notices.length === 0 && !loading" class="empty">
				<image class="empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
				<view class="empty-text">暂无通知</view>
			</view>

			<view v-else class="notice-list">
				<view
					v-for="item in notices"
					:key="item.id"
					class="notice-item"
					:data-id="item.id"
					@click="handleNoticeDetail"
				>
					<image v-if="item.cover" class="notice-cover" :src="item.cover" mode="aspectFill"></image>
					<view class="notice-info">
						<view class="notice-title">
							<text v-if="item.is_top == 1" class="top-badge">置顶</text>
							{{ item.title }}
						</view>
						<view class="notice-date">{{ item.create_time }}</view>
					</view>
				</view>
			</view>

			<view v-if="loading" class="loading">
				<view class="loading-text">加载中...</view>
			</view>

			<view v-if="!hasMore && notices.length > 0" class="no-more">
				<view class="no-more-text">没有更多了</view>
			</view>
		</view>
	</view>
</template>

<script>
import api from '../../utils/api.js';

const API_BASE = 'https://mc.xzwmc.com/csgocs';

export default {
	data() {
		return {
			notices: [],
			loading: false,
			page: 1,
			size: 10,
			total: 0,
			hasMore: true
		};
	},

	onLoad(options) {
		this.loadNotices();
	},

	onReachBottom() {
		this.loadNotices();
	},

	methods: {
		async loadNotices() {
			if (this.loading || !this.hasMore) return;

			this.loading = true;

			try {
				const res = await api.getNotices(this.page, this.size);

				const list = res.data.list.map(item => ({
					...item,
					cover: item.cover ? API_BASE + item.cover : ''
				}));

				this.notices = [...this.notices, ...list];
				this.total = res.data.total;
				this.page = this.page + 1;
				this.hasMore = this.notices.length < res.data.total;
				this.loading = false;
			} catch (err) {
				this.loading = false;
				uni.showToast({
					title: err.msg || '加载失败',
					icon: 'none'
				});
			}
		},

		handleNoticeDetail(e) {
			const id = e.currentTarget.dataset.id;
			uni.navigateTo({
				url: '/pages/black-notice-info/index?id=' + id
			});
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
	font-size: 26rpx;
	color: #48416f;
	line-height: 1.5;
}

.notice-list {
	margin-top: 25rpx;
}

.notice-item {
	background: white;
	padding: 30rpx;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
	display: flex;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.notice-cover {
	width: 200rpx;
	height: 120rpx;
	border-radius: 16rpx;
	flex-shrink: 0;
}

.notice-info {
	display: flex;
	flex-direction: column;
	padding-left: 30rpx;
	flex: 1;
}

.notice-title {
	font-size: 28rpx;
	flex: 1;
	color: #48416f;
	font-weight: bold;
}

.top-badge {
	display: inline-block;
	background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
	color: white;
	font-size: 20rpx;
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	margin-right: 10rpx;
	font-weight: normal;
	vertical-align: middle;
}

.notice-date {
	text-align: right;
	color: #948eb3;
	font-size: 24rpx;
}

.empty {
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

.loading {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 30rpx 0;
}

.loading-text {
	color: #948eb3;
	font-size: 26rpx;
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
