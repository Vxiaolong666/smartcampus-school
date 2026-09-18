/**
 * 课程节次时间（作息）加载器
 * ------------------------------------------------------------------
 * 教务系统接口只返回「节数」（如 classTime = 105106 表示第 5、6 节），不返回具体时间；
 * 各节次时间由后台「课程时间配置」按课程模式维护。
 *
 * 这里统一请求后台并在成功后**就地更新** common.js 的 timeSlots，
 * 因此各页面继续使用 timeSlots 即可拿到后台配置；请求失败或后台未配置时
 * 自动沿用内置默认作息，不影响功能。
 */

const api = require('./api.js');
const { applyTimeSlots, timeSlots } = require('./common.js');

// 已成功加载过的模式（'' 表示默认模式）
let loadedKey = null;
let loadedAt = 0;
// 缓存有效期：避免每次进页面都请求，又能让后台调整后较快生效
const CACHE_TTL = 5 * 60 * 1000;
// 正在请求中的模式，避免同一模式的并发重复请求
let loadingKey = null;
let loadingPromise = null;

/**
 * 加载并应用课程节次时间
 *
 * @param {string} [modeId] 课程模式 ID（教务 kbjcmsid），留空表示使用「默认模式」
 * @param {boolean} [force=false] 是否强制重新请求（忽略缓存）
 * @returns {Promise<Object>} 始终 resolve，返回最新的 timeSlots 对象
 */
const loadScheduleTimes = (modeId = '', force = false) => {
    const key = modeId ? String(modeId) : '';

    if (!force && loadedKey === key && Date.now() - loadedAt < CACHE_TTL) {
        return Promise.resolve(timeSlots);
    }
    if (loadingKey === key && loadingPromise) {
        return loadingPromise;
    }

    loadingKey = key;
    loadingPromise = api
        .getScheduleTimes(key)
        .then(res => {
            const periods = res && res.data && res.data.periods ? res.data.periods : [];
            if (applyTimeSlots(periods)) {
                loadedKey = key;
                loadedAt = Date.now();
            }
            return timeSlots;
        })
        .catch(err => {
            console.warn('获取课程时间配置失败，沿用内置默认作息', err);
            return timeSlots;
        })
        .then(result => {
            loadingKey = null;
            loadingPromise = null;
            return result;
        });

    return loadingPromise;
};

module.exports = { loadScheduleTimes };
module.exports.default = loadScheduleTimes;
