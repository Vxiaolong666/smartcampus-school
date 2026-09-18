<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">电费查询</view>
			<view slot="right" class="nav-action" @click="goToBatch">
				<text class="action-text">批量查询</text>
			</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view v-if="loading" class="loading-wrapper">
				<view class="loading-text">加载中...</view>
			</view>

			<block v-else-if="dormitories.length > 0">
				<view class="filter-card">
					<view class="filter-row">
						<view class="filter-item">
							<view class="filter-label">选择宿舍</view>
							<picker
								mode="selector"
								:range="dormitories"
								range-key="displayName"
								:value="selectedIndex"
								@change="onPickerChange"
							>
								<view class="filter-value">
									{{ selectedIndex >= 0 && dormitories[selectedIndex] ? dormitories[selectedIndex].displayName : '请选择宿舍' }}
								</view>
							</picker>
						</view>
					</view>
					<view class="filter-actions">
						<button class="query-btn" @click="onQuery" :loading="queryLoading">
							<image class="btn-icon" src="/static/assets/wxmp-search-white.svg" mode="aspectFit"></image>
							<text>查询</text>
						</button>
						<button class="query-all-btn" @click="onQueryAll" :loading="queryLoading">
							<image class="btn-icon" src="/static/assets/wxmp-list-cyan.svg" mode="aspectFit"></image>
							<text>查询全部</text>
						</button>
					</view>
				</view>

				<view v-if="results.length > 0" class="results-wrapper">
					<view
						class="result-card"
						:class="resultCardClass(item.status)"
						v-for="item in results"
						:key="item.id"
					>
						<view class="result-header">
							<view class="result-name">{{ item.displayName }}</view>
							<view class="result-status">{{ item.statusText }}</view>
						</view>
						<view class="result-body">
							<view class="balance-row">
								<view class="balance-item">
									<view class="balance-value">{{ item.balance !== null ? item.balance : '--' }}</view>
									<view class="balance-unit">元</view>
								</view>
								<view class="balance-divider"></view>
								<view class="balance-item">
									<view class="balance-value primary">{{ item.remainingKwh !== null ? item.remainingKwh : '--' }}</view>
									<view class="balance-unit">度</view>
								</view>
							</view>
						</view>
						<view class="estimated-card" v-if="item.estimatedDaysText !== '--'">
							<image class="estimated-icon" src="/static/assets/wxmp-calendar.svg" mode="aspectFit"></image>
							<view class="estimated-content">
								<view class="estimated-label">预计可用天数</view>
								<view class="estimated-number">{{ item.estimatedDaysText }}</view>
								<view class="estimated-label">天</view>
							</view>
						</view>
						<view class="time-range-card" v-if="item.timeRangeText">
							<image class="time-range-icon" src="/static/assets/wxmp-clock.svg" mode="aspectFit"></image>
							<view class="time-range-content">
								<view class="time-range-label">统计时段</view>
								<view class="time-range-value">{{ item.timeRangeText }}</view>
							</view>
						</view>
						<view class="result-footer">
							<view class="result-info">{{ item.school_area }} · {{ item.room_num }}</view>
						</view>
					</view>
				</view>

				<view v-else class="empty-result">
					<image class="empty-icon" src="/static/assets/wxmp-bolt.svg" mode="widthFix"></image>
					<view class="empty-text">选择宿舍后点击查询</view>
				</view>

				</block>

			<block v-else>
				<view class="empty-wrapper">
					<image class="empty-icon" src="/static/assets/wxmp-home2-gray.svg" mode="widthFix"></image>
					<view class="empty-title">暂无绑定的宿舍</view>
					<view class="empty-text">请先绑定宿舍后再查询电费</view>
					<button class="bind-btn" @click="goToBind">立即绑定</button>
				</view>
			</block>

			<view class="tips-card">
				<view class="tips-title">
					<image class="tips-icon" src="/static/assets/wxmp-lightbulb.svg" mode="widthFix"></image>
					<text>电费查询结果说明</text>
				</view>
				<view class="tips-list">
					<view class="tips-item">
						<view class="tips-dot normal"></view>
						<view class="tips-text">电量充足：余额大于等于30度</view>
					</view>
					<view class="tips-item">
						<view class="tips-dot warning"></view>
						<view class="tips-text">电量偏低：10度至30度之间</view>
					</view>
					<view class="tips-item">
						<view class="tips-dot low"></view>
						<view class="tips-text">电量不足：余额小于10度</view>
					</view>
					<view class="tips-item">
						<view class="tips-dot arrears"></view>
						<view class="tips-text">已欠费：余额小于等于0度</view>
					</view>
				</view>
			</view>

			<!-- AI 分析按钮：置于「电费查询结果说明」卡片下方 -->
			<view v-if="results.length > 0" class="ai-analyze-wrap">
				<view class="ai-analyze-btn" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
					<text v-if="!aiLoading">AI 智能分析</text>
					<text v-else>分析中…</text>
				</view>
			</view>
		</view>

		<view v-if="showAiResult" class="ai-mask" @click="closeAiResult" @touchmove.prevent>
			<view class="ai-modal" :style="{ height: sheetHeight + 'px' }" @click.stop>
				<view class="ai-modal-handle"
					@touchstart="onSheetDragStart"
					@touchmove="onSheetDragMove"
					@touchend="onSheetDragEnd"></view>
				<view class="ai-modal-header">
					<text class="ai-modal-title">AI 智能电费分析</text>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<scroll-view scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 右侧浮动 AI 分析按钮（圆形） -->
		<view class="ai-fab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
			<text class="ai-fab-icon">AI</text>
			<text class="ai-fab-label">分析</text>
		</view>
	</view>
