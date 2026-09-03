/**
 * 课程表页面
 * 展示学生课程表信息
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { getCourseColor, weekDays } = require('../../utils/common.js');

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
        schedule: [],           // 课程表数据（二维数组，12行7列）
        courses: [],            // 课程列表（去重后）
        currentWeek: 1,         // 当前周次
        courseColors: {}        // 课程颜色映射（课程名→颜色值）
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
                const { schedule, courses, currentWeek, courseColors } = this.processScheduleData(res.data || []);
                // 更新页面数据，取消加载状态
                this.setData({ schedule, courses, currentWeek, courseColors, loading: false });
            })
            .catch(err => {
                // 查询失败，取消加载状态并提示
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
            });
    },

    //处理课程表数据
    processScheduleData(data) {
        // 初始化课程表二维数组（12行×7列），每个单元格默认无课程
        const schedule = Array.from({ length: 12 }, () => Array.from({ length: 7 }, () => ({ hasCourse: false })));
        // 课程列表（可能有重复，后面去重）
        const courses = [];
        // 课程名→颜色的映射对象
        const courseColors = {};
        // 当前周次，默认第1周
        let currentWeek = 1;

        // 如果有数据且包含courses字段
        if (data && data.length > 0 && data[0].courses) {
            // 取出第一条课程表数据
            const scheduleData = data[0];
            // 获取当前周次
            currentWeek = scheduleData.week || 1;

            // 遍历每门课程
            for (const course of scheduleData.courses) {
                // 解析上课时间字符串，得到星期几和节次数组
                const { weekDay, sections } = this.parseClassTime(course.classTime || '');
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
                for (const section of sections) {
                    // 节次在0-11范围内才填入
                    if (section >= 0 && section < 12) {
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

        // 去重课程列表：同一课程名+星期+节次只保留一条
        const seen = new Set();
        const uniqueCourses = courses.filter(c => {
            // 用课程名+星期+节次拼接作为唯一标识
            const key = c.courseName + c.weekDay + c.section;
            // 已存在则过滤掉，不存在则加入Set并保留
            return seen.has(key) ? false : (seen.add(key), true);
        });

        // 返回处理后的课程表、去重课程列表、当前周次、颜色映射
        return { schedule, courses: uniqueCourses, currentWeek, courseColors };
    },

    //解析上课时间字符串
    parseClassTime(classTime) {
        // 空值或长度不足2位，返回默认值
        if (!classTime || classTime.length < 2) return { weekDay: 0, sections: [] };
        
        // 第一个字符表示星期几（1-7），转为0索引（0-6）
        const weekDay = parseInt(classTime.charAt(0)) - 1;
        // 剩余字符表示节次
        const timeStr = classTime.substring(1);
        // 解析出的节次索引数组
        const sections = [];

        // 优先按两位数格式解析节次（如"0102"表示第1、2节）
        for (let i = 0; i < timeStr.length; i += 2) {
            const section = parseInt(timeStr.substr(i, 2));
            // 节次有效范围1-12，转为0索引存入
            if (section >= 1 && section <= 12) sections.push(section - 1);
        }

        // 如果两位数解析失败（如"12"只有1个节次），尝试一位数格式
        if (sections.length === 0) {
            for (let i = 0; i < timeStr.length; i++) {
                const section = parseInt(timeStr.charAt(i));
                if (section >= 1 && section <= 12) sections.push(section - 1);
            }
        }

        return { weekDay, sections };
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
