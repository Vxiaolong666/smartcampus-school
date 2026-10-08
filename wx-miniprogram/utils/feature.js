/**
 * 功能开关模块
 * 用于控制小程序各功能的启用/禁用状态，支持公告提示
 */

const api = require('./api.js');
const { getActiveContactModal } = require('./contact-modal.js');

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
        return { enabled: true, announcement: null, featureName: '', disabledAction: 'redirect' };
    }
    const feature = settings[featureKey];
    return {
        enabled: feature.is_enabled === 1,
        announcement: feature.announcement ? {
            content: feature.announcement,
            type: feature.announcement_type || 'info'
        } : null,
        featureName: feature.feature_name,
        // 功能未启用时的处理方式（后台专属设置项，目前用于小程序端「AI 对话」）：
        // mask=页面展示遮罩；redirect=跳转到用电统计页
        disabledAction: feature.disabled_action === 'mask' ? 'mask' : 'redirect'
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

            // 优先使用页面上的自定义「联系客服」弹窗（与「我的 - 联系客服」同款，
            // 由 cb-nav-bar 统一挂载）；关闭后与原生弹窗一致，离开当前功能页
            const customModal = getActiveContactModal();
            if (customModal) {
                customModal.open(() => leaveCurrentPage());
                return;
            }

            // 兜底：页面未挂载自定义弹窗时用原生弹窗
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

/* ==================== 「AI 对话」Tab 替换（按后台专属设置处理） ==================== */

// 第 3 个 Tab 的位置与文案（与 app.json 的 tabBar.list 顺序一致：首页 / 课程 / AI助手 / 我的）
const AI_TAB_INDEX = 2;
const AI_TAB_TEXT_DEFAULT = 'AI助手';
const AI_TAB_TEXT_DISABLED = '用电统计';
// 「AI 对话」功能标识
const AI_CHAT_FEATURE_KEY = 'wx_ai_chat';
// 未启用且处理方式为「跳转」时，用于替代 AI 页的页面（用电统计）
const POWER_STATISTICS_PAGE = '/pages/power-statistics/index';
// 自动恢复轮询间隔（毫秒）：仅在 Tab 处于「用电统计」状态期间生效
const AI_REVERT_INTERVAL = 30000;

// 最近一次已知的「Tab 是否已替换为用电统计」状态（null=尚未获取过），供 reapplyAiTabText 使用
let lastAiDisabled = null;
// 自动恢复轮询定时器
let aiRevertTimer = null;

/**
 * 按后台设置计算 Tab 状态
 * 只有「功能未启用 且 处理方式为跳转」才替换为「用电统计」；
 * 已启用 / 处理方式为遮罩 时都显示「AI助手」（遮罩方式下由 AI 页自己展示提示）
 * @param {Object|null} item - 功能设置项（读不到时视为已启用，与 checkFeature 默认行为一致）
 * @returns {{enabled:boolean, action:'mask'|'redirect', disabled:boolean}}
 */
const resolveAiTabState = (item) => {
    const enabled = !item ? true : item.is_enabled === 1;
    const action = (item && item.disabled_action === 'mask') ? 'mask' : 'redirect';
    return { enabled: enabled, action: action, disabled: !enabled && action === 'redirect' };
};

/** 停止「AI 功能重新启用」的自动恢复轮询 */
const stopAiRevertWatcher = () => {
    if (aiRevertTimer) {
        clearInterval(aiRevertTimer);
        aiRevertTimer = null;
    }
};

/**
 * 启动自动恢复轮询：Tab 处于「用电统计」期间，定时重新检测后台开关与处理方式；
 * 一旦 AI 功能重新启用（或处理方式改为遮罩），文案会自动恢复为「AI助手」并停止轮询。
 * 非替换状态下不会启动轮询，因此正常使用时没有额外请求。
 */
const startAiRevertWatcher = () => {
    if (aiRevertTimer) return;
    aiRevertTimer = setInterval(() => {
        if (lastAiDisabled !== true) {
            stopAiRevertWatcher();
            return;
        }
        syncAiTabBar();
    }, AI_REVERT_INTERVAL);
};

/**
 * 设置第 3 个 Tab 的文案（不发起请求）
 * 图标始终沿用 app.json 中配置的 AI 图标，不做改动
 * @param {boolean} disabled - 是否替换为「用电统计」
 */
const setAiTabText = (disabled) => {
    lastAiDisabled = !!disabled;
    try {
        wx.setTabBarItem({
            index: AI_TAB_INDEX,
            text: disabled ? AI_TAB_TEXT_DISABLED : AI_TAB_TEXT_DEFAULT,
            fail: () => {}
        });
    } catch (e) {
        // 非小程序端 / 基础库不支持时忽略
    }
    // 处于替换状态时启动自动恢复轮询；一旦恢复（或本来就是正常状态）立即停止
    if (lastAiDisabled) {
        startAiRevertWatcher();
    } else {
        stopAiRevertWatcher();
    }
};

/**
 * 按最近一次已知状态重新设置 Tab 文案（不发起请求）
 *
 * 用途：wx.showTabBar() 会把 Tab 文案重置回 app.json 中配置的「AI助手」，
 * 因此每次 showTabBar 之后都要重新应用一次（如首页开屏结束后、页面卸载时的兜底恢复）。
 * @returns {boolean} 是否已有已知状态并完成重放
 */
const reapplyAiTabText = () => {
    if (lastAiDisabled === null) return false;
    setAiTabText(lastAiDisabled);
    return true;
};

/**
 * 拉取「AI 对话」开关与未启用处理方式，并同步第 3 个 Tab 文案
 * - 已启用 → 「AI助手」（后台重新启用后自动恢复）
 * - 未启用 + 处理方式为跳转 → 「用电统计」
 * - 未启用 + 处理方式为遮罩 → 仍显示「AI助手」（由 AI 页展示遮罩提示）
 * @async
 * @returns {Promise<{enabled:boolean, action:'mask'|'redirect', disabled:boolean}>}
 */
const syncAiTabBar = async () => {
    let state = { enabled: true, action: 'redirect', disabled: false };
    try {
        const settings = await getFeatureSettings();
        state = resolveAiTabState(settings ? settings[AI_CHAT_FEATURE_KEY] : null);
    } catch (e) {
        return state; // 接口异常时保持原状，避免误改文案
    }
    setAiTabText(state.disabled);
    return state;
};

/**
 * 读取「AI 对话」未启用时的处理方式（后台专属设置项）
 * @async
 * @returns {Promise<'mask'|'redirect'>} mask=页面展示遮罩；redirect=跳转到用电统计页（默认）
 */
const getAiDisabledAction = async () => {
    const settings = await getFeatureSettings();
    return resolveAiTabState(settings ? settings[AI_CHAT_FEATURE_KEY] : null).action;
};

/**
 * AI 功能未启用、且处理方式为「跳转」时，用「用电统计」页代替 AI 页
 *
 * 原生 tabBar 只能改文字/图标、不能改跳转页面，
 * 因此进入 AI 页后跳转到用电统计页。
 * 用 redirectTo 不留返回栈，避免返回后又落回 AI 页来回跳；
 * 用电统计页顶部返回按钮在无上一页时会回到首页（见 cb-nav-bar 的 handleBack），用户不会被困住。
 *
 * @param {Function} [onFail] 跳转失败时的回调（用于保留原「功能未启用」提示兜底）
 */
const goPowerStatisticsInsteadOfAi = (onFail) => {
    wx.redirectTo({
        url: POWER_STATISTICS_PAGE,
        fail: () => {
            if (typeof onFail === 'function') onFail();
        }
    });
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
    getAnnouncementIcon,
    setAiTabText,
    reapplyAiTabText,
    syncAiTabBar,
    getAiDisabledAction,
    goPowerStatisticsInsteadOfAi
};
