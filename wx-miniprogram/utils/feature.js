/**
 * 功能开关模块
 * 用于控制小程序各功能的启用/禁用状态，支持公告提示
 */

const api = require('./api.js');

/**
 * 获取所有功能开关设置
 * @async
 * @returns {Promise<Object|null>} 功能设置对象，失败返回 null
 * @example
 * const settings = await getFeatureSettings();
 * // 返回格式: { power_fee: { is_enabled: 1, feature_name: '电费查询', announcement: '...' }, ... }
 */
const getFeatureSettings = async () => {
    try {
        const res = await api.getFeatureSettings();
        return res.data;
    } catch (err) {
        return null;
    }
};

/**
 * 检查指定功能是否启用
 * @async
 * @param {string} featureKey - 功能标识，如 'power_fee', 'schedule', 'grade' 等
 * @returns {Promise<boolean>} 功能是否启用，默认返回 true
 * @example
 * const enabled = await isFeatureEnabled('power_fee');
 * if (!enabled) {
 *   // 功能已禁用
 * }
 */
const isFeatureEnabled = async (featureKey) => {
    const settings = await getFeatureSettings();
    if (!settings || !settings[featureKey]) {
        return true;
    }
    return settings[featureKey].is_enabled === 1;
};

/**
 * 获取指定功能的公告信息
 * @async
 * @param {string} featureKey - 功能标识
 * @returns {Promise<Object|null>} 公告对象，无公告返回 null
 * @returns {string} return.content - 公告内容
 * @returns {string} return.type - 公告类型：'info' | 'success' | 'warning' | 'danger'
 * @example
 * const announcement = await getFeatureAnnouncement('power_fee');
 * // { content: '系统维护中', type: 'warning' }
 */
const getFeatureAnnouncement = async (featureKey) => {
    const settings = await getFeatureSettings();
    if (!settings || !settings[featureKey]) {
        return null;
    }
    const feature = settings[featureKey];
    if (!feature.announcement) {
        return null;
    }
    return {
        content: feature.announcement,
        type: feature.announcement_type || 'info'
    };
};

/**
 * 检查功能状态（综合检查）
 * @async
 * @param {string} featureKey - 功能标识
 * @returns {Promise<Object>} 功能状态对象
 * @returns {boolean} return.enabled - 是否启用
 * @returns {Object|null} return.announcement - 公告信息
 * @returns {string} return.featureName - 功能名称
 * @example
 * const status = await checkFeature('power_fee');
 * // { enabled: false, announcement: { content: '维护中', type: 'warning' }, featureName: '电费查询' }
 */
const checkFeature = async (featureKey) => {
    const settings = await getFeatureSettings();
    if (!settings || !settings[featureKey]) {
        return { enabled: true, announcement: null, featureName: '' };
    }
    const feature = settings[featureKey];
    return {
        enabled: feature.is_enabled === 1,
        announcement: feature.announcement ? {
            content: feature.announcement,
            type: feature.announcement_type || 'info'
        } : null,
        featureName: feature.feature_name
    };
};

/**
 * 检查「维护开关」weihu_open 是否开启（维护页专用）
 *
 * 注意：维护开关的语义是「开启 = 正在维护」，与普通功能开关相反。
 * 因此「接口失败 / 读不到该开关」时必须判定为「未开启」，
 * 绝不能沿用 checkFeature 的 enabled:true 默认值，否则接口偶发失败时
 * 会被误判为「管理员已开启维护」而跳到维护页，页面随后又判定「维护已结束」跳回首页。
 *
 * @async
 * @returns {Promise<{enabled:boolean, known:boolean, announcement:Object|null, featureName:string}>}
 *   enabled 是否正在维护；known 为 false 表示配置读取失败（无法确认状态）
 */
const checkMaintenanceSwitch = async () => {
    const settings = await getFeatureSettings();
    const item = settings ? settings['weihu_open'] : null;
    if (!item) {
        // settings 为 null：接口失败 → known=false，调用方不应据此判定「维护已结束」
        // settings 非空但没有 weihu_open：后台未配置该开关 → 明确视为未开启（known=true）
        return { enabled: false, known: !!settings, announcement: null, featureName: '' };
    }
    return {
        enabled: item.is_enabled === 1,
        known: true,
        announcement: item.announcement ? {
            content: item.announcement,
            type: item.announcement_type || 'info'
        } : null,
        featureName: item.feature_name || ''
    };
};

/**
 * 判断接口错误是否为「未绑定教务账号」
 * 教务相关接口（成绩 / 课程表 / 学籍等）在未绑定时返回 code -1 且提示包含「绑定」；
 * 各页面据此展示「请先绑定教务账号」而非「无数据」。
 * @param {Object} err - 请求错误对象 { code, msg }
 * @returns {boolean}
 */
