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

			<cb-load-fail />
			<cb-loading v-if="loading" />

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
						@click="openDetail(item)"
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

			<cb-bind-tip v-else type="dorm" desc="绑定宿舍后可查询电费" />

			<!-- 结果说明：加载中 / 未绑定宿舍（遮罩）时不展示 -->
			<view class="tips-card" v-if="!loading && dormitories.length > 0">
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
			<view v-if="!loading && !queryLoading && results.length > 0" class="ai-analyze-wrap">
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
					<view class="ai-modal-title-wrap">
						<text class="ai-modal-badge">AI</text>
						<text class="ai-modal-title">智能电费分析</text>
					</view>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<!-- 正在分析：复用全站统一的圆环 loading，垂直居中 -->
				<view v-if="aiLoading" class="ai-modal-center-body">
					<cb-loading
						class="ai-modal-center-item"
						compact
						large
						text="AI 正在分析…"
						:sub-texts="['读取电费数据，约需十几秒', '正在核对余额与用量…', '正在撰写电费建议…', '马上就好，请稍候…']"
						:slow-after="16000"
						slow-text="分析时间有点久…"
						slow-sub-text="可稍候，或关闭后重新分析"
					/>
				</view>
				<view v-else-if="aiError" class="ai-modal-center-body">
					<!-- 分析失败：复用全站统一的网络异常样式，垂直居中 -->
					<view class="ai-error ai-modal-center-item">
						<image class="ai-error-icon" src="/static/assets/wxmp-network-error.svg" mode="widthFix"></image>
						<view class="ai-error-title">分析失败</view>
						<view class="ai-error-desc">{{ aiError }}</view>
						<view class="ai-error-btn" @click="aiAnalyze">重新分析</view>
					</view>
				</view>
				<scroll-view v-else scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<!-- 分析结果 -->
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
						<view class="ai-result-foot">内容由 AI 生成，仅供参考</view>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 右侧浮动 AI 分析按钮（圆形） -->
		<view v-if="!loading && results.length > 0" class="ai-fab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
			<text class="ai-fab-icon">AI</text>
			<text class="ai-fab-label">分析</text>
		</view>

		<!-- 电费详情自定义弹窗 -->
		<view class="detail-mask" v-if="showDetail" @click="closeDetail">
			<view class="detail-modal" @click.stop>
				<view class="detail-modal-header" :style="'background: linear-gradient(160deg, ' + detailTint.bg + ' 0%, #ffffff 100%);'">
					<view class="detail-title-wrap">
						<view class="detail-title" :style="'color: ' + detailTint.text + ';'">{{ detailItem.displayName }}</view>
						<view class="detail-sub">
							<text class="detail-sub-chip" :style="'background-color: ' + detailTint.text + ';'">{{ detailItem.statusText }}</text>
						</view>
					</view>
					<view class="detail-close" @click="closeDetail">✕</view>
				</view>

				<view class="detail-modal-body">
					<view class="detail-grid">
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.balance !== null ? detailItem.balance : '--' }}</view>
							<view class="detail-grid-label">余额（元）</view>
						</view>
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.remainingKwh !== null ? detailItem.remainingKwh : '--' }}</view>
							<view class="detail-grid-label">剩余电量（度）</view>
						</view>
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.estimatedDaysText }}</view>
							<view class="detail-grid-label">预计可用（天）</view>
						</view>
					</view>

					<view class="detail-row">
						<text class="detail-row-label">电价</text>
						<text class="detail-row-value">{{ detailItem.pricePerKwh != null ? detailItem.pricePerKwh + ' 元/度' : '-' }}</text>
					</view>
					<view class="detail-row">
						<text class="detail-row-label">统计时段</text>
						<text class="detail-row-value">{{ detailItem.timeRangeText || '-' }}</text>
					</view>
					<view class="detail-row">
						<text class="detail-row-label">房间位置</text>
						<text class="detail-row-value">{{ detailItem.school_area }}{{ detailItem.building }} {{ detailItem.room_num }}</text>
					</view>

					<button class="detail-btn" @click="closeDetail" :style="'background: linear-gradient(90deg, ' + detailTint.text + ', ' + detailTint.light + '); box-shadow: 0 10rpx 24rpx ' + detailTint.shadow + ';'">我知道了</button>
				</view>
			</view>
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
			aiError: '',
			aiLoading: false,
			aiCache: {},
			windowHeight: 0,
			sheetHeight: 0,
			dragStartY: 0,
			dragStartHeight: 0,
			dragging: false,
			// 电费详情自定义弹窗
			showDetail: false,
			detailItem: {},
			detailTint: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
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
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip();
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
			const index = e.detail.value;
			// 未更换宿舍时不重复查询
			if (Number(index) === Number(this.selectedIndex)) return;
			this.selectedIndex = index;
			// 切换宿舍后自动查询，无需再手动点「查询」
			this.onQuery();
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

		// 打开电费详情（自定义弹窗，与课程详情风格一致）
		openDetail(item) {
			if (!item) return;
			const tints = {
				normal: { text: '#52c41a', bg: '#f6ffed', light: '#95de64', shadow: 'rgba(82, 196, 26, 0.3)' },
				warning: { text: '#faad14', bg: '#fffbe6', light: '#ffd666', shadow: 'rgba(250, 173, 20, 0.3)' },
				low: { text: '#ff4d4f', bg: '#fff0f0', light: '#ff7875', shadow: 'rgba(255, 77, 79, 0.3)' },
				arrears: { text: '#722ed1', bg: '#f9f0ff', light: '#b37feb', shadow: 'rgba(114, 46, 209, 0.3)' },
				error: { text: '#8c8c8c', bg: '#f5f5f5', light: '#bfbfbf', shadow: 'rgba(140, 140, 140, 0.3)' }
			};
			this.detailTint = tints[item.status] || tints.normal;
			this.detailItem = item;
			this.showDetail = true;
		},

		// 关闭电费详情
		closeDetail() {
			this.showDetail = false;
			this.detailItem = {};
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
			// 页面内已缓存，直接展示，不重新生成、不发请求
			if (this.aiCache[key]) {
				this.aiError = '';
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			// 先算好浮层高度再展示，避免首帧 sheetHeight 为 0 的遮罩空窗
			this.initSheetHeight();
			this.aiLoading = true;
			this.aiError = '';
			this.aiResult = '';
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
					this.aiError = 'AI 未返回有效内容，请稍后重试';
					return;
				}
				this.aiCache[key] = content;
				this.aiError = '';
				this.aiResult = content;
				this.showAiResult = true;
			} catch (err) {
				this.aiError = (err && err.msg) || '网络异常，请检查网络后重试';
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

/* ==================== 电费详情自定义弹窗 ==================== */

.result-card:active {
	opacity: 0.92;
}

.detail-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(50, 44, 84, 0.45);
	backdrop-filter: blur(4px);
	-webkit-backdrop-filter: blur(4px);
	z-index: 998;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 60rpx;
	animation: detailMaskFade 0.25s ease;
}

