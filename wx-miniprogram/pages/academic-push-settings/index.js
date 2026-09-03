/**
 * 教务推送设置页面
 * 管理课程提醒和成绩通知设置
 */
const app = getApp()
const api = require('../../utils/api.js')
const feature = require('../../utils/feature.js')

Page({
    data: {
        featureEnabled: true,       // 功能是否启用
        announcement: null,          // 公告信息
        studentId: '',                    // 学号
        hasAcademicAccount: false,        // 是否已绑定教务账号
        isEnabled: false,                 // 推送总开关
        pushWechat: true,                 // 微信推送开关
        pushEmail: false,                 // 邮件推送开关
        scheduleReminderEnabled: true,    // 课程提醒开关
        scheduleReminderTime: '20:00',    // 课程提醒时间
        gradeNotifyEnabled: true,         // 成绩通知开关
        logs: [],                         // 推送日志
        loading: true,                    // 加载状态
        saving: false,                    // 保存状态
        globalConfig: {                   // 全局配置
            schedule_reminder_enabled: 1, // 课程提醒开关：1为开启
            grade_notify_enabled: 1,      // 成绩通知开关：1为开启
            wechat_push_enabled: 1,       // 微信推送开关：1为开启
            email_push_enabled: 1         // 邮件推送开关：1为开启
        },
        academicAccounts: [],             // 已绑定教务账号列表
        academicAccountId: 0,             // 当前选中的教务账号 id
        accountIndex: 0                   // 当前选中账号索引
    },

    //页面加载
    onLoad: function() {
        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadSettings()
            }
        });
    },

    //检查功能是否启用
    async checkFeature() {
        const result = await feature.checkFeature('academic_push');
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

    //加载推送设置
    loadSettings: function(accountId) {
        // 保存this引用，避免回调中this指向丢失
        const that = this
        // 设置加载状态
        that.setData({ loading: true })
        
        // 调用API获取教务推送设置（按具体教务账号读取，修复多账号显示错误）
        api.getAcademicPushSettings({ academic_account_id: accountId || that.data.academicAccountId || undefined }).then(res => {
            // 取出响应数据，无数据时默认空对象
            const data = res.data || {}
            // 取出用户个人推送设置，无则默认空对象
            const settings = data.settings || {}
            // 取出全局推送配置，无则使用默认值（全部开启）
            const globalConfig = data.global_config || {
                schedule_reminder_enabled: 1,   // 默认开启课程提醒
                grade_notify_enabled: 1,        // 默认开启成绩通知
                wechat_push_enabled: 1,         // 默认开启微信推送
                email_push_enabled: 1           // 默认开启邮件推送
            }
            
            // 取出推送类型字符串，默认为'wechat'
            const pushTypes = settings.push_types || 'wechat'
            
            // 更新页面数据
            const accounts = data.academic_accounts || []
            let accountIndex = accounts.findIndex(function(a) { return a.id === data.current_account_id })
            if (accountIndex < 0) accountIndex = 0

            that.setData({
                academicAccounts: accounts,  // 已绑定的教务账号列表
                academicAccountId: data.current_account_id || 0,  // 当前查询的账号
                accountIndex: accountIndex,  // 当前选中索引
                studentId: data.student_id || '',   // 学号
                hasAcademicAccount: data.has_academic_account || false,  // 是否已绑定教务账号
                isEnabled: settings.is_enabled == 1,     // 推送总开关：1为开启
                pushWechat: pushTypes.includes('wechat'),  // 微信推送是否包含在推送类型中
                pushEmail: pushTypes.includes('email'),  // 邮件推送是否包含在推送类型中
                scheduleReminderEnabled: settings.schedule_reminder_enabled == 1 || settings.schedule_reminder_enabled === null,  // 课程提醒开关：1或null(未设置)时开启
                scheduleReminderTime: (settings.schedule_reminder_time || '20:00:00').substring(0, 5),  // 提醒时间，截取HH:mm部分
                gradeNotifyEnabled: settings.grade_notify_enabled == 1 || settings.grade_notify_enabled === null,  // 成绩通知开关：1或null(未设置)时开启
                globalConfig: globalConfig,  // 全局推送配置
                loading: false  // 取消加载状态
            })
            
            // 设置加载完成后，加载推送日志
            that.loadLogs()
        }).catch(err => {
            // 登录失效，提示并跳转到登录页
            if (err.code === -2) {
                wx.showToast({ title: '请先登录', icon: 'none' })
                setTimeout(() => {
                    wx.redirectTo({ url: '/pages/login/index' })
                }, 1500)
            } else {
                // 其他错误，提示并取消加载状态
                wx.showToast({ title: err.msg || '加载失败', icon: 'none' })
                that.setData({ loading: false })
            }
        })
    },

    //加载推送日志
    loadLogs: function() {
        // 保存this引用
        const that = this
        // 调用API获取推送日志记录
        api.getAcademicPushLogs().then(res => {
            // 更新日志列表，无数据时默认空数组
            that.setData({
                logs: res.data || [],  // 更新日志列表
                loading: false  // 取消加载状态
            })
        }).catch(() => {})  // 日志加载失败静默处理，不影响主流程
    },

    //切换推送总开关
    togglePush: function(e) {
        const isEnabled = e.detail.value  // 获取开关状态
        this.setData({ isEnabled })
        
        // 关闭时自动保存
        if (!isEnabled) {
            this.saveSettings(true)  // 关闭时自动保存
        }
    },

    //推送方式变化
    onPushTypeChange: function(e) {
        const vals = e.detail.value || []  // checkbox-group 返回当前所有选中值数组
        this.setData({
            pushWechat: vals.indexOf('wechat') > -1,  // 是否勾选微信推送
            pushEmail: vals.indexOf('email') > -1     // 是否勾选邮件推送
        })
    },

    //课程提醒开关变化
    onScheduleReminderChange: function(e) {
        this.setData({ scheduleReminderEnabled: e.detail.value })  // 更新课程提醒开关
    },



    //成绩通知开关变化
    onGradeNotifyChange: function(e) {
        this.setData({ gradeNotifyEnabled: e.detail.value })
    },

    //提醒时间变化
    onTimeChange: function(e) {
        this.setData({ scheduleReminderTime: e.detail.value })
    },

    //切换教务账号
    onAccountChange: function(e) {
        const idx = e.detail.value
        const accounts = this.data.academicAccounts
        if (accounts[idx]) {
            this.setData({ academicAccountId: accounts[idx].id, accountIndex: idx })
            this.loadSettings()
        }
    },

    //保存推送设置
    saveSettings: function(disableOnly = false) {
        // 保存this引用
        const that = this
        
        // 根据开关状态构建推送类型数组
        const pushTypes = []
        if (that.data.pushWechat) pushTypes.push('wechat')  // 微信推送开启则加入
        if (that.data.pushEmail) pushTypes.push('email')    // 邮件推送开启则加入
        
        // 设置保存中状态
        that.setData({ saving: true })
        
        // 构建提交给后端的请求数据
        const data = {
            academic_account_id: that.data.academicAccountId,                   // 具体教务账号
            push_types: pushTypes.join(','),                                    // 推送类型，逗号分隔的字符串
            schedule_reminder_enabled: that.data.scheduleReminderEnabled ? 1 : 0,  // 课程提醒开关，布尔值转0/1
            schedule_reminder_time: that.data.scheduleReminderTime,             // 课程提醒时间（HH:mm）
            grade_notify_enabled: that.data.gradeNotifyEnabled ? 1 : 0,        // 成绩通知开关，布尔值转0/1
            is_enabled: disableOnly ? 0 : (that.data.isEnabled ? 1 : 0)        // 推送总开关：仅关闭时传0，否则按实际状态传值
        }
        
        // 调用API保存推送设置
        api.saveAcademicPushSettings(data).then(res => {
            // 保存成功，取消保存中状态并提示
            that.setData({ saving: false })
            wx.showToast({ title: '保存成功', icon: 'success' })
        }).catch(err => {
            // 保存失败，取消保存中状态
            that.setData({ saving: false })
            // 登录失效，提示并跳转到登录页
            if (err.code === -2) {
                wx.showToast({ title: '请先登录', icon: 'none' })
                setTimeout(() => {
                    wx.redirectTo({ url: '/pages/login/index' })
                }, 1500)
            } else {
                // 其他错误，提示失败信息
                wx.showToast({ title: err.msg || '保存失败', icon: 'none' })
            }
        })
    },

    //提交保存
    onSubmit: function() {
        this.saveSettings()
    },

    //跳转到绑定教务账号页
    goToBind: function() {
        wx.navigateTo({ url: '/pages/academic-bind/index' })
    }
})
