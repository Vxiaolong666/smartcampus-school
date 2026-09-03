<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">批量查询</view>
			<view class="nav-action" @click="goBack">
				<text class="action-text">返回</text>
			</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement" class="announcement-wrapper">
				<view class="announcement-simple">{{ announcement.content }}</view>
			</view>

			<view class="filter-card">
				<view class="filter-row">
					<view class="filter-item half">
						<view class="filter-label">校区</view>
						<picker mode="selector" :range="areas" :value="areaIndex" @change="onAreaChange">
							<view class="filter-value">{{ areaIndex >= 0 ? areas[areaIndex] : '请选择校区' }}</view>
						</picker>
					</view>
					<view class="filter-item half">
						<view class="filter-label">楼栋</view>
						<picker mode="selector" :range="buildings" :value="buildingIndex" @change="onBuildingChange">
							<view class="filter-value">{{ buildingIndex >= 0 ? buildings[buildingIndex] : '请选择楼栋' }}</view>
						</picker>
					</view>
				</view>

				<view v-if="roomCount > 0" class="room-info">
					<text class="room-count">共发现 {{ roomCount }} 个房间</text>
				</view>

				<button class="query-btn" @click="onQuery" :disabled="querying" :loading="querying">
					{{ querying ? '查询中...' : '开始查询' }}
				</button>
			</view>

			<view v-if="querying" class="progress-card">
				<view class="progress-text">正在查询... {{ queryProgress }}/{{ queryTotal }}</view>
				<progress :percent="queryTotal > 0 ? (queryProgress / queryTotal * 100) : 0" stroke-width="12" activeColor="#13c2c2" backgroundColor="#f0f0f0" />
			</view>

			<view v-if="results.length > 0 && !querying" class="results-wrapper">
				<view class="stats-row">
					<view class="stat-item normal">
						<view class="stat-value">{{ stats.normal }}</view>
						<view class="stat-label">充足</view>
					</view>
					<view class="stat-item warning">
						<view class="stat-value">{{ stats.warning }}</view>
						<view class="stat-label">偏低</view>
					</view>
					<view class="stat-item low">
						<view class="stat-value">{{ stats.low }}</view>
						<view class="stat-label">不足</view>
					</view>
					<view class="stat-item arrears">
						<view class="stat-value">{{ stats.arrears }}</view>
						<view class="stat-label">欠费</view>
					</view>
				</view>

				<view class="filter-row">
					<view class="filter-item">
						<view class="filter-label">筛选</view>
						<picker mode="selector" :range="['全部', '电量充足', '电量偏低', '电量不足', '已欠费']" :value="filterIndex" @change="onFilterChange">
							<view class="filter-value-small">{{ filterLabel }}</view>
						</picker>
					</view>
				</view>

				<view class="result-list">
					<view
						v-for="item in results"
						:key="item.roomNum"
						v-show="filterType === 'all' || item.status === filterType"
						class="result-item"
						:class="item.status"
					>
						<view class="result-room">{{ item.room }}</view>
						<view class="result-balance">{{ item.balance !== null ? item.balance + ' 度' : '--' }}</view>
						<view class="result-status">
							<text class="status-dot"></text>
							<text>{{ item.status === 'normal' ? '充足' : item.status === 'warning' ? '偏低' : item.status === 'low' ? '不足' : item.status === 'arrears' ? '欠费' : '错误' }}</text>
						</view>
					</view>
				</view>
			</view>

			<view v-if="!querying && results.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-bolt-gray.svg" mode="widthFix"></image>
				<view class="empty-text">选择校区和楼栋后开始查询</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 批量查电页面
 * 按校区和楼栋批量查询宿舍电费余额
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

