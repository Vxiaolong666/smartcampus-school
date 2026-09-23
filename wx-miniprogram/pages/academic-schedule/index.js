/**
 * 课程表页面
 * 展示学生课程表信息
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { getCourseColor, weekDays, timeSlots } = require('../../utils/common.js');
const { loadScheduleTimes } = require('../../utils/schedule-time.js');
const markdownToHtml = require('../../utils/markdown.js');

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
        featureEnabled: true,       // 功能是否启用
        announcement: null,          // 公告信息
        modes: [],                   // 课程表类别列表
        modeIndex: 0,           // 选中的类别索引
        currentMode: '',        // 当前课程表类别ID
        weeks: ['当前周', '第1周', '第2周', '第3周', '第4周', '第5周', '第6周', '第7周', '第8周', '第9周', '第10周', '第11周', '第12周', '第13周', '第14周', '第15周', '第16周', '第17周', '第18周', '第19周', '第20周'],
        weekIndex: 0,           // 选中的周次索引
        loading: false,         // 加载状态
        schedule: [],           // 课程表数据（二维数组，行数=节次上限，列数=7）
        courses: [],            // 课程列表（去重后）
        currentWeek: 1,         // 当前周次
        courseColors: {},        // 课程颜色映射（课程名→颜色值）
        showDetail: false,       // 课程详情弹窗显示状态
        detailCourse: null,       // 当前查看详情的课程对象
        detailIcons: null,        // 弹窗图标 data URI（按课程色生成）
        detailBtnBgLight: '#36cfc9',  // 弹窗按钮渐变终点色
        detailBtnShadow: 'rgba(19, 194, 194, 0.35)',  // 弹窗按钮投影色
        sectionTimes: [],        // 节次时间（后台「课程时间配置」，下标 0 = 第1节）
        showAiResult: false,     // AI 分析结果浮层
        aiResult: '',            // AI 分析结果（Markdown 原文）
        aiResultHtml: '',        // 渲染用 HTML
        aiLoading: false,        // 是否正在分析
        aiCache: {},             // 分析结果缓存（按周次）
        windowHeight: 0,
        sheetHeight: 0,          // 浮层高度（视口 80%）
        weekDates: [],            // 本周各星期对应的日期（如 "09-18"）
        weekHeaders: weekDays.slice(1)  // 表头星期（周一~周日）
    },

    //页面加载
    onLoad(options) {
        // 检查登录状态，未登录则提示并跳转登录页
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                // 先用内置作息渲染，后台作息到达后再刷新
                this.syncSectionTimes();
                this.loadScheduleModes();
            }
        });
    },

    //检查功能是否启用
    async checkFeature() {
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
    },

    //加载课程表类别
    loadScheduleModes() {
        // 设置加载状态
        this.setData({ loading: true });

        // 调用API获取课程表类别列表（如本学期、辅修等）
        api.getScheduleMode()
            .then(res => {
                // 取出类别列表，无数据时默认空数组
                const modes = res.data || [];
                // 取第一个类别的ID作为默认选中
                const currentMode = modes.length > 0 ? (modes[0].kbjcmsid || '') : '';
                // 更新类别列表、选中索引、当前类别ID，取消加载状态
                this.setData({ modes, modeIndex: 0, currentMode, loading: false });
                // 先取该课程模式的后台作息，再查课程，保证节次时间与后台配置一致
                this.loadScheduleTimes(currentMode);
                // 如果有类别，自动加载该类别下的课程表
                if (currentMode) this.loadSchedule();
            })
            .catch(err => {
                // 获取类别失败，取消加载状态并提示
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '获取课程表类别失败', icon: 'none' });
            });
    },

    //课程表类别变化
    onModeChange(e) {
        // 根据picker选中索引取出对应类别对象
        const mode = this.data.modes[e.detail.value];
        // 更新选中索引和类别ID
        this.setData({ modeIndex: e.detail.value, currentMode: mode.kbjcmsid || '' });
        // 不同课程模式可有各自的作息时间
        this.loadScheduleTimes(this.data.currentMode);
        // 类别变化后重新加载课程表
        this.loadSchedule();
    },

    //周次选择变化
    onWeekChange(e) {
        // 仅更新周次索引，不自动查询（需点击查询按钮）
        this.setData({ weekIndex: e.detail.value });
    },

    //查询课程表
    onQuery() {
        // 点击查询按钮时触发，调用加载课程表方法
        this.loadSchedule();
    },

    //加载课程表数据
    loadSchedule() {
        // 解构获取当前选中的周次索引和类别ID
        const { weekIndex, currentMode } = this.data;
        // 周次索引为0表示"当前周"，传空字符串让后端自动判断
        const week = weekIndex === 0 ? '' : weekIndex.toString();

        // 设置加载状态
        this.setData({ loading: true });

        // 调用API查询指定周次和类别的课程表
        api.querySchedule(week, currentMode)
            .then(res => {
                // 将原始数据交给processScheduleData处理，解构出课程表、课程列表、当前周次、颜色映射
                const { schedule, courses, currentWeek, courseColors, weekDates } = this.processScheduleData(res.data || []);
                // 更新页面数据，取消加载状态
                this.setData({ schedule, courses, currentWeek, courseColors, weekDates: weekDates || [], loading: false });
            })
            .catch(err => {
                // 查询失败，取消加载状态并提示
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
            });
    },

    //处理课程表数据
    processScheduleData(data) {
        // 行数至少 12 行，后台作息配置更多节次时按配置补齐（如第 12 节及以后）
        const schedule = Array.from({ length: this.maxSection() }, () => Array.from({ length: 7 }, () => ({ hasCourse: false })));
        // 课程列表（可能有重复，后面去重）
        const courses = [];
        // 课程名→颜色的映射对象
        const courseColors = {};
        // 当前周次，默认第1周
        let currentWeek = 1;
        let weekDates = [];

        // 如果有数据且包含courses字段
        if (data && data.length > 0 && data[0].courses) {
            // 取出第一条课程表数据
            const scheduleData = data[0];
            // 获取当前周次
            currentWeek = scheduleData.week || 1;

            // 解析本周各星期对应的日期（与 uniapp 一致）
            const dateByXq = {};
            if (scheduleData.date && scheduleData.date.length) {
                for (const d of scheduleData.date) {
                    const xq = (d.xqmc || '').trim();
                    if (xq) dateByXq[xq] = (d.mxrq || '').slice(-5);
                }
            }
            weekDates = weekDays.slice(1).map(day => dateByXq[day.charAt(1)] || '');

            // 遍历每门课程
            for (const course of scheduleData.courses) {
                // 解析上课时间字符串，得到星期几和节次数组
                const { weekDay, sections } = this.parseClassTime(course);
                // 星期超出0-6范围则跳过
                if (weekDay < 0 || weekDay >= 7) continue;

                // 获取课程名称
                const courseName = course.courseName || '';
                // 根据课程名获取对应颜色
                const color = getCourseColor(courseName);
                // 记录课程名→颜色映射（同一课程统一颜色）
                if (!courseColors[courseName]) courseColors[courseName] = color;

                // 构建课程信息对象，用于列表展示和详情弹窗
                const courseInfo = {
                    courseName,                                                    // 课程名称
                    weekDay: weekDays[weekDay + 1],                                // 星期几的中文文本（如"周一"）
                    section: sections.map(s => s + 1).join(','),                   // 节次，从0索引转为1起始，逗号分隔
                    _sections: sections.map(s => s + 1),                          // 节次数字数组（用于合并相邻节次）
                    location: course.location || course.classroomName || '',       // 上课地点
                    teacher: course.teacherName || '',                              // 授课教师
                    weeks: course.classWeek || '',                                  // 上课周次（如"1-16"）
                    courseNature: course.courseNature || '',                        // 课程性质
                    examinationNature: course.examinationNature || '',              // 考试性质
                    credit: course.credit || '',                                    // 学分
                    buildingName: course.buildingName || '',                        // 楼栋名称
                    classroomNub: course.classroomNub || '',                        // 教室编号
                    startTime: course.startTime || '',                              // 开始时间
                    endTIme: course.endTIme || '',                                  // 结束时间
                    color                                                          // 课程颜色
                };

                // 将课程填入课程表二维数组对应位置
                const maxSection = this.maxSection();
                for (const section of sections) {
                    // 节次在有效范围内才填入（动态节次上限，避免第 12 节及以后被丢弃）
                    if (section >= 0 && section < maxSection) {
                        schedule[section][weekDay] = {
                            hasCourse: true,           // 标记该位置有课程
                            courseName,                // 课程名称（用于单元格显示）
                            location: courseInfo.location,  // 地点（用于单元格显示）
                            teacher: courseInfo.teacher,     // 教师（用于单元格显示）
                            weeks: courseInfo.weeks,         // 周次（用于单元格显示）
                            courseInfo,                      // 完整课程信息（用于详情弹窗）
                            color                            // 课程颜色
                        };
                    }
                }
                // 将课程信息加入列表
                courses.push(courseInfo);
            }
        }

        // 合并同一门课在同一天的相邻节次（与 uniapp / 网页一致）
        const mergedCourses = this.mergeCourseSections(courses);

        // 返回处理后的课程表、合并后课程列表、当前周次、颜色映射、本周日期
        return { schedule, courses: mergedCourses, currentWeek, courseColors, weekDates };
    },

    //合并同一门课在同一天的相邻节次（与 uniapp / 网页一致）
    mergeCourseSections(courses) {
        const getKey = c => [c.courseName, c.weekDay, c.location, c.teacher, c.weeks].join('|');
        const formatSection = sections => {
            if (!sections || sections.length === 0) return '';
            const sorted = [...sections].sort((a, b) => a - b);
            const parts = [];
            let start = sorted[0], prev = sorted[0];
            for (let i = 1; i < sorted.length; i++) {
                if (sorted[i] === prev + 1) {
                    prev = sorted[i];
                } else {
                    parts.push(start === prev ? String(start) : start + '-' + prev);
                    start = prev = sorted[i];
                }
            }
            parts.push(start === prev ? String(start) : start + '-' + prev);
            return parts.join(',');
        };

        courses = courses.slice().sort((a, b) => {
            const ka = getKey(a);
            const kb = getKey(b);
            if (ka !== kb) return ka.localeCompare(kb);
            return Math.min(...a._sections) - Math.min(...b._sections);
        });

        const groups = [];
        for (const c of courses) {
            if (!c._sections || c._sections.length === 0) continue;
            const key = getKey(c);
            const cMin = Math.min(...c._sections);
            const cMax = Math.max(...c._sections);
            let merged = false;
            for (const g of groups) {
                if (g._mergeKey !== key) continue;
                const gMin = Math.min(...g._sections);
                const gMax = Math.max(...g._sections);
                if (cMin <= gMax + 1 && gMin <= cMax + 1) {
                    g._sections = Array.from(new Set([...g._sections, ...c._sections])).sort((a, b) => a - b);
                    g.section = formatSection(g._sections);
                    if (c.startTime && (!g.startTime || c.startTime < g.startTime)) g.startTime = c.startTime;
                    if (c.endTIme && (!g.endTIme || c.endTIme > g.endTIme)) g.endTIme = c.endTIme;
                    merged = true;
                    break;
                }
            }
            if (!merged) {
                groups.push({ ...c, _mergeKey: key, _sectionFirst: cMin });
            }
        }

        return groups.sort((a, b) => {
            const wa = weekDays.indexOf(a.weekDay);
            const wb = weekDays.indexOf(b.weekDay);
            if (wa !== wb) return wa - wb;
            return (a._sectionFirst || 0) - (b._sectionFirst || 0);
        });
    },

    //解析上课时间（与网页 getCourseTimeSlots 保持一致）
    parseClassTime(course) {
        const classTime = course.classTime || '';
        // 星期几：优先使用接口返回的 weekDay 字段（1-7）
        let weekDay = -1;
        if (course.weekDay !== undefined && course.weekDay !== null && course.weekDay !== '') {
            const wd = parseInt(course.weekDay);
            if (wd >= 1 && wd <= 7) weekDay = wd - 1;
        }
        if (weekDay < 0 && classTime.length >= 1) {
            const wd = parseInt(classTime.charAt(0));
            if (wd >= 1 && wd <= 7) weekDay = wd - 1;
        }

        const sections = [];
        // 节次上限按后台作息（至少 12 节），避免第 12 节及以后的课程被丢弃
        const maxSection = this.maxSection();
        const pushSection = n => { if (n >= 1 && n <= maxSection) sections.push(n - 1); };

        // 优先使用 weekNoteDetail（如 "1节,2节"）
        const weekNoteDetail = course.weekNoteDetail || '';
        if (weekNoteDetail && weekNoteDetail.trim()) {
            const parts = weekNoteDetail.split(/[,，]/);
            for (const part of parts) {
                const p = part.trim();
                if (p.length >= 2) {
                    const num = parseInt(p.slice(-2));
                    if (num >= 1 && num <= maxSection) pushSection(num);
                }
            }
        }

        // 其次按 "1XX" 节次编码扫描 classTime（如 "105106" 表示第5、6节），与网页 getCourseTimeSlots 一致
        if (sections.length === 0) {
            for (let i = 1; i <= maxSection; i++) {
                const code = '1' + ('0' + i).slice(-2);
                if (classTime.indexOf(code) !== -1) pushSection(i);
            }
        }

        // 兼容旧格式："星期(1位) + 两位节次拼接"（如 "20506" 表示周二第5、6节）
        if (sections.length === 0) {
            const timeStr = weekDay >= 0 ? classTime.substring(1) : classTime;
            for (let i = 0; i < timeStr.length; i += 2) {
                const section = parseInt(timeStr.substr(i, 2));
                if (section >= 1 && section <= maxSection) pushSection(section);
            }
            if (sections.length === 0) {
                for (let i = 0; i < timeStr.length; i++) {
                    const section = parseInt(timeStr.charAt(i));
                    if (section >= 1 && section <= maxSection) pushSection(section);
                }
            }
        }

        return { weekDay, sections: [...new Set(sections)].sort((a, b) => a - b) };
    },

    // 课程表行数 / 节次上限：至少 12 节，后台作息配置更多时按配置
    maxSection() {
        return Math.max(12, this.sectionTotalCount());
    },

    // 取当前作息里最大的节次数（课程表默认 12 行，配置更多节次时按配置补齐）
    sectionTotalCount() {
        let max = 0;
        Object.keys(timeSlots).forEach(key => {
            const n = parseInt(key, 10);
            if (n > max) max = n;
        });
        return max;
    },

    // 把作息同步到响应式数据，供模板渲染（后台配置到达后会自动刷新）
    // 注意：WXML 不支持调用 Page 方法，需预先算好下标 0 = 第1节 的数组
    syncSectionTimes() {
        const total = Math.max(12, this.sectionTotalCount());
        const list = [];
        for (let i = 1; i <= total; i++) {
            const t = timeSlots[i];
            list[i - 1] = t && t.time ? t.time : '';
        }
        this.setData({ sectionTimes: list });
    },

    // 加载指定课程模式的后台作息（留空表示默认模式）
    loadScheduleTimes(modeId) {
        return loadScheduleTimes(modeId).then(() => {
            this.syncSectionTimes();
        });
    },

    //点击课程表单元格
    onCourseTap(e) {
        // 从dataset获取点击的列（星期）和行（节次）
        const { day, section } = e.currentTarget.dataset;
        // 从课程表二维数组中取出对应单元格数据
        const cell = this.data.schedule[section]?.[day];
        // 有课程且有完整信息时，显示课程详情
        if (cell?.hasCourse && cell.courseInfo) this.showCourseDetail(cell.courseInfo);
    },

    //点击课程列表项
    onCourseItemTap(e) {
        // 从dataset获取课程对象，有则显示详情
        if (e.currentTarget.dataset.course) this.showCourseDetail(e.currentTarget.dataset.course);
    },

    //显示课程详情（自定义弹窗，与页面风格一致）
    showCourseDetail(course) {
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
            // 按钮渐变终点色：课程色向白色混合 40%，避免纯色过深
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

    // ===== AI 智能分析课表 =====
    async aiAnalyze() {
        if (this.data.aiLoading) {
            // 正在分析中再次点击：聚焦浮层并提示，不重复发起请求
            this.setData({
                aiResult: '### AI 正在分析…\n\n正在生成分析报告，请稍候（通常需要十几秒）。',
                aiResultHtml: markdownToHtml('### AI 正在分析…\n\n正在生成分析报告，请稍候（通常需要十几秒）。'),
                showAiResult: true
            });
            this.initSheetHeight();
            wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
            return;
        }
        if (this.data.courses.length === 0) {
            wx.showToast({ title: '暂无课程数据', icon: 'none' });
            return;
        }
        const key = 'w' + this.data.weekIndex + '|' + this.data.currentMode + '|' + this.data.courses.length;
        // 命中缓存直接展示
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
        const loadingText = '### AI 正在分析…\n\n正在读取课表数据并生成分析报告，请稍候（通常需要十几秒）。';
        this.setData({
            aiResult: loadingText,
            aiResultHtml: markdownToHtml(loadingText),
            showAiResult: true
        });
        this.initSheetHeight();
        const courses = this.data.courses.map(c => ({
            name: c.courseName,
            weekDay: c.weekDay,
            section: c.section,
            location: c.location,
            teacher: c.teacher,
            weeks: c.weeks,
            credit: c.credit,
            nature: c.courseNature
        }));
        try {
            const res = await api.aiAnalyzeSchedule({
                type: 'semester',
                week: this.data.weekIndex === 0 ? this.data.currentWeek : this.data.weekIndex,
                courses
            });
            const content = res.data && res.data.content ? String(res.data.content).trim() : '';
            if (!content) {
                const emptyText = '### 暂无分析结果\n\nAI 未返回有效内容，请稍后重试。';
                this.setData({ aiResult: emptyText, aiResultHtml: markdownToHtml(emptyText) });
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
            const errText = '### 分析失败\n\n' + msg + '\n\n请稍后重试，或联系管理员检查「AI 配置」是否正确、额度是否充足。';
            this.setData({ aiResult: errText, aiResultHtml: markdownToHtml(errText) });
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
    noop() {}
});