const isAcademicNotBound = (err) => {
    return !!(err && Number(err.code) === -1 && /绑定/.test(String(err.msg || '')));
};

/**
 * 打开「联系客服」：拉取后台维护的联系方式并以弹窗展示
 * （功能禁用弹窗的联系客服入口，与公告 / 维护页联系方式同源）
 * 关闭（点击「我知道了」）后同样离开当前功能页，与功能禁用弹窗的确认行为保持一致
 */
const openContactInfo = () => {
    wx.showLoading({ title: '加载中...' });
    api.getContactInfo()
        .then(res => {
            const info = (res && res.data) || null;
            wx.hideLoading();
            const lines = [];
            if (info) {
                if (info.phone) lines.push('联系电话：' + info.phone);
                if (info.email) lines.push('电子邮箱：' + info.email);
                if (info.qq) lines.push('QQ：' + info.qq);
                if (info.worktime) lines.push('工作时间：' + info.worktime);
                if (info.address) lines.push('联系地址：' + info.address);
                if (info.desc) lines.push('说明：' + info.desc);
            }
            if (!lines.length) {
                lines.push('暂未配置联系方式，请在「我的」页面通过「联系客服」与我们取得联系。');
            }
            wx.showModal({
                title: '联系客服',
                content: lines.join('\n'),
                showCancel: false,
                confirmText: '我知道了',
                success: () => {
                    // 与功能禁用弹窗点「我知道了」一致：查看完联系方式后离开当前功能页
                    leaveCurrentPage();
                }
            });
        })
        .catch(() => {
            wx.hideLoading();
            wx.showToast({ title: '联系方式获取失败', icon: 'none' });
        });
};

/**
 * 离开当前功能页（与 goLoginWithTip 的回退逻辑一致）
 * 页面栈里有上一页则返回，无上一页（如标签页）则回到可免登录浏览的首页
 */
const leaveCurrentPage = () => {
    const pages = typeof getCurrentPages === 'function' ? getCurrentPages() : [];
    if (pages.length > 1) {
        wx.navigateBack({ delta: 1 });
    } else {
        wx.switchTab({ url: '/pages/white-home/index' });
    }
};

/**
 * 显示功能禁用提示弹窗
 * 提供「联系客服」（打开联系方式）与「我知道了」（关闭并回退页面）两个入口
 * @param {string} featureName - 功能名称
 * @param {Object|null} [announcement=null] - 公告对象
 * @param {string} announcement.content - 公告内容
 * @param {string} announcement.type - 公告类型
 * @example
 * showDisabledAlert('电费查询', { content: '系统维护中，预计明天恢复', type: 'warning' });
 */
const showDisabledAlert = (featureName, announcement = null) => {
    let content = `${featureName || '该功能'}暂未开放，请稍后再试`;
    if (announcement && announcement.content) {
        content = announcement.content;
    }
    wx.showModal({
        title: '功能暂未开放',
        content: content,
        showCancel: true,
        cancelText: '联系客服',
        confirmText: '我知道了',
        success: (res) => {
            // 点击「联系客服」：拉取后台联系方式后展示
            if (res.cancel) {
                openContactInfo();
                return;
            }
            // 点击「我知道了」：关闭弹窗并回退页面
            if (res.confirm) {
                leaveCurrentPage();
            }
        }
    });
};

/**
 * 根据公告类型获取对应的 CSS 类名
 * @param {string} type - 公告类型：'info' | 'success' | 'warning' | 'danger'
 * @returns {string} CSS 类名
 * @example
 * const className = getAnnouncementClass('warning'); // 'announcement-warning'
 */
const getAnnouncementClass = (type) => {
    const classes = {
        'info': 'announcement-info',
        'success': 'announcement-success',
        'warning': 'announcement-warning',
        'danger': 'announcement-danger'
    };
    return classes[type] || 'announcement-info';
};

/**
 * 根据公告类型获取对应的图标路径
 * @param {string} type - 公告类型：'info' | 'success' | 'warning' | 'danger'
 * @returns {string} 图标 SVG 文件路径
 * @example
 * const iconPath = getAnnouncementIcon('warning'); // '/assets/wxmp-warning.svg'
 */
const getAnnouncementIcon = (type) => {
    const icons = {
        'info': '/assets/wxmp-info.svg',
        'success': '/assets/wxmp-check.svg',
        'warning': '/assets/wxmp-warning.svg',
        'danger': '/assets/wxmp-error.svg'
    };
    return icons[type] || icons['info'];
};

module.exports = {
    getFeatureSettings,
    isFeatureEnabled,
    getFeatureAnnouncement,
    checkFeature,
    checkMaintenanceSwitch,
    isAcademicNotBound,
    showDisabledAlert,
    getAnnouncementClass,
    getAnnouncementIcon
};