export default {
	data() {
		return {
			featureEnabled: true,
			announcement: null,
			areas: [],
			buildings: [],
			areaIndex: -1,
			buildingIndex: -1,
			roomCount: 0,
			queryProgress: 0,
			queryTotal: 0,
			querying: false,
			results: [],
			stats: {
				normal: 0,
				warning: 0,
				low: 0,
				arrears: 0
			},
			filterType: 'all',
			filterTypes: ['all', 'normal', 'warning', 'low', 'arrears'],
			filterLabels: ['全部', '电量充足', '电量偏低', '电量不足', '已欠费']
		};
	},

	computed: {
		filterIndex() {
			return this.filterTypes.indexOf(this.filterType);
		},
		filterLabel() {
			return this.filterLabels[this.filterIndex] || '全部';
		}
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}

		this.checkFeature().then(canProceed => {
			if (canProceed) {
				this.loadAreas();
			}
		});
	},

	methods: {
		async checkFeature() {
			const result = await feature.checkFeature('power_fee_batch');

			if (!result.enabled) {
				this.featureEnabled = false;
				this.announcement = null;
				feature.showDisabledAlert(result.featureName, result.announcement);
				return false;
			}

			if (result.announcement) {
				this.announcement = result.announcement;
			}
			return true;
		},

		async loadAreas() {
			if (!this.featureEnabled) return;
			try {
				const res = await api.getDormAreas();
				this.areas = res.data || [];
			} catch (err) {
				uni.showToast({ title: '获取校区失败', icon: 'none' });
			}
		},

		async onAreaChange(e) {
			const index = e.detail.value;
			const area = this.areas[index];

			this.areaIndex = index;
			this.buildingIndex = -1;
			this.buildings = [];
			this.roomCount = 0;
			this.results = [];

			if (area) {
				try {
					const res = await api.getDormBuildings(area);
					this.buildings = res.data || [];
				} catch (err) {
					uni.showToast({ title: '获取楼栋失败', icon: 'none' });
				}
			}
		},

		async onBuildingChange(e) {
			const index = e.detail.value;
			const building = this.buildings[index];
			const area = this.areas[this.areaIndex];

			this.buildingIndex = index;
			this.roomCount = 0;
			this.results = [];

			if (building && area) {
				try {
					const res = await api.getDormRooms(area, building);
					this.roomCount = (res.data || []).length;
				} catch (err) {
					uni.showToast({ title: '获取房间失败', icon: 'none' });
				}
			}
		},

		async onQuery() {
			const { areaIndex, buildingIndex, areas, buildings } = this;

			if (areaIndex < 0 || buildingIndex < 0) {
				uni.showToast({ title: '请选择校区和楼栋', icon: 'none' });
				return;
			}

			const area = areas[areaIndex];
			const building = buildings[buildingIndex];

			this.querying = true;
			this.results = [];
			this.queryProgress = 0;
			this.queryTotal = 0;
			this.stats = { normal: 0, warning: 0, low: 0, arrears: 0 };

			try {
				const roomsRes = await api.getDormRooms(area, building);
				const rooms = roomsRes.data || [];

				if (rooms.length === 0) {
					this.querying = false;
					uni.showToast({ title: '该楼栋暂无房间数据', icon: 'none' });
					return;
				}

				this.queryTotal = rooms.length;

				const results = [];
				const stats = { normal: 0, warning: 0, low: 0, arrears: 0 };

				for (let i = 0; i < rooms.length; i++) {
					const room = rooms[i];

					try {
						const res = await api.queryPowerFee(room.room_num);
						const balance = res.data.balance;
						const status = res.data.status;

						results.push({
							room: room.room,
							roomNum: room.room_num,
							balance: balance,
							status: status
						});

						if (status === 'normal') stats.normal++;
						else if (status === 'warning') stats.warning++;
						else if (status === 'low') stats.low++;
						else if (status === 'arrears') stats.arrears++;
					} catch (err) {
						results.push({
							room: room.room,
							roomNum: room.room_num,
							balance: null,
							status: 'error'
						});
					}

					this.queryProgress = i + 1;
					this.results = [...results];
					this.stats = { ...stats };

					await new Promise(resolve => setTimeout(resolve, 100));
				}

				this.querying = false;
			} catch (err) {
				this.querying = false;
				uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
			}
		},

		onFilterChange(e) {
			const index = e.detail.value;
			this.filterType = this.filterTypes[index];
		},

		goBack() {
			uni.navigateBack();
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
	flex: 1;
	font-size: 32rpx;
	font-weight: bold;
	position: relative;
	z-index: 1;
	color: white;
	letter-spacing: 6rpx;
}

.nav-action {
	margin-left: auto;
	padding: 0 10rpx;
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
	padding-bottom: 100rpx;
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
	display: flex;
	gap: 20rpx;
	margin-bottom: 20rpx;
}

.filter-item {
	flex: 1;
}

.filter-item.half {
	flex: 1;
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

.filter-value-small {
	font-size: 26rpx;
	color: #48416f;
	background: #f7f8fc;
	padding: 16rpx;
	border-radius: 12rpx;
}

.room-info {
	margin-bottom: 20rpx;
}

.room-count {
	font-size: 26rpx;
	color: #13c2c2;
}

.query-btn {
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

.query-btn[disabled] {
	background: #ccc;
}

.progress-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.progress-text {
	font-size: 26rpx;
	color: #48416f;
	margin-bottom: 16rpx;
}

.results-wrapper {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.stats-row {
	display: flex;
	gap: 16rpx;
	margin-bottom: 24rpx;
}

.stat-item {
	flex: 1;
	text-align: center;
	padding: 20rpx;
	border-radius: 12rpx;
}

.stat-item.normal {
	background: #f6ffed;
}

.stat-item.warning {
	background: #fffbe6;
}

.stat-item.low {
	background: #fff2f0;
}

.stat-item.arrears {
	background: #f9f0ff;
}

.stat-value {
	font-size: 36rpx;
	font-weight: bold;
}

.stat-item.normal .stat-value {
	color: #52c41a;
}

.stat-item.warning .stat-value {
	color: #faad14;
}

.stat-item.low .stat-value {
	color: #ff4d4f;
}

.stat-item.arrears .stat-value {
	color: #722ed1;
}

.stat-label {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 4rpx;
}

.result-list {
	max-height: 600rpx;
	overflow-y: auto;
}

.result-item {
	display: flex;
	align-items: center;
	padding: 20rpx 0;
	border-bottom: 2rpx solid #f0f0f0;
}

.result-item:last-child {
	border-bottom: none;
}

.result-room {
	flex: 1;
	font-size: 28rpx;
	color: #48416f;
}

.result-balance {
	width: 160rpx;
	text-align: right;
	font-size: 28rpx;
	font-weight: bold;
}

.result-item.normal .result-balance {
	color: #52c41a;
}

.result-item.warning .result-balance {
	color: #faad14;
}

.result-item.low .result-balance {
	color: #ff4d4f;
}

.result-item.arrears .result-balance {
	color: #722ed1;
}

.result-status {
	width: 120rpx;
	text-align: right;
	font-size: 24rpx;
	color: #948eb3;
	display: flex;
	align-items: center;
	justify-content: flex-end;
}

.status-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	margin-right: 8rpx;
}

.result-item.normal .status-dot {
	background: #52c41a;
}

.result-item.warning .status-dot {
	background: #faad14;
}

.result-item.low .status-dot {
	background: #ff4d4f;
}

.result-item.arrears .status-dot {
	background: #722ed1;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 0;
	background: white;
	border-radius: 20rpx;
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

.announcement-wrapper {
	margin-bottom: 20rpx;
}

.announcement-simple {
	background: rgba(59, 130, 246, 0.1);
	color: #3b82f6;
	padding: 16rpx 24rpx;
	border-radius: 12rpx;
	font-size: 26rpx;
	border: 1rpx solid rgba(59, 130, 246, 0.2);
}
</style>
