/**
 * 电费查询页面
 * 查询宿舍电费余额和使用情况
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const { formatTimeRange } = require('../../utils/common.js');
const markdownToHtml = require('../../utils/markdown.js');

Page({
    data: {
        loading: true,          // 加载状态
        featureEnabled: true,   // 功能是否启用
        announcement: null,     // 公告信息
        dormitories: [],        // 宿舍列表
        selectedIndex: -1,      // 选中的宿舍索引
        results: [],            // 查询结果列表
        queryLoading: false,    // 查询加载状态
        showAiResult: false,    // AI 分析结果浮层
        aiResult: '',           // AI 分析结果（Markdown）
        aiResultHtml: '',       // 渲染用 HTML
        aiLoading: false,       // 是否正在分析
        aiCache: {},            // 分析结果缓存
        windowHeight: 0,
        sheetHeight: 0,         // 浮层高度（视口 80%）
        // 电费详情自定义弹窗
        showDetail: false,
        detailItem: {},
        detailTint: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
    },

    //页面加载
    onLoad() {
        // 检查登录状态
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
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

    //宿舍选择变化：切换宿舍后自动查询，无需再手动点「查询」
    onPickerChange(e) {
        const index = e.detail.value;
        // 未更换宿舍时不重复查询
        if (Number(index) === Number(this.data.selectedIndex)) return;
        this.setData({ selectedIndex: index });
        this.onQuery();
    },

    //打开电费详情（自定义弹窗，与课程详情风格一致）
    openDetail(e) {
        const index = e.currentTarget.dataset.index;
        const item = this.data.results[index];
        if (!item) return;
        const tints = {
            normal: { text: '#52c41a', bg: '#f6ffed', light: '#95de64', shadow: 'rgba(82, 196, 26, 0.3)' },
            warning: { text: '#faad14', bg: '#fffbe6', light: '#ffd666', shadow: 'rgba(250, 173, 20, 0.3)' },
            low: { text: '#ff4d4f', bg: '#fff0f0', light: '#ff7875', shadow: 'rgba(255, 77, 79, 0.3)' },
            arrears: { text: '#722ed1', bg: '#f9f0ff', light: '#b37feb', shadow: 'rgba(114, 46, 209, 0.3)' },
            error: { text: '#8c8c8c', bg: '#f5f5f5', light: '#bfbfbf', shadow: 'rgba(140, 140, 140, 0.3)' }
        };
        this.setData({
            detailTint: tints[item.status] || tints.normal,
            detailItem: item,
            showDetail: true
        });
    },

    //关闭电费详情
    closeDetail() {
        this.setData({ showDetail: false, detailItem: {} });
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
    },

    // ===== AI 智能分析电费 =====
    async aiAnalyze() {
        if (this.data.aiLoading) {
            const t = '### AI 正在分析…\n\n正在生成分析报告，请稍候......\n（该过程通常需要十几秒）';
            this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t), showAiResult: true });
            this.initSheetHeight();
            wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
            return;
        }
        if (this.data.results.length === 0) {
            wx.showToast({ title: '暂无电费数据', icon: 'none' });
            return;
        }
        const key = this.data.results
            .map(r => r.room_num + ':' + r.balance + ':' + r.remainingKwh)
            .join('|');
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
        const loadingText = '### AI 正在分析…\n\n正在读取电费数据并生成分析报告，请稍候......\n（该过程通常需要十几秒）';
        this.setData({
            aiResult: loadingText,
            aiResultHtml: markdownToHtml(loadingText),
            showAiResult: true
        });
        this.initSheetHeight();
        const rooms = this.data.results.map(r => ({
            name: r.displayName,
            balance: r.balance,
            remainingKwh: r.remainingKwh,
            pricePerKwh: r.pricePerKwh,
            estimatedDays: r.estimatedDays,
            statusText: r.statusText,
            timeRangeText: r.timeRangeText
        }));
        try {
            const res = await api.aiAnalyzePower({ type: 'query', rooms });
            const content = res.data && res.data.content ? String(res.data.content).trim() : '';
            if (!content) {
                const t = '### 暂无分析结果\n\nAI 未返回有效内容，请稍后重试。';
                this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t), showAiResult: true });
                return;
            }
            const cache = Object.assign({}, this.data.aiCache);
            cache[key] = content;
            this.setData({
                aiCache: cache,
                aiResult: content,
                aiResultHtml: markdownToHtml(content),
                showAiResult: true
            });
        } catch (err) {
            const msg = (err && err.msg) || '分析失败';
            const t = '### 分析失败\n\n' + msg + '\n\n请稍后重试，或联系管理员检查「AI 配置」是否正确、额度是否充足。';
            this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t), showAiResult: true });
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

    noop() {}
});
