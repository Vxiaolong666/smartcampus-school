<template>
	<view>
		<view class="background-wrapper">
			<cb-nav-bar :top-gap="0">
				<view class="school-selector">
					<image class="school-selector-icon" src="/static/assets/wxmp-location-white.svg" mode="aspectFit"></image>
					<text class="school-selector-name">广东茂名幼儿师范专科学校</text>
				</view>
			</cb-nav-bar>
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
			<view class="background-subject">
				<view class="background-subject-title">欢迎回来，{{ apiUserInfo.username || '用户' }}</view>
				<view class="background-subject-poem">{{ randomPoem }}</view>
			</view>
		</view>

		<view class="content-wrapper">
			<view v-if="indexAnnouncement">
				<announcement :content="indexAnnouncement.content" :type="indexAnnouncement.type" :closable="true" />
			</view>

			<view v-if="lowSubscriptions.length > 0" class="subscription-alert" @click="handleSubscription">
				<view class="subscription-alert-content">
					<text class="subscription-alert-title">有{{ lowSubscriptions.length }}个订阅次数不足，点击增加</text>
					<image class="subscription-alert-arrow" src="/static/assets/wxmp-right.svg" mode="aspectFit"></image>
				</view>
			</view>

			<view class="content-swiper">
				<swiper
					class="swiper"
					:indicator-dots="true"
					indicator-color="rgba(255,255,255,0.4)"
					indicator-active-color="#ffffff"
					:autoplay="swiperAutoplay"
					:interval="4000"
					:circular="true"
					:duration="500"
				>
					<swiper-item v-for="item in swiperList" :key="item.id">
						<image class="swiper-image" :src="item.image" mode="aspectFill" @click="handleSwiperTap" :data-url="item.url"></image>
					</swiper-item>
				</swiper>
			</view>

			<view class="content-menus">
				<view class="content-menus-item" @click="handleDormitoryBind">
					<image class="content-menus-item-logo" src="/static/assets/icon-one/ssbd.svg" mode="aspectFit"></image>
					<view class="content-menus-item-text">宿舍绑定</view>
				</view>
				<view class="content-menus-item" @click="handleStudentInfo">
					<image class="content-menus-item-logo" src="/static/assets/icon-one/jwbd.svg" mode="aspectFit"></image>
					<view class="content-menus-item-text">教务绑定</view>
				</view>
				<view class="content-menus-item" @click="handlePowerStatistics">
					<image class="content-menus-item-logo" src="/static/assets/icon-one/tssz.svg" mode="aspectFit"></image>
					<view class="content-menus-item-text">用电记录</view>
				</view>
			</view>

			<view class="content-menus content-menus-sub">
				<view class="content-menus-item content-menus-item-h" @click="handlePushSettings">
					<image class="content-menus-item-logo content-menus-item-logo-sm" src="/static/assets/icon-one/ydtj.svg" mode="aspectFit"></image>
					<view class="content-menus-item-text">电费推送设置</view>
				</view>
				<view class="content-menus-item content-menus-item-h" @click="handleAcademicPushSettings">
					<image class="content-menus-item-logo content-menus-item-logo-sm" src="/static/assets/icon-one/cjts.svg" mode="aspectFit"></image>
					<view class="content-menus-item-text">学术推送设置</view>
				</view>
			</view>

			<view class="glance-section">
				<view class="glance-cards">
					<view class="glance-card glance-course" @click="handleTodaySchedule">
						<view class="glance-card-content">
							<block v-if="nextCourse">
								<view class="glance-card-header">
									<view class="glance-card-label">课程信息</view>
									<view class="glance-card-tag" :class="nextCourse.statusClass">{{ nextCourse.statusText }}</view>
								</view>
								<view class="glance-card-value">{{ nextCourse.courseName }}</view>
								<view class="glance-card-info">
									{{ nextCourse.timeDisplay }}
									<text v-if="nextCourse.location"> · {{ nextCourse.location }}</text>
								</view>
							</block>
							<block v-else-if="courseLoadError">
								<view class="glance-card-header">
									<view class="glance-card-label">课程信息</view>
								</view>
								<view class="glance-card-value">加载失败</view>
								<view class="glance-card-info">点击重新加载</view>
							</block>
							<block v-else-if="allCoursesEnded">
								<view class="glance-card-header">
									<view class="glance-card-label">课程信息</view>
								</view>
								<view class="glance-card-value">今日课程已结束</view>
								<view class="glance-card-info">好好休息吧~</view>
							</block>
							<block v-else>
								<view class="glance-card-header">
									<view class="glance-card-label">下一节课</view>
								</view>
								<view class="glance-card-value">暂无课程</view>
								<view class="glance-card-info">今天没有课哦</view>
							</block>
						</view>
						<image class="glance-decor" src="/static/assets/hhhh.svg" mode="aspectFit"></image>
					</view>

					<view class="glance-card glance-power" @click="handlePowerFee">
						<view class="glance-card-content">
							<block v-if="powerInfo">
								<view class="glance-card-header">
									<view class="glance-card-label">电费余额</view>
									<view class="glance-card-tag" :class="powerInfo.statusClass">{{ powerInfo.statusText }}</view>
								</view>
								<view class="glance-card-value">¥{{ powerInfo.balance }}</view>
								<view class="glance-card-info">{{ powerInfo.roomName }}</view>
							</block>
							<block v-else-if="noDormitory">
								<view class="glance-card-header">
									<view class="glance-card-label">电费余额</view>
								</view>
								<view class="glance-card-value">未绑定宿舍</view>
								<view class="glance-card-info">请先绑定宿舍</view>
							</block>
							<block v-else>
								<view class="glance-card-header">
									<view class="glance-card-label">电费余额</view>
								</view>
								<view class="glance-card-value">加载中...</view>
								<view class="glance-card-info">正在获取电费信息</view>
							</block>
						</view>
						<image class="glance-decor" src="/static/assets/xinxin.svg" mode="aspectFit"></image>
					</view>
				</view>
			</view>

			<view class="ctrls-section">
				<view class="ctrls-title">快捷功能</view>
				<view class="content-ctrls">
					<view style="flex: 1.2; padding-right: 30rpx">
						<view class="content-ctrls-left" @click="handleSchedule">
							<image class="content-ctrls-left-decor" src="/static/assets/heart_reate_detecting_bottom_icon.png" mode="aspectFit"></image>
							<image src="/static/assets/myclass.svg" mode="aspectFill" style="width: 140rpx; height: 140rpx"></image>
							<view class="content-ctrls-left-text">我的课程表</view>
						</view>
					</view>
					<view class="content-ctrls-right">
						<view class="content-ctrls-right-top" @click="handleGrade">
							<image src="/static/assets/chenj.svg" mode="aspectFit" style="width: 70rpx; height: 70rpx"></image>
							<view class="content-ctrls-right-text">成绩查询</view>
						</view>
						<view class="content-ctrls-right-bottom" @click="handlePowerFee">
							<image src="/static/assets/dianf.svg" mode="aspectFit" style="width: 70rpx; height: 70rpx"></image>
							<view class="content-ctrls-right-text">电费查询</view>
						</view>
					</view>
				</view>
			</view>

			<view class="notices-section">
				<view class="notices-title">通知公告</view>
				<view class="content-notices">
					<view
						v-for="item in notices"
						:key="item.id"
						class="content-notice"
						:data-id="item.id"
						@click="handleNotice"
					>
						<image class="content-notice-bg" src="/static/assets/score_dialog_bg1.png" mode="aspectFill"></image>
						<image v-if="item.cover" class="content-notice-cover" :src="item.cover" mode="aspectFill"></image>
						<view class="content-notice-info">
							<view class="content-notice-title">
								<text v-if="item.is_top == 1" class="top-badge">置顶</text>
								{{ item.title }}
							</view>
							<view class="content-notice-date">{{ item.create_time }}</view>
						</view>
					</view>
					<view class="content-notice-more" @click="handleNotices">查看更多</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 首页
 * 展示通知列表、快捷入口和订阅状态
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import { sceneTypeNames } from '../../utils/common.js';

