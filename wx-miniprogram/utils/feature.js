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
 * 显示功能禁用提示弹窗
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
        showCancel: false,
        confirmText: '我知道了'
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
    showDisabledAlert,
    getAnnouncementClass,
    getAnnouncementIcon
};
