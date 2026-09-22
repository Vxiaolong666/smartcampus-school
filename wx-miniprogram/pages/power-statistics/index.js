/**
 * 用电统计页面
 * 展示宿舍用电统计和趋势图表
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const markdownToHtml = require('../../utils/markdown.js');

Page({
    data: {
        featureEnabled: true,   // 功能是否启用
        announcement: null,     // 公告信息
        dormitories: [],       // 宿舍列表
        selectedIndex: 0,      // 选中的宿舍索引
        stats: null,           // 统计数据对象
        recentTrend: [],       // 近期趋势数据（用于折线图）
        dailyTrend: [],        // 每日趋势数据（用于折线图）
        records: [],           // 用电记录列表
        pricePerKwh: 0.61,     // 每度电价格（元）
        loading: true,         // 加载状态
        timeRange: 'all',      // 时间筛选：all=全部, 7=近7天, 30=近30天, custom=自定义
        customStart: '',       // 自定义开始日期
        customEnd: '',         // 自定义结束日期
        showAiResult: false,   // AI 分析结果浮层
        aiResult: '',          // AI 分析结果（Markdown）
        aiResultHtml: '',      // 渲染用 HTML
        aiLoading: false,      // 是否正在分析
        aiCache: {},           // 分析结果缓存
        windowHeight: 0,
        sheetHeight: 0,        // 浮层高度（视口 80%）
        recordFilter: 'all',   // 历史电费记录筛选：all=全部, increase=电费增加, decrease=电费减少
        recordsLoading: false, // 切换记录筛选时的局部加载状态
        balanceLive: false     // 当前余额是否已通过实时电费查询接口刷新
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
    },

    //时间筛选切换
    setTimeRange(e) {
        const range = e.currentTarget.dataset.range;
        this.setData({ timeRange: range });
        this.loadStatistics();
    },

    //历史电费记录筛选切换
    setRecordFilter(e) {
        const filter = e.currentTarget.dataset.filter;
        if (this.data.recordFilter === filter) return;
        this.setData({ recordFilter: filter });
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
            const res = await api.request({
                act: 'get_power_statistics',
                method: 'GET',
                data: this.buildQuery(dorm.room_num)
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
                recentTrend: data.recent_trend || [],       // 近期趋势数据
                dailyTrend: data.daily_trend || [],         // 每日趋势数据
                records: processedRecords,                   // 处理后的用电记录
                pricePerKwh: data.price_per_kwh || 0.61,    // 每度电价格
                loading: false,                              // 取消加载状态
                balanceLive: false                           // 先置为离线，待实时查询成功后再标记
            });
            
            // 当前余额改用实时电费查询接口获取（统计接口返回的是最后一条记录的余额，非实时）
            this.loadRealtimeBalance(dorm.room_num);
            
            // 延迟200ms绘制图表，等待DOM渲染完成
            setTimeout(() => {
                // 绘制近期趋势图（X轴标签为time字段）
                this.drawChart('recentChart', data.recent_trend || [], 'time');
                // 绘制每日趋势图（X轴标签为date字段）
                this.drawChart('dailyChart', data.daily_trend || [], 'date');
            }, 200);
        } catch (err) {
            // 获取统计数据失败
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '获取数据失败', icon: 'none' });
        }
    },

    // 统一处理记录：解析展示字段（记录已由服务端按筛选条件返回）
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
            }
        } catch (e) {
            // 实时查询失败时保留统计接口返回的余额，不打断页面
        }
    },

    //绘制折线图表
    drawChart(canvasId, data, labelKey) {
        // 无数据则不绘制
        if (!data || data.length === 0) return;
        
        // 获取Canvas节点（微信小程序Canvas 2D API）
        const query = wx.createSelectorQuery().in(this);
        query.select('#' + canvasId).fields({ node: true, size: true }).exec((res) => {
            // 节点不存在则返回
            if (!res || !res[0] || !res[0].node) return;
            
            // 获取Canvas节点和2D绘图上下文
            const canvas = res[0].node;
            const ctx = canvas.getContext('2d');
            // 获取Canvas的CSS尺寸
            const width = res[0].width;
            const height = res[0].height;
            
            // 根据设备像素比设置Canvas实际分辨率，确保高清显示
            const dpr = wx.getSystemInfoSync().pixelRatio;
            canvas.width = width * dpr;
            canvas.height = height * dpr;
            // 缩放绘图上下文以匹配设备像素比
            ctx.scale(dpr, dpr);
            
            // 定义图表边距（上、右、下、左）
            const padding = { top: 20, right: 15, bottom: 35, left: 45 };
            // 计算图表绘制区域的宽高
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            // 绘制白色背景
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, width, height);
            
            // 提取所有数据点的usage值，计算Y轴范围
            const values = data.map(d => d.usage);
            let maxVal = Math.max(...values);
            let minVal = Math.min(...values);
            // 所有值相同时，扩展范围避免除零
            if (maxVal === minVal) {
                maxVal = minVal + 1;
                minVal = Math.max(0, minVal - 1);
            }
            // 上下各留10%的边距，使数据点不贴边
            const range = maxVal - minVal;
            maxVal = maxVal + range * 0.1;
            minVal = Math.max(0, minVal - range * 0.1);
            
            // 绘制4条水平网格线和对应的Y轴标签
            ctx.strokeStyle = '#f0f0f0';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 4; i++) {
                // 计算网格线Y坐标
                const y = padding.top + chartHeight * i / 4;
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                
                // 计算Y轴标签值（从上到下递减）
                const val = maxVal - (maxVal - minVal) * i / 4;
                ctx.fillStyle = '#999';
                ctx.font = '10px sans-serif';
                ctx.textAlign = 'right';
                // 在Y轴左侧绘制标签值，保留2位小数
                ctx.fillText(val.toFixed(2), padding.left - 5, y + 3);
            }
            
            // 计算X轴步长（数据点之间的水平间距）
            const xStep = data.length > 1 ? chartWidth / (data.length - 1) : 0;
            // 计算每个数据点的像素坐标
            const points = data.map((d, i) => {
                // X坐标：只有一个数据点时居中，多个时等间距分布
                const x = padding.left + (data.length > 1 ? i * xStep : chartWidth / 2);
                // Y坐标：将usage值映射到图表高度范围内
                const y = padding.top + chartHeight - ((d.usage - minVal) / (maxVal - minVal)) * chartHeight;
                return { x, y, usage: d.usage, label: d[labelKey] || '' };
            });
            
            // 绘制折线下方的填充区域（半透明蓝色）
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            points.forEach((p, i) => {
                if (i > 0) ctx.lineTo(p.x, p.y);
            });
            // 连线到右下角和左下角，形成封闭区域
            ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
            ctx.lineTo(points[0].x, height - padding.bottom);
            ctx.closePath();
            ctx.fillStyle = 'rgba(0, 123, 255, 0.1)';
            ctx.fill();
            
            // 绘制折线（蓝色实线）
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            points.forEach((p, i) => {
                if (i > 0) ctx.lineTo(p.x, p.y);
            });
            ctx.strokeStyle = '#007bff';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // 绘制数据点（蓝色圆点+白色描边）
            points.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 3, 0, 2 * Math.PI);
                ctx.fillStyle = '#007bff';
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });
            
            // 绘制X轴标签（间隔显示，避免重叠）
            const labelStep = Math.ceil(data.length / 5);
            ctx.fillStyle = '#666';
            ctx.font = '9px sans-serif';
            ctx.textAlign = 'center';
            data.forEach((d, i) => {
                // 每隔labelStep个点显示一个标签，最后一个点也显示
                if (i % labelStep === 0 || i === data.length - 1) {
                    const x = padding.left + (data.length > 1 ? i * xStep : chartWidth / 2);
                    let label = d[labelKey] || '';
                    // 截断过长的标签：time类型保留月日时分，date类型保留月日
                    if (labelKey === 'time' && label.length > 11) {
                        label = label.substring(5, 16);
                    } else if (labelKey === 'date' && label.length > 5) {
                        label = label.substring(5);
                    }
                    ctx.fillText(label, x, height - padding.bottom + 15);
                }
            });
        });
    },

    // ===== AI 智能分析用电 =====
    async aiAnalyze() {
        if (this.data.aiLoading) {
            const t = '### AI 正在分析…\n\n正在生成分析报告，请稍候（通常需要十几秒）。';
            this.setData({ aiResult: t, aiResultHtml: markdownToHtml(t), showAiResult: true });
            this.initSheetHeight();
            wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
            return;
        }
        if (!this.data.stats) {
            wx.showToast({ title: '暂无用电数据', icon: 'none' });
            return;
        }
        const dorm = this.data.dormitories[this.data.selectedIndex];
        const key = 'd' + (dorm ? dorm.id : 0) + '|' + this.data.timeRange;
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
        const loadingText = '### AI 正在分析…\n\n正在读取用电数据并生成分析报告，请稍候（通常需要十几秒）。';
        this.setData({
            aiResult: loadingText,
            aiResultHtml: markdownToHtml(loadingText),
            showAiResult: true
        });
        this.initSheetHeight();
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
