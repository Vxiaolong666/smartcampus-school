<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">用电统计</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view v-if="loading" class="loading-section">
				<view class="loading-spinner"></view>
				<text class="loading-text">加载中...</text>
			</view>

			<view v-else-if="dormitories.length === 0" class="empty-section">
				<image class="empty-icon" src="/static/assets/wxmp-home2-gray.svg" mode="widthFix"></image>
				<view class="empty-title">请先绑定宿舍</view>
				<view class="empty-text">绑定宿舍后可查看用电统计</view>
			</view>

			<block v-else>
				<view class="filter-section">
					<view class="filter-item">
						<text class="filter-label">选择宿舍</text>
						<picker
							mode="selector"
							:range="dormitories"
							range-key="displayName"
							:value="selectedIndex"
							@change="onDormitoryChange"
						>
							<view class="picker-value">
								<image class="picker-icon" src="/static/assets/wxmp-home2.svg" mode="widthFix"></image>
								<text class="picker-text">{{ dormitories[selectedIndex] ? dormitories[selectedIndex].displayName : '' }}</text>
								<text class="picker-arrow">▼</text>
							</view>
						</picker>
					</view>
				</view>

				<view class="time-filter">
				<view class="time-filter-btns">
					<view class="time-btn" :class="{ active: timeRange === 'all' }" @click="setTimeRange('all')">全部</view>
					<view class="time-btn" :class="{ active: timeRange === '7' }" @click="setTimeRange('7')">近7天</view>
					<view class="time-btn" :class="{ active: timeRange === '30' }" @click="setTimeRange('30')">近30天</view>
					<view class="time-btn" :class="{ active: timeRange === 'custom' }" @click="setTimeRange('custom')">自定义</view>
				</view>
				<view v-if="timeRange === 'custom'" class="time-custom">
					<picker mode="date" class="custom-picker" :value="customStart" @change="onCustomStart">
						<view>{{ customStart || '开始日期' }}</view>
					</picker>
					<text class="custom-sep">至</text>
					<picker mode="date" class="custom-picker" :value="customEnd" @change="onCustomEnd">
						<view>{{ customEnd || '结束日期' }}</view>
					</picker>
				</view>
			</view>

			<view v-if="stats" class="stats-section">
					<view class="stats-header">
						<image class="stats-header-icon" src="/static/assets/wxmp-chart.svg" mode="widthFix"></image>
						<text class="stats-header-title">用电概览</text>
					</view>

					<view class="stats-cards">
						<view class="stat-card" :class="'health-' + stats.healthStatus">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" :src="healthIcon" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ stats.healthText }}</view>
									<view class="stat-label">用电健康度</view>
								</view>
							</view>
						</view>

						<view class="stat-card info">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" src="/static/assets/wxmp-bolt-cyan.svg" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ stats.yesterdayUsage }}<text class="stat-unit">度</text></view>
									<view class="stat-label">昨日用电量</view>
									<view class="stat-sub">约 ¥{{ stats.yesterdayCost }}</view>
								</view>
							</view>
						</view>

						<view class="stat-card primary">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" src="/static/assets/wxmp-trend-blue.svg" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ stats.dailyAvgUsage }}<text class="stat-unit">度</text></view>
									<view class="stat-label">日均用电量</view>
									<view class="stat-sub">约 ¥{{ stats.dailyAvgCost }}/天</view>
								</view>
							</view>
						</view>

						<view class="stat-card success">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" src="/static/assets/wxmp-wallet-green.svg" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ stats.latestBalance || '-' }}<text class="stat-unit">元</text></view>
									<view class="stat-label">当前余额</view>
								</view>
							</view>
						</view>

						<view class="stat-card estimated" v-if="stats.estimatedDays !== null && stats.estimatedDays !== undefined">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" src="/static/assets/wxmp-calendar.svg" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ stats.estimatedDays }}<text class="stat-unit">天</text></view>
									<view class="stat-label">预计可用</view>
								</view>
							</view>
						</view>
					</view>
				</view>

				<button class="ai-btn" @click="aiAnalyze" :loading="aiLoading">AI 智能分析</button>

				<view class="chart-section">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-activity.svg" mode="widthFix"></image>
						<text class="section-title">用电量趋势</text>
					</view>
					<view v-if="recentTrend.length === 0" class="no-data">
						<image class="no-data-icon" src="/static/assets/wxmp-coffee.svg" mode="widthFix"></image>
						<text>暂无数据</text>
					</view>
					<!-- #ifdef MP-WEIXIN -->
					<canvas
						v-else
						type="2d"
						class="chart-canvas"
						id="recentChart"
						:style="{ width: '100%', height: '200px' }"
					></canvas>
					<!-- #endif -->
					<!-- #ifndef MP-WEIXIN -->
					<canvas
						v-else
						class="chart-canvas"
						id="recentChart"
						canvas-id="recentChart"
						:style="{ width: '100%', height: '200px' }"
					></canvas>
					<!-- #endif -->
					<view class="chart-footer">
						<view class="chart-footer-item">
							<text class="chart-footer-label">数据点</text>
							<text class="chart-footer-value">{{ recentTrend.length }}</text>
						</view>
						<view class="chart-footer-item">
							<text class="chart-footer-label">电价</text>
							<text class="chart-footer-value">{{ pricePerKwh }}元/度</text>
						</view>
					</view>
				</view>

				<view class="chart-section">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-calendar.svg" mode="widthFix"></image>
						<text class="section-title">每日用电总量趋势</text>
					</view>
					<view v-if="dailyTrend.length === 0" class="no-data">
						<image class="no-data-icon" src="/static/assets/wxmp-coffee.svg" mode="widthFix"></image>
						<text>暂无数据</text>
					</view>
					<!-- #ifdef MP-WEIXIN -->
					<canvas
						v-else
						type="2d"
						class="chart-canvas"
						id="dailyChart"
						:style="{ width: '100%', height: '200px' }"
					></canvas>
					<!-- #endif -->
					<!-- #ifndef MP-WEIXIN -->
					<canvas
						v-else
						class="chart-canvas"
						id="dailyChart"
						canvas-id="dailyChart"
						:style="{ width: '100%', height: '200px' }"
					></canvas>
					<!-- #endif -->
					<view class="chart-footer">
						<view class="chart-footer-item">
							<text class="chart-footer-label">统计天数</text>
							<text class="chart-footer-value">{{ dailyTrend.length }}天</text>
						</view>
					</view>
				</view>

				<view class="records-section">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-list.svg" mode="widthFix"></image>
						<text class="section-title">历史电费记录</text>
						<text class="section-count">共{{ stats ? stats.recordCount : 0 }}条</text>
					</view>
					<view v-if="records.length === 0" class="no-data">
						<image class="no-data-icon" src="/static/assets/wxmp-coffee.svg" mode="widthFix"></image>
						<text>暂无记录</text>
					</view>
					<view v-else class="records-timeline">
						<view v-for="(item, index) in records" :key="item.id" class="record-item">
							<view class="record-timeline-left">
								<view class="record-dot" :class="item.usageClass"></view>
								<view class="record-line" v-if="index < records.length - 1"></view>
							</view>
							<view class="record-content">
								<view class="record-header">
									<text class="record-time">{{ item.record_time || item.created_at }}</text>
								</view>
								<view class="record-data">
									<view class="record-main">
										<view class="record-balance">
											<text class="record-balance-label">余额</text>
											<text class="record-balance-value">¥{{ item.balance }}</text>
										</view>
									</view>
									<view class="record-details" v-if="item.usageDisplay !== '-'">
										<view class="record-detail-item">
											<text class="record-detail-label">用电</text>
											<text class="record-detail-value" :class="item.usageClass">{{ item.usageDisplay }}度</text>
										</view>
										<view class="record-detail-item">
											<text class="record-detail-label">费用</text>
											<text class="record-detail-value" :class="item.costClass">{{ item.costDisplay }}元</text>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
										<view class="records-footer">用电量根据余额变化和电价({{ pricePerKwh }}元/度)自动计算</view>
				</view>
			</block>
		</view>

		<view v-if="showAiResult" class="ai-mask" @click="closeAiResult" @touchmove.prevent>
			<view class="ai-modal" :style="{ height: sheetHeight + 'px' }" @click.stop>
				<view class="ai-modal-handle"
					@touchstart="onSheetDragStart"
					@touchmove="onSheetDragMove"
					@touchend="onSheetDragEnd"></view>
				<view class="ai-modal-header">
					<text class="ai-modal-title">AI 智能用电分析</text>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<scroll-view scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
					</view>
				</scroll-view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 用电统计页面
 * 展示宿舍用电统计和趋势图表
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import markdownToHtml from '../../utils/markdown.js';
import { aiScrollLockMixin } from '../../utils/scroll-lock.js';

