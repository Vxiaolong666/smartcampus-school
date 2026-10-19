/**
 * 用电统计页面
 * 展示宿舍用电统计和趋势图表
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const markdownToHtml = require('../../utils/markdown.js');
// 日期展示格式化（别名：小程序的 formatDate 方法用于把 Date 转为查询参数，避免重名）
const { formatDate: formatDateText } = require('../../utils/common.js');

Page({
    data: {
        featureEnabled: true,   // 功能是否启用
        announcement: null,     // 公告信息
        dormitories: [],       // 宿舍列表
        selectedIndex: 0,      // 选中的宿舍索引
        stats: null,           // 统计数据对象
        recentTrend: [],       // 近24小时趋势数据（用于折线图）
        dailyTrend: [],        // 每日趋势数据（用于折线图）
        // 趣味账单：支持周/半月/月账单 + 周期前后翻看（由 get_power_bill 独立取数）
        billData: null,        // 账单接口返回：bill / period_text / tag / can_prev / can_next / has_data
        billRange: 'week',     // week=周账单, half=半月账单, month=月账单
        billOffset: 0,         // 0=当前周期, 1=上一个周期…
        billRangeOptions: [
            { value: 'week', label: '周账单' },
            { value: 'half', label: '半月账单' },
            { value: 'month', label: '月账单' }
        ],
        records: [],           // 用电记录列表
        pricePerKwh: 0.61,     // 每度电价格（元）
        loading: true,         // 加载状态
        timeRange: '30',      // 时间筛选：all=全部, 7=近7天, 30=近30天, custom=自定义（默认近30天）
        customStart: '',       // 自定义开始日期
        customEnd: '',         // 自定义结束日期
        showAiResult: false,   // AI 分析结果浮层
        aiResult: '',          // AI 分析结果（Markdown）
        aiResultHtml: '',      // 渲染用 HTML
        aiError: '',           // 分析失败提示（空字符串表示无错误）
        aiLoading: false,      // 是否正在分析
        aiCache: {},           // 分析结果缓存
        windowHeight: 0,
        sheetHeight: 0,        // 浮层高度（视口 80%）
        // 历史电费记录筛选/排序（同一行的下拉选项，切换后由服务端筛选排序）
        recordFilter: 'all',   // all=全部, increase=电费增加, decrease=电费减少
        recordSort: 'desc',    // desc=最新在前（默认，每次进入不继承上次选择）, asc=最早在前
        recordFilterOptions: [
            { value: 'all', label: '全部记录' },
            { value: 'increase', label: '电费增加' },
            { value: 'decrease', label: '电费减少' }
        ],
        recordSortOptions: [
            { value: 'desc', label: '最新在前' },
            { value: 'asc', label: '最早在前' }
        ],
        recordFilterIndex: 0,  // 筛选下拉当前选中项
        recordSortIndex: 0,    // 排序下拉当前选中项
        recordsLoading: false, // 切换记录筛选时的局部加载状态
        balanceLive: false,    // 当前余额是否已通过实时电费查询接口刷新
        balanceKwhText: '',    // 当前余额按电价换算的电量文本
        trendColor: '#007bff', // 图表主色（随健康度：节能=绿 / 正常=橙 / 偏高=红）
        recentChartData: [],   // 折线图数据（近期趋势，{label, value}）
        dailyChartData: [],    // 折线图数据（每日趋势，{label, value}）
        periodText: '',        // 统计周期文案（如 2026/9/20-2026/12/1）
        hourlyAvgUsage: '-',   // 平均每小时用电量（按日均 ÷ 24 估算）
        hourlyAvgCost: '-'     // 平均每小时电费（按日均费用 ÷ 24 估算）
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
            const result = await feature.checkFeature('power_statistics');
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
            // 调用API获取已绑定的宿舍列表
            const res = await api.getDormitories();
            // 处理宿舍列表，生成显示名称：优先用display_name，其次用alias拼接，最后用校区+楼栋+房间
            const dormitories = (res.data || []).map(item => ({
                ...item,
                displayName: item.display_name || (item.alias ? (item.alias + '（' + item.school_area + item.building + ' ' + item.room + '）') : (item.school_area + item.building + ' ' + item.room))
            }));

            if (dormitories && dormitories.length > 0) {
                // 有宿舍数据，更新列表并自动加载第一个宿舍的统计
                this.setData({ 
                    dormitories: dormitories,
                    loading: false
                });
                this.loadStatistics();
                this.loadBill();
            } else {
                // 无宿舍数据，仅更新状态
                this.setData({ 
                    loading: false,
                    dormitories: []
                });
            }
        } catch (err) {
            // 获取宿舍列表失败
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '获取宿舍列表失败', icon: 'none' });
        }
    },

    //宿舍选择变化
    onDormitoryChange(e) {
        // 更新选中的宿舍索引
        this.setData({ selectedIndex: e.detail.value });
        // 切换宿舍后重新加载统计数据
        this.loadStatistics();
        this.loadBill();
    },

    // ===== 趣味账单：按账单周期独立取数（不受页面筛选条件影响）=====
    loadBill() {
        const dorm = this.data.dormitories[this.data.selectedIndex];
        if (!dorm) {
            this.setData({ billData: null });
            return;
        }
        api.request({
            act: 'get_power_bill',
            method: 'GET',
            data: {
                room_num: dorm.room_num,
                bill_range: this.data.billRange,
                bill_offset: this.data.billOffset
            }
        })
            .then(res => {
                this.setData({ billData: (res && res.data) || null });
            })
            .catch(err => {
                wx.showToast({ title: err.msg || '账单加载失败', icon: 'none' });
            });
    },

    //切换账单类型（周/半月/月）：回到当前周期
    setBillRange(e) {
        const range = e.currentTarget.dataset.range;
        if (this.data.billRange === range) return;
        this.setData({ billRange: range, billOffset: 0 });
        this.loadBill();
    },

    //查看更早的周期
    billPrev() {
        const billData = this.data.billData;
        if (billData && billData.can_prev === false) return;
        this.setData({ billOffset: this.data.billOffset + 1 });
        this.loadBill();
    },

    //回到较新的周期（最多到当前周期）
    billNext() {
        if (this.data.billOffset <= 0) return;
        this.setData({ billOffset: this.data.billOffset - 1 });
        this.loadBill();
    },

    //时间筛选切换
    setTimeRange(e) {
        const range = e.currentTarget.dataset.range;
        this.setData({ timeRange: range });
        this.loadStatistics();
    },

    //历史电费记录筛选下拉切换（全部记录/电费增加/电费减少）
    onRecordFilterChange(e) {
        const index = Number(e.detail.value) || 0;
        const option = this.data.recordFilterOptions[index];
        if (!option || this.data.recordFilter === option.value) return;
        this.setData({ recordFilter: option.value, recordFilterIndex: index });
        this.loadRecords();
    },

    //自定义开始日期
    onCustomStart(e) {
        this.setData({ customStart: e.detail.value });
        this.loadStatistics();
    },

    //自定义结束日期
    onCustomEnd(e) {
        this.setData({ customEnd: e.detail.value });
        this.loadStatistics();
    },

    //构造查询参数（含时间筛选）
    buildQuery(roomNum) {
        const params = { room_num: roomNum };
        const range = this.data.timeRange;
        if (range === '7' || range === '30') {
            const days = range === '7' ? 6 : 29;
            const end = this.formatDate(new Date());
            const start = this.formatDate(new Date(Date.now() - days * 86400000));
            params.start = start;
            params.end = end;
        } else if (range === 'custom') {
            if (this.data.customStart) params.start = this.data.customStart;
            if (this.data.customEnd) params.end = this.data.customEnd;
        }
        // 记录增减筛选交由服务端处理，避免前端过滤大量记录造成卡顿
        const rf = this.data.recordFilter;
        if (rf === 'increase' || rf === 'decrease') {
            params.record_filter = rf;
        }
        // 记录时间排序同样交由服务端处理
        params.record_sort = this.data.recordSort;
        return params;
    },

    //格式化日期为 Y-m-d
    formatDate(d) {
        const y = d.getFullYear();
        const m = (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1);
        const day = (d.getDate() < 10 ? '0' : '') + d.getDate();
        return y + '-' + m + '-' + day;
    },

    //加载用电统计数据
    async loadStatistics() {
        // 解构获取宿舍列表和选中索引
        const { dormitories, selectedIndex } = this.data;
        
        // 选中的宿舍不存在则返回
        if (!dormitories[selectedIndex]) return;
        
        // 获取选中的宿舍对象
        const dorm = dormitories[selectedIndex];
        
        // 设置加载状态
        this.setData({ loading: true });

        try {
            // 调用API获取用电统计数据，传入房间编号
            const query = this.buildQuery(dorm.room_num);
            const res = await api.request({
                act: 'get_power_statistics',
                method: 'GET',
                data: query
            });

            // 取出响应数据
            const data = res.data;
            
            // 处理用电记录：为每条记录生成显示文本和样式类名（记录已由服务端按筛选条件返回）
            const processedRecords = this.processRecords(data.records);
            
            // 更新页面数据：统计摘要、趋势数据、用电记录、电价、取消加载状态
            this.setData({ 
                stats: {
                    healthStatus: data.health_status,       // 健康状态标识
                    healthText: data.health_text,           // 健康状态文本（如"正常"、"偏高"）
                    yesterdayUsage: data.yesterday_usage,   // 昨日用电量
                    yesterdayCost: data.yesterday_cost,     // 昨日费用
                    dailyAvgUsage: data.daily_avg_usage,    // 日均用电量
                    dailyAvgCost: data.daily_avg_cost,      // 日均费用
                    latestBalance: data.latest_balance,     // 最新余额
                    recordCount: data.record_count,         // 记录总数
                    thresholds: data.thresholds,            // 阈值设置（低余额/危险余额）
                    estimatedDays: data.estimated_days      // 预计可用天数
                },
                recentTrend: data.recent_trend || [],       // 近24小时趋势数据
                dailyTrend: data.daily_trend || [],         // 每日趋势数据
                recentChartData: (data.recent_trend || []).map(d => ({ label: d.time || d.date || '', value: Number(d.usage) || 0 })),
                dailyChartData: (data.daily_trend || []).map(d => ({ label: d.date || d.time || '', value: Number(d.usage) || 0 })),
                trendColor: this.trendColorOf(data.health_status),      // 图表主色（健康度）
                periodText: this.resolvePeriod(query, data),            // 统计周期
                hourlyAvgUsage: this.hourlyOf(data.daily_avg_usage),    // 平均每小时用电量
                hourlyAvgCost: this.hourlyOf(data.daily_avg_cost),      // 平均每小时电费
                records: processedRecords,                   // 处理后的用电记录
                pricePerKwh: data.price_per_kwh || 0.61,    // 每度电价格
                loading: false,                              // 取消加载状态
                balanceLive: false                           // 先置为离线，待实时查询成功后再标记
            });

            // 按当前余额与电价换算电量并展示在余额卡片下方
            this.updateBalanceKwh();

            // 当前余额改用实时电费查询接口获取（统计接口返回的是最后一条记录的余额，非实时）
            this.loadRealtimeBalance(dorm.room_num);
        } catch (err) {
            // 获取统计数据失败
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '获取数据失败', icon: 'none' });
        }
    },

    // 统一处理记录：解析展示字段（记录已由服务端按筛选与排序条件返回）
    processRecords(list) {
        return (list || []).map(r => {
            const usageKwh = parseFloat(r.usage_kwh) || 0;
            const usageCost = parseFloat(r.usage_cost) || 0;

            let usageDisplay = '-';
            let usageClass = '';
            if (usageKwh > 0) {
                usageDisplay = '↓' + usageKwh.toFixed(4);
                usageClass = 'usage-down';
            } else if (usageKwh < 0) {
                usageDisplay = '↑' + Math.abs(usageKwh).toFixed(4);
                usageClass = 'usage-up';
            }

            let costDisplay = '-';
            let costClass = '';
            if (usageCost > 0) {
                costDisplay = '↓' + usageCost.toFixed(2);
                costClass = 'usage-down';
            } else if (usageCost < 0) {
                costDisplay = '↑' + Math.abs(usageCost).toFixed(2);
                costClass = 'usage-up';
            }

            return {
                ...r,
                usageDisplay,
                usageClass,
                costDisplay,
                costClass
            };
        });
    },

    // 历史电费记录时间排序下拉切换（排序交由服务端处理，仅重新拉取记录列表）
    onRecordSortChange(e) {
        const index = Number(e.detail.value) || 0;
        const option = this.data.recordSortOptions[index];
        if (!option || this.data.recordSort === option.value) return;
        this.setData({ recordSort: option.value, recordSortIndex: index });
        this.loadRecords();
    },

    // 切换记录筛选时仅重新拉取历史记录（服务端过滤），不整页刷新
    async loadRecords() {
        const { dormitories, selectedIndex } = this.data;
        const dorm = dormitories[selectedIndex];
        if (!dorm) return;
        this.setData({ recordsLoading: true });
        try {
            const res = await api.request({
                act: 'get_power_statistics',
                method: 'GET',
                data: this.buildQuery(dorm.room_num)
            });
            this.setData({
                records: this.processRecords((res.data || {}).records),
                recordsLoading: false
            });
        } catch (err) {
            this.setData({ recordsLoading: false });
            wx.showToast({ title: err.msg || '获取记录失败', icon: 'none' });
        }
    },

    // 通过实时电费查询接口刷新「当前余额」
    async loadRealtimeBalance(roomNum) {
        if (!roomNum) return;
        try {
            const res = await api.queryPowerFee(roomNum);
            const data = (res && res.data) || {};
            if (this.data.stats && data.balance !== undefined && data.balance !== null) {
                const stats = Object.assign({}, this.data.stats);
                stats.latestBalance = data.balance;
                this.setData({ stats: stats, balanceLive: true });
                // 余额更新后同步刷新换算电量
                this.updateBalanceKwh();
            }
        } catch (e) {
            // 实时查询失败时保留统计接口返回的余额，不打断页面
        }
    },

    // 当前余额按电价换算电量，用于余额卡片下方的展示
    updateBalanceKwh() {
        const balance = this.data.stats ? parseFloat(this.data.stats.latestBalance) : NaN;
        const price = parseFloat(this.data.pricePerKwh) || 0;
        if (isNaN(balance) || price <= 0) {
            this.setData({ balanceKwhText: '' });
            return;
        }
        this.setData({ balanceKwhText: (balance / price).toFixed(2) });
    },

    // 健康度 → 图表主色：节能=绿，正常=橙，偏高=红
    trendColorOf(status) {
        if (status === 'saving') return '#52c41a';
        if (status === 'normal') return '#fa8c16';
        return '#ff4d4f';
    },

    // 平均每小时数值（按日均 ÷ 24 估算，去多余 0）
    hourlyOf(v) {
        const n = parseFloat(v);
        if (isNaN(n) || n <= 0) return '-';
        return String(Number((n / 24).toFixed(3)));
    },

    // 统计周期文案：优先用查询区间；「全部」时从返回数据取最早/最晚日期
    resolvePeriod(params, data) {
        let start = (params && params.start) || '';
        let end = (params && params.end) || '';
        if (!start || !end) {
            // 趋势日期为展示格式（2026/9/21），排序改用补零 ISO 键，避免字典序错乱
            const dates = [];
            (data.daily_trend || []).forEach(d => {
                if (d && d.date) dates.push(String(d.date));
            });
            if (!dates.length) {
                (data.records || []).forEach(r => {
                    if (r && r.record_time) dates.push(String(r.record_time));
                });
            }
            const valid = dates
                .filter(d => this.isoKey(d))
                .sort((a, b) => (this.isoKey(a) < this.isoKey(b) ? -1 : (this.isoKey(a) > this.isoKey(b) ? 1 : 0)));
            if (valid.length) {
                if (!start) start = valid[0];
                if (!end) end = valid[valid.length - 1];
            }
        }
        const a = formatDateText(start);
        const b = formatDateText(end);
        if (!a && !b) return '';
        return (a || '?') + '-' + (b || '?');
    },

    // 排序键：2026-9-5 / 2026/9/5 / 2026-09-05 12:00 → 2026-09-05
    isoKey(s) {
        const m = /^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/.exec(String(s || ''));
        if (!m) return '';
        return m[1] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[3]).slice(-2);
    },

    // ===== AI 智能分析用电 =====
    async aiAnalyze() {
        if (this.data.aiLoading) {
            // 正在分析中再次点击：聚焦浮层并提示，不重复发起请求
            this.setData({ showAiResult: true });
            wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
            return;
        }
        if (!this.data.stats) {
            wx.showToast({ title: '暂无用电数据', icon: 'none' });
            return;
        }
        const dorm = this.data.dormitories[this.data.selectedIndex];
        const key = 'd' + (dorm ? dorm.id : 0) + '|' + this.data.timeRange;
        // 命中缓存直接展示
        if (this.data.aiCache[key]) {
            this.setData({
                aiError: '',
                aiResult: this.data.aiCache[key],
                aiResultHtml: markdownToHtml(this.data.aiCache[key]),
                showAiResult: true
            });
            this.initSheetHeight();
            return;
        }
        // 先算好浮层高度再展示，避免首帧 sheetHeight 为 0 的遮罩空窗
        this.initSheetHeight();
        this.setData({
            aiLoading: true,
            aiError: '',
            aiResult: '',
            aiResultHtml: '',
            showAiResult: true
        });
        const payload = {
            type: 'statistics',
            dormitory: dorm ? dorm.displayName : '',
            stats: {
                healthText: this.data.stats.healthText,
                yesterdayUsage: this.data.stats.yesterdayUsage,
                yesterdayCost: this.data.stats.yesterdayCost,
                dailyAvgUsage: this.data.stats.dailyAvgUsage,
                dailyAvgCost: this.data.stats.dailyAvgCost,
                latestBalance: this.data.stats.latestBalance,
                estimatedDays: this.data.stats.estimatedDays
            },
            recentTrend: (this.data.recentTrend || []).map(d => ({
                label: d.time || d.date || '',
                usage: d.usage
            })),
            records: (this.data.records || []).slice(0, 20).map(r => ({
                time: r.record_time || r.created_at || '',
                balance: r.balance,
                usage: parseFloat(r.usage_kwh) || 0,
                cost: parseFloat(r.usage_cost) || 0
            }))
        };
        try {
            const res = await api.aiAnalyzePower(payload);
            const content = res.data && res.data.content ? String(res.data.content).trim() : '';
            if (!content) {
                this.setData({ aiError: 'AI 未返回有效内容，请稍后重试' });
                return;
            }
            const cache = Object.assign({}, this.data.aiCache);
            cache[key] = content;
            this.setData({
                aiCache: cache,
                aiError: '',
                aiResult: content,
                aiResultHtml: markdownToHtml(content),
                showAiResult: true
            });
        } catch (err) {
            this.setData({ aiError: (err && err.msg) || '网络异常，请检查网络后重试' });
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
