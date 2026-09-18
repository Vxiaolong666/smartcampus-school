/**
 * 首页
 * 展示通知列表、快捷入口和订阅状态
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { sceneTypeNames } = require('../../utils/common.js');

const __tab__ = 0;  // 当前TabBar索引

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
        powerInfo: null,        // 电费信息
        noDormitory: false,     // 是否未绑定宿舍
        randomPoem: '',          // 随机古诗词一言
        _dataLoaded: false,      // 数据是否已加载（避免重复加载）
        featureSettings: {},     // 功能开关设置
        indexAnnouncement: null  // 首页公告
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
        this.loadFeatureSettings();
        this.loadBanners();
        
        // 未登录则跳转到登录页（使用 redirectTo 避免页面栈累积）
        if (!api.isLoggedIn()) {
            wx.redirectTo({
                url: '/pages/login/index',
            });
            return;
        }
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

    //加载今日速览数据
    async loadGlanceData() {
        if (!api.isLoggedIn()) return;
        try {
            const res = await api.getGlanceData();
            const data = res.data || {};
            this.setData({
                nextCourse: data.next_course || null,
                allCoursesEnded: data.all_courses_ended || false,
                courseLoadError: data.course_load_error || false,
                powerInfo: data.power_info || null,
                noDormitory: data.no_dormitory || false
            });
        } catch (err) {
            console.error('加载速览数据失败', err);
            this.setData({
                nextCourse: null,
                allCoursesEnded: false,
                courseLoadError: true,
                powerInfo: null,
                noDormitory: false
            });
        }
    },

    //检查登录状态
    checkLoginStatus() {
        const isLoggedIn = api.isLoggedIn();
        const apiUserInfo = api.getUserInfoCache();
        this.setData({ isLoggedIn, apiUserInfo });
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

        // 检查登录状态
        this.checkLoginStatus();

        // 仅首次显示时加载数据（避免重复加载）
        if (!this.data._dataLoaded) {
            this.setData({ _dataLoaded: true });
            this.loadGlanceData();
            this.loadLowSubscriptions();
            this.loadNotices();
        }
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
                url: item.url || ''
            }));
            if (list.length > 0) {
                this.setData({ swiperList: list });
            }
        } catch (err) {
            console.warn('加载轮播图配置失败，使用默认轮播图', err);
        }
    },

    //轮播图点击
    handleSwiperTap(e) {
        const url = e.currentTarget.dataset.url;
        if (!url) return;
        // 站外链接（http开头）小程序无法直接打开，复制链接提示
        if (/^https?:\/\//i.test(url)) {
            wx.setClipboardData({
                data: url,
                success: () => wx.showToast({ title: '链接已复制，请在浏览器打开', icon: 'none' })
            });
            return;
        }
        wx.navigateTo({ url });
    },

    //跳转到今日课表页
    handleTodaySchedule() {
        wx.switchTab({ url: '/pages/today-schedule/index' });
    },

    //跳转到课程表页
    handleSchedule() {
        this.checkFeatureAndNavigate('schedule', '/pages/academic-schedule/index');
    },

    //跳转到成绩查询页
    handleGrade() {
        this.checkFeatureAndNavigate('grade', '/pages/academic-grade/index');
    },

    //跳转到电费查询页
    handlePowerFee() {
        this.checkFeatureAndNavigate('power_fee', '/pages/power-fee/index');
    },

    //跳转到用电统计页
    handlePowerStatistics() {
        this.checkFeatureAndNavigate('power_statistics', '/pages/power-statistics/index');
    },

    //跳转到宿舍绑定页
    handleDormitoryBind() {
        wx.navigateTo({ url: '/pages/dormitory-bind/index' });
    },

    //跳转到学生信息页
    handleStudentInfo() {
        wx.navigateTo({ url: '/pages/academic-info/index' });
    },

    //跳转到推送设置页
    handlePushSettings() {
        wx.navigateTo({
            url: '/pages/push-settings/index',
        });
    },

    handleAcademicPushSettings() {
        wx.navigateTo({
            url: '/pages/academic-push-settings/index',
        });
    },

    //下拉刷新
    onPullDownRefresh() {
        this.loadNotices();
        this.loadGlanceData();
        wx.stopPullDownRefresh();
    }
})
