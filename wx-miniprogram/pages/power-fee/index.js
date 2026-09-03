/**
 * 电费查询页面
 * 查询宿舍电费余额和使用情况
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { formatTimeRange } = require('../../utils/common.js');

Page({
    data: {
        loading: true,          // 加载状态
        featureEnabled: true,   // 功能是否启用
        announcement: null,     // 公告信息
        dormitories: [],        // 宿舍列表
        selectedIndex: -1,      // 选中的宿舍索引
        results: [],            // 查询结果列表
        queryLoading: false     // 查询加载状态
    },

    //页面加载
    onLoad() {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            wx.showToast({ title: '请先登录', icon: 'none' });
            setTimeout(() => wx.navigateTo({ url: '/pages/login/index' }), 1000);
            return;
        }

        // 检查功能开关
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadDormitories();
            }
        });
    },

    //检查功能是否启用
    async checkFeature() {
        try {
            const result = await feature.checkFeature('electricity');
            if (!result.enabled) {
                this.setData({
                    featureEnabled: false,
                    loading: false,
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

    //加载宿舍列表
    async loadDormitories() {
        try {
            const res = await api.getDormitories();
            // 处理宿舍列表，生成显示名称
            const dormitories = (res.data || []).map(item => ({
                ...item,
                displayName: item.display_name || (item.alias ? `${item.alias}（${item.school_area}${item.building} ${item.room}）` : `${item.school_area}${item.building} ${item.room}`)
            }));

            if (dormitories.length > 0) {
                // 查找默认宿舍，没有则选择第一个
                const defaultIndex = dormitories.findIndex(d => d.is_default == 1);
                const selectedIndex = defaultIndex >= 0 ? defaultIndex : 0;
                this.setData({ dormitories, loading: false, selectedIndex });
                // 自动查询一次
                this.onQuery();
            } else {
                this.setData({ dormitories, loading: false });
            }
        } catch (err) {
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '加载失败', icon: 'none' });
        }
    },

    //宿舍选择变化
    onPickerChange(e) {
        this.setData({ selectedIndex: e.detail.value });
    },

    //构建查询结果数据
    buildResult(dorm, res) {
        return {
            ...dorm,
            balance: res.data.balance,                          // 余额
            remainingKwh: res.data.remaining_kwh,               // 剩余电量
            pricePerKwh: res.data.price_per_kwh,                // 每度电价格
            estimatedDays: res.data.estimated_days,             // 预计可用天数
            estimatedDaysText: res.data.estimated_days != null ? String(res.data.estimated_days) : '--',
            timeRangeText: formatTimeRange(res.data.time_range), // 时间范围
            status: res.data.status,                            // 状态
            statusText: res.data.status_text                    // 状态文本
        };
    },

    //查询单个宿舍电费
    async onQuery() {
        const { dormitories, selectedIndex } = this.data;
        
        // 验证选择
        if (selectedIndex < 0 || !dormitories[selectedIndex]) {
            wx.showToast({ title: '请选择宿舍', icon: 'none' });
            return;
        }

        this.setData({ queryLoading: true });
        try {
            const res = await api.queryPowerFee(dormitories[selectedIndex].room_num);
            this.setData({ results: [this.buildResult(dormitories[selectedIndex], res)], queryLoading: false });
        } catch (err) {
            this.setData({ queryLoading: false });
            wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
        }
    },

    //查询所有宿舍电费
    async onQueryAll() {
        const { dormitories } = this.data;
        
        if (dormitories.length === 0) {
            wx.showToast({ title: '暂无绑定的宿舍', icon: 'none' });
            return;
        }

        this.setData({ queryLoading: true, results: [] });
        const results = [];

        // 逐个查询每个宿舍
        for (const dorm of dormitories) {
            try {
                const res = await api.queryPowerFee(dorm.room_num);
                results.push(this.buildResult(dorm, res));
            } catch (err) {
                // 查询失败也添加到结果中
                results.push({
                    ...dorm,
                    balance: null,
                    remainingKwh: null,
                    estimatedDays: null,
                    estimatedDaysText: '--',
                    timeRangeText: '',
                    status: 'error',
                    statusText: err.msg || '查询失败'
                });
            }
        }

        this.setData({ results, queryLoading: false });
    },

    //跳转到绑定宿舍页
    goToBind() {
        wx.navigateTo({ url: '/pages/dormitory-bind/index' });
    },

    //跳转到批量查电页
    goToBatch() {
        wx.navigateTo({ url: '/pages/power-fee-batch/index' });
    }
});