</template>

<script>
/**
 * 电费查询页面
 * 查询宿舍电费余额和使用情况
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import { formatTimeRange } from '../../utils/common.js';
import markdownToHtml from '../../utils/markdown.js';
import { aiScrollLockMixin } from '../../utils/scroll-lock.js';

export default {
	// AI 结果浮层弹出时锁定背景页面滚动，避免页面与浮层同时滚动
	mixins: [aiScrollLockMixin],

	data() {
		return {
			loading: true,
			featureEnabled: true,
			announcement: null,
			dormitories: [],
			selectedIndex: -1,
			results: [],
			queryLoading: false,
			showAiResult: false,
			aiResult: '',
			aiLoading: false,
			aiCache: {},
			windowHeight: 0,
			sheetHeight: 0,
			dragStartY: 0,
			dragStartHeight: 0,
			dragging: false
		};
	},

	watch: {
		showAiResult(val) {
			if (val) {
				this.initSheetHeight();
			}
		}
	},

	computed: {
		aiResultHtml() {
			return markdownToHtml(this.aiResult);
		}
	},

	onLoad() {
		try {
			this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
		} catch (e) {
			this.windowHeight = 0;
		}
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => uni.navigateTo({ url: '/pages/login/index' }), 1000);
			return;
		}

		this.checkFeature().then(canProceed => {
			if (canProceed) {
				this.loadDormitories();
			}
		});
	},

	methods: {
		async checkFeature() {
			try {
				const result = await feature.checkFeature('electricity');
				if (!result.enabled) {
					this.featureEnabled = false;
					this.loading = false;
					this.announcement = null;
					feature.showDisabledAlert(result.featureName, result.announcement);
					return false;
				}

				if (result.announcement) {
					this.announcement = result.announcement;
				}
				return true;
			} catch (err) {
				console.error('检查功能开关失败', err);
				return true;
			}
		},

		async loadDormitories() {
			try {
				const res = await api.getDormitories();
				const dormitories = (res.data || []).map(item => ({
					...item,
					displayName:
						item.display_name ||
						(item.alias
							? `${item.alias}（${item.school_area}${item.building} ${item.room}）`
							: `${item.school_area}${item.building} ${item.room}`)
				}));

				if (dormitories.length > 0) {
					const defaultIndex = dormitories.findIndex(d => d.is_default == 1);
					const selectedIndex = defaultIndex >= 0 ? defaultIndex : 0;
					this.dormitories = dormitories;
					this.loading = false;
					this.selectedIndex = selectedIndex;
					this.onQuery();
				} else {
					this.dormitories = dormitories;
					this.loading = false;
				}
			} catch (err) {
				this.loading = false;
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			}
		},

		onPickerChange(e) {
			this.selectedIndex = e.detail.value;
		},

		buildResult(dorm, res) {
			return {
				...dorm,
				balance: res.data.balance,
				remainingKwh: res.data.remaining_kwh,
				pricePerKwh: res.data.price_per_kwh,
				estimatedDays: res.data.estimated_days,
				estimatedDaysText: res.data.estimated_days != null ? String(res.data.estimated_days) : '--',
				timeRangeText: formatTimeRange(res.data.time_range),
				status: res.data.status,
				statusText: res.data.status_text
			};
		},

		async onQuery() {
			const { dormitories, selectedIndex } = this;

			if (selectedIndex < 0 || !dormitories[selectedIndex]) {
				uni.showToast({ title: '请选择宿舍', icon: 'none' });
				return;
			}

			this.queryLoading = true;
			try {
				const res = await api.queryPowerFee(dormitories[selectedIndex].room_num);
				this.results = [this.buildResult(dormitories[selectedIndex], res)];
				this.queryLoading = false;
			} catch (err) {
				this.queryLoading = false;
				uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
			}
		},

		async onQueryAll() {
			const { dormitories } = this;

			if (dormitories.length === 0) {
				uni.showToast({ title: '暂无绑定的宿舍', icon: 'none' });
				return;
			}

			this.queryLoading = true;
			this.results = [];
			const results = [];

			for (const dorm of dormitories) {
				try {
					const res = await api.queryPowerFee(dorm.room_num);
					results.push(this.buildResult(dorm, res));
				} catch (err) {
					results.push({
						...dorm,
						balance: null,
						remainingKwh: null,
						estimatedDays: null,
						estimatedDaysText: '--',
						timeRangeText: '',
						status: 'error',
						statusText: err.msg || '查询失败'
					});
				}
			}

			this.results = results;
			this.queryLoading = false;
		},

		resultCardClass(status) {
			if (status === 'normal') return 'card-normal';
			if (status === 'warning') return 'card-warning';
			if (status === 'low') return 'card-low';
			if (status === 'arrears') return 'card-arrears';
			return 'card-error';
		},

		goToBind() {
			uni.navigateTo({ url: '/pages/dormitory-bind/index' });
		},

		goToBatch() {
			uni.navigateTo({ url: '/pages/power-fee-batch/index' });
		},

		async aiAnalyze() {
			if (this.aiLoading) {
			// 正在分析中允许再次点击：重新聚焦分析弹窗并提示，不重复发起请求
			this.aiResult = '### AI 正在分析…\n\n正在生成分析报告，请稍候（通常需要十几秒）。';
			this.showAiResult = true;
			uni.showToast({ title: 'AI 正在分析...', icon: 'none' });
			return;
		}
			if (this.results.length === 0) {
				uni.showToast({ title: '暂无电费数据', icon: 'none' });
				return;
			}
			const key = this.results
				.map(r => r.room_num + ':' + r.balance + ':' + r.remainingKwh)
				.join('|');
			if (this.aiCache[key]) {
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			this.aiLoading = true;
			// 立即给出反馈：AI 生成较慢，先弹出加载态
			this.aiResult = '### AI 正在分析…\n\n正在读取电费数据并生成分析报告，请稍候（通常需要十几秒）。';
			this.showAiResult = true;
			const rooms = this.results.map(r => ({
				name: r.displayName,
				balance: r.balance,
				remainingKwh: r.remainingKwh,
				pricePerKwh: r.pricePerKwh,
				estimatedDays: r.estimatedDays,
				statusText: r.statusText,
				timeRangeText: r.timeRangeText
			}));
			try {
				const res = await api.aiAnalyzePower({ type: 'query', rooms });
				const content = res.data && res.data.content ? String(res.data.content).trim() : '';
				if (!content) {
					this.aiResult = '### 暂无分析结果\n\nAI 未返回有效内容，请稍后重试。';
					this.showAiResult = true;
					return;
				}
				this.aiCache[key] = content;
				this.aiResult = content;
				this.showAiResult = true;
			} catch (err) {
				const msg = (err && err.msg) || '分析失败';
				this.aiResult = '### 分析失败\n\n' + msg + '\n\n请稍后重试，或联系管理员检查「AI 配置」是否正确、额度是否充足。';
				this.showAiResult = true;
			} finally {
				this.aiLoading = false;
			}
		},

		closeAiResult() {
			this.showAiResult = false;
		},

		initSheetHeight() {
			if (!this.windowHeight) {
				try {
					this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
				} catch (e) {
					this.windowHeight = 0;
				}
			}
			this.sheetHeight = Math.round(this.windowHeight * 0.8);
		},

		onSheetDragStart(e) {
			this.dragging = true;
			this.dragStartY = e.touches[0].clientY;
			this.dragStartHeight = this.sheetHeight;
		},

		onSheetDragMove(e) {
			if (!this.dragging) return;
			const deltaY = e.touches[0].clientY - this.dragStartY;
			let newH = this.dragStartHeight - deltaY;
			const maxH = this.windowHeight || 99999;
			const minH = Math.round((this.windowHeight || 0) * 0.25);
			if (newH > maxH) newH = maxH;
			if (newH < minH) newH = minH;
			this.sheetHeight = newH;
		},

		onSheetDragEnd() {
			this.dragging = false;
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

.nav-action {
	padding: 0 30rpx;
}

.action-text {
	font-size: 26rpx;
	color: white;
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

.loading-wrapper {
	display: flex;
	justify-content: center;
	padding: 100rpx 0;
}

.loading-text {
	font-size: 28rpx;
	color: #948eb3;
}

.filter-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-top: 25rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.filter-row {
	margin-bottom: 20rpx;
}

.filter-item {
	margin-bottom: 16rpx;
}

.filter-label {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 8rpx;
}

.filter-value {
	font-size: 28rpx;
	color: #48416f;
	background: #f7f8fc;
	padding: 20rpx;
	border-radius: 12rpx;
}

.filter-actions {
	display: flex;
	gap: 20rpx;
}

.query-btn {
	flex: 1;
	background: #13c2c2;
	color: white;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0;
	border-radius: 12rpx;
	margin: 0;
	display: flex;
	align-items: center;
	justify-content: center;
}

.query-all-btn {
	flex: 1;
	background: white;
	color: #13c2c2;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0;
	border-radius: 12rpx;
	margin: 0;
	border: 2rpx solid #13c2c2;
	display: flex;
	align-items: center;
	justify-content: center;
}

.btn-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 10rpx;
}

.results-wrapper {
	margin-bottom: 24rpx;
}

.result-card {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.result-header {
	padding: 20rpx 24rpx;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.card-normal .result-header {
	background: #52c41a;
}

.card-warning .result-header {
	background: #faad14;
}

.card-low .result-header {
	background: #ff4d4f;
}

.card-arrears .result-header {
	background: #722ed1;
}

.card-error .result-header {
	background: #8c8c8c;
}

.result-name {
	font-size: 28rpx;
	font-weight: bold;
	color: white;
}

.result-status {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.9);
}

.result-body {
	padding: 40rpx 24rpx;
	display: flex;
	align-items: baseline;
	justify-content: center;
}

.balance-row {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 40rpx;
}

.balance-item {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.balance-divider {
	width: 2rpx;
	height: 80rpx;
	background: #e8e8e8;
}

.balance-value {
	font-size: 56rpx;
	font-weight: bold;
	color: #48416f;
}

.balance-value.primary {
	color: #13c2c2;
}

.balance-value.info {
	color: #1890ff;
}

.balance-unit {
	font-size: 24rpx;
	color: #948eb3;
	margin-top: 8rpx;
}

.estimated-card {
	margin: 0 24rpx 24rpx 24rpx;
	padding: 20rpx 24rpx;
	background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 100%);
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.estimated-icon {
	width: 40rpx;
	height: 40rpx;
}

.estimated-content {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.estimated-label {
	font-size: 26rpx;
	color: #948eb3;
}

.estimated-number {
	font-size: 40rpx;
	font-weight: bold;
	color: #1890ff;
}

.time-range-card {
	margin: 0 24rpx 24rpx 24rpx;
	padding: 20rpx 24rpx;
	background: linear-gradient(135deg, #f6ffed 0%, #e6fffb 100%);
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.time-range-icon {
	width: 40rpx;
	height: 40rpx;
}

.time-range-content {
	display: flex;
	flex-direction: column;
	gap: 4rpx;
}

.time-range-label {
	font-size: 22rpx;
	color: #948eb3;
}

.time-range-value {
	font-size: 26rpx;
	color: #48416f;
	font-weight: 500;
}

.result-footer {
	padding: 16rpx 24rpx;
	background: #f7f8fc;
}

.result-info {
	font-size: 24rpx;
	color: #948eb3;
}

.empty-result {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0;
	background: white;
	border-radius: 20rpx;
	margin-bottom: 24rpx;
}

.empty-icon {
	width: 60rpx;
	height: 60rpx;
	margin-bottom: 16rpx;
}

.empty-text {
	font-size: 26rpx;
	color: #948eb3;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 0;
	background: white;
	border-radius: 20rpx;
	margin-bottom: 24rpx;
}

.empty-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.empty-text {
	font-size: 26rpx;
	color: #948eb3;
	margin-bottom: 24rpx;
}

.bind-btn {
	background: #13c2c2;
	color: white;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0 60rpx;
	border-radius: 12rpx;
	margin: 0;
	display: flex;
	align-items: center;
	justify-content: center;
}

.tips-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.tips-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	display: flex;
	align-items: center;
}

.tips-icon {
	width: 28rpx;
	height: 28rpx;
	margin-right: 10rpx;
}

.tips-list {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.tips-item {
	display: flex;
	align-items: center;
	width: calc(50% - 8rpx);
}

.tips-dot {
	width: 16rpx;
	height: 16rpx;
	border-radius: 50%;
	margin-right: 12rpx;
}

.tips-dot.normal {
	background: #52c41a;
}

.tips-dot.warning {
	background: #faad14;
}

.tips-dot.low {
	background: #ff4d4f;
}

.tips-dot.arrears {
	background: #722ed1;
}

.tips-text {
	font-size: 22rpx;
	color: #948eb3;
}

/* AI 分析按钮与浮动按钮样式已统一到 App.vue 全局（.ai-analyze-wrap / .ai-analyze-btn / .ai-fab） */

.ai-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.55);
	z-index: 999;
	display: flex;
	align-items: flex-end;
	justify-content: center;
	animation: aiMaskFade 0.25s ease;
}

@keyframes aiMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.ai-modal {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.18);
	animation: aiModalUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes aiModalUp {
	from { transform: translateY(100%); }
	to { transform: translateY(0); }
}

.ai-modal-handle {
	width: 72rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #d8d4e8;
	margin: 18rpx auto 2rpx;
	transition: background 0.2s ease;
}

.ai-modal-handle:active {
	background: #b9b2d6;
}

.ai-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 14rpx 44rpx 22rpx;
	border-bottom: 1rpx solid #f0f0f4;
}

.ai-modal-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #322c54;
}

.ai-modal-close {
	width: 56rpx;
	height: 56rpx;
	line-height: 56rpx;
	text-align: center;
	font-size: 30rpx;
	color: #948eb3;
	background: #f3f2f8;
	border-radius: 50%;
}

.ai-modal-body {
	flex: 1;
	min-height: 0;
}

.ai-modal-body-inner {
	padding: 30rpx 44rpx;
}

.ai-modal-content {
	display: block;
	font-size: 28rpx;
	color: #48416f;
	line-height: 1.75;
	word-break: break-word;
}
</style>
