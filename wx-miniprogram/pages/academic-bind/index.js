/**
 * 教务绑定页面
 * 处理教务账号绑定和解绑
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

Page({
    data: {
        studentId: '',        // 学工号
        password: '',         // 教务密码
        realName: '',         // 真实姓名（可选）
        loading: false,       // 加载状态
        isBound: false,       // 是否已绑定教务账号
        academicInfo: null,
        announcement: null    // 教务账号信息
    },

    //页面加载
    onLoad(options) {
        // 检查用户是否已登录
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadAcademicInfo();
            }
        });
    },


    // 检查功能开关
    async checkFeature() {
        try {
            const result = await feature.checkFeature('academic_bind');
            if (!result.enabled) {
                this.setData({ announcement: null });
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

    //页面显示
    onShow() {
        // 如果已登录，重新加载绑定信息
        if (api.isLoggedIn()) {
            this.loadAcademicInfo();
        }
    },

    //加载教务账号绑定信息
    loadAcademicInfo() {
        // 调用API获取教务账号信息
        api.getAcademic()
            .then(res => {
                // 成功：设置为已绑定true，更新状态
                this.setData({
                    isBound: true,
                    academicInfo: res.data
                });
            })
            .catch(err => {
                // 失败：设置为未绑定false，清空状态
                this.setData({
                    isBound: false,
                    academicInfo: null
                });
            });
    },


    //学工号输入
    onStudentIdInput(e) {
        this.setData({ studentId: e.detail.value });
    },


    //密码输入
    onPasswordInput(e) {
        this.setData({ password: e.detail.value });
    },


    //真实姓名输入
    onRealNameInput(e) {
        this.setData({ realName: e.detail.value });
    },

    //绑定教务账号
    onBind() {
        // 解构获取数据
        const { studentId, password, realName, loading } = this.data;

        // 防止重复提交
        if (loading) return;

        // 验证学工号
        if (!studentId.trim()) {
            wx.showToast({ title: '请输入学工号', icon: 'none' });
            return;
        }

        // 验证密码
        if (!password.trim()) {
            wx.showToast({ title: '请输入教务密码', icon: 'none' });
            return;
        }

        // 开始绑定
        this.setData({ loading: true });
        wx.showLoading({ title: '绑定中...' });

        // 调用绑定API
        api.bindAcademic(studentId, password, realName)
            .then(res => {
                // 绑定成功
                wx.hideLoading();
                this.setData({ loading: false });

                wx.showToast({ title: '绑定成功', icon: 'success' });

                // 延迟1秒后刷新绑定信息
                setTimeout(() => {
                    this.loadAcademicInfo();
                }, 1000);
            })
            .catch(err => {
                // 绑定失败
                wx.hideLoading();
                this.setData({ loading: false });
                wx.showToast({ title: err.msg || '绑定失败', icon: 'none' });
            });
    },

    //解绑教务账号
    onUnbind() {
        // 显示确认对话框
        wx.showModal({
            title: '提示',
            content: '确定要解绑教务账号吗？',
            success: (res) => {
                // 用户点击确认
                if (res.confirm) {
                    wx.showLoading({ title: '解绑中...' });

                    // 调用解绑API
                    api.unbindAcademic()
                        .then(res => {
                            // 解绑成功
                            wx.hideLoading();
                            wx.showToast({ title: '解绑成功', icon: 'success' });
                            // 清空绑定状态
                            this.setData({ isBound: false, academicInfo: null });
                        })
                        .catch(err => {
                            // 解绑失败
                            wx.hideLoading();
                            wx.showToast({ title: err.msg || '解绑失败', icon: 'none' });
                        });
                }
            }
        });
    },

    //跳转到成绩查询页
    goToGrade() {
        wx.navigateTo({ url: '/pages/academic-grade/index' });
    },

    //跳转到课程表页
    goToSchedule() {
        wx.navigateTo({ url: '/pages/academic-schedule/index' });
    },

    //跳转到学生信息页
    goToStudentInfo() {
        wx.navigateTo({ url: '/pages/academic-info/index' });
    }
});
