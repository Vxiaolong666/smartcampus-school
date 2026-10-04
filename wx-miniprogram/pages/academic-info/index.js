/**
 * 学生信息页面
 * 展示学生个人信息
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

Page({
    data: {
        featureEnabled: true,       // 功能是否启用
        announcement: null,          // 公告信息
        loading: false,              // 加载状态
        needBind: false,             // 是否未绑定教务账号
        studentInfo: null            // 学生信息
    },

    //页面加载
    onLoad(options) {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadStudentInfo();
            }
        });
    },

    //检查功能是否启用
    async checkFeature() {
        const result = await feature.checkFeature('student_info');
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

    //加载学生信息
    loadStudentInfo() {
        this.setData({ loading: true, needBind: false });

        api.queryStudentInfo()
            .then(res => {
                const rawData = res.data || [];
                let studentInfo = null;

                if (rawData.length > 0) {
                    const info = rawData[0];
                    // 构建学生信息对象
                    studentInfo = {
                        name: info.xm || '-',              // 姓名
                        studentId: info.xh || '-',        // 学号
                        gender: info.xb || '-',           // 性别
                        nation: info.mz || '-',           // 民族
                        college: info.yx || '-',          // 学院
                        major: info.zymc || '-',          // 专业
                        className: info.bj || '-',        // 班级
                        grade: this.extractGrade(info.xh), // 年级
                        political: info.zzmm || '-',      // 政治面貌
                        origin: info.jg || '-',           // 籍贯
                        status: '在读'                     // 学籍状态
                    };
                }

                this.setData({ 
                    studentInfo: studentInfo,
                    loading: false 
                });
            })
            .catch(err => {
                // 未绑定教务账号：展示「请先绑定」空态，不弹「无数据」提示
                if (feature.isAcademicNotBound(err)) {
                    this.setData({ loading: false, needBind: true });
                    return;
                }
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
            });
    },

    //从学号中提取年级
    extractGrade(studentId) {
        if (!studentId || studentId.length < 4) return '-';
        const year = studentId.substring(0, 4);
        if (/^\d{4}$/.test(year)) { // 检查是否为4位数字
            return year + '级';
        }
        return '-';
    },

    //刷新学生信息
    onRefresh() {
        this.loadStudentInfo();
    },

    // 跳转教务账号绑定页
    goToBind() {
        wx.navigateTo({ url: '/pages/academic-bind/index' });
    }
});