@keyframes detailMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.detail-modal {
	width: 100%;
	max-width: 620rpx;
	background: white;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 24rpx 80rpx rgba(50, 44, 84, 0.25);
	animation: detailModalUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes detailModalUp {
	from { transform: translateY(80rpx) scale(0.96); opacity: 0; }
	to { transform: translateY(0) scale(1); opacity: 1; }
}

.detail-modal-header {
	display: flex;
	align-items: flex-start;
	padding: 36rpx 32rpx 28rpx;
}

.detail-title-wrap {
	flex: 1;
	min-width: 0;
}

.detail-title {
	font-size: 36rpx;
	font-weight: bold;
	line-height: 1.35;
	word-break: break-all;
	letter-spacing: 1rpx;
}

.detail-sub {
	margin-top: 16rpx;
}

.detail-sub-chip {
	display: inline-block;
	font-size: 22rpx;
	color: white;
	padding: 8rpx 22rpx;
	border-radius: 999rpx;
	letter-spacing: 1rpx;
}

.detail-close {
	width: 52rpx;
	height: 52rpx;
	line-height: 50rpx;
	text-align: center;
	font-size: 26rpx;
	color: #948eb3;
	background: rgba(255, 255, 255, 0.85);
	border-radius: 50%;
	flex-shrink: 0;
	margin-left: 16rpx;
	box-shadow: 0 4rpx 12rpx rgba(50, 44, 84, 0.08);
}

.detail-close:active {
	background: #f0eef6;
	transform: scale(0.92);
}

.detail-modal-body {
	padding: 8rpx 36rpx 40rpx;
}

.detail-grid {
	display: flex;
	gap: 20rpx;
	margin-top: 12rpx;
	margin-bottom: 24rpx;
}

.detail-grid-item {
	flex: 1;
	background: #f7f8fc;
	border-radius: 20rpx;
	padding: 24rpx 12rpx;
	text-align: center;
}

.detail-grid-num {
	font-size: 30rpx;
	font-weight: bold;
	color: #13c2c2;
	word-break: break-all;
}

.detail-grid-label {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 8rpx;
}

.detail-row {
	display: flex;
	align-items: center;
	padding: 22rpx 0;
	border-top: 1rpx solid #f2f3f8;
}

.detail-row:first-of-type {
	margin-top: 20rpx;
}

.detail-row-label {
	font-size: 26rpx;
	color: #948eb3;
	width: 160rpx;
	flex-shrink: 0;
}

.detail-row-value {
	flex: 1;
	font-size: 27rpx;
	color: #48416f;
	font-weight: 500;
	text-align: right;
	word-break: break-all;
	line-height: 1.5;
}

.detail-btn {
	margin-top: 36rpx;
	height: 84rpx;
	line-height: 84rpx;
	border-radius: 42rpx;
	color: white;
	font-size: 30rpx;
	font-weight: bold;
	letter-spacing: 4rpx;
	padding: 0;
}

.detail-btn:active {
	opacity: 0.85;
	transform: scale(0.98);
}

.detail-btn::after {
	border: none;
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
	flex-shrink: 0;
	padding: 16rpx 44rpx 22rpx;
	border-bottom: 1rpx solid #f0f0f4;
	background: linear-gradient(180deg, #f7fbfd 0%, #ffffff 100%);
}

/* 标题区：AI 徽标 + 标题 */
.ai-modal-title-wrap {
	display: flex;
	align-items: center;
}

.ai-modal-badge {
	margin-right: 14rpx;
	padding: 4rpx 14rpx;
	font-size: 22rpx;
	font-weight: bold;
	line-height: 1.4;
	color: #ffffff;
	letter-spacing: 1rpx;
	border-radius: 10rpx;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	box-shadow: 0 4rpx 12rpx rgba(19, 194, 194, 0.28);
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

/* 加载态：空间充足时垂直居中；被拖小后自动贴顶并可滚动，避免内容溢出压住标题 */
.ai-modal-center-body {
	flex: 1;
	min-height: 0;
	display: flex;
	padding: 20rpx 44rpx;
	overflow-y: auto;
	box-sizing: border-box;
}

/* margin: auto 实现「安全居中」：放得下就居中，放不下就顶部对齐 */
.ai-modal-center-item {
	margin: auto;
	flex-shrink: 0;
	width: 100%;
}

/* 失败态：去掉为顶部对齐准备的上下留白，保证在居中容器里视觉居中 */
.ai-modal-center-item.ai-error {
	padding: 0 20rpx;
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

/* 分析失败：与全站 cb-load-fail 视觉一致 */
.ai-error {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 20rpx 20rpx;
	text-align: center;
}

.ai-error-icon {
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 24rpx;
	opacity: 0.85;
}

.ai-error-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.ai-error-desc {
	font-size: 26rpx;
	color: #948eb3;
	line-height: 1.6;
}

.ai-error-btn {
	margin-top: 32rpx;
	height: 72rpx;
	line-height: 72rpx;
	padding: 0 60rpx;
	font-size: 28rpx;
	font-weight: bold;
	color: #ffffff;
	border-radius: 36rpx;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.28);
}

.ai-error-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

/* 结果页脚注 */
.ai-result-foot {
	margin-top: 28rpx;
	padding-top: 20rpx;
	border-top: 1rpx dashed #eceaf5;
	text-align: center;
	font-size: 22rpx;
	color: #b0abc7;
	letter-spacing: 0.5rpx;
}
</style>
