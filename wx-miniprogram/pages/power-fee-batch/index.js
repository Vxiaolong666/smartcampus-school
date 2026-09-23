/**
 * 批量查电页面
 * 按校区和楼栋批量查询宿舍电费余额
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

Page({
    data: {
        featureEnabled: true,           // 功能是否启用（功能开关控制）
        announcement: null,            // 公告信息（功能禁用时显示）
        areas: [],                     // 校区列表（如：广州校区、茂名校区）
        buildings: [],                 // 楼栋列表（如：A栋、B栋）
        areaIndex: -1,                 // 选中的校区索引（picker选择后更新）
        buildingIndex: -1,              // 选中的楼栋索引（picker选择后更新）
        roomCount: 0,                   // 该楼栋的房间数量（选择楼栋后自动获取）
        queryProgress: 0,              // 当前查询进度（已完成数量）
        queryTotal: 0,                 // 总查询数量（房间总数）
        querying: false,               // 是否正在查询中（控制按钮状态）
        results: [],                   // 查询结果列表（包含每个房间的电费信息）
        stats: {                       // 统计结果（按状态分类计数）
            normal: 0,                // 正常数量（余额充足）
            warning: 0,               // 预警数量（余额较低）
            low: 0,                   // 电量低数量（即将欠费）
            arrears: 0                // 欠费数量（已欠费）
        },
        filterType: 'all'              // 筛选类型（all/normal/warning/low/arrears）
    },

    // 页面加载时执行
    onLoad() {
        // 检查登录状态，未登录则跳转登录页
        if (!api.isLoggedIn()) {
            // 先说明登录用途（合规），再跳转登录页
            api.goLoginWithTip();
            return;
        }
        
        // 检查功能开关，根据结果决定是否继续加载
        this.checkFeature().then(canProceed => {
            if (canProceed) {
                this.loadAreas();
            }
        });
    },



    // 检查批量查电功能是否启用
    async checkFeature() {
        // 调用功能开关接口
        const result = await feature.checkFeature('power_fee_batch');
        
        // 功能禁用处理
        if (!result.enabled) {
            this.setData({
                featureEnabled: false,
                announcement: null
            });
            // 弹出禁用提示框
            feature.showDisabledAlert(result.featureName, result.announcement);
            return false;
        }

        // 保存公告信息（如有）
        if (result.announcement) {
            this.setData({ announcement: result.announcement });
        }
        return true;
    },


    // 加载校区列表，从API获取所有可选校区，填充校区选择器
    async loadAreas() {
        // 功能禁用时直接返回
        if (!this.data.featureEnabled) return;
        
        try {
            // 调用API获取校区列表
            const res = await api.getDormAreas();
            // 更新校区数据
            this.setData({ areas: res.data || [] });
        } catch (err) {
            // 获取失败提示
            wx.showToast({ title: '获取校区失败', icon: 'none' });
        }
    },



    // 校区选择变化事件：选择新校区后，重置楼栋、房间数和查询结果，并自动加载该校区下的楼栋列表
    async onAreaChange(e) {
        // 获取选中的校区索引
        const index = e.detail.value;
        const area = this.data.areas[index];

        // 重置相关状态
        this.setData({
            areaIndex: index,
            buildingIndex: -1,      // 重置楼栋选择
            buildings: [],          // 清空楼栋列表
            roomCount: 0,           // 重置房间数
            results: []             // 清空查询结果
        });

        // 加载该校区下的楼栋列表
        if (area) {
            try {
                const res = await api.getDormBuildings(area);
                this.setData({ buildings: res.data || [] });
            } catch (err) {
                wx.showToast({ title: '获取楼栋失败', icon: 'none' });
            }
        }
    },

    // 楼栋选择变化事件：选择新楼栋后，重置查询结果，并自动获取该楼栋的房间数量
    async onBuildingChange(e) {
        // 获取选中的楼栋索引
        const index = e.detail.value;
        const building = this.data.buildings[index];
        const area = this.data.areas[this.data.areaIndex];

        // 重置相关状态
        this.setData({
            buildingIndex: index,
            roomCount: 0,          // 重置房间数
            results: []            // 清空查询结果
        });

        // 获取该楼栋的房间数量
        if (building && area) {
            try {
                const res = await api.getDormRooms(area, building);
                // 更新房间数量显示
                this.setData({ roomCount: (res.data || []).length });
            } catch (err) {
                wx.showToast({ title: '获取房间失败', icon: 'none' });
            }
        }
    },



    // 开始批量查询：获取当前楼栋下所有房间，逐个查询电费信息，实时更新进度和统计结果
    async onQuery() {
        const { areaIndex, buildingIndex, areas, buildings } = this.data;

        // 验证选择：必须同时选择校区和楼栋
        if (areaIndex < 0 || buildingIndex < 0) {
            wx.showToast({ title: '请选择校区和楼栋', icon: 'none' });
            return;
        }

        const area = areas[areaIndex];
        const building = buildings[buildingIndex];

        // 初始化查询状态
        this.setData({
            querying: true,                // 开始查询
            results: [],                  // 清空结果
            queryProgress: 0,             // 重置进度
            queryTotal: 0,                // 重置总数
            stats: { normal: 0, warning: 0, low: 0, arrears: 0 }  // 重置统计
        });

        try {
            // 获取该楼栋下的所有房间列表
            const roomsRes = await api.getDormRooms(area, building);
            const rooms = roomsRes.data || [];

            // 无房间数据处理
            if (rooms.length === 0) {
                this.setData({ querying: false });
                wx.showToast({ title: '该楼栋暂无房间数据', icon: 'none' });
                return;
            }

            // 更新总查询数
            this.setData({ queryTotal: rooms.length });

            // 初始化结果和统计
            const results = [];
            const stats = { normal: 0, warning: 0, low: 0, arrears: 0 };

            // 逐个查询每个房间的电费
            for (let i = 0; i < rooms.length; i++) {
                const room = rooms[i];

                try {
                    // 调用电费查询接口
                    const res = await api.queryPowerFee(room.room_num);
                    const balance = res.data.balance;
                    const status = res.data.status;

                    // 添加成功结果
                    results.push({
                        room: room.room,           // 房间显示名
                        roomNum: room.room_num,    // 房间编号
                        balance: balance,          // 余额
                        status: status             // 状态
                    });

                    // 更新统计计数
                    if (status === 'normal') stats.normal++;
                    else if (status === 'warning') stats.warning++;
                    else if (status === 'low') stats.low++;
                    else if (status === 'arrears') stats.arrears++;

                } catch (err) {
                    // 查询失败结果
                    results.push({
                        room: room.room,
                        roomNum: room.room_num,
                        balance: null,
                        status: 'error'
                    });
                }

                // 实时更新页面数据（显示进度）
                this.setData({
                    queryProgress: i + 1,      // 已完成数量
                    results: [...results],     // 追加结果
                    stats: {...stats}          // 更新统计
                });

                // 控制查询间隔（100ms），避免请求过快导致接口限流
                await new Promise(resolve => setTimeout(resolve, 100));
            }

            // 查询完成
            this.setData({ querying: false });

        } catch (err) {
            // 查询异常处理
            this.setData({ querying: false });
            wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
        }
    },


    // 筛选类型变化事件：根据选择的状态类型筛选结果列表
    onFilterChange(e) {
        const index = e.detail.value;
        // 筛选类型数组（与wxml中的picker对应）
        const filterTypes = ['all', 'normal', 'warning', 'low', 'arrears'];
        this.setData({ filterType: filterTypes[index] });
    },

    // 获取筛选后的结果，用于wxml中根据filterType动态显示筛选结果
    getFilteredResults() {
        const { results, filterType } = this.data;
        // 'all'显示全部，其他按状态筛选
        if (filterType === 'all') return results;
        return results.filter(r => r.status === filterType);
    },



    // 返回上一页，用于顶部导航栏返回按钮
    goBack() {
        wx.navigateBack();
    }
});
