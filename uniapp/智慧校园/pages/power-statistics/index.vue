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

			<cb-loading v-if="loading" />

			<cb-bind-tip v-else-if="dormitories.length === 0" type="dorm" desc="绑定宿舍后可查看用电统计" />

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
						<view>{{ fmtDate(customStart) || '开始日期' }}</view>
					</picker>
					<text class="custom-sep">至</text>
					<picker mode="date" class="custom-picker" :value="customEnd" @change="onCustomEnd">
						<view>{{ fmtDate(customEnd) || '结束日期' }}</view>
					</picker>
				</view>
			</view>

			<view v-if="stats" class="stats-section">
					<view class="stats-header">
						<image class="stats-header-icon" src="/static/assets/wxmp-chart.svg" mode="widthFix"></image>
						<text class="stats-header-title">用电概览</text>
						<text class="stats-header-period" v-if="periodText">{{ periodText }}</text>
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
									<view class="stat-label">当前余额<text v-if="balanceLive" class="stat-live">实时</text></view>
									<view class="stat-sub" v-if="balanceKwhText">≈{{ balanceKwhText }}度电</view>
								</view>
							</view>
						</view>

						<view class="stat-card hourly">
							<view class="stat-card-content">
								<view class="stat-icon-wrapper">
									<image class="stat-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
								</view>
								<view class="stat-info">
									<view class="stat-value">{{ hourlyAvgUsage }}<text class="stat-unit">度</text></view>
									<view class="stat-label">平均每小时用电</view>
									<view class="stat-sub" v-if="hourlyAvgCost !== '-'">约 ¥{{ hourlyAvgCost }}/时</view>
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

				<view class="chart-section">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-activity-teal.svg" mode="widthFix"></image>
						<text class="section-title">近24小时用电量趋势</text>
					</view>
					<view v-if="recentTrend.length === 0" class="no-data">
						<image class="no-data-icon" src="/static/assets/wxmp-coffee.svg" mode="widthFix"></image>
						<text>暂无数据</text>
					</view>
					<line-chart
						v-else
						:data="recentChartData"
						:color="trendColor"
						:height="200"
						unit="度"
					/>
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
					<view class="chart-note">
						固定展示最近 24 小时数据，不受上方筛选条件影响；其中 06:00 一条为前一晚 23:00-06:00 的整晚合计。
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
					<line-chart
						v-else
						:data="dailyChartData"
						:color="trendColor"
						:height="200"
						unit="度"
					/>
					<view class="chart-footer">
						<view class="chart-footer-item">
							<text class="chart-footer-label">统计天数</text>
							<text class="chart-footer-value">{{ dailyTrend.length }}天</text>
						</view>
					</view>
				</view>

				<view class="records-section">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-list-teal.svg" mode="widthFix"></image>
						<text class="section-title">历史电费记录</text>
						<text class="section-count">共{{ records.length }}条</text>
					</view>
					<!-- 筛选与时间排序：同一行下拉选择 -->
					<view class="record-toolbar">
						<picker
							class="record-picker-wrap"
							mode="selector"
							:range="recordFilterOptions"
							range-key="label"
							:value="recordFilterIndex"
							@change="onRecordFilterChange"
						>
							<view class="record-picker" :class="{ active: recordFilter !== 'all' }">
								<text class="record-picker-text">{{ recordFilterOptions[recordFilterIndex].label }}</text>
								<text class="record-picker-arrow">▼</text>
							</view>
						</picker>
						<picker
							class="record-picker-wrap"
							mode="selector"
							:range="recordSortOptions"
							range-key="label"
							:value="recordSortIndex"
							@change="onRecordSortChange"
						>
							<view class="record-picker" :class="{ active: recordSort !== 'desc' }">
								<text class="record-picker-text">{{ recordSortOptions[recordSortIndex].label }}</text>
								<text class="record-picker-arrow">▼</text>
							</view>
						</picker>
					</view>
					<view v-if="recordsLoading" class="records-loading">
						<view class="records-loading-spinner"></view>
						<text>加载中…</text>
					</view>
					<view v-else-if="records.length === 0" class="no-data">
						<image class="no-data-icon" src="/static/assets/wxmp-coffee.svg" mode="widthFix"></image>
						<text>{{ recordFilter === 'all' ? '暂无记录' : '无符合条件的记录' }}</text>
					</view>
					<view v-else class="records-timeline">
						<view v-for="(item, index) in records" :key="item.id" class="record-item">
							<view class="record-timeline-left">
								<view class="record-dot" :class="item.usageClass"></view>
								<view class="record-line" v-if="index < records.length - 1"></view>
							</view>
							<view class="record-content">
								<view class="record-header">
									<text class="record-time">{{ fmtDateTime(item.record_time || item.created_at) }}</text>
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

				<!-- 趣味账单：周/半月/月账单 + 周期前后翻看（数据由 get_power_bill 独立返回，不受页面筛选影响） -->
				<view class="fun-bill">
					<view class="section-header">
						<image class="section-icon" src="/static/assets/wxmp-star.svg" mode="widthFix"></image>
						<text class="section-title">趣味账单</text>
						<text v-if="billData && billData.bill" class="fun-badge">{{ billData.bill.title }}</text>
					</view>

					<!-- 账单类型 -->
					<view class="fun-tabs">
						<view
							v-for="opt in billRangeOptions"
							:key="opt.value"
							class="fun-tab"
							:class="{ active: billRange === opt.value }"
							@click="setBillRange(opt.value)"
						>{{ opt.label }}</view>
					</view>

					<!-- 周期选择：◀ 看更早的周期，▶ 回到较新的周期 -->
					<view class="fun-nav">
						<text class="fun-nav-btn" :class="{ disabled: !billData || billData.can_prev === false }" @click="billPrev">◀</text>
						<view class="fun-nav-mid">
							<text class="fun-nav-period">{{ billData ? billData.period_text : '加载中…' }}</text>
							<text v-if="billData && billData.tag" class="fun-nav-tag">{{ billData.tag }}</text>
						</view>
						<text class="fun-nav-btn" :class="{ disabled: !billOffset }" @click="billNext">▶</text>
					</view>

					<view v-if="billData && !billData.has_data" class="fun-empty">该周期暂无用电数据，可切换周期查看</view>

					<view v-if="billData && billData.bill">
					<view class="fun-totals">
						<view class="fun-total">
							<text class="fun-total-value">{{ billData.bill.total_usage }}<text class="fun-total-unit">度</text></text>
							<text class="fun-total-label">周期用电</text>
						</view>
						<view class="fun-total-divider"></view>
						<view class="fun-total">
							<text class="fun-total-value">{{ billData.bill.total_cost }}<text class="fun-total-unit">元</text></text>
							<text class="fun-total-label">周期电费</text>
						</view>
					</view>

					<view class="fun-block">
						<text class="fun-block-title">这些电都够干什么</text>
						<view class="fun-panel">
							<view class="fun-row" v-for="(eq, i) in billData.bill.equivalents" :key="i">
								<text class="fun-row-icon">{{ eq.icon }}</text>
								<text class="fun-row-label">{{ eq.label }}</text>
								<text class="fun-row-value">{{ eq.value }}{{ eq.unit }}</text>
							</view>
							<view class="fun-row" v-if="billData.bill.cost_fun">
								<text class="fun-row-icon">{{ billData.bill.cost_fun.icon }}</text>
								<text class="fun-row-label">{{ billData.bill.cost_fun.label }}</text>
								<text class="fun-row-value">{{ billData.bill.cost_fun.value }}{{ billData.bill.cost_fun.unit }}</text>
							</view>
						</view>
					</view>

					<view class="fun-block" v-if="billData.bill.max_day">
						<text class="fun-block-title">用电之最</text>
						<view class="fun-panel">
							<view class="fun-extreme">
								<text class="fun-extreme-tag high">最猛</text>
								<text class="fun-extreme-date">{{ billData.bill.max_day.date }}</text>
								<text class="fun-extreme-value">{{ billData.bill.max_day.usage }} 度</text>
							</view>
							<view class="fun-extreme" v-if="billData.bill.min_day">
								<text class="fun-extreme-tag low">最省</text>
								<text class="fun-extreme-date">{{ billData.bill.min_day.date }}</text>
								<text class="fun-extreme-value">{{ billData.bill.min_day.usage }} 度</text>
							</view>
						</view>
					</view>

					<view class="fun-block" v-if="billData.bill.night">
						<text class="fun-block-title">夜猫子指数 · {{ billData.bill.night.level }}</text>
						<view class="fun-night-bar">
							<view class="fun-night-fill" :style="'width:' + billData.bill.night.ratio + '%'"></view>
						</view>
						<text class="fun-night-text">
							最近 24 小时里，夜间（23:00-06:00）用了 {{ billData.bill.night.usage }} 度，占全部用电的 {{ billData.bill.night.ratio }}%
						</text>
					</view>

					<view class="fun-comment">{{ billData.bill.comment }}</view>
					</view>
				</view>

				<!-- AI 智能分析按钮：置于页面最下方 -->
				<view class="ai-analyze-wrap">
					<view class="ai-analyze-btn" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
						<text v-if="!aiLoading">AI 智能分析</text>
						<text v-else>分析中…</text>
					</view>
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

		<!-- 右侧浮动 AI 分析按钮（圆形） -->
		<view v-if="!loading && dormitories.length > 0" class="ai-fab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
			<text class="ai-fab-icon">AI</text>
			<text class="ai-fab-label">分析</text>
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
			// 趣味账单：支持周/半月/月账单 + 周期前后翻看（由 get_power_bill 独立取数）
			billData: null,       // 账单接口返回：bill / period_text / tag / can_prev / can_next / has_data
			billRange: 'week',    // week=周账单, half=半月账单, month=月账单
			billOffset: 0,        // 0=当前周期, 1=上一个周期…
			billRangeOptions: [
				{ value: 'week', label: '周账单' },
				{ value: 'half', label: '半月账单' },
				{ value: 'month', label: '月账单' }
			],
			records: [],
			periodText: '',
			pricePerKwh: 0.61,
			loading: true,
			// 时间筛选：all=全部, 7=近7天, 30=近30天, custom=自定义（默认近30天）
			timeRange: '30',
			customStart: '',
			customEnd: '',
			// 历史电费记录筛选/排序（同一行的下拉选项，切换后由服务端筛选排序）
			recordFilter: 'all',   // all=全部, increase=电费增加(消费), decrease=电费减少(余额增加/充值)
			recordSort: 'desc',    // desc=最新在前（默认，每次进入不继承上次选择）, asc=最早在前
			recordFilterOptions: [
				{ value: 'all', label: '全部记录' },
				{ value: 'increase', label: '电费增加' },
				{ value: 'decrease', label: '电费减少' }
			],
			recordSortOptions: [
				{ value: 'desc', label: '最新在前' },
				{ value: 'asc', label: '最早在前' }
			],
			recordFilterIndex: 0,  // 筛选下拉当前选中项
			recordSortIndex: 0,    // 排序下拉当前选中项
			// 当前余额是否已通过实时电费查询接口刷新
			balanceLive: false,
			// 切换记录筛选时的局部加载状态
			recordsLoading: false,
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
		},
		// 用电健康度对应的图表主色：节能=绿，正常=橙，偏高=红
		trendColor() {
			const s = this.stats && this.stats.healthStatus;
			if (s === 'saving') return '#52c41a';
			if (s === 'normal') return '#fa8c16';
			return '#ff4d4f';
		},
		// 当前余额按电价换算的电量（元 ÷ 元/度），用于余额卡片下方展示
		balanceKwhText() {
			const balance = this.stats ? parseFloat(this.stats.latestBalance) : NaN;
			const price = parseFloat(this.pricePerKwh) || 0;
			if (isNaN(balance) || price <= 0) return '';
			return (balance / price).toFixed(2);
		},
		// 平均每小时用电量（按日均 ÷ 24 估算）
		hourlyAvgUsage() {
			const v = parseFloat(this.stats && this.stats.dailyAvgUsage);
			if (isNaN(v) || v <= 0) return '-';
			return String(Number((v / 24).toFixed(3)));
		},
		// 平均每小时电费（按日均费用 ÷ 24 估算）
		hourlyAvgCost() {
			const v = parseFloat(this.stats && this.stats.dailyAvgCost);
			if (isNaN(v) || v <= 0) return '-';
			return String(Number((v / 24).toFixed(3)));
		},
		// 图表数据：近24小时用电量趋势（每小时一个采集点，由后端固定取最近 24 小时）
		recentChartData() {
			return (this.recentTrend || []).map(d => ({ label: d.time || d.date || '', value: Number(d.usage) || 0 }));
		},
		// 图表数据：每日用电总量趋势
		dailyChartData() {
			return (this.dailyTrend || []).map(d => ({ label: d.date || d.time || '', value: Number(d.usage) || 0 }));
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
					this.loadBill();
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
			this.loadBill();
		},

		// ===== 趣味账单：按账单周期独立取数（不受页面筛选条件影响）=====
		loadBill() {
			const dorm = this.dormitories[this.selectedIndex];
			if (!dorm) {
				this.billData = null;
				return;
			}
			api.request({
				act: 'get_power_bill',
				method: 'GET',
				data: {
					room_num: dorm.room_num,
					bill_range: this.billRange,
					bill_offset: this.billOffset
				}
			})
				.then(res => {
					this.billData = res.data || null;
				})
				.catch(err => {
					uni.showToast({ title: err.msg || '账单加载失败', icon: 'none' });
				});
		},

		// 切换账单类型（周/半月/月）：回到当前周期
		setBillRange(range) {
			if (this.billRange === range) return;
			this.billRange = range;
			this.billOffset = 0;
			this.loadBill();
		},

		// 查看更早的周期
		billPrev() {
			if (this.billData && this.billData.can_prev === false) return;
			this.billOffset += 1;
			this.loadBill();
		},

		// 回到较新的周期（最多到当前周期）
		billNext() {
			if (this.billOffset <= 0) return;
			this.billOffset -= 1;
			this.loadBill();
		},

		setTimeRange(range) {
			this.timeRange = range;
			this.loadStatistics();
		},

		// 历史电费记录筛选下拉切换（全部记录/电费增加/电费减少）
		onRecordFilterChange(e) {
			const index = Number(e.detail.value) || 0;
			const option = this.recordFilterOptions[index];
			if (!option || this.recordFilter === option.value) return;
			this.recordFilter = option.value;
			this.recordFilterIndex = index;
			// 切换筛选后重新请求（服务端过滤），不整体刷新页面
			this.loadRecords();
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
			// 记录增减筛选交由服务端处理，避免前端过滤大量记录造成卡顿
			if (this.recordFilter === 'increase' || this.recordFilter === 'decrease') {
				params.record_filter = this.recordFilter;
			}
			// 记录时间排序同样交由服务端处理
			params.record_sort = this.recordSort;
			return params;
		},

		formatDate(d) {
			const y = d.getFullYear();
			const m = String(d.getMonth() + 1).padStart(2, '0');
			const day = String(d.getDate()).padStart(2, '0');
			return y + '-' + m + '-' + day;
		},

		// 统计周期文案：优先用查询区间；「全部」时从返回数据取最早/最晚日期
		resolvePeriod(params, data) {
			let start = (params && params.start) || '';
			let end = (params && params.end) || '';
			if (!start || !end) {
				// 趋势日期为展示格式（2026/9/21），排序改用补零 ISO 键，避免字典序错乱
				const dates = [];
				(data.daily_trend || []).forEach(d => {
					if (d && d.date) dates.push(String(d.date));
				});
				if (!dates.length) {
					(data.records || []).forEach(r => {
						if (r && r.record_time) dates.push(String(r.record_time));
					});
				}
				const valid = dates
					.filter(d => this.isoKey(d))
					.sort((a, b) => (this.isoKey(a) < this.isoKey(b) ? -1 : (this.isoKey(a) > this.isoKey(b) ? 1 : 0)));
				if (valid.length) {
					if (!start) start = valid[0];
					if (!end) end = valid[valid.length - 1];
				}
			}
			const a = this.fmtDate(start);
			const b = this.fmtDate(end);
			if (!a && !b) return '';
			return (a || '?') + '-' + (b || '?');
		},

		// 排序键：2026-9-5 / 2026/9/5 / 2026-09-05 12:00 → 2026-09-05
		isoKey(s) {
			const m = /^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/.exec(String(s || ''));
			if (!m) return '';
			return m[1] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[3]).slice(-2);
		},

		async loadStatistics() {
			const { dormitories, selectedIndex } = this;

			if (!dormitories[selectedIndex]) return;

			const dorm = dormitories[selectedIndex];

			this.loading = true;

			try {
				const query = this.buildQuery(dorm.room_num);
				const res = await api.request({
					act: 'get_power_statistics',
					method: 'GET',
					data: query
				});

				const data = res.data;

				const processedRecords = this.processRecords(data.records);

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
				this.periodText = this.resolvePeriod(query, data);
				this.loading = false;

				// 当前余额改用实时电费查询接口获取（统计接口返回的是最后一条记录的余额，非实时）
				this.balanceLive = false;
				this.loadRealtimeBalance(dorm.room_num);
			} catch (err) {
				this.loading = false;
				uni.showToast({ title: err.msg || '获取数据失败', icon: 'none' });
			}
		},

		// 统一处理记录：解析展示字段（记录已由服务端按筛选与排序条件返回）
		processRecords(list) {
			return (list || []).map(r => {
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
			});
		},

		// 历史电费记录时间排序下拉切换（排序交由服务端处理，仅重新拉取记录列表）
		onRecordSortChange(e) {
			const index = Number(e.detail.value) || 0;
			const option = this.recordSortOptions[index];
			if (!option || this.recordSort === option.value) return;
			this.recordSort = option.value;
			this.recordSortIndex = index;
			this.loadRecords();
		},

		// 切换记录筛选时仅重新拉取历史记录（服务端过滤），不整页刷新
		async loadRecords() {
			const dorm = this.dormitories[this.selectedIndex];
			if (!dorm) return;
			this.recordsLoading = true;
			try {
				const res = await api.request({
					act: 'get_power_statistics',
					method: 'GET',
					data: this.buildQuery(dorm.room_num)
				});
				this.records = this.processRecords((res.data || {}).records);
			} catch (err) {
				uni.showToast({ title: err.msg || '获取记录失败', icon: 'none' });
			} finally {
				this.recordsLoading = false;
			}
		},

		// 通过实时电费查询接口刷新「当前余额」
		async loadRealtimeBalance(roomNum) {
			if (!roomNum) return;
			try {
				const res = await api.queryPowerFee(roomNum);
				const data = (res && res.data) || {};
				if (this.stats && data.balance !== undefined && data.balance !== null) {
					this.stats.latestBalance = data.balance;
					this.balanceLive = true;
				}
			} catch (e) {
				// 实时查询失败时保留统计接口返回的余额，不打断页面
			}
		},

		async aiAnalyze() {
			if (this.aiLoading) {
			// 正在分析中允许再次点击：重新聚焦分析弹窗并提示，不重复发起请求
			this.aiResult = '### AI 正在分析…\n\n正在生成分析报告，请稍候......\n（该过程通常需要十几秒）';
			this.showAiResult = true;
			uni.showToast({ title: 'AI 正在分析...', icon: 'none' });
			return;
		}
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
			this.aiResult = '### AI 正在分析…\n\n正在读取用电数据并生成分析报告，请稍候......\n（该过程通常需要十几秒）';
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

.stats-header-period {
	margin-left: auto;
	padding-left: 16rpx;
	font-size: 22rpx;
	color: #948eb3;
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

.stat-card.hourly .stat-icon-wrapper {
	background: rgba(19, 194, 194, 0.12);
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

.stat-live {
	display: inline-block;
	margin-left: 8rpx;
	padding: 0 10rpx;
	font-size: 18rpx;
	line-height: 1.6;
	color: #13c2c2;
	background: rgba(19, 194, 194, 0.12);
	border-radius: 8rpx;
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

/* 图表下方口径说明（如「近24小时」「06:00 为整晚合计」） */
.chart-note {
	margin-top: 12rpx;
	font-size: 22rpx;
	color: #948eb3;
	line-height: 1.6;
	text-align: center;
}

/* ==================== 趣味账单 ==================== */
/* 与上方卡片（.chart-section）一致的白色卡片样式 */
.fun-bill {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

/* 账单类型：周 / 半月 / 月 */
.fun-tabs {
	display: flex;
	margin-top: 20rpx;
	padding: 6rpx;
	background: #f7f8fc;
	border-radius: 16rpx;
}

.fun-tab {
	flex: 1;
	text-align: center;
	padding: 12rpx 0;
	font-size: 24rpx;
	color: #6b6485;
	border-radius: 12rpx;
}

.fun-tab.active {
	color: #ffffff;
	background: linear-gradient(135deg, #13c2c2 0%, #0ea5a5 100%);
	font-weight: 600;
}

/* 周期选择：◀ 周期 ▶（左右箭头往中间收，不贴卡片两侧） */
.fun-nav {
	display: flex;
	align-items: center;
	margin-top: 16rpx;
	padding: 0 60rpx;
}

.fun-nav-btn {
	width: 56rpx;
	height: 56rpx;
	line-height: 56rpx;
	text-align: center;
	font-size: 22rpx;
	color: #13c2c2;
	background: #f7f8fc;
	border-radius: 50%;
}

.fun-nav-btn.disabled {
	color: #c8c4d6;
}

.fun-nav-mid {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.fun-nav-period {
	font-size: 26rpx;
	font-weight: 600;
	color: #1f1a33;
}

.fun-nav-tag {
	margin-top: 2rpx;
	font-size: 22rpx;
	color: #948eb3;
}

.fun-empty {
	margin-top: 20rpx;
	padding: 30rpx 0;
	text-align: center;
	font-size: 24rpx;
	color: #948eb3;
	background: #f7f8fc;
	border-radius: 16rpx;
}

.fun-badge {
	padding: 6rpx 18rpx;
	font-size: 22rpx;
	color: #ffffff;
	background: linear-gradient(135deg, #13c2c2 0%, #0ea5a5 100%);
	border-radius: 24rpx;
}

.fun-totals {
	display: flex;
	align-items: center;
	margin-top: 24rpx;
	padding: 20rpx 0;
	background: #f7f8fc;
	border-radius: 16rpx;
}

.fun-total {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.fun-total-divider {
	width: 2rpx;
	height: 56rpx;
	background: #f0f0f5;
}

.fun-total-value {
	font-size: 40rpx;
	font-weight: bold;
	color: #13c2c2;
}

.fun-total-unit {
	margin-left: 4rpx;
	font-size: 22rpx;
	font-weight: normal;
}

.fun-total-label {
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #948eb3;
}

.fun-block {
	margin-top: 24rpx;
}

/* 浅色底圆角块：与周期统计（.fun-totals）一致的背景形状 */
.fun-panel {
	margin-top: 14rpx;
	padding: 10rpx 20rpx;
	background: #f7f8fc;
	border-radius: 16rpx;
}

/* 面板内的行去掉上外边距，改为纵向内间距（首尾由面板内边距补足） */
.fun-panel .fun-row,
.fun-panel .fun-extreme {
	margin-top: 0;
	padding: 12rpx 0;
}

.fun-block-title {
	font-size: 24rpx;
	font-weight: 600;
	color: #48416f;
}

.fun-row {
	display: flex;
	align-items: center;
	margin-top: 14rpx;
}

.fun-row-icon {
	font-size: 30rpx;
}

.fun-row-label {
	flex: 1;
	margin-left: 14rpx;
	font-size: 26rpx;
	color: #48416f;
}

.fun-row-value {
	font-size: 26rpx;
	font-weight: 600;
	color: #13c2c2;
}

.fun-extreme {
	display: flex;
	align-items: center;
	margin-top: 14rpx;
}

.fun-extreme-tag {
	padding: 2rpx 14rpx;
	font-size: 22rpx;
	border-radius: 8rpx;
}

.fun-extreme-tag.high {
	color: #ff4d4f;
	background: #fff1f0;
}

.fun-extreme-tag.low {
	color: #52c41a;
	background: #f6ffed;
}

.fun-extreme-date {
	flex: 1;
	margin-left: 14rpx;
	font-size: 26rpx;
	color: #48416f;
}

.fun-extreme-value {
	font-size: 26rpx;
	font-weight: 600;
	color: #1f1a33;
}

.fun-night-bar {
	margin-top: 16rpx;
	height: 16rpx;
	background: #edf2f7;
	border-radius: 8rpx;
	overflow: hidden;
}

.fun-night-fill {
	height: 100%;
	background: linear-gradient(90deg, #13c2c2 0%, #52c41a 100%);
	border-radius: 8rpx;
}

.fun-night-text {
	display: block;
	margin-top: 12rpx;
	font-size: 22rpx;
	color: #948eb3;
	line-height: 1.6;
}

.fun-comment {
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 2rpx solid #eef2f6;
	font-size: 24rpx;
	color: #6b6485;
	line-height: 1.7;
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
	margin-bottom: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

/* 筛选 + 时间排序：同一行两个下拉选择 */
.record-toolbar {
	display: flex;
	align-items: center;
	gap: 16rpx;
	margin-bottom: 20rpx;
}

.record-picker-wrap {
	flex: 1;
	min-width: 0;
}

.record-picker {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 14rpx 20rpx;
	border-radius: 12rpx;
	background: #f5f6fa;
	border: 2rpx solid transparent;
	box-sizing: border-box;
}

.record-picker.active {
	background: rgba(19, 194, 194, 0.1);
	border-color: rgba(19, 194, 194, 0.5);
}

.record-picker-text {
	flex: 1;
	min-width: 0;
	font-size: 24rpx;
	color: #48416f;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.record-picker.active .record-picker-text {
	color: #13c2c2;
	font-weight: 500;
}

.record-picker-arrow {
	flex-shrink: 0;
	margin-left: 8rpx;
	font-size: 18rpx;
	color: #948eb3;
}

.record-picker.active .record-picker-arrow {
	color: #13c2c2;
}

.records-loading {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 40rpx 0;
	font-size: 26rpx;
	color: #948eb3;
}

.records-loading-spinner {
	width: 32rpx;
	height: 32rpx;
	border: 3rpx solid #e0e0e0;
	border-top-color: #13c2c2;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
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

/* AI 分析按钮与浮动按钮样式已统一到 App.vue 全局（.ai-analyze-wrap / .ai-analyze-btn / .ai-fab） */
/* 本页需与下方「近24小时用电量趋势」区块保持间距 */
.ai-analyze-wrap {
	margin-bottom: 30rpx;
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
