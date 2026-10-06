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
				<!-- 今日日期 + 当前教学周（教务未返回周次时只显示日期） -->
				<view v-if="todayText" class="background-subject-meta">
					<text class="background-subject-date">{{ todayText }}</text>
					<view v-if="currentWeek > 0" class="background-subject-week">当前第{{ currentWeek }}周</view>
				</view>
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
					<view class="content-menus-item-text">学籍信息</view>
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
							<block v-else-if="!isLoggedIn">
								<view class="glance-card-header">
									<view class="glance-card-label">课程信息</view>
								</view>
								<view class="glance-card-value">登录后查看</view>
								<view class="glance-card-info">点击前往登录</view>
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
							<block v-else-if="!isLoggedIn">
								<view class="glance-card-header">
									<view class="glance-card-label">电费余额</view>
								</view>
								<view class="glance-card-value">登录后查看</view>
								<view class="glance-card-info">点击前往登录</view>
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
							<view class="content-notice-date">{{ fmtDateTime(item.create_time) }}</view>
						</view>
					</view>
					<view class="content-notice-more" @click="handleNotices">查看更多</view>
				</view>
			</view>
		</view>

		<!-- 版本更新弹窗 -->
		<update-modal
			:show="updateVisible"
			:version="updateInfo.version"
			:changelog="updateInfo.changelog"
			:force="updateInfo.force"
			:download-url="updateInfo.downloadUrl"
			:download-type="updateInfo.downloadType"
			:download-source="updateInfo.downloadSource"
			:download-pwd="updateInfo.downloadPwd"
			:download-page="updateInfo.downloadPage"
			@close="updateVisible = false"
		/>

		<!-- 右下角浮动刷新按钮（独立样式：纯白底 + 黑色图标，刷新整个首页数据） -->
		<view class="home-refresh-fab" :class="{ 'home-refresh-fab-loading': pageRefreshing }" @click="handlePageRefresh">
			<image class="home-refresh-icon" src="/static/assets/wxmp-refresh-black.svg" mode="aspectFit"></image>
		</view>

		<!-- 开屏广告 -->
		<splash-ad
			:show="splashVisible"
			:image="splashImage"
			:url="splashUrl"
			:duration="splashDuration"
			:name="splashName"
			@close="splashVisible = false"
		/>
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

// ===== 开屏广告会话标记 =====
// 用于区分「首次进入」与「首页刷新」：H5 用 sessionStorage（刷新保留、关闭标签页清除）；
// App / 小程序用内存标记（冷启动重置）。首次进入不受冷却限制，仅同会话内刷新才受冷却。
function splashSessionHandled() {
	let handled = false;
	// #ifdef H5
	try { handled = window.sessionStorage.getItem('splash_session') === '1'; } catch (e) {}
	// #endif
	// #ifndef H5
	try {
		const app = getApp();
		handled = !!(app && app.globalData && app.globalData.__splashSession);
	} catch (e) {}
	// #endif
	return handled;
}

