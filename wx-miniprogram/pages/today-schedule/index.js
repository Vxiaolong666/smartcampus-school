/**
 * 今日课程页面
 * 展示当天课程安排和实时上课状态
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { getCourseColor, timeSlots, weekDays } = require('../../utils/common.js');
const { loadScheduleTimes } = require('../../utils/schedule-time.js');
const markdownToHtml = require('../../utils/markdown.js');

const FULL_DAY_NAMES = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

// 课程详情弹窗图标 path 数据（与 assets 下 SVG 图标一致，fill 颜色按课程色动态生成）
const ICON_CLOCK_PATHS = [
    'M512 96C282.2 96 96 282.2 96 512s186.2 416 416 416 416-186.2 416-416S741.8 96 512 96z m0 752c-185.6 0-336-150.4-336-336S326.4 176 512 176s336 150.4 336 336-150.4 336-336 336z',
    'M512 248c-4.4 0-8 3.6-8 8v256c0 4.4 3.6 8 8 8h200c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H568V256c0-4.4-3.6-8-8-8h-48z'
];
const ICON_LOCATION_PATHS = [
    'M512 64C323.5 64 168 219.5 168 408c0 193.5 184.5 412.5 302.5 540.5 23 25 62 25 85 0C673.5 820.5 858 601.5 858 408 858 219.5 700.5 64 512 64z m0 448c-70.7 0-128-57.3-128-128s57.3-128 128-128 128 57.3 128 128-57.3 128-128 128z'
];
const ICON_USER_PATHS = [
    'M512 512c123.8 0 224-100.2 224-224S635.8 64 512 64 288 164.2 288 288s100.2 224 224 224z m0-352c70.7 0 128 57.3 128 128s-57.3 128-128 128-128-57.3-128-128 57.3-128 128-128z',
    'M858.5 764.4C781.8 656.9 654.5 592 512 592s-269.8 64.9-346.5 172.4c-10.3 14.8-6.1 35.2 8.7 45.5 14.8 10.3 35.2 6.1 45.5-8.7C281.6 714.3 389.8 664 512 664s230.4 50.3 292.3 137.2c6.3 9.1 16.4 14 26.6 14 6.4 0 12.9-1.9 18.6-5.9 14.8-10.3 18.9-30.7 8.6-45.5z'
];
const ICON_CALENDAR_PATHS = [
    'M880 184H712v-64c0-4.4-3.6-8-8-8h-56c-4.4 0-8 3.6-8 8v64H384v-64c0-4.4-3.6-8-8-8h-56c-4.4 0-8 3.6-8 8v64H144c-17.7 0-32 14.3-32 32v664c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V216c0-17.7-14.3-32-32-32z m-40 600H184V272h128v48c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8v-48h256v48c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8v-48h128v512z',
    'M424 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
    'M552 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
    'M680 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
    'M424 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
    'M552 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
    'M680 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z'
];

Page({
    data: {
        loading: true,              // 加载状态
        needBind: false,            // 是否未绑定教务账号
        featureEnabled: true,       // 功能是否启用
        announcement: null,         // 公告信息
        currentWeek: 1,             // 当前周次
        selectedDay: 0,             // 选中的星期（0=周日, 1=周一, ..., 6=周六）
        todayDayOfWeek: 0,          // 今天是星期几（0=周日）
        weekDates: [],              // 本周日期列表
        allCourses: [],             // 所有课程（全周）
        dayCourses: [],             // 当天课程
        morningCourses: [],         // 上午课程（按后台作息时间归类）
        afternoonCourses: [],       // 下午课程（按后台作息时间归类）
        eveningCourses: [],         // 晚上课程（按后台作息时间归类）
        currentTime: '',            // 当前时间（HH:mm格式）
        currentSection: null,       // 当前所在节次（1-11，null表示不在上课时间）
        currentCourse: null,        // 当前正在上的课程
        nextCourse: null,           // 下一节课程
        inBreak: false,             // 是否在课间（已下课但下节课未开始）
        minutesToEnd: 0,            // 距离当前课程下课还有多少分钟
        minutesToNext: 0,           // 距离下节课开始还有多少分钟
        allCoursesEnded: false,     // 当天所有课程是否已结束
        notStarted: false,          // 当天课程是否还未开始
        isEndingSoon: false,        // 当前课程是否即将下课（剩余时间≤20%）
        showDetail: false,          // 课程详情弹窗显示状态
        detailCourse: null,         // 当前查看详情的课程对象
        showAiResult: false,        // AI 分析结果浮层
        aiResult: '',               // AI 分析结果（Markdown）
        aiResultHtml: '',           // 渲染用 HTML
        aiLoading: false,           // 是否正在分析
        aiCache: {},                // 分析结果缓存（按选中日）
        windowHeight: 0,
        sheetHeight: 0,             // 浮层高度（视口 80%）
        detailIcons: null,          // 弹窗图标 data URI（按课程色生成）
        detailBtnBgLight: '#36cfc9',  // 弹窗按钮渐变终点色
        detailBtnShadow: 'rgba(19, 194, 194, 0.35)'  // 弹窗按钮投影色
    },

    //页面加载
    onLoad() {
        // 页面初始化统一放在 onShow：tab 页只加载一次，未登录提示必须每次进入都执行
    },

    //页面显示（含从其它 Tab / 页面切回）：未登录每次都提示，已登录仅首次初始化
    onShow() {
        // 未登录：每次切到本页都提示。
        // tab 页的 onLoad 只会执行一次，若只在 onLoad 提示，第二次点「课程」Tab 就不会再提示。
        if (!api.isLoggedIn()) {
            api.goLoginWithTip({ mode: 'redirect' });
            return;
        }

        // 已登录：已初始化则只刷新一次时间（避免重复请求）
        if (this.pageInited) {
            this.updateTime();
            return;
        }
        this.pageInited = true;

        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (!canProceed) return;

            // 获取今天的日期对象
            const today = new Date();
            // 获取本周日期列表
            const weekDates = this.getWeekDates();

            // 初始化页面数据：今天星期几、选中今天、本周日期
            this.setData({
                todayDayOfWeek: today.getDay(),
                selectedDay: today.getDay(),
                weekDates
            });

            // 先用内置作息即时渲染，再拉后台「课程时间配置」，最后按后台作息查询并归类课程。
            // 节次归属哪个时段由作息时间决定：后台「下午」可能不止 5~7 节，
            // 不加载后台作息会把「下午最后一节」按内置默认时间误判成「晚上第一节」。
            this.updateTime();

            loadScheduleTimes().then(() => {
                this.loadSchedule();
                this.updateTime();
            });

            // 每秒更新时间，实时追踪上课状态
            this.timeInterval = setInterval(() => this.updateTime(), 1000);
        });
    },

    //检查功能开关
    async checkFeature() {
        try {
            const result = await feature.checkFeature('schedule');
            if (!result.enabled) {
                this.setData({
                    featureEnabled: false,
                    announcement: null
                });
                feature.showDisabledAlert(result.featureName, result.announcement);
                return false;
            }
            
            if (result.announcement) {
                this.setData({ announcement: result.announcement });
            }
            return true;
        } catch (err) {
            console.error('检查功能开关失败', err);
            return true;
        }
    },

    //页面卸载
    onUnload() {
        // 清除定时器，避免内存泄漏
        if (this.timeInterval) clearInterval(this.timeInterval);
    },

    //点击底部「课程」导航（含重复点击当前 Tab）：异步刷新课程表，不显示 loading
    onTabItemTap() {
        if (!api.isLoggedIn()) return;
        // 首次进入尚未初始化时交给 onShow 处理，避免重复/未初始化请求
        if (!this.pageInited) return;
        this.loadSchedule({ silent: true });
        this.updateTime();
    },

    //获取本周日期列表
    getWeekDates() {
        const today = new Date();
        // 计算本周日的日期（作为一周的起始）
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay());

        // 生成周日到周六共7天的日期信息
        return Array.from({ length: 7 }, (_, i) => {
            const date = new Date(weekStart);
            date.setDate(weekStart.getDate() + i);
            return {
                date: date.getDate(),     // 日期（几号）
                full: `${date.getFullYear()}年${String(date.getMonth() + 1).padStart(2, '0')}月${String(date.getDate()).padStart(2, '0')}日`,  // 完整日期文本
                dateStr: date.toISOString().split('T')[0]   // ISO日期字符串（YYYY-MM-DD）
            };
        });
    },

    //更新当前时间和节次
    updateTime() {
        const now = new Date();
        // 格式化当前时间为HH:mm
        const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        let currentSection = null;

        // 节次按后台作息动态取（数量可能不是 11 节），避免漏判或取值报错
        const sectionKeys = Object.keys(timeSlots)
            .map(key => parseInt(key, 10))
            .filter(n => n > 0)
            .sort((a, b) => a - b);
        for (const i of sectionKeys) {
            const slot = timeSlots[i];
            if (!slot || !slot.time) continue;
            const [start, end] = slot.time.split('-');
            // 当前时间在某个节次的开始和结束时间之间
            if (start && end && currentTime >= start && currentTime <= end) {
                currentSection = i;
                break;
            }
        }

        // 更新时间和节次，回调中更新课程状态
        this.setData({ currentTime, currentSection }, () => this.updateCourseStatus());
    },

    //时间字符串转分钟数（用于时间差计算）
    timeToMinutes(timeStr) {
        const [h, m] = timeStr.split(':');
        return parseInt(h) * 60 + parseInt(m);
    },

    //按节次时间判断所属时段（morning / afternoon / evening）
    //不再写死节次边界：后台作息中「下午」可能不止 5~7 节，
    //写死「>=8 归晚上」会把下午最后一节误分到晚上
    sectionSession(section) {
        const slot = timeSlots[section];
        const start = (slot && slot.time) ? String(slot.time).split('-')[0] : '';
        if (start) {
            const m = this.timeToMinutes(start);
            if (!isNaN(m)) {
                if (m < 12 * 60) return 'morning';
                if (m < 18 * 60) return 'afternoon';
                return 'evening';
            }
        }
        //取不到时间时回退到节次边界（兼容内置默认作息）
        if (section <= 4) return 'morning';
        if (section <= 7) return 'afternoon';
        return 'evening';
    },

    //更新课程状态（当前课/下一节/课间等）
    updateCourseStatus() {
        const { dayCourses, currentSection, selectedDay, todayDayOfWeek, currentTime } = this.data;
        // 判断选中的是否为今天
        const isToday = selectedDay === todayDayOfWeek;

        // 不是今天或没有课程，清空所有状态
        if (!isToday || dayCourses.length === 0) {
            this.setData({
                currentCourse: null, nextCourse: null, inBreak: false,
                minutesToEnd: 0, minutesToNext: 0, allCoursesEnded: false, notStarted: false, isEndingSoon: false
            });
            return;
        }

        // 当前时间转为分钟数，便于计算时间差
        const currentMinutes = this.timeToMinutes(currentTime);
        let currentCourse = null, nextCourse = null, minutesToEnd = 0, minutesToNext = 0;
        let allCoursesEnded = false, notStarted = false, isEndingSoon = false;

        // 为每门课程计算是否已结束（当前时间 > 课程结束时间）
        const updatedCourses = dayCourses.map(course => {
            if (course.slots?.length > 0) {
                // 取课程最后一个节次的结束时间
                const lastSlot = Math.max(...course.slots);
                const endTime = timeSlots[lastSlot]?.time.split('-')[1] || '';
                return { ...course, isEnded: currentMinutes > this.timeToMinutes(endTime) };
            }
            return { ...course, isEnded: false };
        });

        // 查找当前正在上的课程：当前节次在课程的slots数组中
        if (currentSection) {
            currentCourse = updatedCourses.find(c => c.slots?.includes(currentSection));
        }

        if (currentCourse) {
            // 有当前课程，计算距离下课的时间
            const lastSlot = Math.max(...currentCourse.slots);
            const endTime = timeSlots[lastSlot]?.time.split('-')[1] || '';
            const firstSlot = Math.min(...currentCourse.slots);
            const startTime = timeSlots[firstSlot]?.time.split('-')[0] || '';
            // 距下课剩余分钟数
            minutesToEnd = Math.max(0, this.timeToMinutes(endTime) - currentMinutes);
            // 课程总时长（分钟）
            const totalDuration = this.timeToMinutes(endTime) - this.timeToMinutes(startTime);
            // 剩余时间≤总时长的20%时标记为即将下课
            isEndingSoon = totalDuration > 0 && minutesToEnd <= totalDuration * 0.2;
        } else {
            // 没有当前课程，查找下一节课
            const firstCourse = updatedCourses[0];
            // 第一节课的开始时间（分钟数）
            const firstStartMinutes = this.timeToMinutes(timeSlots[Math.min(...firstCourse.slots)]?.time.split('-')[0] || '23:59');

            if (currentMinutes < firstStartMinutes) {
                // 当前时间在第一节课之前，课程还未开始
                notStarted = true;
                nextCourse = firstCourse;
                minutesToNext = firstStartMinutes - currentMinutes;
            } else {
                // 当前时间已过第一节课开始时间，查找最近的一节未开始的课
                for (const course of updatedCourses) {
                    if (course.slots?.length > 0) {
                        const startMinutes = this.timeToMinutes(timeSlots[Math.min(...course.slots)]?.time.split('-')[0] || '23:59');
                        // 找开始时间晚于当前时间且最早的课程
                        if (startMinutes > currentMinutes && (!nextCourse || startMinutes < this.timeToMinutes(timeSlots[Math.min(...nextCourse.slots)]?.time.split('-')[0] || '23:59'))) {
                            nextCourse = course;
                            minutesToNext = startMinutes - currentMinutes;
                        }
                    }
                }

                // 没找到下一节课，检查是否所有课程都已结束
                if (!nextCourse && updatedCourses.length > 0) {
                    const lastCourse = updatedCourses[updatedCourses.length - 1];
                    const endTime = timeSlots[Math.max(...lastCourse.slots)]?.time.split('-')[1] || '';
                    // 当前时间超过最后一节课的结束时间
                    allCoursesEnded = currentMinutes > this.timeToMinutes(endTime);
                }
            }
        }

        // 更新所有状态：inBreak = 没有当前课但有下一节课且不是未开始状态
        this.setData({
            dayCourses: updatedCourses, currentCourse, nextCourse,
            inBreak: !currentCourse && !!nextCourse && !notStarted,
            minutesToEnd, minutesToNext, allCoursesEnded, notStarted, isEndingSoon
        });
    },

    //加载课程表
    // options.silent = true 表示点击 Tab 静默刷新：不显示 loading，失败保留原数据、不打扰用户
    loadSchedule(options) {
        const silent = !!(options && options.silent);
        if (!silent) {
            // 设置加载状态
            this.setData({ loading: true, needBind: false });
        }
        // 调用API查询课程表，传入空参数获取当前学期数据
        api.querySchedule('', '')
            .then(res => this.processScheduleData(res.data || []))
            .catch(err => {
                // 未绑定教务账号：展示「请先绑定」空态，不弹「无数据」提示
                if (feature.isAcademicNotBound(err)) {
                    this.setData({ loading: false, needBind: true });
                    return;
                }
                if (silent) return;
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
            });
    },

    //处理课程表数据
    processScheduleData(data) {
        let currentWeek = 1;
        const allCourses = [];

        if (data?.length > 0 && data[0].courses) {
            // 取当前周次
            currentWeek = data[0].week || 1;
            // 遍历每门课程
            for (const course of data[0].courses) {
                // 解析上课时间字符串，获取星期和节次
                const { weekDay, sections } = this.parseClassTime(course.classTime || '');
                // 星期不在0-6范围内则跳过
                if (weekDay < 0 || weekDay >= 7) continue;

                const courseName = course.courseName || '';
                // 根据课程名生成固定颜色
                const color = getCourseColor(courseName);

                // 为每个节次创建独立的课程对象（同一门课不同节次分开存储）
                for (const section of sections) {
                    allCourses.push({
                        courseName,                     // 课程名称
                        weekDay,                        // 星期（0=周一, ..., 6=周日）
                        weekDayName: weekDays[weekDay + 1],  // 星期中文名
                        slots: [section],               // 占用节次数组
                        location: course.location || course.classroomName || '',  // 上课地点
                        teacher: course.teacherName || '',   // 授课教师
                        weeks: course.classWeek || '',       // 上课周次
                        classTime: course.classTime || '',   // 原始时间字符串
                        timeDisplay: timeSlots[section]?.time || '',  // 时间段显示文本
                        color                           // 课程颜色
                    });
                }
            }
        }

        // 更新当前周次和所有课程，回调中筛选当天课程
        this.setData({ currentWeek, allCourses }, () => this.filterDayCourses());
    },

    //节次上限：至少 12 节，后台作息配置更多时按配置（与 uniapp 端保持一致）
    maxSection() {
        let max = 0;
        Object.keys(timeSlots).forEach(key => {
            const n = parseInt(key, 10);
            if (n > max) max = n;
        });
        return Math.max(12, max || 11);
    },

    //解析上课时间字符串（如"10506"表示周一第5、6节）
    parseClassTime(classTime) {
        // 空字符串或长度不足则返回默认值
        if (!classTime || classTime.length < 2) return { weekDay: 0, sections: [] };

        // 第一个字符表示星期（1=周一, ..., 7=周日），转为0-based索引
        const weekDay = parseInt(classTime.charAt(0)) - 1;
        // 剩余字符为节次信息
        const timeStr = classTime.substring(1);
        const sections = [];
        // 节次上限按后台作息（至少 12 节），第 12 节及以后的课程不再被丢弃
        const maxSection = this.maxSection();

        // 优先按两位数格式解析节次（如"05"表示第5节）
        for (let i = 0; i < timeStr.length; i += 2) {
            const section = parseInt(timeStr.substr(i, 2));
            if (section >= 1 && section <= maxSection) sections.push(section);
        }

        // 两位数解析失败时，尝试一位数格式（如"5"表示第5节）
        if (sections.length === 0) {
            for (let i = 0; i < timeStr.length; i++) {
                const section = parseInt(timeStr.charAt(i));
                if (section >= 1 && section <= maxSection) sections.push(section);
            }
        }

        return { weekDay, sections };
    },

    //筛选当天课程
    filterDayCourses() {
        const { allCourses, selectedDay, currentWeek } = this.data;
        // selectedDay是0-6（0=周日），转为1-7（1=周一, 7=周日）
        const targetDay = selectedDay === 0 ? 7 : selectedDay;

        // 筛选选中日期的课程
        let dayCourses = allCourses.filter(course => {
            // 星期不匹配则排除
            if (course.weekDay !== targetDay - 1) return false;
            // 无周次信息则保留
            if (!course.weeks) return true;
            
            // 检查当前周次是否在上课周次范围内
            const match = course.weeks.match(/^(\d+)-(\d+)$/);
            if (match) {
                // 格式为"1-16"，判断当前周是否在范围内
                const [_, start, end] = match.map(Number);
                return currentWeek >= start && currentWeek <= end;
            }
            // 其他格式（如逗号分隔），检查是否包含当前周次
            return course.weeks.includes(String(currentWeek));
        }).map(c => ({ ...c, isEnded: false }))   // 初始化未结束状态
          .sort((a, b) => (a.slots[0] || 99) - (b.slots[0] || 99));  // 按第一节次升序排列

        // 按节次时间分类：上午 / 下午 / 晚上（不再写死节次边界，
        // 避免后台作息中「下午」不止 5~7 节时把下午最后一节误分到晚上）
        const morningCourses = [];
        const afternoonCourses = [];
        const eveningCourses = [];
        dayCourses.forEach(c => {
            const session = this.sectionSession((c.slots && c.slots.length) ? c.slots[0] : 0);
            if (session === 'morning') morningCourses.push(c);
            else if (session === 'afternoon') afternoonCourses.push(c);
            else eveningCourses.push(c);
        });

        this.setData({
            dayCourses,
            morningCourses,
            afternoonCourses,
            eveningCourses,
            loading: false
        }, () => this.updateCourseStatus());   // 回调中更新课程状态
    },

    //选择星期
    onDaySelect(e) {
        // 从dataset获取选中的星期值，更新后重新筛选课程
        this.setData({ selectedDay: e.currentTarget.dataset.day }, () => this.filterDayCourses());
    },

    //点击课程查看详情（自定义弹窗，与页面风格一致）
    onCourseTap(e) {
        // 从dataset获取课程对象
        const course = e.currentTarget.dataset.course;
        if (!course) return;

        // 课程主色（文字色），无则回退主题青色
        const color = (course.color && course.color.text) ? course.color.text : '#13c2c2';
        // 图标与按钮均使用当前课程的颜色配置
        this.setData({
            showDetail: true,
            detailCourse: course,
            detailIcons: {
                time: this.buildIconDataUri(ICON_CLOCK_PATHS, color),
                location: this.buildIconDataUri(ICON_LOCATION_PATHS, color),
                user: this.buildIconDataUri(ICON_USER_PATHS, color),
                calendar: this.buildIconDataUri(ICON_CALENDAR_PATHS, color)
            },
            // 按钮渐变终点色：课程色向白色混合 40%
            detailBtnBgLight: this.mixWithWhite(color, 0.4),
            detailBtnShadow: this.hexToRgba(color, 0.35)
        });
    },

    //生成指定颜色的 SVG 图标 data URI（供 <image> 渲染）
    buildIconDataUri(paths, color) {
        const body = paths.map(d => `<path d="${d}" fill="${color}"/>`).join('');
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">${body}</svg>`;
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    },

    //十六进制颜色转 rgba 字符串
    hexToRgba(hex, alpha) {
        let h = String(hex || '#13c2c2').replace('#', '');
        if (h.length === 3) h = h.split('').map(c => c + c).join('');
        const r = parseInt(h.slice(0, 2), 16) || 0;
        const g = parseInt(h.slice(2, 4), 16) || 0;
        const b = parseInt(h.slice(4, 6), 16) || 0;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    },

    //颜色向白色混合（ratio 为白色的占比 0~1）
    mixWithWhite(hex, ratio) {
        let h = String(hex || '#13c2c2').replace('#', '');
        if (h.length === 3) h = h.split('').map(c => c + c).join('');
        const mix = v => Math.round(v + (255 - v) * ratio);
        const r = mix(parseInt(h.slice(0, 2), 16) || 0);
        const g = mix(parseInt(h.slice(2, 4), 16) || 0);
        const b = mix(parseInt(h.slice(4, 6), 16) || 0);
        const toHex = v => ('0' + v.toString(16)).slice(-2);
        return '#' + toHex(r) + toHex(g) + toHex(b);
    },

    //关闭课程详情弹窗
    closeDetail() {
        this.setData({ showDetail: false, detailCourse: null });
    },

    // ===== AI 智能分析今日安排 =====
    async aiAnalyze() {
        if (this.data.aiLoading) {
            const t = '### AI 正在分析…\n\n正在生成分析报告，请稍候......\n（该过程通常需要十几秒）';
            this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t), showAiResult: true });
            this.initSheetHeight();
            wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
            return;
        }
        if (this.data.dayCourses.length === 0) {
            wx.showToast({ title: '今日暂无课程', icon: 'none' });
            return;
        }
        const key = 'k' + this.data.selectedDay;
        if (this.data.aiCache[key]) {
            this.setData({
                aiResult: this.data.aiCache[key],
                aiResultHtml: markdownToHtml(this.data.aiCache[key]),
                showAiResult: true
            });
            this.initSheetHeight();
            return;
        }
        this.setData({ aiLoading: true });
        const loadingText = '### AI 正在分析…\n\n正在读取今日课程安排并生成分析报告，请稍候......\n（该过程通常需要十几秒）';
        this.setData({
            aiResult: loadingText,
            aiResultHtml: markdownToHtml(loadingText),
            showAiResult: true
        });
        this.initSheetHeight();
        const courses = this.data.dayCourses.map(c => ({
            name: c.courseName,
            time: c.timeDisplay,
            location: c.location,
            teacher: c.teacher
        }));
        const dateInfo = (this.data.weekDates[this.data.selectedDay] && this.data.weekDates[this.data.selectedDay].full) || '';
        try {
            const res = await api.aiAnalyzeSchedule({
                type: 'today',
                dayName: FULL_DAY_NAMES[this.data.selectedDay],
                date: dateInfo,
                courses
            });
            const content = res.data && res.data.content ? String(res.data.content).trim() : '';
            if (!content) {
                const t = '### 暂无分析结果\n\nAI 未返回有效内容，请稍后重试。';
                this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t) });
                return;
            }
            const cache = Object.assign({}, this.data.aiCache);
            cache[key] = content;
            this.setData({
                aiCache: cache,
                aiResult: content,
                aiResultHtml: markdownToHtml(content)
            });
        } catch (err) {
            const msg = (err && err.msg) || '分析失败';
            const t = '### 分析失败\n\n' + msg + '\n\n请稍后重试，或联系管理员检查「AI 配置」是否正确、额度是否充足。';
            this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t) });
        } finally {
            this.setData({ aiLoading: false });
        }
    },

    //关闭 AI 分析结果浮层
    closeAiResult() {
        this.setData({ showAiResult: false });
    },

    //初始化浮层高度（视口 80%）
    initSheetHeight() {
        let h = this.data.windowHeight;
        if (!h) {
            try {
                h = wx.getSystemInfoSync().windowHeight || 0;
            } catch (e) {
                h = 0;
            }
            this.setData({ windowHeight: h });
        }
        this.setData({ sheetHeight: Math.round(h * 0.8) });
    },

    //阻止冒泡占位
    noop() {},

    //跳转到完整课程表页面
    goToFullSchedule() {
        wx.navigateTo({ url: '/pages/academic-schedule/index' });
    },

    // 跳转教务账号绑定页
    goToBind() {
        wx.navigateTo({ url: '/pages/academic-bind/index' });
    }
});
