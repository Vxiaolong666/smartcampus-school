/**
 * 宿舍绑定页面
 * 管理宿舍绑定和解绑操作
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

Page({
    data: {
        loading: true,          // 加载状态
        dormitories: [],        // 已绑定的宿舍列表
        areas: [],              // 校区列表
        buildings: [],          // 楼栋列表
        rooms: [],              // 房间列表
        areaIndex: -1,          // 选中的校区索引
        buildingIndex: -1,      // 选中的楼栋索引
        roomIndex: -1,          // 选中的房间索引
        alias: '',              // 宿舍别名
        showAddPanel: false,    // 是否显示添加面板
        maxDormitories: 5,      // 最大绑定数量
        announcement: null      // 公告信息
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
            if (!canProceed) return;
            this.loadDormitories();
        });
    },

    //检查功能开关
    async checkFeature() {
        try {
            const result = await feature.checkFeature('dormitory_bind');
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

    //加载已绑定的宿舍列表
    async loadDormitories() {
        try {
            const res = await api.getDormitories();
            // 处理宿舍列表，生成显示名称
            const dormitories = (res.data || []).map(item => ({
                ...item,
                displayName: item.display_name || (item.alias ? (item.alias + '（' + item.school_area + item.building + ' ' + item.room + '）') : (item.school_area + item.building + ' ' + item.room))
            }));
            this.setData({
                dormitories: dormitories,
                loading: false
            });
        } catch (err) {
            this.setData({ loading: false });
            wx.showToast({ title: err.msg || '加载失败', icon: 'none' });
        }
    },

    //加载校区列表
    async loadAreas() {
        try {
            const res = await api.getDormAreas();
            this.setData({ areas: res.data || [] });
        } catch (err) {
            wx.showToast({ title: '获取校区失败', icon: 'none' });
        }
    },

    //校区选择变化
    async onAreaChange(e) {
        const index = e.detail.value;
        const area = this.data.areas[index];

        // 重置楼栋和房间选择
        this.setData({
            areaIndex: index,
            buildingIndex: -1,
            roomIndex: -1,
            buildings: [],
            rooms: []
        });

        // 加载该校区下的楼栋
        if (area) {
            try {
                const res = await api.getDormBuildings(area);
                this.setData({ buildings: res.data || [] });
            } catch (err) {
                wx.showToast({ title: '获取楼栋失败', icon: 'none' });
            }
        }
    },

    //楼栋选择变化
    async onBuildingChange(e) {
        const index = e.detail.value;
        const building = this.data.buildings[index];
        const area = this.data.areas[this.data.areaIndex];

        // 重置房间选择
        this.setData({
            buildingIndex: index,
            roomIndex: -1,
            rooms: []
        });

        // 加载该楼栋下的房间
        if (building && area) {
            try {
                const res = await api.getDormRooms(area, building);
                this.setData({ rooms: res.data || [] });
            } catch (err) {
                wx.showToast({ title: '获取房间失败', icon: 'none' });
            }
        }
    },

    //房间选择变化
    onRoomChange(e) {
        this.setData({ roomIndex: e.detail.value });
    },

    //输入宿舍别名
    onAliasInput(e) {
        this.setData({ alias: e.detail.value });
    },

    //显示添加宿舍面板
    showAddPanel() {
        // 检查是否已达最大绑定数量
        if (this.data.dormitories.length >= this.data.maxDormitories) {
            wx.showToast({ title: '最多绑定5个宿舍', icon: 'none' });
            return;
        }
        this.setData({ showAddPanel: true });
        this.loadAreas();
    },

    //隐藏添加宿舍面板
    hideAddPanel() {
        this.setData({
            showAddPanel: false,
            areaIndex: -1,
            buildingIndex: -1,
            roomIndex: -1,
            alias: ''
        });
    },

    //提交绑定宿舍
    async onBindSubmit() {
        const { areaIndex, buildingIndex, roomIndex, areas, buildings, rooms, alias, dormitories } = this.data;

        // 验证选择是否完整
        if (areaIndex < 0 || buildingIndex < 0 || roomIndex < 0) {
            wx.showToast({ title: '请选择完整信息', icon: 'none' });
            return;
        }

        // 构建绑定数据
        const room = rooms[roomIndex];
        const data = {
            school_area: areas[areaIndex],
            building: buildings[buildingIndex],
            room: room.room,
            room_num: room.room_num,
            alias: alias
        };

        // 调用绑定API
        wx.showLoading({ title: '绑定中...' });
        try {
            await api.bindDormitory(data);
            wx.hideLoading();
            wx.showToast({ title: '绑定成功', icon: 'success' });
            this.hideAddPanel();
            this.loadDormitories();
        } catch (err) {
            wx.hideLoading();
            wx.showToast({ title: err.msg || '绑定失败', icon: 'none' });
        }
    },

    //解绑宿舍
    async onUnbind(e) {
        const id = e.currentTarget.dataset.id;

        // 确认解绑
        const res = await wx.showModal({
            title: '确认解绑',
            content: '确定要解绑这个宿舍吗？'
        });

        if (res.confirm) {
            try {
                await api.unbindDormitory(id);
                wx.showToast({ title: '解绑成功', icon: 'success' });
                this.loadDormitories();
            } catch (err) {
                wx.showToast({ title: err.msg || '解绑失败', icon: 'none' });
            }
        }
    },

    //设置默认宿舍
    async onSetDefault(e) {
        const id = e.currentTarget.dataset.id;
        const item = (this.data.dormitories || []).find(d => String(d.id) === String(id)) || {};

        // 已是默认宿舍：灰色按钮仅作展示，点击给出提示
        if (item.is_default == 1) {
            wx.showToast({ title: '该宿舍已是默认宿舍', icon: 'none' });
            return;
        }

        const name = item.displayName || '该宿舍';
        const res = await new Promise(resolve => {
            wx.showModal({
                title: '设置默认',
                content: '确认把默认宿舍改为「' + name + '」吗？',
                confirmText: '确认',
                cancelText: '取消',
                success: resolve
            });
        });
        if (!res.confirm) return;

        try {
            await api.setDefaultDormitory(id);
            wx.showToast({ title: '设置成功', icon: 'success' });
            this.loadDormitories();
        } catch (err) {
            wx.showToast({ title: err.msg || '设置失败', icon: 'none' });
        }
    },

    //跳转到电费查询页
    goToPowerFee() {
        wx.navigateTo({ url: '/pages/power-fee/index' });
    },

    //阻止事件冒泡
    preventBubble() {}
});
