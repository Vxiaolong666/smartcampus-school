/**
 * 课程表页面
 * 展示学生课程表信息
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { getCourseColor, weekDays, timeSlots } = require('../../utils/common.js');
const { loadScheduleTimes } = require('../../utils/schedule-time.js');

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
        sectionTimes: [],        // 节次时间（后台「课程时间配置」，索引即节次：1=第1节）
        weekDates: [],            // 本周各星期对应的日期（如 "09-18"）
        weekHeaders: weekDays.slice(1)  // 表头星期（周一~周日）
    },

    //页面加载
    onLoad(options) {
        // 检查登录状态，未登录则提示并跳转登录页
        if (!api.isLoggedIn()) {
            wx.showToast({ title: '请先登录', icon: 'none' });
            setTimeout(() => wx.navigateTo({ url: '/pages/login/index' }), 1000);
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
    syncSectionTimes() {
        const total = Math.max(12, this.sectionTotalCount());
        const list = [];
        for (let i = 1; i <= total; i++) {
            const t = timeSlots[i];
            list[i] = t && t.time ? t.time : '';
        }
        this.setData({ sectionTimes: list });
    },

    // 加载指定课程模式的后台作息（留空表示默认模式）
    loadScheduleTimes(modeId) {
        return loadScheduleTimes(modeId).then(() => {
            this.syncSectionTimes();
        });
    },

    // 取某行（节次）对应的作息时间文本
    sectionTime(rowIndex) {
        return this.data.sectionTimes[rowIndex + 1] || '';
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

    //显示课程详情
    showCourseDetail(course) {
        // 构建详情弹窗的文本行，过滤掉空值行
        const lines = [
            `课程：${course.courseName || '未知'}`,                              // 课程名称
            `时间：${course.weekDay || ''} 第${course.section || '-'}节`,        // 星期和节次
            course.startTime ? `时段：${course.startTime} - ${course.endTIme || ''}` : '',  // 具体时段（有开始时间才显示）
            `地点：${course.location || '待定'}`,                                // 上课地点
            `教师：${course.teacher || '待定'}`,                                 // 授课教师
            `周次：${course.weeks || '-'}`,                                      // 上课周次
            course.credit ? `学分：${course.credit}` : '',                       // 学分（有值才显示）
            course.courseNature ? `性质：${course.courseNature}` : ''            // 课程性质（有值才显示）
        ].filter(Boolean);  // 过滤掉空字符串行

        // 使用模态弹窗展示课程详情
        wx.showModal({
            title: '课程详情',
            content: lines.join('\n'),  // 每行用换行符连接
            showCancel: false,          // 不显示取消按钮
            confirmText: '关闭'         // 确认按钮文本
        });
    }
});
