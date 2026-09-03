<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">通知详情</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" :src="(notice && notice.cover) || '/static/assets/wxmp-banner.jpg'" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="loading" class="loading-section">
				<view class="loading-spinner"></view>
				<text class="loading-text">加载中...</text>
			</view>

			<view v-else-if="notice" class="notice-detail">
				<view class="notice-card">
					<image v-if="notice.cover" class="notice-cover" :src="notice.cover" mode="widthFix" @click="onPreviewCover"></image>
					<view class="notice-title">
						<text v-if="notice.is_top == 1" class="top-badge">置顶</text>
						{{ notice.title }}
					</view>
					<view class="notice-meta">
						<text v-if="notice.author">作者：{{ notice.author }}</text>
						<text>浏览：{{ notice.view_count }}</text>
						<text>{{ notice.create_time }}</text>
					</view>
					<view class="divider"></view>
					<view class="notice-content">{{ notice.content }}</view>
					<image v-if="notice.image" class="notice-image" :src="notice.image" mode="widthFix" @click="onPreviewImage"></image>
				</view>
			</view>

			<view v-else class="empty-section">
				<image class="empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
				<text class="empty-text">通知不存在</text>
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
			notice: null,
			loading: true
		};
	},

	async onLoad(options) {
		try {
			const res = await api.getNoticeDetail(options.id);

			this.notice = {
				...res.data,
				cover: res.data.cover ? API_BASE + res.data.cover : '',
				image: res.data.image ? API_BASE + res.data.image : ''
			};
			this.loading = false;
		} catch (err) {
			this.loading = false;
			uni.showToast({
				title: err.msg || '加载失败',
				icon: 'none'
			});
		}
	},

	onShareAppMessage() {
		const notice = this.notice;
		return {
			title: (notice && notice.title) || '通知详情',
			path: `/pages/black-notice-info/index?id=${notice ? notice.id : ''}`,
			imageUrl: (notice && notice.cover) || ''
		};
	},

	methods: {
		onPreviewCover() {
			if (this.notice && this.notice.cover) {
				uni.previewImage({
					current: this.notice.cover,
					urls: [this.notice.cover]
				});
			}
		},

		onPreviewImage() {
			if (this.notice && this.notice.image) {
				uni.previewImage({
					current: this.notice.image,
					urls: [this.notice.image]
				});
			}
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
	background-image: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.3), transparent);
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

.notice-detail {
	margin-top: 25rpx;
}

.notice-card {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.notice-cover {
	width: 100%;
}

.notice-title {
	padding: 30rpx;
	font-weight: bold;
	font-size: 36rpx;
	color: #48416f;
	line-height: 1.5;
	word-wrap: break-word;
	word-break: break-all;
	white-space: normal;
}

.top-badge {
	display: inline-block;
	background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
	color: white;
	font-size: 24rpx;
	padding: 6rpx 16rpx;
	border-radius: 8rpx;
	margin-right: 16rpx;
	font-weight: normal;
	vertical-align: middle;
}

.notice-meta {
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx;
	font-size: 24rpx;
	color: #948eb3;
	padding: 0 30rpx 30rpx 30rpx;
}

.notice-meta text {
	margin-right: 10rpx;
}

.divider {
	height: 1rpx;
	background: #f0f0f0;
	margin: 20rpx 30rpx;
}

.notice-content {
	padding: 0 30rpx 30rpx 30rpx;
	color: #48416f;
	font-size: 30rpx;
	text-indent: 2em;
	letter-spacing: 2rpx;
	line-height: 1.8;
	word-wrap: break-word;
	word-break: break-all;
	white-space: pre-wrap;
}

.notice-image {
	width: 100%;
	padding: 0 30rpx 30rpx 30rpx;
	box-sizing: border-box;
	border-radius: 12rpx;
}
</style>
