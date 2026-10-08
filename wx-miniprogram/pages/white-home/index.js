/**
 * 首页
 * 展示通知列表、快捷入口和订阅状态
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { sceneTypeNames } = require('../../utils/common.js');

const __tab__ = 0;  // 当前TabBar索引

// ===== 开屏广告会话标记 =====
// 用于区分「首次进入」与「首页刷新」：内存标记，冷启动重置。
// 首次进入不受冷却限制，仅同会话内再次进入才受冷却。
function splashSessionHandled() {
    try {
        const app = getApp();
        return !!(app && app.globalData && app.globalData.__splashSession);
    } catch (e) {
        return false;
    }
}

function markSplashSession() {
    try {
        const app = getApp();
        if (app) {
            app.globalData = app.globalData || {};
            app.globalData.__splashSession = true;
        }
    } catch (e) {}
}

Page({
    data: {
        isLoggedIn: false,      // 是否已登录
        apiUserInfo: null,      // 用户信息
        notices: [],            // 通知列表
        lowSubscriptions: [],   // 订阅数不足的列表
        swiperList: [           // 轮播图数据
            { id: 2, image: '/assets/banner/banner1.jpg', url: '' },
            { id: 3, image: '/assets/banner/banner2.jpg', url: '/pages/academic-push-settings/index' },
            { id: 4, image: '/assets/banner/banner3.jpg', url: '/pages/academic-grade/index' }
        ],
        nextCourse: null,       // 下一节课信息
        allCoursesEnded: false,  // 今日课程是否已全部结束
        courseLoadError: false,  // 课程数据加载是否失败
        currentWeek: 0,          // 当前教学周（0=未取到，不展示）
        powerInfo: null,        // 电费信息
        noDormitory: false,     // 是否未绑定宿舍
        pageRefreshing: false,  // 整个首页是否正在刷新
        randomPoem: '',          // 随机古诗词一言
        todayText: '',           // 今日日期（M月D日）
        _dataLoaded: false,      // 数据是否已加载（避免重复加载）
        featureSettings: {},     // 功能开关设置
        indexAnnouncement: null, // 首页公告
        splashVisible: false,    // 开屏广告是否展示
        splashImage: '',         // 开屏广告图片
        splashUrl: '',           // 开屏广告跳转链接
        splashCountdown: 3,      // 开屏广告倒计时（秒）
        splashName: '智慧校园'    // 开屏底部应用名（取后台站点名）
    },

    // 古诗词一言列表
    poems: [
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
    ],

    //页面加载
    onLoad(options) {
        // 仅做同步的状态检查，不阻塞页面渲染
        this.checkLoginStatus();
        this.loadRandomPoem();
        this.setData({ todayText: this.getTodayText() });
        this.loadFeatureSettings();
        this.loadBanners();
        // 开屏广告（后台可配置，免登录）
        this.loadSplashAd();

        // 合规要求：首页免登录即可浏览（轮播、公告、通知等），不再强制跳转登录页
        // 需要账号的功能在各自页面入口处提示登录，登录入口保留在「我的」页
    },

    //加载功能开关设置
    async loadFeatureSettings() {
        try {
            const settings = await feature.getFeatureSettings();
            if (settings) {
                this.setData({ featureSettings: settings });
            }

            const indexResult = await feature.checkFeature('index');
            if (indexResult.announcement) {
                this.setData({ indexAnnouncement: indexResult.announcement });
            }
        } catch (err) {
            console.error('加载功能设置失败', err);
        }
    },

    //检查功能并跳转
    async checkFeatureAndNavigate(featureKey, url) {
        const result = await feature.checkFeature(featureKey);
        if (!result.enabled) {
            feature.showDisabledAlert(result.featureName, result.announcement);
            return;
        }
        wx.navigateTo({ url });
    },

    //获取随机古诗词一言
    loadRandomPoem() {
        const poems = this.poems;
        const randomIndex = Math.floor(Math.random() * poems.length);
        this.setData({
            randomPoem: poems[randomIndex]
        });
    },

    //今日日期文案（M月D日 周X），与教学周一起展示在古诗词下方
    getTodayText() {
        const d = new Date();
        const weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
        return `${d.getMonth() + 1}月${d.getDate()}日 ${weekdays[d.getDay()]}`;
    },

    //加载今日速览数据（下一节课 / 电费余额）
    // options.silent = true 表示后台静默刷新（登录成功回跳首页、点击首页 Tab 时使用）：
    // 失败时保留原有展示，避免数据被清空造成闪空
    async loadGlanceData(options) {
        const silent = !!(options && options.silent);
        if (!api.isLoggedIn()) return;
        try {
            const res = await api.getGlanceData();
            const data = res.data || {};
            this.setData({
                nextCourse: data.next_course || null,
                allCoursesEnded: data.all_courses_ended || false,
                courseLoadError: data.course_load_error || false,
                currentWeek: parseInt(data.current_week, 10) || 0,
                powerInfo: data.power_info || null,
                noDormitory: data.no_dormitory || false
            });
        } catch (err) {
            console.error('加载速览数据失败', err);
            if (silent) return;
            this.setData({
                nextCourse: null,
                allCoursesEnded: false,
                courseLoadError: true,
                currentWeek: 0,
                powerInfo: null,
                noDormitory: false
            });
        }
    },

    //浮动按钮：刷新整个首页数据（公告/功能开关、轮播图、通知公告、速览卡片、订阅提醒）
    async handlePageRefresh() {
        if (this.data.pageRefreshing) return;
        this.setData({ pageRefreshing: true });
        try {
            await Promise.all([
                this.loadFeatureSettings(),
                this.loadBanners(),
                this.loadNotices(),
                this.loadGlanceData(),
                this.loadLowSubscriptions()
            ]);
            wx.showToast({ title: '已刷新', icon: 'none' });
        } finally {
            this.setData({ pageRefreshing: false });
        }
    },

    //检查登录状态
    checkLoginStatus() {
        const isLoggedIn = api.isLoggedIn();
        const apiUserInfo = api.getUserInfoCache();
        this.setData({ isLoggedIn, apiUserInfo });
    },

    //教务/电费等功能需要登录：未登录时弹出提示并阻止跳转，已登录返回 true
    ensureLoggedIn() {
        if (api.isLoggedIn()) return true;
        // 首页本身可免登录浏览：选择「暂不登录」后原地停留，不做跳转
        api.goLoginWithTip({ stayOnCancel: true });
        return false;
    },

    //加载通知列表
    async loadNotices() {
        try {
            const res = await api.getNotices(1, 3);
            const list = res.data.list.map(item => ({
                ...item,
                cover: api.getFileUrl(item.cover)
            }));
            this.setData({ notices: list });
        } catch (err) {
            console.error('加载通知失败', err);
        }
    },

    //加载订阅数不足的订阅
    async loadLowSubscriptions() {
        // 未登录则不加载
        if (!api.isLoggedIn()) return;

        try {
            const res = await api.request({
                act: 'get_user_subscriptions',
                method: 'GET'
            });

            // 筛选订阅数小于2的
            const lowSubscriptions = (res.data.list || [])
                .filter(item => item.subscribe_count < 2)
                .map(item => ({
                    ...item,
                    sceneName: sceneTypeNames[item.scene_type] || '通知'
                }));

            this.setData({ lowSubscriptions });
        } catch (err) {
            console.error('加载订阅数据失败', err);
        }
    },

    //页面显示
    onShow() {
        // 更新TabBar状态
        if (typeof this.getTabBar === 'function' && this.getTabBar() && __tab__ !== -1) {
            this.getTabBar().switchTabByIndex(__tab__);
        }

        // 同步底部「AI 对话」Tab 文案（后台未启用小程序端 AI 功能时显示「用电统计」）
        // 每次显示都同步：返回本页时若文案被重置（如开屏结束调用 showTabBar），这里会重新纠正
        feature.syncAiTabBar();

        // 检查登录状态
        this.checkLoginStatus();

        // 刷新今日日期（跨天后回到页面也能更新）
        this.setData({ todayText: this.getTodayText() });

        // 首次显示：完整加载（避免重复加载）
        if (!this.data._dataLoaded) {
            this.setData({ _dataLoaded: true });
            this.loadGlanceData();
            this.loadLowSubscriptions();
            this.loadNotices();
        } else {
            // 再次显示（登录成功回跳首页 / 从其它页面返回）：异步刷新课程与电费信息，
            // 不展示 loading、失败也不清空，避免闪烁
            this.loadGlanceData({ silent: true });
        }
    },

    //点击底部「首页」导航（含重复点击当前 Tab）：异步刷新课程与电费信息
    onTabItemTap() {
        this.loadGlanceData({ silent: true });
    },

    //跳转到订阅管理页
    handleSubscription() {
        wx.navigateTo({
            url: '/pages/subscription/index',
        });
    },

    //跳转到通知列表页
    handleNotices() {
        wx.navigateTo({
            url: '/pages/black-notice/index',
        });
    },

    //跳转到通知详情页
    handleNotice(e) {
        const id = e.currentTarget.dataset.id;
        wx.navigateTo({
            url: '/pages/black-notice-info/index?id=' + id,
        });
    },

    //显示开发中提示
    handleComingSoon() {
        wx.showToast({
            title: '功能开发中',
            icon: 'none'
        });
    },

    //加载后台「轮播图配置」，失败或为空时保留内置默认轮播图
    async loadBanners() {
        try {
            const res = await api.getBanners();
            const list = (res.data || []).map(item => ({
                id: item.id,
                image: item.image_url || item.image,
                // 小程序端读取 url_wx，为空回退通用 url
                url: item.url_wx || item.url || ''
            }));
            if (list.length > 0) {
                this.setData({ swiperList: list });
            }
        } catch (err) {
            console.warn('加载轮播图配置失败，使用默认轮播图', err);
        }
    },

    //空方法：供开屏遮罩 catch:touchmove 阻止滚动穿透
    noop() {},

    //开屏广告：带本地缓存，成功则更新缓存（并把图片存到本地），失败时用缓存兜底，确保始终有内容可展示
    loadSplashAd() {
        const cacheKey = 'splash_ad_cache';
        const shownKey = 'splash_ad_shown_at';
        const COOLDOWN = 30 * 60 * 1000; // 展示冷却：30 分钟内不重复展示（刷新首页/重新进入不重复弹）

        // 仅「同一会话内再次进入首页（刷新）」应用冷却；首次进入不受冷却限制
        if (splashSessionHandled()) {
            let lastShown = 0;
            try { lastShown = wx.getStorageSync(shownKey) || 0; } catch (e) {}
            if (lastShown && (Date.now() - lastShown) < COOLDOWN) return;
        }

        let cached = null;
        try { cached = wx.getStorageSync(cacheKey) || null; } catch (e) {}

        api.getSplashAd('wx').then(res => {
            const d = res && res.data;
            if (!d || !(d.image_url || d.image)) {
                // 后台明确无广告/已关闭 → 清缓存，避免展示过期内容
                try { wx.removeStorageSync(cacheKey); } catch (e) {}
                return;
            }
            let sec = parseInt(d.duration, 10);
            if (isNaN(sec) || sec < 1) sec = 3;
            const config = {
                image: d.image_url || d.image,
                localImage: '',
                url: d.url || '',
                duration: sec,
                name: d.site_name || '智慧校园',
                ts: Date.now()
            };
            this.showSplash(config);
            try { wx.setStorageSync(cacheKey, config); } catch (e) {}
            this.cacheSplashImage(config, cacheKey);
        }).catch(() => {
            // 请求失败 → 用本地缓存兜底
            if (this.isSplashCacheValid(cached)) this.showSplash(cached);
        });
    },

    //缓存是否可用（有图片且未过期，TTL 3 天）
    isSplashCacheValid(c) {
        if (!c || !(c.localImage || c.image)) return false;
        const TTL = 3 * 24 * 60 * 60 * 1000;
        if (c.ts && (Date.now() - c.ts) > TTL) return false;
        return true;
    },

    //展示开屏广告（优先使用本地缓存图片）
    showSplash(config) {
        let sec = parseInt(config.duration, 10);
        if (isNaN(sec) || sec < 1) sec = 3;
        // 先隐藏原生底部导航栏（原生 tabBar 在页面之上、覆盖不到），再展示开屏
        try { wx.hideTabBar({ animation: false, fail: () => {} }); } catch (e) {}
        this.setData({
            splashVisible: true,
            splashImage: config.localImage || config.image,
            splashUrl: config.url || '',
            splashCountdown: sec,
            splashName: config.name || '智慧校园'
        });
        // 记录展示时间与会话标记，配合冷却期避免同会话重复展示
        try { wx.setStorageSync('splash_ad_shown_at', Date.now()); } catch (e) {}
        markSplashSession();
        this.startSplashCountdown(sec);
    },

    //把开屏图片下载并保存到本地，供离线/加速展示（best effort）
    cacheSplashImage(config, cacheKey) {
        try {
            wx.downloadFile({
                url: config.image,
                success: (res) => {
                    if (!res || res.statusCode !== 200 || !res.tempFilePath) return;
                    wx.saveFile({
                        tempFilePath: res.tempFilePath,
                        success: (s) => {
                            if (!s || !s.savedFilePath) return;
                            config.localImage = s.savedFilePath;
                            try { wx.setStorageSync(cacheKey, config); } catch (e) {}
                        }
                    });
                }
            });
        } catch (e) {}
    },

    //开屏广告倒计时
    startSplashCountdown(sec) {
        this.clearSplashTimer();
        this.splashTimer = setInterval(() => {
            const n = (this.data.splashCountdown || 0) - 1;
            if (n <= 0) {
                this.closeSplash();
            } else {
                this.setData({ splashCountdown: n });
            }
        }, 1000);
    },

    //清除开屏广告倒计时
    clearSplashTimer() {
        if (this.splashTimer) {
            clearInterval(this.splashTimer);
            this.splashTimer = null;
        }
    },

    //关闭开屏广告
    closeSplash() {
        this.clearSplashTimer();
        this.setData({ splashVisible: false });
        // 恢复原生底部导航栏
        try { wx.showTabBar({ animation: false, fail: () => {} }); } catch (e) {}
        // showTabBar 会把 Tab 文案重置回 app.json 的配置，这里按已知状态重放一次
        feature.reapplyAiTabText();
    },

    //点击开屏广告
    handleSplashTap() {
        const url = this.data.splashUrl;
        this.closeSplash();
        if (!url) return;
        // 站外链接小程序无法直接打开，复制链接提示
        if (/^https?:\/\//i.test(url)) {
            wx.setClipboardData({
                data: url,
                success: () => wx.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' }),
                fail: (err) => {
                    const msg = (err && err.errMsg) || '';
                    // 隐私接口未授权：提示用户授权，其余情况提示手动打开
                    if (msg.indexOf('privacy') > -1 || msg.indexOf('auth') > -1) {
                        wx.showToast({ title: '未授权，暂时无法复制链接', icon: 'none' });
                    } else {
                        wx.showToast({ title: '复制失败，请在浏览器中打开链接', icon: 'none' });
                    }
                }
            });
            return;
        }
        wx.navigateTo({
            url,
            fail: () => wx.switchTab({ url })
        });
    },

    //页面卸载
    onUnload() {
        this.clearSplashTimer();
        // 兜底：确保底部导航栏处于显示状态
        try { wx.showTabBar({ animation: false, fail: () => {} }); } catch (e) {}
        // 同上：showTabBar 会重置文案，按已知状态重放
        feature.reapplyAiTabText();
    },

    //轮播图点击
    handleSwiperTap(e) {
        const url = e.currentTarget.dataset.url;
        if (!url) return;
        // 站外链接（http开头）小程序无法直接打开，复制链接提示
        if (/^https?:\/\//i.test(url)) {
            wx.setClipboardData({
                data: url,
                success: () => wx.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' }),
                fail: (err) => {
                    const msg = (err && err.errMsg) || '';
                    // 隐私接口未授权：提示用户授权，其余情况提示手动打开
                    if (msg.indexOf('privacy') > -1 || msg.indexOf('auth') > -1) {
                        wx.showToast({ title: '未授权，暂时无法复制链接', icon: 'none' });
                    } else {
                        wx.showToast({ title: '复制失败，请在浏览器中打开链接', icon: 'none' });
                    }
                }
            });
            return;
        }
        wx.navigateTo({ url });
    },

    //跳转到今日课表页（教务功能，需登录）
    handleTodaySchedule() {
        if (!this.ensureLoggedIn()) return;
        wx.switchTab({ url: '/pages/today-schedule/index' });
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
        wx.navigateTo({ url: '/pages/dormitory-bind/index' });
    },

    //跳转到学生信息页（教务功能，需登录）
    handleStudentInfo() {
        if (!this.ensureLoggedIn()) return;
        wx.navigateTo({ url: '/pages/academic-info/index' });
    },

    //跳转到推送设置页（电费功能，需登录）
    handlePushSettings() {
        if (!this.ensureLoggedIn()) return;
        wx.navigateTo({
            url: '/pages/push-settings/index',
        });
    },

    // 课程/成绩推送设置共用同一页面，通过 scene 参数让目标页按入口展示标题
    // （首页入口不带 data-scene → 显示「学术推送设置」）
    handleAcademicPushSettings(e) {
        if (!this.ensureLoggedIn()) return;
        const scene = (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.scene) || '';
        wx.navigateTo({
            url: '/pages/academic-push-settings/index' + (scene ? '?scene=' + scene : ''),
        });
    },

    //下拉刷新
    onPullDownRefresh() {
        this.loadNotices();
        this.loadGlanceData();
        wx.stopPullDownRefresh();
    }
})