export default {
	// AI 结果浮层弹出时锁定背景页面滚动，避免页面与浮层同时滚动
	mixins: [aiScrollLockMixin],

	data() {
		return {
			featureEnabled: true,
			announcement: null,
			dormitories: [],
			selectedIndex: 0,
			stats: null,
			recentTrend: [],
			dailyTrend: [],
			records: [],
			pricePerKwh: 0.61,
			loading: true,
			// 时间筛选：all=全部, 7=近7天, 30=近30天, custom=自定义
			timeRange: 'all',
			customStart: '',
			customEnd: '',
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
			},
			healthIcon() {
			const s = this.stats && this.stats.healthStatus;
			if (s === 'saving') return '/static/assets/wxmp-heart-green.svg';
			if (s === 'normal') return '/static/assets/wxmp-heart-yellow.svg';
			return '/static/assets/wxmp-heart-red.svg';
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
				const result = await feature.checkFeature('power_statistics');
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
							? item.alias + '（' + item.school_area + item.building + ' ' + item.room + '）'
							: item.school_area + item.building + ' ' + item.room)
				}));

				if (dormitories && dormitories.length > 0) {
					this.dormitories = dormitories;
					this.loading = false;
					this.loadStatistics();
				} else {
					this.loading = false;
					this.dormitories = [];
				}
			} catch (err) {
				this.loading = false;
				uni.showToast({ title: err.msg || '获取宿舍列表失败', icon: 'none' });
			}
		},

		onDormitoryChange(e) {
			this.selectedIndex = e.detail.value;
			this.loadStatistics();
		},

		setTimeRange(range) {
			this.timeRange = range;
			this.loadStatistics();
		},

		onCustomStart(e) {
			this.customStart = e.detail.value;
			this.loadStatistics();
		},

		onCustomEnd(e) {
			this.customEnd = e.detail.value;
			this.loadStatistics();
		},

		buildQuery(roomNum) {
			const params = { room_num: roomNum };
			const range = this.timeRange;
			if (range === '7' || range === '30') {
				const days = range === '7' ? 6 : 29;
				const end = this.formatDate(new Date());
				const start = this.formatDate(new Date(Date.now() - days * 86400000));
				params.start = start;
				params.end = end;
			} else if (range === 'custom') {
				if (this.customStart) params.start = this.customStart;
				if (this.customEnd) params.end = this.customEnd;
			}
			return params;
		},

		formatDate(d) {
			const y = d.getFullYear();
			const m = String(d.getMonth() + 1).padStart(2, '0');
			const day = String(d.getDate()).padStart(2, '0');
			return y + '-' + m + '-' + day;
		},

		async loadStatistics() {
			const { dormitories, selectedIndex } = this;

			if (!dormitories[selectedIndex]) return;

			const dorm = dormitories[selectedIndex];

			this.loading = true;

			try {
				const res = await api.request({
					act: 'get_power_statistics',
					method: 'GET',
					data: this.buildQuery(dorm.room_num)
				});

				const data = res.data;

				const parseRecordTime = v => {
					if (v === null || v === undefined || v === '') return 0;
					if (typeof v === 'number') {
						return v < 1e12 ? v * 1000 : v;
					}
					if (typeof v === 'string') {
						if (/^\d+$/.test(v)) {
							const n = parseInt(v, 10);
							return n < 1e12 ? n * 1000 : n;
						}
						return new Date(v.replace(/-/g, '/')).getTime();
					}
					return 0;
				};

				const processedRecords = (data.records || [])
					.map(r => {
						const usageKwh = parseFloat(r.usage_kwh) || 0;
						const usageCost = parseFloat(r.usage_cost) || 0;

						let usageDisplay = '-';
						let usageClass = '';
						if (usageKwh > 0) {
							usageDisplay = '↓' + usageKwh.toFixed(4);
							usageClass = 'usage-down';
						} else if (usageKwh < 0) {
							usageDisplay = '↑' + Math.abs(usageKwh).toFixed(4);
							usageClass = 'usage-up';
						}

						let costDisplay = '-';
						let costClass = '';
						if (usageCost > 0) {
							costDisplay = '↓' + usageCost.toFixed(2);
							costClass = 'usage-down';
						} else if (usageCost < 0) {
							costDisplay = '↑' + Math.abs(usageCost).toFixed(2);
							costClass = 'usage-up';
						}

						return {
							...r,
							usageDisplay,
							usageClass,
							costDisplay,
							costClass
						};
					})
					.sort((a, b) => {
						const ta = parseRecordTime(a.record_time || a.created_at);
						const tb = parseRecordTime(b.record_time || b.created_at);
						return tb - ta;
					});

				this.stats = {
					healthStatus: data.health_status,
					healthText: data.health_text,
					yesterdayUsage: data.yesterday_usage,
					yesterdayCost: data.yesterday_cost,
					dailyAvgUsage: data.daily_avg_usage,
					dailyAvgCost: data.daily_avg_cost,
					latestBalance: data.latest_balance,
					recordCount: data.record_count,
					thresholds: data.thresholds,
					estimatedDays: data.estimated_days
				};
				this.recentTrend = data.recent_trend || [];
				this.dailyTrend = data.daily_trend || [];
				this.records = processedRecords;
				this.pricePerKwh = data.price_per_kwh || 0.61;
				this.loading = false;

				setTimeout(() => {
					this.drawChart('recentChart', data.recent_trend || [], 'time');
					this.drawChart('dailyChart', data.daily_trend || [], 'date');
				}, 200);
			} catch (err) {
				this.loading = false;
				uni.showToast({ title: err.msg || '获取数据失败', icon: 'none' });
			}
		},

		/**
		 * 统一获取画布上下文
		 * 微信小程序使用 Canvas 2D（node）接口，其他平台使用旧版 canvas-id 接口
		 */
		getCanvasContext(canvasId) {
			return new Promise(resolve => {
				// #ifdef MP-WEIXIN
				uni.createSelectorQuery()
					.in(this)
					.select('#' + canvasId)
					.fields({ node: true, size: true })
					.exec(res => {
						if (!res || !res[0] || !res[0].node) {
							resolve(null);
							return;
						}
						const canvas = res[0].node;
						const ctx = canvas.getContext('2d');
						const width = res[0].width;
						const height = res[0].height;
						const sysInfo = uni.getWindowInfo ? uni.getWindowInfo() : uni.getSystemInfoSync();
						const dpr = sysInfo.pixelRatio || 1;
						canvas.width = width * dpr;
						canvas.height = height * dpr;
						ctx.scale(dpr, dpr);
						resolve({ ctx, width, height, legacy: false });
					});
				// #endif

				// #ifndef MP-WEIXIN
				uni.createSelectorQuery()
					.in(this)
					.select('#' + canvasId)
					.boundingClientRect()
					.exec(res => {
						const rect = res && res[0];
						const width = (rect && rect.width) || 320;
						const height = (rect && rect.height) || 200;
						const ctx = uni.createCanvasContext(canvasId, this);
						resolve({ ctx: this.wrapLegacyContext(ctx), width, height, legacy: true });
					});
				// #endif
			});
		},

		/**
		 * 将旧版 canvas 上下文适配为 Canvas 2D 风格的接口
		 */
		wrapLegacyContext(ctx) {
			return {
				set fillStyle(v) {
					ctx.setFillStyle(v);
				},
				set strokeStyle(v) {
					ctx.setStrokeStyle(v);
				},
				set lineWidth(v) {
					ctx.setLineWidth(v);
				},
				set font(v) {
					const m = /(\d+)px/.exec(v);
					if (m) ctx.setFontSize(parseInt(m[1], 10));
				},
				set textAlign(v) {
					ctx.setTextAlign(v);
				},
				fillRect: (...a) => ctx.fillRect(...a),
				beginPath: () => ctx.beginPath(),
				moveTo: (...a) => ctx.moveTo(...a),
				lineTo: (...a) => ctx.lineTo(...a),
				stroke: () => ctx.stroke(),
				fill: () => ctx.fill(),
				closePath: () => ctx.closePath(),
				arc: (...a) => ctx.arc(...a),
				fillText: (...a) => ctx.fillText(...a),
				scale: () => {},
				draw: () => ctx.draw()
			};
		},

		async drawChart(canvasId, data, labelKey) {
			if (!data || data.length === 0) return;

			const target = await this.getCanvasContext(canvasId);
			if (!target) return;

			const { ctx, width, height, legacy } = target;

			const padding = { top: 20, right: 15, bottom: 35, left: 45 };
			const chartWidth = width - padding.left - padding.right;
			const chartHeight = height - padding.top - padding.bottom;

			ctx.fillStyle = '#fff';
			ctx.fillRect(0, 0, width, height);

			const values = data.map(d => d.usage);
			let maxVal = Math.max.apply(null, values);
			let minVal = Math.min.apply(null, values);
			if (maxVal === minVal) {
				maxVal = minVal + 1;
				minVal = Math.max(0, minVal - 1);
			}
			const range = maxVal - minVal;
			maxVal = maxVal + range * 0.1;
			minVal = Math.max(0, minVal - range * 0.1);

			ctx.strokeStyle = '#f0f0f0';
			ctx.lineWidth = 1;
			for (let i = 0; i <= 4; i++) {
				const y = padding.top + (chartHeight * i) / 4;
				ctx.beginPath();
				ctx.moveTo(padding.left, y);
				ctx.lineTo(width - padding.right, y);
				ctx.stroke();

				const val = maxVal - ((maxVal - minVal) * i) / 4;
				ctx.fillStyle = '#999';
				ctx.font = '10px sans-serif';
				ctx.textAlign = 'right';
				ctx.fillText(val.toFixed(2), padding.left - 5, y + 3);
			}

			const xStep = data.length > 1 ? chartWidth / (data.length - 1) : 0;
			const points = data.map((d, i) => {
				const x = padding.left + (data.length > 1 ? i * xStep : chartWidth / 2);
				const y = padding.top + chartHeight - ((d.usage - minVal) / (maxVal - minVal)) * chartHeight;
				return { x, y, usage: d.usage, label: d[labelKey] || '' };
			});

			ctx.beginPath();
			ctx.moveTo(points[0].x, points[0].y);
			points.forEach((p, i) => {
				if (i > 0) ctx.lineTo(p.x, p.y);
			});
			ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
			ctx.lineTo(points[0].x, height - padding.bottom);
			ctx.closePath();
			ctx.fillStyle = 'rgba(0, 123, 255, 0.1)';
			ctx.fill();

			ctx.beginPath();
			ctx.moveTo(points[0].x, points[0].y);
			points.forEach((p, i) => {
				if (i > 0) ctx.lineTo(p.x, p.y);
			});
			ctx.strokeStyle = '#007bff';
			ctx.lineWidth = 2;
			ctx.stroke();

			points.forEach(p => {
				ctx.beginPath();
				ctx.arc(p.x, p.y, 3, 0, 2 * Math.PI);
				ctx.fillStyle = '#007bff';
				ctx.fill();
				ctx.strokeStyle = '#fff';
				ctx.lineWidth = 1.5;
				ctx.stroke();
			});

			const labelStep = Math.ceil(data.length / 5);
			ctx.fillStyle = '#666';
			ctx.font = '9px sans-serif';
			ctx.textAlign = 'center';
			data.forEach((d, i) => {
				if (i % labelStep === 0 || i === data.length - 1) {
					const x = padding.left + (data.length > 1 ? i * xStep : chartWidth / 2);
					let label = d[labelKey] || '';
					if (labelKey === 'time' && label.length > 11) {
						label = label.substring(5, 16);
					} else if (labelKey === 'date' && label.length > 5) {
						label = label.substring(5);
					}
					ctx.fillText(label, x, height - padding.bottom + 15);
				}
			});

			if (legacy && ctx.draw) ctx.draw();
		},

		async aiAnalyze() {
			if (this.aiLoading) return;
			if (!this.stats) {
				uni.showToast({ title: '暂无统计数据', icon: 'none' });
				return;
			}
			const dorm = this.dormitories[this.selectedIndex];
			const key = this.timeRange + ':' + (this.customStart || '') + ':' + (this.customEnd || '') + ':' + (dorm ? dorm.room_num : '');
			if (this.aiCache[key]) {
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			this.aiLoading = true;
			// 立即给出反馈：AI 生成较慢，先弹出加载态
			this.aiResult = '### AI 正在分析…\n\n正在读取用电数据并生成分析报告，请稍候（通常需要十几秒）。';
			this.showAiResult = true;
			const payload = {
				type: 'statistics',
				dormitory: dorm ? dorm.displayName : '',
				stats: {
					healthText: this.stats.healthText,
					yesterdayUsage: this.stats.yesterdayUsage,
					yesterdayCost: this.stats.yesterdayCost,
					dailyAvgUsage: this.stats.dailyAvgUsage,
					dailyAvgCost: this.stats.dailyAvgCost,
					latestBalance: this.stats.latestBalance,
					estimatedDays: this.stats.estimatedDays
				},
				recentTrend: (this.recentTrend || []).map(d => ({
					label: d.time || d.date || '',
					usage: d.usage
				})),
				records: (this.records || []).slice(0, 20).map(r => ({
					time: r.record_time || r.created_at || '',
					balance: r.balance,
					usage: parseFloat(r.usage_kwh) || 0,
					cost: parseFloat(r.usage_cost) || 0
				}))
			};
			try {
				const res = await api.aiAnalyzePower(payload);
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

.filter-section {
	display: flex;
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-top: 25rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.filter-item {
	flex: 1;
}

.filter-label {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 12rpx;
	display: block;
}

.picker-value {
	display: flex;
	align-items: center;
	padding: 20rpx 24rpx;
	background: linear-gradient(135deg, #f7f8fc 0%, #eff1ff 100%);
	border-radius: 16rpx;
	border: 2rpx solid #e8eaf6;
}

.picker-icon {
	width: 36rpx;
	height: 36rpx;
	margin-right: 16rpx;
}

.picker-text {
	flex: 1;
	font-size: 28rpx;
	color: #48416f;
	font-weight: 500;
}

.picker-arrow {
	font-size: 20rpx;
	color: #948eb3;
	margin-left: 8rpx;
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
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 24rpx;
	opacity: 0.6;
}

.empty-title {
	font-size: 32rpx;
	color: #48416f;
	font-weight: bold;
	margin-bottom: 12rpx;
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

.stats-section {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.stats-header {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
	padding-bottom: 16rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.stats-header-icon {
	width: 36rpx;
	height: 36rpx;
	margin-right: 12rpx;
}

.stats-header-title {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
}

.stats-cards {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.stat-card {
	flex: 1;
	min-width: 45%;
	background: white;
	border-radius: 16rpx;
	overflow: hidden;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.stat-card-content {
	display: flex;
	align-items: center;
	padding: 20rpx 24rpx;
}

.stat-icon-wrapper {
	width: 56rpx;
	height: 56rpx;
	border-radius: 14rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 16rpx;
	flex-shrink: 0;
}

.stat-card.health-saving .stat-icon-wrapper {
	background: rgba(40, 167, 69, 0.1);
}

.stat-card.health-normal .stat-icon-wrapper {
	background: rgba(255, 193, 7, 0.1);
}

.stat-card.health-high .stat-icon-wrapper {
	background: rgba(220, 53, 69, 0.1);
}

.stat-card.info .stat-icon-wrapper {
	background: rgba(23, 162, 184, 0.1);
}

.stat-card.primary .stat-icon-wrapper {
	background: rgba(0, 123, 255, 0.1);
}

.stat-card.success .stat-icon-wrapper {
	background: rgba(40, 167, 69, 0.1);
}

.stat-card.estimated .stat-icon-wrapper {
	background: rgba(0, 123, 255, 0.1);
}

.stat-icon {
	width: 28rpx;
	height: 28rpx;
}

.stat-info {
	flex: 1;
}

.stat-value {
	font-size: 32rpx;
	font-weight: bold;
	line-height: 1.2;
	color: #48416f;
}

.stat-unit {
	font-size: 20rpx;
	font-weight: normal;
	color: #948eb3;
	margin-left: 4rpx;
}

.stat-label {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 4rpx;
}

.stat-sub {
	font-size: 20rpx;
	color: #948eb3;
	margin-top: 4rpx;
}

.chart-section {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.section-header {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
}

.section-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 12rpx;
}

.section-title {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
	flex: 1;
}

.section-count {
	font-size: 22rpx;
	color: #948eb3;
	background: #f5f5f5;
	padding: 6rpx 16rpx;
	border-radius: 20rpx;
}

.chart-canvas {
	width: 100%;
	height: 200px;
}

.chart-footer {
	display: flex;
	justify-content: center;
	gap: 40rpx;
	padding-top: 16rpx;
	margin-top: 16rpx;
	border-top: 2rpx solid #f5f5f5;
}

.chart-footer-item {
	display: flex;
	align-items: center;
	gap: 8rpx;
}

.chart-footer-label {
	font-size: 22rpx;
	color: #948eb3;
}

.chart-footer-value {
	font-size: 22rpx;
	color: #48416f;
	font-weight: 500;
}

.no-data {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0;
	color: #948eb3;
}

.no-data-icon {
	width: 80rpx;
	height: 80rpx;
	margin-bottom: 16rpx;
	opacity: 0.5;
}

.records-section {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 10rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.records-timeline {
	max-height: 600rpx;
	overflow-y: auto;
}

.record-item {
	display: flex;
	min-height: 120rpx;
}

.record-timeline-left {
	display: flex;
	flex-direction: column;
	align-items: center;
	width: 32rpx;
	flex-shrink: 0;
	margin-right: 20rpx;
}

.record-dot {
	width: 16rpx;
	height: 16rpx;
	border-radius: 50%;
	background: #13c2c2;
	flex-shrink: 0;
	margin-top: 8rpx;
	box-shadow: 0 0 0 4rpx rgba(19, 194, 194, 0.2);
}

.record-dot.usage-down {
	background: #dc3545;
	box-shadow: 0 0 0 4rpx rgba(220, 53, 69, 0.2);
}

.record-dot.usage-up {
	background: #28a745;
	box-shadow: 0 0 0 4rpx rgba(40, 167, 69, 0.2);
}

.record-line {
	flex: 1;
	width: 2rpx;
	background: linear-gradient(to bottom, #e0e0e0, #f0f0f0);
	margin-top: 4rpx;
}

.record-content {
	flex: 1;
	padding-bottom: 20rpx;
}

.record-header {
	margin-bottom: 12rpx;
}

.record-time {
	font-size: 24rpx;
	color: #948eb3;
}

.record-data {
	background: #f8f9fc;
	border-radius: 12rpx;
	padding: 16rpx;
}

.record-main {
	margin-bottom: 12rpx;
}

.record-balance {
	display: flex;
	align-items: baseline;
	gap: 8rpx;
}

.record-balance-label {
	font-size: 22rpx;
	color: #948eb3;
}

.record-balance-value {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
}

.record-details {
	display: flex;
	gap: 24rpx;
	padding-top: 12rpx;
	border-top: 2rpx solid #e8eaf6;
}

.record-detail-item {
	display: flex;
	align-items: center;
	gap: 8rpx;
}

.record-detail-label {
	font-size: 22rpx;
	color: #948eb3;
}

.record-detail-value {
	font-size: 24rpx;
	font-weight: 500;
	color: #48416f;
}

.usage-down {
	color: #dc3545;
}

.usage-up {
	color: #28a745;
}

.records-footer {
	text-align: center;
	padding-top: 16rpx;
	margin-top: 16rpx;
	border-top: 2rpx solid #f5f5f5;
	font-size: 22rpx;
	color: #948eb3;
}



.time-filter {
	display: flex;
	flex-direction: column;
	background: white;
	border-radius: 20rpx;
	padding: 20rpx 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.time-filter-btns {
	display: flex;
	gap: 16rpx;
}

.time-btn {
	flex: 1;
	text-align: center;
	font-size: 26rpx;
	color: #48416f;
	padding: 16rpx 0;
	border-radius: 14rpx;
	background: #f5f6fa;
	border: 2rpx solid transparent;
}

.time-btn.active {
	background: #13c2c2;
	color: #fff;
}

.time-custom {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-top: 20rpx;
}

.custom-picker {
	flex: 1;
	min-height: 76rpx;
	line-height: 72rpx;
	text-align: center;
	font-size: 26rpx;
	color: #48416f;
	border-radius: 14rpx;
	background: #f5f6fa;
	border: 2rpx solid #e3e0f0;
	box-sizing: border-box;
}

.custom-sep {
	font-size: 26rpx;
	color: #948eb3;
}

.ai-btn {
	width: 100%;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	color: white;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0;
	border-radius: 12rpx;
	margin: 0 0 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.25);
}

.ai-btn::after {
	border: none;
}

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
