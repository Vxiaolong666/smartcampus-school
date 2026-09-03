/**
 * 今日课程页面
 * 展示当天课程安排和实时上课状态
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { getCourseColor, timeSlots, weekDays } = require('../../utils/common.js');

Page({
    data: {
        loading: true,              // 加载状态
        featureEnabled: true,       // 功能是否启用
        announcement: null,         // 公告信息
        currentWeek: 1,             // 当前周次
        selectedDay: 0,             // 选中的星期（0=周日, 1=周一, ..., 6=周六）
        todayDayOfWeek: 0,          // 今天是星期几（0=周日）
        weekDates: [],              // 本周日期列表
        allCourses: [],             // 所有课程（全周）
        dayCourses: [],             // 当天课程
        morningCourses: [],         // 上午课程（第1-4节）
        afternoonCourses: [],       // 下午课程（第5-7节）
        eveningCourses: [],         // 晚上课程（第8-11节）
        currentTime: '',            // 当前时间（HH:mm格式）
        currentSection: null,       // 当前所在节次（1-11，null表示不在上课时间）
        currentCourse: null,        // 当前正在上的课程
        nextCourse: null,           // 下一节课程
        inBreak: false,             // 是否在课间（已下课但下节课未开始）
        minutesToEnd: 0,            // 距离当前课程下课还有多少分钟
        minutesToNext: 0,           // 距离下节课开始还有多少分钟
        allCoursesEnded: false,     // 当天所有课程是否已结束
        notStarted: false,          // 当天课程是否还未开始
        isEndingSoon: false         // 当前课程是否即将下课（剩余时间≤20%）
    },

    //页面加载
    onLoad() {
        // 检查登录状态，未登录则跳转登录页（使用 redirectTo 避免页面栈累积）
        if (!api.isLoggedIn()) {
            wx.redirectTo({ url: '/pages/login/index' });
            return;
        }

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

            // 加载课程表数据
            this.loadSchedule();
            // 首次更新时间和节次
            this.updateTime();
            
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

        // 遍历11个节次，判断当前时间落在哪个节次范围内
        for (let i = 1; i <= 11; i++) {
            const [start, end] = timeSlots[i].time.split('-');
            // 当前时间在某个节次的开始和结束时间之间
            if (currentTime >= start && currentTime <= end) {
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
    loadSchedule() {
        // 设置加载状态
        this.setData({ loading: true });
        // 调用API查询课程表，传入空参数获取当前学期数据
        api.querySchedule('', '')
            .then(res => this.processScheduleData(res.data || []))
            .catch(err => {
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

    //解析上课时间字符串（如"10506"表示周一第5、6节）
    parseClassTime(classTime) {
        // 空字符串或长度不足则返回默认值
        if (!classTime || classTime.length < 2) return { weekDay: 0, sections: [] };

        // 第一个字符表示星期（1=周一, ..., 7=周日），转为0-based索引
        const weekDay = parseInt(classTime.charAt(0)) - 1;
        // 剩余字符为节次信息
        const timeStr = classTime.substring(1);
        const sections = [];

        // 优先按两位数格式解析节次（如"05"表示第5节）
        for (let i = 0; i < timeStr.length; i += 2) {
            const section = parseInt(timeStr.substr(i, 2));
            if (section >= 1 && section <= 11) sections.push(section);
        }

        // 两位数解析失败时，尝试一位数格式（如"5"表示第5节）
        if (sections.length === 0) {
            for (let i = 0; i < timeStr.length; i++) {
                const section = parseInt(timeStr.charAt(i));
                if (section >= 1 && section <= 11) sections.push(section);
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

        // 按时间段分类：上午1-4节、下午5-7节、晚上8-11节
        this.setData({
            dayCourses,
            morningCourses: dayCourses.filter(c => c.slots[0] >= 1 && c.slots[0] <= 4),
            afternoonCourses: dayCourses.filter(c => c.slots[0] >= 5 && c.slots[0] <= 7),
            eveningCourses: dayCourses.filter(c => c.slots[0] >= 8 && c.slots[0] <= 11),
            loading: false
        }, () => this.updateCourseStatus());   // 回调中更新课程状态
    },

    //选择星期
    onDaySelect(e) {
        // 从dataset获取选中的星期值，更新后重新筛选课程
        this.setData({ selectedDay: e.currentTarget.dataset.day }, () => this.filterDayCourses());
    },

    //点击课程查看详情
    onCourseTap(e) {
        // 从dataset获取课程对象
        const course = e.currentTarget.dataset.course;
        if (!course) return;

        // 拼接课程详情文本
        const lines = [
            `课程：${course.courseName || '未知'}`,
            `时间：${course.weekDayName || ''} 第${course.slots?.[0] || '-'}节`,
            `时段：${course.timeDisplay || '待定'}`,
            `地点：${course.location || '待定'}`,
            `教师：${course.teacher || '待定'}`,
            `周次：${course.weeks || '-'}`
        ];

        // 弹窗显示课程详情
        wx.showModal({
            title: '课程详情',
            content: lines.join('\n'),
            showCancel: false,
            confirmText: '关闭'
        });
    },

    //跳转到完整课程表页面
    goToFullSchedule() {
        wx.navigateTo({ url: '/pages/academic-schedule/index' });
    }
});
