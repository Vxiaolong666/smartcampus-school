/**
 * 功能开关模块
 * 用于控制各功能的启用/禁用状态，支持公告提示
 */

import api from './api.js';

/**
 * 功能开关缓存
 * 低端设备每次进入页面都请求一次接口会明显拖慢页面切换，
 * 这里做内存缓存 + 并发请求合并，避免重复、并发的网络等待。
 */
const CACHE_TTL = 5 * 60 * 1000; // 5 分钟

let settingsCache = null;
let settingsCacheAt = 0;
let pendingPromise = null;

/**
 * 获取所有功能开关设置（带内存缓存）
 * @async
 * @param {boolean} [force=false] 是否强制刷新缓存
 * @returns {Promise<Object|null>} 功能设置对象，失败返回 null
 * @example
 * const settings = await getFeatureSettings();
 * // 返回格式: { power_fee: { is_enabled: 1, feature_name: '电费查询', announcement: '...' }, ... }
 */
const getFeatureSettings = async (force = false) => {
    const now = Date.now();

    if (!force && settingsCache && now - settingsCacheAt < CACHE_TTL) {
        return settingsCache;
    }

    // 并发调用合并为一次请求，避免同一时间发起多个相同请求
    if (pendingPromise) return pendingPromise;

    pendingPromise = new Promise(resolve => {
        api.getFeatureSettings()
            .then(res => {
                settingsCache = res.data || null;
                settingsCacheAt = Date.now();
                resolve(settingsCache);
            })
            .catch(() => {
                resolve(settingsCache);
            })
            .then(() => {
                pendingPromise = null;
            });
    });

    return pendingPromise;
};

/**
 * 清空功能开关缓存
 * 退出登录 / 重新登录后调用，保证拿到最新的开关状态
 */
const clearFeatureCache = () => {
    settingsCache = null;
    settingsCacheAt = 0;
};

/**
 * 检查指定功能是否启用
 * @async
 * @param {string} featureKey - 功能标识，如 'power_fee', 'schedule', 'grade' 等
 * @returns {Promise<boolean>} 功能是否启用，默认返回 true
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
 * 显示功能禁用提示弹窗
 * @param {string} featureName - 功能名称
 * @param {Object|null} [announcement=null] - 公告对象
 */
const showDisabledAlert = (featureName, announcement = null) => {
    let content = `${featureName || '该功能'}暂未开放，请稍后再试`;
    if (announcement && announcement.content) {
        content = announcement.content;
    }
    uni.showModal({
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
 */
const getAnnouncementIcon = (type) => {
    const icons = {
        'info': '/static/assets/wxmp-info.svg',
        'success': '/static/assets/wxmp-check.svg',
        'warning': '/static/assets/wxmp-warning.svg',
        'danger': '/static/assets/wxmp-error.svg'
    };
    return icons[type] || icons['info'];
};

export {
    getFeatureSettings,
    clearFeatureCache,
    isFeatureEnabled,
    getFeatureAnnouncement,
    checkFeature,
    showDisabledAlert,
    getAnnouncementClass,
    getAnnouncementIcon
};

export default {
    getFeatureSettings,
    clearFeatureCache,
    isFeatureEnabled,
    getFeatureAnnouncement,
    checkFeature,
    showDisabledAlert,
    getAnnouncementClass,
    getAnnouncementIcon
};