const API_BASE = 'http://127.0.0.12';
const __tab__ = 0;

// 诗句为静态数据，放到 data 外避免被 Vue 做无意义的响应式代理
const POEMS = [
	'「读书不觉已春深，一寸光阴一寸金」',
	'「纸上得来终觉浅，绝知此事要躬行」',
	'「黑发不知勤学早，白首方悔读书迟」',
	'「少壮不努力，老大徒伤悲」',
	'「业精于勤，荒于嬉；行成于思，毁于随」',
	'「学而不思则罔，思而不学则殆」',
	'「博学之，审问之，慎思之，明辨之，笃行之」',
	'「路漫漫其修远兮，吾将上下而求索」',
	'「长风破浪会有时，直挂云帆济沧海」',
	'「天生我材必有用，千金散尽还复来」',
	'「海内存知己，天涯若比邻」',
	'「桃花潭水深千尺，不及汪伦送我情」',
	'「人生自古谁无死，留取丹心照汗青」',
	'「春蚕到死丝方尽，蜡炬成灰泪始干」',
	'「落红不是无情物，化作春泥更护花」',
	'「采菊东篱下，悠然见南山」',
	'「明月几时有，把酒问青天」',
	'「但愿人长久，千里共婵娟」',
	'「山重水复疑无路，柳暗花明又一村」',
	'「不识庐山真面目，只缘身在此山中」'
];