function markSplashSession() {
	// #ifdef H5
	try { window.sessionStorage.setItem('splash_session', '1'); } catch (e) {}
	// #endif
	// #ifndef H5
	try {
		const app = getApp();
		if (app) {
			app.globalData = app.globalData || {};
			app.globalData.__splashSession = true;
		}
	} catch (e) {}
	// #endif
}

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
			currentWeek: 0,          // 当前教学周（0=未取到，不展示）
			powerInfo: null,
			noDormitory: false,
			pageRefreshing: false,   // 整个首页是否正在刷新
			randomPoem: '',
			todayText: '',           // 今日日期（M月D日）
			swiperAutoplay: true,
			_dataLoaded: false,
			featureSettings: {},
			indexAnnouncement: null,
			updateVisible: false,
			updateInfo: {},
			splashVisible: false,
			splashImage: '',
			splashUrl: '',
			splashDuration: 3,
			splashName: '智慧校园'
			};
			},

	onLoad(options) {
		this.checkLoginStatus();
		this.loadRandomPoem();
		this.todayText = this.getTodayText();
		this.loadFeatureSettings();
		this.loadBanners();
		// 开屏广告（后台可配置，免登录）
		this.loadSplashAd();
		// App 启动后首页加载时自动检查版本更新（免登录，与「关于我们-检查更新」逻辑一致）
		this.checkAppUpdate();

		// 合规要求：首页免登录即可浏览（轮播、公告、通知等），不再强制跳转登录页
		// 需要账号的功能在各自页面入口处提示登录，登录入口保留在「我的」页
	},

	onShow() {
		this.swiperAutoplay = true;
		this.checkLoginStatus();
		// 刷新今日日期（跨天后回到页面也能更新）
		this.todayText = this.getTodayText();

		if (!this._dataLoaded) {
			this._dataLoaded = true;
			this.loadGlanceData();
			this.loadLowSubscriptions();
			this.loadNotices();
		} else {
			// 再次显示（登录成功回跳首页 / 从其它页面返回）：异步刷新课程与电费信息，
			// 不展示 loading、失败也不清空，避免闪烁
			this.loadGlanceData({ silent: true });
		}
	},

	// 点击底部「首页」导航（含重复点击当前 Tab）：异步刷新课程与电费信息
	onTabItemTap() {
		this.loadGlanceData({ silent: true });
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

		//今日日期文案（M月D日 周X），与教学周一起展示在古诗词下方
		getTodayText() {
			const d = new Date();
			const weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
			return `${d.getMonth() + 1}月${d.getDate()}日 ${weekdays[d.getDay()]}`;
		},

		//加载后台「轮播图配置」，失败或为空时保留内置默认轮播图
		async loadBanners() {
			try {
				const res = await api.getBanners();
				const list = (res.data || []).map(item => ({
					id: item.id,
					image: item.image_url || item.image,
					// Uniapp 端读取 url_app，为空回退通用 url
					url: item.url_app || item.url || ''
				}));
				if (list.length > 0) {
					this.swiperList = list;
				}
			} catch (err) {
				console.warn('加载轮播图配置失败，使用默认轮播图', err);
			}
		},

		// 加载今日速览数据（下一节课 / 电费余额）
		// options.silent = true 表示后台静默刷新（登录成功回跳首页、点击首页 Tab 时使用）：
		// 失败时保留原有展示，避免数据被清空造成闪空
		async loadGlanceData(options) {
			const silent = !!(options && options.silent);
			if (!api.isLoggedIn()) return;
			try {
				const res = await api.getGlanceData();
				const data = res.data || {};
				this.nextCourse = data.next_course || null;
				this.allCoursesEnded = data.all_courses_ended || false;
				this.courseLoadError = data.course_load_error || false;
				this.currentWeek = parseInt(data.current_week, 10) || 0;
				this.powerInfo = data.power_info || null;
				this.noDormitory = data.no_dormitory || false;
			} catch (err) {
				console.error('加载速览数据失败', err);
				if (silent) return;
				this.nextCourse = null;
				this.allCoursesEnded = false;
				this.courseLoadError = true;
				this.currentWeek = 0;
				this.powerInfo = null;
				this.noDormitory = false;
			}
		},

		//浮动按钮：刷新整个首页数据（公告/功能开关、轮播图、通知公告、速览卡片、订阅提醒）
		async handlePageRefresh() {
			if (this.pageRefreshing) return;
			this.pageRefreshing = true;
			try {
				// 先清掉功能开关/公告缓存，确保「刷新」能拿到后台最新的公告内容
				feature.clearFeatureCache();
				await Promise.all([
					this.loadFeatureSettings(),
					this.loadBanners(),
					this.loadNotices(),
					this.loadGlanceData(),
					this.loadLowSubscriptions()
				]);
				uni.showToast({ title: '已刷新', icon: 'none' });
			} finally {
				this.pageRefreshing = false;
			}
		},

		checkLoginStatus() {
			this.isLoggedIn = api.isLoggedIn();
			this.apiUserInfo = api.getUserInfoCache() || {};
		},

		//教务/电费等功能需要登录：未登录时弹出提示并阻止跳转，已登录返回 true
		ensureLoggedIn() {
			if (api.isLoggedIn()) return true;
			// 首页本身可免登录浏览：选择「暂不登录」后原地停留，不做跳转
			api.goLoginWithTip({ stayOnCancel: true });
			return false;
		},

		// 开屏广告（后台「开屏广告」配置；免登录）
		// 带本地缓存：成功则更新缓存（并把图片存到本地），失败时用缓存兜底，确保始终有内容可展示
		async loadSplashAd() {
			const cacheKey = 'splash_ad_cache';
			const shownKey = 'splash_ad_shown_at';
			const COOLDOWN = 30 * 60 * 1000; // 展示冷却：30 分钟内不重复展示（刷新首页/重新进入不重复弹）

			// 仅「同一会话内再次进入首页（刷新）」应用冷却；首次进入不受冷却限制
			if (splashSessionHandled()) {
				let lastShown = 0;
				try { lastShown = uni.getStorageSync(shownKey) || 0; } catch (e) {}
				if (lastShown && (Date.now() - lastShown) < COOLDOWN) return;
			}

			let cached = null;
			try { cached = uni.getStorageSync(cacheKey) || null; } catch (e) {}

			try {
				const res = await api.getSplashAd('app');
				const d = res && res.data;
				if (!d || !(d.image_url || d.image)) {
					// 后台明确无广告/已关闭 → 清缓存，避免展示过期内容
					try { uni.removeStorageSync(cacheKey); } catch (e) {}
					return;
				}
				const config = {
					image: d.image_url || d.image,
					localImage: '',
					url: d.url || '',
					duration: parseInt(d.duration, 10) || 3,
					name: d.site_name || '智慧校园',
					ts: Date.now()
				};
				this.showSplash(config);
				try { uni.setStorageSync(cacheKey, config); } catch (e) {}
				this.cacheSplashImage(config, cacheKey);
			} catch (e) {
				// 请求失败 → 用本地缓存兜底
				if (this.isSplashCacheValid(cached)) {
					this.showSplash(cached);
				}
			}
		},

		// 缓存是否可用（有图片且未过期，TTL 3 天）
		isSplashCacheValid(c) {
			if (!c || !(c.localImage || c.image)) return false;
			const TTL = 3 * 24 * 60 * 60 * 1000;
			if (c.ts && (Date.now() - c.ts) > TTL) return false;
			return true;
		},

		// 展示开屏广告（优先使用本地缓存图片）
		showSplash(config) {
			this.splashImage = config.localImage || config.image;
			this.splashUrl = config.url || '';
			this.splashDuration = parseInt(config.duration, 10) || 3;
			this.splashName = config.name || '智慧校园';
			this.splashVisible = true;
			// 记录展示时间与会话标记，配合冷却期避免同会话重复展示
			try { uni.setStorageSync('splash_ad_shown_at', Date.now()); } catch (e) {}
			markSplashSession();
		},

		// 把开屏图片下载并保存到本地，供离线/加速展示（best effort）
		cacheSplashImage(config, cacheKey) {
			// #ifdef APP-PLUS || MP-WEIXIN
			try {
				uni.downloadFile({
					url: config.image,
					success: (res) => {
						if (!res || res.statusCode !== 200 || !res.tempFilePath) return;
						uni.saveFile({
							tempFilePath: res.tempFilePath,
							success: (s) => {
								if (!s || !s.savedFilePath) return;
								config.localImage = s.savedFilePath;
								try { uni.setStorageSync(cacheKey, config); } catch (e) {}
							}
						});
					}
				});
			} catch (e) {}
			// #endif
		},

		// App 启动后首页加载时自动检查版本更新（仅打包环境；免登录接口）
		checkAppUpdate() {
			// #ifdef APP-PLUS
			const app = getApp();
			if (app) {
				if (app.globalData && app.globalData.appUpdateChecked) return;
				if (!app.globalData) app.globalData = {};
				app.globalData.appUpdateChecked = true;
			}
			try {
				const clientCode = parseInt(plus.runtime.versionCode, 10) || 0;
				let platform = 'all';
				try {
					const p = uni.getSystemInfoSync().platform;
					if (p === 'android') platform = 'android';
					else if (p === 'ios') platform = 'ios';
				} catch (e) {}
				api.getAppVersion({ version_code: clientCode, platform }).then((res) => {
					const d = (res && res.data) || {};
					if (!d.has_update) return;
					// 交给自定义更新弹窗组件展示（头部图片 + 更新日志）
					this.updateInfo = {
						version: d.version || '',
						changelog: d.changelog || '',
						force: parseInt(d.force_update, 10) === 1,
						downloadUrl: d.download_url || '',
						downloadType: d.download_type || 'external',
						downloadSource: d.download_source || 'external',
						downloadPwd: d.download_pwd || '',
						downloadPage: d.download_page || ''
					};
					this.updateVisible = true;
				}).catch(() => {});
			} catch (e) {}
			// #endif
		},

		async loadNotices() {
			try {
				const res = await api.getNotices(1, 3);
				const list = res.data.list.map(item => ({
					...item,
					cover: api.getFileUrl(item.cover)
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
			if (!url) return;
			// 站外链接：H5 直接新窗口打开，小程序端复制链接提示
			if (/^https?:\/\//i.test(url)) {
				// #ifdef H5
				window.open(url, '_blank');
				return;
				// #endif
				uni.setClipboardData({
					data: url,
					success: () => uni.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' }),
					fail: (err) => {
						const msg = (err && err.errMsg) || '';
						if (msg.indexOf('privacy') > -1 || msg.indexOf('auth') > -1) {
							uni.showToast({ title: '未授权，暂时无法复制链接', icon: 'none' });
						} else {
							uni.showToast({ title: '复制失败，请在浏览器中打开链接', icon: 'none' });
						}
					}
				});
				return;
			}
			uni.navigateTo({ url });
		},

		//跳转到今日课表页（教务功能，需登录）
		handleTodaySchedule() {
			if (!this.ensureLoggedIn()) return;
			uni.switchTab({ url: '/pages/today-schedule/index' });
		},

		//跳转到课程表页（教务功能，需登录）
		handleSchedule() {
			if (!this.ensureLoggedIn()) return;
			this.checkFeatureAndNavigate('schedule', '/pages/academic-schedule/index');
		},

		//跳转到成绩查询页（教务功能，需登录）
		handleGrade() {
			if (!this.ensureLoggedIn()) return;
			this.checkFeatureAndNavigate('grade', '/pages/academic-grade/index');
		},

		//跳转到电费查询页（电费功能，需登录）
		handlePowerFee() {
			if (!this.ensureLoggedIn()) return;
			this.checkFeatureAndNavigate('power_fee', '/pages/power-fee/index');
		},

		//跳转到用电统计页（电费功能，需登录）
		handlePowerStatistics() {
			if (!this.ensureLoggedIn()) return;
			this.checkFeatureAndNavigate('power_statistics', '/pages/power-statistics/index');
		},

		//跳转到宿舍绑定页（电费功能，需登录）
		handleDormitoryBind() {
			if (!this.ensureLoggedIn()) return;
			uni.navigateTo({ url: '/pages/dormitory-bind/index' });
		},

		//跳转到学生信息页（教务功能，需登录）
		handleStudentInfo() {
			if (!this.ensureLoggedIn()) return;
			uni.navigateTo({ url: '/pages/academic-info/index' });
		},

		//跳转到推送设置页（电费功能，需登录）
		handlePushSettings() {
			if (!this.ensureLoggedIn()) return;
			uni.navigateTo({
				url: '/pages/push-settings/index'
			});
		},

		// 课程/成绩推送设置共用同一页面，通过 scene 参数让目标页按入口展示标题
		// （首页入口不传 scene → 显示「学术推送设置」）
		handleAcademicPushSettings(scene) {
			if (!this.ensureLoggedIn()) return;
			uni.navigateTo({
				url: '/pages/academic-push-settings/index' + (scene ? '?scene=' + scene : '')
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

/* 今日日期 + 当前教学周（古诗词下方） */
.background-subject-meta {
	display: flex;
	align-items: center;
	margin-top: 22rpx;
}

/* 今日日期：纯文字（M月D日 周X） */
.background-subject-date {
	font-size: 24rpx;
	opacity: 0.85;
	letter-spacing: 1rpx;
}

/* 当前教学周：胶囊标签，与日期分开 */
.background-subject-week {
	margin-left: 14rpx;
	padding: 4rpx 18rpx;
	border-radius: 100rpx;
	font-size: 22rpx;
	line-height: 1.7;
	color: #fff;
	background: rgba(255, 255, 255, 0.18);
	border: 1rpx solid rgba(255, 255, 255, 0.35);
	letter-spacing: 1rpx;
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

/* 右下角浮动刷新按钮（独立样式：纯白底 + 黑色图标） */
.home-refresh-fab {
	position: fixed;
	right: 28rpx;
	bottom: 160rpx;
	width: 104rpx;
	height: 104rpx;
	border-radius: 50%;
	background: #ffffff;
	border: 1rpx solid rgba(0, 0, 0, 0.06);
	box-shadow: 0 10rpx 28rpx rgba(0, 0, 0, 0.14);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 90;
}

.home-refresh-fab:active {
	transform: scale(0.92);
}

.home-refresh-icon {
	width: 46rpx;
	height: 46rpx;
}

.home-refresh-fab-loading .home-refresh-icon {
	animation: home-refresh-spin 0.8s linear infinite;
}

@keyframes home-refresh-spin {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
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
