<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">宿舍绑定</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view class="info-card" v-if="!loading">
				<image class="info-icon" src="/static/assets/wxmp-home2.svg" mode="widthFix"></image>
				<view class="info-content">
					<view class="info-text">最多可绑定{{ maxDormitories }}个宿舍，当前已绑定{{ dormitories.length }}个</view>
				</view>
			</view>

			<view class="btn-group" v-if="!loading">
				<button class="secondary-btn" @click="goToPowerFee">
					<image class="btn-icon" src="/static/assets/wxmp-search.svg" mode="widthFix"></image>
					查询电费
				</button>
				<button v-if="dormitories.length < maxDormitories" class="primary-btn" @click="openAddPanel">
					<image class="btn-icon btn-icon-add" src="/static/assets/wxmp-add-plain.svg" mode="widthFix"></image>
					添加宿舍
				</button>
			</view>

			<cb-loading v-if="loading" />

			<view v-else-if="dormitories.length > 0" class="dorm-list">
				<view class="dorm-item" v-for="item in dormitories" :key="item.id">
					<view class="dorm-info">
						<view class="dorm-name">
							{{ item.displayName }}
							<text v-if="item.is_default == 1" class="default-tag">默认</text>
						</view>
						<view class="dorm-detail">{{ item.school_area }} · {{ item.room_num }}</view>
						<view class="dorm-time">绑定于 {{ fmtDateTime(item.bind_time) }}</view>
					</view>
					<view class="dorm-actions">
						<button class="action-btn default-btn" :class="{ 'is-default': item.is_default == 1 }" :data-id="item.id" @click="onSetDefault">
							<image class="btn-icon" src="/static/assets/wxmp-star.svg" mode="widthFix"></image>
							设为默认
						</button>
						<button class="action-btn unbind-btn" :data-id="item.id" @click="onUnbind">
							<image class="btn-icon" src="/static/assets/wxmp-delete.svg" mode="widthFix"></image>
							解绑
						</button>
					</view>
				</view>
			</view>

			<view v-if="!loading && dormitories.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-home2-gray.svg" mode="widthFix"></image>
				<view class="empty-text">暂无绑定的宿舍</view>
			</view>
		</view>

		<view class="modal-mask" v-if="showAddPanel" @click="hideAddPanel">
			<view class="modal-content" @click.stop="preventBubble">
				<view class="modal-header">
					<view class="modal-title">添加宿舍</view>
					<view class="modal-close" @click="hideAddPanel">×</view>
				</view>
				<view class="modal-body">
					<view class="form-item" @click.stop="preventBubble">
						<view class="form-label">校区</view>
						<picker mode="selector" :range="areas" :value="areaIndex" @change="onAreaChange">
							<view class="form-value">{{ areaIndex >= 0 ? areas[areaIndex] : '请选择校区' }}</view>
						</picker>
					</view>
					<view class="form-item" @click.stop="preventBubble">
						<view class="form-label">楼栋</view>
						<picker mode="selector" :range="buildings" :value="buildingIndex" @change="onBuildingChange">
							<view class="form-value">{{ buildingIndex >= 0 ? buildings[buildingIndex] : '请选择楼栋' }}</view>
						</picker>
					</view>
					<view class="form-item" @click.stop="preventBubble">
						<view class="form-label">房间</view>
						<picker mode="selector" :range="rooms" range-key="room" :value="roomIndex" @change="onRoomChange">
							<view class="form-value">{{ roomIndex >= 0 ? rooms[roomIndex].room : '请选择房间' }}</view>
						</picker>
					</view>
					<view class="form-item" @click.stop="preventBubble">
						<view class="form-label">别名（可选）</view>
						<input class="form-input" placeholder="例如：我的宿舍" :value="alias" @input="onAliasInput" />
					</view>
				</view>
				<view class="modal-footer">
					<button class="modal-btn cancel-btn" @click.stop="hideAddPanel">取消</button>
					<button class="modal-btn confirm-btn" @click.stop="onBindSubmit">绑定</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 宿舍绑定页面
 * 管理宿舍绑定和解绑操作
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';