export default {
	data() {
		return {
			isLoggedIn: false,
			apiUserInfo: null,
			notices: [],
			lowSubscriptions: [],
			swiperList: [
				{ id: 2, image: '/static/assets/banner/banner1.jpg', url: '' },
				{ id: 3, image: '/static/assets/banner/banner2.jpg', url: '/pages/academic-push-settings/index' },
				{ id: 4, image: '/static/assets/banner/banner3.jpg', url: '/pages/academic-grade/index' }
			],
			nextCourse: null,
			allCoursesEnded: false,
			courseLoadError: false,
			powerInfo: null,
			noDormitory: false,
			randomPoem: '',
			swiperAutoplay: true,
			_dataLoaded: false,
			featureSettings: {},
			indexAnnouncement: null
			};
			},

	onLoad(options) {
		this.checkLoginStatus();
		this.loadRandomPoem();
		this.loadFeatureSettings();

		if (!api.isLoggedIn()) {
			uni.redirectTo({
				url: '/pages/login/index'
			});
			return;
		}
	},

	onShow() {
		this.swiperAutoplay = true;
		this.checkLoginStatus();

		if (!this._dataLoaded) {
			this._dataLoaded = true;
			this.loadGlanceData();
			this.loadLowSubscriptions();
			this.loadNotices();
		}
	},

	onHide() {
		// 页面不可见时停止轮播，避免后台持续绘制
		this.swiperAutoplay = false;
	},

	onPullDownRefresh() {
		this.loadNotices();
		this.loadGlanceData();
		uni.stopPullDownRefresh();
	},

	methods: {
		async loadFeatureSettings() {
			try {
				const settings = await feature.getFeatureSettings();
				if (settings) {
					this.featureSettings = settings;
				}

				const indexResult = await feature.checkFeature('index');
				if (indexResult.announcement) {
					this.indexAnnouncement = indexResult.announcement;
				}
			} catch (err) {
				console.error('加载功能设置失败', err);
			}
		},

		async checkFeatureAndNavigate(featureKey, url) {
			const result = await feature.checkFeature(featureKey);
			if (!result.enabled) {
				feature.showDisabledAlert(result.featureName, result.announcement);
				return;
			}
			uni.navigateTo({ url });
		},

		loadRandomPoem() {
			this.randomPoem = POEMS[Math.floor(Math.random() * POEMS.length)];
		},

		async loadGlanceData() {
			if (!api.isLoggedIn()) return;
			try {
				const res = await api.getGlanceData();
				const data = res.data || {};
				this.nextCourse = data.next_course || null;
				this.allCoursesEnded = data.all_courses_ended || false;
				this.courseLoadError = data.course_load_error || false;
				this.powerInfo = data.power_info || null;
				this.noDormitory = data.no_dormitory || false;
			} catch (err) {
				console.error('加载速览数据失败', err);
				this.nextCourse = null;
				this.allCoursesEnded = false;
				this.courseLoadError = true;
				this.powerInfo = null;
				this.noDormitory = false;
			}
		},

		checkLoginStatus() {
			this.isLoggedIn = api.isLoggedIn();
			this.apiUserInfo = api.getUserInfoCache() || {};
		},

		async loadNotices() {
			try {
				const res = await api.getNotices(1, 3);
				const list = res.data.list.map(item => ({
					...item,
					cover: item.cover ? API_BASE + item.cover : ''
				}));
				this.notices = list;
			} catch (err) {
				console.error('加载通知失败', err);
			}
		},

		async loadLowSubscriptions() {
			if (!api.isLoggedIn()) return;

			try {
				const res = await api.request({
					act: 'get_user_subscriptions',
					method: 'GET'
				});

				const lowSubscriptions = (res.data.list || [])
					.filter(item => item.subscribe_count < 2)
					.map(item => ({
						...item,
						sceneName: sceneTypeNames[item.scene_type] || '通知'
					}));

				this.lowSubscriptions = lowSubscriptions;
			} catch (err) {
				console.error('加载订阅数据失败', err);
			}
		},

		handleSubscription() {
			uni.navigateTo({
				url: '/pages/subscription/index'
			});
		},

		handleNotices() {
			uni.navigateTo({
				url: '/pages/black-notice/index'
			});
		},

		handleNotice(e) {
			const id = e.currentTarget.dataset.id;
			uni.navigateTo({
				url: '/pages/black-notice-info/index?id=' + id
			});
		},

		handleComingSoon() {
			uni.showToast({
				title: '功能开发中',
				icon: 'none'
			});
		},

		handleSwiperTap(e) {
			const url = e.currentTarget.dataset.url;
			if (url) uni.navigateTo({ url });
		},

		handleTodaySchedule() {
			uni.switchTab({ url: '/pages/today-schedule/index' });
		},

		handleSchedule() {
			this.checkFeatureAndNavigate('schedule', '/pages/academic-schedule/index');
		},

		handleGrade() {
			this.checkFeatureAndNavigate('grade', '/pages/academic-grade/index');
		},

		handlePowerFee() {
			this.checkFeatureAndNavigate('power_fee', '/pages/power-fee/index');
		},

		handlePowerStatistics() {
			this.checkFeatureAndNavigate('power_statistics', '/pages/power-statistics/index');
		},

		handleDormitoryBind() {
			uni.navigateTo({ url: '/pages/dormitory-bind/index' });
		},

		handleStudentInfo() {
			uni.navigateTo({ url: '/pages/academic-info/index' });
		},

		handlePushSettings() {
			uni.navigateTo({
				url: '/pages/push-settings/index'
			});
		},

		handleAcademicPushSettings() {
			uni.navigateTo({
				url: '/pages/academic-push-settings/index'
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
.title {
	height: 100%;
	display: flex;
	align-items: center;
	padding-left: 40rpx;
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
}

.school-selector {
	position: relative;
	z-index: 2;
	display: flex;
	align-items: center;
	padding: 16rpx 0 0;
	margin-left: -50rpx;
}

.school-selector-icon {
	width: 28rpx;
	height: 28rpx;
	margin-right: 8rpx;
}

.school-selector-name {
	font-size: 26rpx;
	color: white;
	letter-spacing: 1rpx;
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

.background-subject {
	position: relative;
	color: white;
	padding: 30rpx;
}

.background-subject-subtitle {
	font-size: 30rpx;
}

.background-subject-title {
	font-size: 50rpx;
	font-weight: bold;
}

.background-subject-poem {
	font-size: 24rpx;
	opacity: 0.8;
	margin-top: 12rpx;
	letter-spacing: 2rpx;
}

.content-wrapper {
	position: relative;
	z-index: 1;
	background: #eff1ff;
	top: 450rpx;
	min-height: 50vh;
	border-top-left-radius: 60rpx;
	border-top-right-radius: 60rpx;
	box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
	padding: 24rpx 30rpx;
}

.content-menus {
	display: flex;
	align-items: center;
	margin-bottom: 30rpx;
}

.content-menus-sub {
	margin-top: -16rpx;
	margin-bottom: 30rpx;
}

.content-menus-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	background: white;
	border-radius: 24rpx;
	border: 1rpx solid #f0f0f5;
	padding: 20rpx 0;
	margin-right: 16rpx;
}

.content-menus-item:last-child {
	margin-right: 0;
}

.content-menus-item-logo {
	width: 64rpx;
	height: 64rpx;
}

.content-menus-item-text {
	font-size: 24rpx;
	color: #48416f;
	font-weight: 500;
	white-space: nowrap;
	margin-top: 12rpx;
}

.content-menus-item-h {
	flex-direction: row;
	justify-content: center;
	padding: 24rpx 0;
}

.content-menus-item-logo-sm {
	width: 44rpx;
	height: 44rpx;
}

.content-menus-item-h .content-menus-item-text {
	margin-top: 0;
	margin-left: 12rpx;
}

.content-ctrls {
	margin-top: 30rpx;
	display: flex;
	height: 300rpx;
}

.content-ctrls-left {
	background: white;
	height: 100%;
	width: 100%;
	border-radius: 30rpx 0 30rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	position: relative;
	overflow: hidden;
}

.content-ctrls-left-decor {
	position: absolute;
	right: -10rpx;
	bottom: -10rpx;
	width: 160rpx;
	height: 140rpx;
	pointer-events: none;
}

.content-ctrls-left-text {
	margin-top: 30rpx;
	color: #5a5477;
	font-weight: bold;
	font-size: 30rpx;
}

.content-ctrls-right {
	display: flex;
	flex-direction: column;
	flex: 1;
}

.content-ctrls-right-top {
	background: white;
	border-radius: 0 30rpx 0 30rpx;
	flex: 1;
	margin-bottom: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.content-ctrls-right-text {
	color: #5a5477;
	font-weight: bold;
	font-size: 30rpx;
	margin-left: 20rpx;
}

.content-ctrls-right-bottom {
	background: white;
	border-radius: 30rpx 0 30rpx 0;
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
}

.content-notices {
	display: flex;
	flex-direction: column;
	margin-top: 30rpx;
	padding-bottom: 15rpx;
}

.content-notice {
	padding: 30rpx;
	border-radius: 30rpx;
	font-size: 24rpx;
	margin-bottom: 20rpx;
	display: flex;
	position: relative;
	overflow: hidden;
}

.content-notice-bg {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 0;
}

.content-notice-cover,
.content-notice-info {
	position: relative;
	z-index: 1;
}

.content-notice-cover {
	width: 200rpx;
	height: 120rpx;
	border-radius: 20rpx;
}

.content-notice-info {
	display: flex;
	flex-direction: column;
	padding-left: 30rpx;
	flex: 1;
}

.content-notice-title {
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

.content-notice-date {
	text-align: right;
	color: #948eb3;
}

.content-notice-more {
	text-align: center;
	color: #5a5477;
	font-size: 26rpx;
	text-decoration: underline;
}

.subscription-alert {
	background: white;
	border-radius: 30rpx;
	margin-bottom: 30rpx;
	overflow: hidden;
}

.subscription-alert-content {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 24rpx 30rpx;
}

.subscription-alert-title {
	font-size: 26rpx;
	color: #e74c3c;
	font-weight: bold;
}

.subscription-alert-arrow {
	width: 32rpx;
	height: 32rpx;
}

.content-swiper {
	margin-bottom: 30rpx;
}

.swiper {
	width: 100%;
	height: 280rpx;
	border-radius: 20rpx;
	overflow: hidden;
}

.swiper-image {
	width: 100%;
	height: 100%;
}

.glance-section {
	margin-bottom: 30rpx;
}

.glance-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	padding-left: 20rpx;
	position: relative;
}

.glance-title::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 28rpx;
	background: linear-gradient(180deg, #ff6348 0%, #ee5a24 100%);
	border-radius: 3rpx;
}

.glance-cards {
	display: flex;
	gap: 20rpx;
}

.glance-card {
	flex: 1;
	background: white;
	border-radius: 20rpx;
	padding: 28rpx 24rpx;
	position: relative;
	overflow: hidden;
}

.glance-decor {
	position: absolute;
	right: -10rpx;
	bottom: -10rpx;
	width: 140rpx;
	height: 120rpx;
	opacity: 0.15;
	pointer-events: none;
}

.glance-card-content {
	display: flex;
	flex-direction: column;
}

.glance-card-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
}

.glance-card-label {
	font-size: 22rpx;
	color: #948eb3;
	font-weight: 500;
	padding-left: 16rpx;
	position: relative;
}

.glance-card-label::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 22rpx;
	border-radius: 3rpx;
}

.glance-course .glance-card-label::before {
	background: #00b894;
}

.glance-power .glance-card-label::before {
	background: #f39c12;
}

.glance-card-value {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 6rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.glance-card-info {
	font-size: 22rpx;
	color: #948eb3;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.tag-normal {
	color: #00b894;
	background: rgba(0, 184, 148, 0.1);
}

.tag-active {
	color: #27ae60;
	background: rgba(39, 174, 96, 0.1);
}

.tag-warning {
	color: #f39c12;
	background: rgba(243, 156, 18, 0.1);
}

.tag-urgent {
	color: #e74c3c;
	background: rgba(231, 76, 60, 0.1);
}

.glance-card-tag {
	display: inline-block;
	font-size: 20rpx;
	padding: 6rpx 16rpx;
	border-radius: 20rpx;
}

.ctrls-section {
	margin-top: 30rpx;
	margin-bottom: 20rpx;
}

.ctrls-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	padding-left: 20rpx;
	position: relative;
}

.ctrls-title::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 28rpx;
	background: linear-gradient(180deg, #9b59b6 0%, #8e44ad 100%);
	border-radius: 3rpx;
}

.notices-section {
	margin-top: 30rpx;
	margin-bottom: 30rpx;
}

.notices-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
	padding-left: 20rpx;
	position: relative;
}

.notices-title::before {
	content: '';
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	width: 6rpx;
	height: 28rpx;
	background: linear-gradient(180deg, #00b894 0%, #00a381 100%);
	border-radius: 3rpx;
}
</style>
