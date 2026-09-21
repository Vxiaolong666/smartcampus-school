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

					<!-- AI 智能总结（页面加载自动异步生成，命中历史缓存不重复消耗额度；完成后才展示卡片） -->
					<view class="ai-summary-card" v-if="summaryHtml">
						<view class="ai-summary-head">
							<view class="ai-summary-badge">
							<image class="ai-summary-badge-icon" src="/static/assets/wxmp-ai-robot.svg" mode="aspectFit"></image>
							AI 摘要
						</view>
						</view>
						<view class="ai-summary-body">
							<rich-text v-if="summaryHtml" :nodes="summaryHtml" class="ai-summary-rich"></rich-text>
						</view>
						<view class="ai-summary-foot">本卡片由 AI 阅读通知后生成，可能存在疏漏，请以通知正文为准。</view>
					</view>

					<view v-if="summaryHtml" class="ai-summary-divider"></view>

					<view v-if="contentBlocks.length" class="notice-content">
						<block v-for="(block, idx) in contentBlocks" :key="idx">
							<rich-text v-if="block.type === 'html'" :nodes="block.html"></rich-text>
							<image v-else class="notice-content-img" :src="block.src" mode="widthFix" @click="onPreviewContentImage(block.src)"></image>
						</block>
					</view>
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
import markdownToHtml, { markdownToBlocks } from '../../utils/markdown.js';

export default {
	data() {
		return {
			notice: null,
			loading: true,
			aiLoading: false,
			summaryHtml: ''
		};
	},

	computed: {
		// 公告正文：拆成「文本块 + 图片块」。文本块走 rich-text（markdown 解析各端一致），
		// 图片块走原生 <image mode="widthFix">，避免 rich-text 忽略 img 内联样式 / 真机图片不显示
		contentBlocks() {
			return this.notice ? markdownToBlocks(this.notice.content || '', { resolveUrl: api.getFileUrl }) : [];
		}
	},

	async onLoad(options) {
		try {
			const res = await api.getNoticeDetail(options.id);

			this.notice = {
				...res.data,
				cover: api.getFileUrl(res.data.cover),
				image: api.getFileUrl(res.data.image)
			};
			this.loading = false;
			// 通知加载完成后自动异步获取 AI 总结（命中历史缓存不重复消耗额度）
			this.loadAiSummary(options.id);
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
		},

		// 预览正文中的图片（原生 image 渲染，点击可放大）
		onPreviewContentImage(src) {
			if (src) {
				uni.previewImage({
					current: src,
					urls: [src]
				});
			}
		},

		// 自动异步拉取 AI 对本文的总结，命中历史缓存则标记
		async loadAiSummary(id) {
			if (!id) return;
			this.aiLoading = true;
			try {
				const res = await api.getNoticeAiSummary(id);
				this.summaryHtml = markdownToHtml(res.data.content);
			} catch (e) {
				// 总结失败不影响正文浏览
			} finally {
				this.aiLoading = false;
			}
		},

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
	padding-bottom: 30rpx;
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
	display: block;
	box-sizing: border-box;
	padding: 0 30rpx 30rpx 30rpx;
	color: #48416f;
	font-size: 30rpx;
	letter-spacing: 1rpx;
	line-height: 1.8;
	word-wrap: break-word;
	word-break: break-all;
}

/* 正文图片：原生 image mode="widthFix" 渲染，宽度自适应、高度自动，各端稳定显示 */
.notice-content-img {
	display: block;
	width: 100%;
	margin: 24rpx 0;
	border-radius: 12rpx;
}

.notice-image {
	width: 100%;
	padding: 0 30rpx 30rpx 30rpx;
	box-sizing: border-box;
	border-radius: 12rpx;
}

/* ===== AI 智能总结卡片（内嵌于公告卡片，浅色主题渐变，呼应 #13c2c2→#1890ff 主题） ===== */
.ai-summary-card {
	background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 55%, #e6fffb 100%);
	border: 1rpx solid rgba(19, 194, 194, .18);
	border-radius: 20rpx;
	padding: 24rpx 28rpx;
	margin: 0 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(24, 144, 255, .08);
}

/* AI 总结与公告正文之间的分隔线（灰色，与上方 divider 一致；仅在有 AI 总结时展示） */
.ai-summary-divider {
	height: 1rpx;
	margin: 28rpx 30rpx 24rpx;
	background: #f0f0f0;
}

.ai-summary-head {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-bottom: 16rpx;
}

.ai-summary-badge {
	display: inline-flex;
	align-items: center;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	color: #fff;
	font-size: 22rpx;
	font-weight: 600;
	padding: 6rpx 20rpx;
	border-radius: 999rpx;
	box-shadow: 0 4rpx 12rpx rgba(19, 194, 194, .35);
}

.ai-summary-badge-icon {
	width: 24rpx;
	height: 24rpx;
	margin-right: 8rpx;
}

.ai-summary-meta {
	font-size: 22rpx;
	color: #8a94a6;
}

.ai-summary-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #2a303b;
	line-height: 1.4;
	margin-bottom: 14rpx;
}

.ai-summary-body {
	font-size: 26rpx;
	line-height: 1.8;
	color: #3c4554;
}

.ai-summary-rich {
	font-size: 26rpx;
	line-height: 1.8;
	color: #3c4554;
	word-wrap: break-word;
	word-break: break-all;
}

.ai-summary-loading {
	text-align: center;
	color: #8a94a6;
	padding: 20rpx 0;
	font-size: 26rpx;
}

.ai-summary-foot {
	margin-top: 16rpx;
	padding-top: 14rpx;
	border-top: 1rpx dashed rgba(19, 194, 194, .25);
	font-size: 22rpx;
	color: #8a94a6;
}
</style>