export default {
	data() {
		return {
			loading: true,
			dormitories: [],
			areas: [],
			buildings: [],
			rooms: [],
			areaIndex: -1,
			buildingIndex: -1,
			roomIndex: -1,
			alias: '',
			showAddPanel: false,
			maxDormitories: 5,
			announcement: null
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip();
			return;
		}
		this.checkFeature().then(canProceed => {
			if (!canProceed) return;
			this.loadDormitories();
		});
	},

	methods: {
		async checkFeature() {
			try {
				const result = await feature.checkFeature('dormitory_bind');
				if (!result.enabled) {
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
				this.dormitories = dormitories;
				this.loading = false;
			} catch (err) {
				this.loading = false;
				uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
			}
		},

		async loadAreas() {
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
			this.roomIndex = -1;
			this.buildings = [];
			this.rooms = [];

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
			this.roomIndex = -1;
			this.rooms = [];

			if (building && area) {
				try {
					const res = await api.getDormRooms(area, building);
					this.rooms = res.data || [];
				} catch (err) {
					uni.showToast({ title: '获取房间失败', icon: 'none' });
				}
			}
		},

		onRoomChange(e) {
			this.roomIndex = e.detail.value;
		},

		onAliasInput(e) {
			this.alias = e.detail.value;
		},

		openAddPanel() {
			if (this.dormitories.length >= this.maxDormitories) {
				uni.showToast({ title: '最多绑定5个宿舍', icon: 'none' });
				return;
			}
			this.showAddPanel = true;
			this.loadAreas();
		},

		hideAddPanel() {
			this.showAddPanel = false;
			this.areaIndex = -1;
			this.buildingIndex = -1;
			this.roomIndex = -1;
			this.alias = '';
		},

		async onBindSubmit() {
			const { areaIndex, buildingIndex, roomIndex, areas, buildings, rooms, alias } = this;

			if (areaIndex < 0 || buildingIndex < 0 || roomIndex < 0) {
				uni.showToast({ title: '请选择完整信息', icon: 'none' });
				return;
			}

			const room = rooms[roomIndex];
			const data = {
				school_area: areas[areaIndex],
				building: buildings[buildingIndex],
				room: room.room,
				room_num: room.room_num,
				alias: alias
			};

			uni.showLoading({ title: '绑定中...' });
			try {
				await api.bindDormitory(data);
				uni.hideLoading();
				uni.showToast({ title: '绑定成功', icon: 'success' });
				this.hideAddPanel();
				this.loadDormitories();
			} catch (err) {
				uni.hideLoading();
				uni.showToast({ title: err.msg || '绑定失败', icon: 'none' });
			}
		},

		async onUnbind(e) {
			const id = e.currentTarget.dataset.id;

			const res = await new Promise(resolve => {
				uni.showModal({
					title: '确认解绑',
					content: '确定要解绑这个宿舍吗？',
					success: resolve
				});
			});

			if (res.confirm) {
				try {
					await api.unbindDormitory(id);
					uni.showToast({ title: '解绑成功', icon: 'success' });
					this.loadDormitories();
				} catch (err) {
					uni.showToast({ title: err.msg || '解绑失败', icon: 'none' });
				}
			}
		},

		async onSetDefault(e) {
			const id = e.currentTarget.dataset.id;
			const item = (this.dormitories || []).find(d => String(d.id) === String(id)) || {};

			// 已是默认宿舍：灰色按钮仅作展示，点击给出提示
			if (item.is_default == 1) {
				uni.showToast({ title: '该宿舍已是默认宿舍', icon: 'none' });
				return;
			}

			const name = item.displayName || '该宿舍';
			const res = await new Promise(resolve => {
				uni.showModal({
					title: '设置默认',
					content: '确认把默认宿舍改为「' + name + '」吗？',
					confirmText: '确认',
					cancelText: '取消',
					success: resolve
				});
			});
			if (!res.confirm) return;

			try {
				await api.setDefaultDormitory(id);
				uni.showToast({ title: '设置成功', icon: 'success' });
				this.loadDormitories();
			} catch (err) {
				uni.showToast({ title: err.msg || '设置失败', icon: 'none' });
			}
		},

		goToPowerFee() {
			uni.navigateTo({ url: '/pages/power-fee/index' });
		},

		preventBubble() {}
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
	width: 40rpx;
	height: 40rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.info-text {
	font-size: 26rpx;
	color: #48416f;
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

.dorm-list {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.dorm-item {
	padding: 24rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.dorm-item:last-child {
	border-bottom: none;
}

.dorm-info {
	margin-bottom: 16rpx;
}

.dorm-name {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 8rpx;
}

.default-tag {
	font-size: 22rpx;
	color: white;
	background: #13c2c2;
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	margin-left: 12rpx;
	font-weight: normal;
}

.dorm-detail {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 4rpx;
}

.dorm-time {
	font-size: 22rpx;
	color: #c0c0c0;
}

.dorm-actions {
	display: flex;
	gap: 16rpx;
}

.action-btn {
	font-size: 24rpx;
	padding: 14rpx 18rpx;
	border-radius: 8rpx;
	margin: 0;
	line-height: 1.4;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	white-space: nowrap;
}

.action-btn::after {
	border: none;
}

.btn-icon {
	width: 24rpx;
	height: 24rpx;
	margin-right: 6rpx;
}

.default-btn {
	background: #e8f9f9;
	color: #13c2c2;
	border: 2rpx solid #13c2c2;
}

/* 已是默认宿舍：按钮置灰（仅作展示） */
.default-btn.is-default {
	background: #f2f3f7;
	color: #b8bcc9;
	border: 2rpx solid #e2e4ee;
}

.default-btn.is-default .btn-icon {
	filter: grayscale(1);
	opacity: 0.65;
}

.unbind-btn {
	background: #fff0f0;
	color: #ff4d4f;
	border: 2rpx solid #ff4d4f;
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

.empty-icon {
	width: 80rpx;
	height: 80rpx;
	margin-bottom: 20rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}

.btn-group {
	display: flex;
	gap: 20rpx;
	margin-top: 30rpx;
	margin-bottom: 24rpx;
}

.primary-btn {
	flex: 1;
	background: #13c2c2;
	color: white;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	border-radius: 16rpx;
	margin: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0;
}

.primary-btn::after {
	border: none;
}

.secondary-btn {
	flex: 1;
	background: white;
	color: #13c2c2;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	border-radius: 16rpx;
	margin: 0;
	border: 2rpx solid #13c2c2;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0;
}

.secondary-btn::after {
	border: none;
}

.primary-btn .btn-icon,
.secondary-btn .btn-icon {
	width: 28rpx;
	height: 28rpx;
	margin-right: 8rpx;
}

.modal-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 1000;
	display: flex;
	align-items: flex-end;
	justify-content: center;
}

.modal-content {
	width: 100%;
	background: white;
	border-top-left-radius: 32rpx;
	border-top-right-radius: 32rpx;
	animation: slideUp 0.3s ease;
}

@keyframes slideUp {
	from {
		transform: translateY(100%);
	}
	to {
		transform: translateY(0);
	}
}

.modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 30rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.modal-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
}

.modal-close {
	font-size: 48rpx;
	color: #948eb3;
	line-height: 1;
}

.modal-body {
	padding: 30rpx;
}

.form-item {
	margin-bottom: 24rpx;
}

.form-label {
	font-size: 26rpx;
	color: #948eb3;
	margin-bottom: 12rpx;
}

.form-value {
	font-size: 28rpx;
	color: #48416f;
	background: #f7f8fc;
	padding: 20rpx;
	border-radius: 12rpx;
}

.form-input {
	font-size: 28rpx;
	color: #48416f;
	background: #f7f8fc;
	padding: 20rpx;
	border-radius: 12rpx;
}

.modal-footer {
	display: flex;
	padding: 20rpx 30rpx 40rpx;
	gap: 20rpx;
}

.modal-btn {
	flex: 1;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	border-radius: 12rpx;
	margin: 0;
	padding: 0;
	display: flex;
	align-items: center;
	justify-content: center;
}

.cancel-btn {
	background: #f7f8fc;
	color: #948eb3;
}

.confirm-btn {
	background: #13c2c2;
	color: white;
}
</style>
