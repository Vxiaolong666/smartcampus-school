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

					<!-- AI 智能总结（页面加载自动异步生成，命中历史缓存不重复消耗额度） -->
					<view class="ai-summary-card" v-if="aiLoading || summaryHtml || detailHtml">
						<view class="ai-summary-head">
							<view class="ai-summary-badge">AI 摘要</view>
							<text class="ai-summary-meta">{{ aiTime ? aiTime + ' 生成' : '' }}</text>
						</view>
						<view class="ai-summary-title">{{ notice.title }}</view>
						<view class="ai-summary-tabs">
							<text class="ai-summary-tab" :class="{ active: activeTab === 'summary' }" @click="switchTab('summary')">总结</text>
							<text v-if="detailHtml" class="ai-summary-tab" :class="{ active: activeTab === 'detail' }" @click="switchTab('detail')">要点</text>
						</view>
						<view class="ai-summary-body">
							<view v-if="aiLoading" class="ai-summary-loading">AI 正在总结本文内容…</view>
							<rich-text v-else-if="activeTab === 'summary'" :nodes="summaryHtml" class="ai-summary-rich"></rich-text>
							<rich-text v-else :nodes="detailHtml" class="ai-summary-rich"></rich-text>
						</view>
						<view class="ai-summary-foot">本卡片由 AI 阅读通知后生成，可能存在疏漏，请以通知正文为准。</view>
					</view>

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
import markdownToHtml from '../../utils/markdown.js';

export default {
	data() {
		return {
			notice: null,
			loading: true,
			aiLoading: false,
			aiTime: '',
			activeTab: 'summary',
			summaryHtml: '',
			detailHtml: ''
		};
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

		// 自动异步拉取 AI 对本文的总结，命中历史缓存则标记
		async loadAiSummary(id) {
			if (!id) return;
			this.aiLoading = true;
			try {
				const res = await api.getNoticeAiSummary(id);
				const now = new Date();
				const p = (n) => (n < 10 ? '0' : '') + n;
				this.aiTime = now.getFullYear() + '-' + p(now.getMonth() + 1) + '-' + p(now.getDate())
					+ ' ' + p(now.getHours()) + ':' + p(now.getMinutes());
				const parts = this.parseAiSummary(res.data.content);
				this.summaryHtml = markdownToHtml(parts.summary || res.data.content);
				this.detailHtml = parts.detail ? markdownToHtml(parts.detail) : '';
				this.activeTab = 'summary';
			} catch (e) {
				// 总结失败不影响正文浏览
			} finally {
				this.aiLoading = false;
			}
		},

		// 总结 / 要点 页签切换
		switchTab(tab) {
			this.activeTab = tab;
		},

		// 将 AI 返回内容拆分为「总结」（第一段概括）与「要点」（其余列表/说明），返回 Markdown 文本
		parseAiSummary(text) {
			const lines = (text || '').split(/\r?\n/);
			const summary = [];
			const detail = [];
			let inDetail = false;
			let started = false;
			for (let raw of lines) {
				const line = raw.trim();
				if (!inDetail) {
					if (!line) {
						if (started) inDetail = true;
						continue;
					}
					const isItem = /^([-*•]|\d+[.、)])\s*/.test(line);
					const isHeader = /^\*\*[^*]+\*\*:?$/.test(line) || /^【[^】]+】$/.test(line);
					if (isItem || isHeader) {
						inDetail = true;
						detail.push(line);
						continue;
					}
					summary.push(line);
					started = true;
				} else if (line) {
					detail.push(line);
				}
			}
			return { summary: summary.join('\n'), detail: detail.join('\n') };
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

/* ===== AI 智能总结卡片（内嵌于公告卡片，浅色主题渐变，呼应 #13c2c2→#1890ff 主题） ===== */
.ai-summary-card {
	background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 55%, #e6fffb 100%);
	border: 1rpx solid rgba(19, 194, 194, .18);
	border-radius: 20rpx;
	padding: 24rpx 28rpx;
	margin: 0 30rpx 30rpx 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(24, 144, 255, .08);
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

.ai-summary-tabs {
	display: inline-flex;
	align-items: center;
	gap: 10rpx;
	background: rgba(255, 255, 255, .6);
	border: 1rpx solid rgba(19, 194, 194, .15);
	padding: 8rpx;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
}

.ai-summary-tab {
	font-size: 26rpx;
	font-weight: 600;
	color: #5b6b7b;
	padding: 12rpx 34rpx;
	border-radius: 16rpx;
	transition: color .2s ease, background .2s ease, box-shadow .2s ease;
}

.ai-summary-tab.active {
	color: #fff;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	box-shadow: 0 4rpx 12rpx rgba(19, 194, 194, .35);
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
