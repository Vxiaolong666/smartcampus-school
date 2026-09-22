/**
 * 电费推送设置页面
 * 管理宿舍电费推送通知设置
 */
const app = getApp()
const api = require('../../utils/api.js')
const feature = require('../../utils/feature.js')
const validate = require('../../utils/validate.js')

Page({
    data: {
        featureEnabled: true,       // 功能是否启用
        announcement: null,          // 公告信息
        dormitories: [],             // 宿舍列表（含推送设置）
        config: {},            // 全局推送配置
        loading: true          // 加载状态
    },

    //页面加载
    onLoad: function() {
        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadPushSettings()
            }
        });
    },

    //检查功能是否启用
    async checkFeature() {
        const result = await feature.checkFeature('push_notification');
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
    loadPushSettings: function() {
        // 保存this引用，避免回调中this指向丢失
        const that = this
        // 设置加载状态
        that.setData({ loading: true })
        
        // 调用API获取电费推送设置
        api.getPushSettings().then(res => {
            // 取出宿舍绑定列表，无则默认空数组
            const dormitories = res.data && res.data.bindings ? res.data.bindings : []
            // 取出全局推送配置，无则默认空对象
            const config = res.data && res.data.config ? res.data.config : {}
            
            // 遍历每个宿舍，将后端字段转换为前端展示字段
            dormitories.forEach(dorm => {
                // 绑定记录ID
                dorm.id = dorm.binding_id
                // 生成显示名称：优先display_name，其次alias拼接，最后校区+楼栋+房间
                dorm.displayName = dorm.display_name || (dorm.alias ? (dorm.alias + '（' + dorm.school_area + dorm.building + ' ' + dorm.room + '）') : (dorm.school_area + dorm.building + ' ' + dorm.room))
                
                // 解析通知类型字符串为数组，无则默认全部开启
                dorm.notifyTypes = dorm.notify_types ? dorm.notify_types.split(',') : ['daily_summary', 'balance_low', 'balance_critical', 'arrears']
                // 推送方式字符串，默认微信推送
                dorm.pushTypes = dorm.push_types || 'wechat'
                // 推送总开关：1为开启
                dorm.isEnabled = dorm.is_enabled == 1
                
                // 低余额阈值：优先个人设置，其次全局配置，最后默认30
                dorm.balanceLowThreshold = dorm.balance_low_threshold || config.low_balance_threshold || 30
                // 危险余额阈值：优先个人设置，其次全局配置，最后默认10
                dorm.balanceCriticalThreshold = dorm.balance_critical_threshold || config.critical_balance_threshold || 10
                
                // 通知类型开关：判断对应类型是否在notifyTypes数组中
                dorm.notifyDaily = dorm.notifyTypes.includes('daily_summary')         // 每日汇总通知
                dorm.notifyLow = dorm.notifyTypes.includes('balance_low')             // 低余额通知
                dorm.notifyCritical = dorm.notifyTypes.includes('balance_critical')   // 危险余额通知
                dorm.notifyArrears = dorm.notifyTypes.includes('arrears')             // 欠费通知
                
                // 推送方式开关：判断对应方式是否在pushTypes字符串中
                dorm.pushWechat = dorm.pushTypes.includes('wechat')   // 微信推送
                dorm.pushEmail = dorm.pushTypes.includes('email')     // 邮件推送
                
                // 全局配置开关：0为关闭，非0为开启
                dorm.dailySummaryEnabled = config.daily_summary_enabled != 0       // 每日汇总全局开关
                dorm.lowBalanceEnabled = config.low_balance_enabled != 0           // 低余额全局开关
                dorm.criticalBalanceEnabled = config.critical_balance_enabled != 0 // 危险余额全局开关
                dorm.arrearsEnabled = config.arrears_enabled != 0                  // 欠费全局开关
            })
            
            // 更新页面数据
            that.setData({
                dormitories: dormitories,
                config: config,
                loading: false
            })
        }).catch(err => {
            // 登录失效，提示并跳转登录页
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

    //切换推送开关
    togglePush: function(e) {
        // 获取点击的宿舍索引
        const index = e.currentTarget.dataset.index
        // 取出宿舍列表引用
        const dormitories = this.data.dormitories
        // 更新对应宿舍的推送开关状态
        dormitories[index].isEnabled = e.detail.value
        // 刷新页面数据
        this.setData({ dormitories })
        // 开关变化时自动保存，disableOnly=true表示仅更新is_enabled字段
        this.saveSettings(index, !e.detail.value)
    },

    //推送类型变化处理
    onPushTypeChange: function(e) {
        // 获取宿舍索引和推送类型标识
        const index = e.currentTarget.dataset.index
        const type = e.currentTarget.dataset.type
        const dormitories = this.data.dormitories
        // checkbox-group的value数组长度>0表示选中
        const checked = e.detail.value.length > 0
        
        // 根据类型更新对应的推送方式开关
        if (type === 'wechat') {
            dormitories[index].pushWechat = checked
        } else if (type === 'email') {
            dormitories[index].pushEmail = checked
        }
        
        this.setData({ dormitories })
    },

    //通知类型变化处理
    onNotifyTypeChange: function(e) {
        // 获取宿舍索引和通知类型标识
        const index = e.currentTarget.dataset.index
        const type = e.currentTarget.dataset.type
        const dormitories = this.data.dormitories
        const checked = e.detail.value.length > 0
        
        // 根据通知类型更新对应的开关
        if (type === 'daily_summary') {
            dormitories[index].notifyDaily = checked           // 每日汇总
        } else if (type === 'balance_low') {
            dormitories[index].notifyLow = checked             // 低余额
        } else if (type === 'balance_critical') {
            dormitories[index].notifyCritical = checked        // 危险余额
        } else if (type === 'arrears') {
            dormitories[index].notifyArrears = checked         // 欠费
        }
        
        this.setData({ dormitories })
    },

    //阈值变化处理
    onThresholdChange: function(e) {
        // 获取宿舍索引和阈值类型标识
        const index = e.currentTarget.dataset.index
        const type = e.currentTarget.dataset.type
        const dormitories = this.data.dormitories
        // 解析输入值为浮点数，无效时默认0
        const value = parseFloat(e.detail.value) || 0
        
        // 根据阈值类型更新对应字段
        if (type === 'low') {
            dormitories[index].balanceLowThreshold = value         // 低余额阈值
        } else if (type === 'critical') {
            dormitories[index].balanceCriticalThreshold = value    // 危险余额阈值
        }
        
        this.setData({ dormitories })
    },

    //保存设置
    saveSettings: function(index, disableOnly = false) {
        // 获取对应宿舍的设置数据
        const dorm = this.data.dormitories[index]

        // 表单校验：阈值必须是不小于 0 的数字，且危险阈值不能高于低余额阈值
        const lowThreshold = parseFloat(dorm.balanceLowThreshold)
        const criticalThreshold = parseFloat(dorm.balanceCriticalThreshold)
        if (!validate.isNumberInRange(lowThreshold, 0)) {
            wx.showToast({ title: '低余额阈值需为不小于0的数字', icon: 'none' })
            return
        }
        if (!validate.isNumberInRange(criticalThreshold, 0)) {
            wx.showToast({ title: '危险余额阈值需为不小于0的数字', icon: 'none' })
            return
        }
        if (criticalThreshold > lowThreshold) {
            wx.showToast({ title: '危险阈值不能高于低余额阈值', icon: 'none' })
            return
        }
        
        // 根据开关状态构建推送类型数组
        const pushTypes = []
        if (dorm.pushWechat) pushTypes.push('wechat')   // 微信推送开启则加入
        if (dorm.pushEmail) pushTypes.push('email')      // 邮件推送开启则加入
        
        // 根据开关状态构建通知类型数组
        const notifyTypes = []
        if (dorm.notifyDaily) notifyTypes.push('daily_summary')         // 每日汇总
        if (dorm.notifyLow) notifyTypes.push('balance_low')             // 低余额
        if (dorm.notifyCritical) notifyTypes.push('balance_critical')   // 危险余额
        if (dorm.notifyArrears) notifyTypes.push('arrears')             // 欠费
        
        // 构建提交给后端的请求数据
        const data = {
            dormitory_binding_id: dorm.id,                                    // 宿舍绑定记录ID
            push_types: pushTypes.join(','),                                  // 推送类型，逗号分隔
            notify_types: notifyTypes.join(','),                              // 通知类型，逗号分隔
            balance_low_threshold: dorm.balanceLowThreshold,                  // 低余额阈值
            balance_critical_threshold: dorm.balanceCriticalThreshold,        // 危险余额阈值
            is_enabled: disableOnly ? 0 : (dorm.isEnabled ? 1 : 0)           // 推送总开关：仅关闭时传0，否则按实际状态
        }
        
        // 调用API保存推送设置
        api.savePushSettings(data).then(res => {
            // 保存成功提示
            wx.showToast({ title: '保存成功', icon: 'success' })
        }).catch(err => {
            // 登录失效，提示并跳转登录页
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
    onSubmit: function(e) {
        // 从dataset获取宿舍索引，调用保存方法
        const index = e.currentTarget.dataset.index
        this.saveSettings(index)
    },

    //跳转到绑定宿舍页
    goToBind: function() {
        wx.navigateTo({ url: '/pages/dormitory-bind/index' })
    }
})
