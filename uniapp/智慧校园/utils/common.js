/**
 * 公共工具模块
 * 1. 课程颜色管理 - 为不同课程分配不同颜色
 * 2. 时间格式化 - 格式化时间范围显示
 * 3. 常量配置 - 时间段、星期、场景类型等配置
 */

// 课程颜色配置，bg: 背景色、text: 文字色、border: 边框色
const COURSE_COLORS = [
    { bg: '#e3f2fd', text: '#1565c0', border: '#90caf9' },
    { bg: '#e8f5e9', text: '#2e7d32', border: '#a5d6a7' },
    { bg: '#fff3e0', text: '#e65100', border: '#ffcc80' },
    { bg: '#fce4ec', text: '#c2185b', border: '#f48fb1' },
    { bg: '#f3e5f5', text: '#7b1fa2', border: '#ce93d8' },
    { bg: '#e0f7fa', text: '#00838f', border: '#80deea' },
    { bg: '#fff8e1', text: '#f57f17', border: '#ffe082' },
    { bg: '#efebe9', text: '#5d4037', border: '#bcaaa4' },
    { bg: '#e8eaf6', text: '#303f9f', border: '#9fa8da' },
    { bg: '#f1f8e9', text: '#558b2f', border: '#c5e1a5' },
    { bg: '#ffebee', text: '#c62828', border: '#ef9a9a' },
    { bg: '#e1f5fe', text: '#0277bd', border: '#81d4fa' },
    { bg: '#f9fbe7', text: '#9e9d24', border: '#dce775' },
    { bg: '#eceff1', text: '#455a64', border: '#b0bec5' },
    { bg: '#fdf2f9', text: '#ad1457', border: '#f8bbd9' },
    { bg: '#e0f2f1', text: '#00695c', border: '#80cbc4' }
];

// 根据课程名称获取颜色，使用哈希算法，确保同一门课程每次都显示相同的颜色。
const getCourseColor = (courseName) => {
    // 如果没有课程名，返回默认颜色
    if (!courseName) return COURSE_COLORS[0];

    // 计算课程名的哈希值
    let hash = 0;
    for (let i = 0; i < courseName.length; i++) {
        hash = courseName.charCodeAt(i) + ((hash << 5) - hash);
        hash = hash & hash;
    }

    // 根据哈希值返回对应的颜色
    return COURSE_COLORS[Math.abs(hash) % COURSE_COLORS.length];
};

/**
 * 格式化时间范围
 * @param {Object} timeRange - 时间范围对象
 * @param {string} timeRange.start - 开始时间
 * @param {string} timeRange.end - 结束时间
 * @returns {string} 格式化后的时间字符串
 */
const formatTimeRange = (timeRange) => {
    if (!timeRange || !timeRange.start || !timeRange.end) return '';
    const startDate = formatDate(timeRange.start);
    const endDate = formatDate(timeRange.end);
    if (startDate === endDate) {
        return `${startDate} ${String(timeRange.start).substring(11, 16)}-${String(timeRange.end).substring(11, 16)}`;
    }
    return `${formatDateTime(timeRange.start)} - ${formatDateTime(timeRange.end)}`;
};

/**
 * 日期展示（仅日期）：2026-9-21 12:30:00 / 2026/9/21 → 2026/9/21
 * 仅处理 YYYY-M-D 或 YYYY/M/D（可带时间）形式，其余原样返回
 */
const formatDate = (v) => {
    if (v === null || v === undefined || v === '') return '';
    const s = String(v).trim();
    const m = /^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::\d{2})?)?$/.exec(s);
    if (!m) return s;
    return m[1] + '/' + parseInt(m[2], 10) + '/' + parseInt(m[3], 10);
};

/**
 * 日期时间展示：2026-9-21 8:05:30 / 2026/9/21 08:05 → 2026/9/21 08:05
 * 无时间部分时等同 formatDate，其余原样返回
 */
const formatDateTime = (v) => {
    if (v === null || v === undefined || v === '') return '';
    const s = String(v).trim();
    const m = /^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::\d{2})?)?$/.exec(s);
    if (!m) return s;
    const date = m[1] + '/' + parseInt(m[2], 10) + '/' + parseInt(m[3], 10);
    if (m[4] === undefined) return date;
    return date + ' ' + (m[4].length < 2 ? '0' + m[4] : m[4]) + ':' + m[5];
};

// 时间段配置
// 键为节次，值为时间段名称和时间范围
const timeSlots = {
    1: { name: '第1节', time: '08:15-08:55' },
    2: { name: '第2节', time: '09:05-09:45' },
    3: { name: '第3节', time: '10:05-10:45' },
    4: { name: '第4节', time: '10:55-11:35' },
    5: { name: '第5节', time: '14:30-15:10' },
    6: { name: '第6节', time: '15:20-16:00' },
    7: { name: '第7节', time: '16:15-16:55' },
    8: { name: '第8节', time: '18:50-19:30' },
    9: { name: '第9节', time: '19:40-20:20' },
    10: { name: '第10节', time: '20:30-21:10' },
    11: { name: '第11节', time: '21:20-22:00' }
};

// 内置默认作息（副本，用于后台配置缺失时兜底）
const DEFAULT_TIME_SLOTS = JSON.parse(JSON.stringify(timeSlots));

/**
 * 应用后台「课程时间配置」的节次时间
 * - 就地修改 timeSlots 对象，已 import timeSlots 的页面无需改动即可生效
 * - 后台未配置的节次保留内置默认值，避免出现空节次导致取值报错
 *
 * @param {Array} periods 后台返回的 periods，形如 [{index:1,name:'第1节',start:'08:15',end:'08:55'}]
 * @returns {boolean} 是否有配置生效
 */
const applyTimeSlots = (periods) => {
    if (!Array.isArray(periods) || periods.length === 0) return false;

    const next = {};
    // 先铺内置默认，保证 1~11 节始终存在
    Object.keys(DEFAULT_TIME_SLOTS).forEach(key => {
        next[key] = {
            name: DEFAULT_TIME_SLOTS[key].name,
            time: DEFAULT_TIME_SLOTS[key].time
        };
    });

    let applied = 0;
    periods.forEach((p, i) => {
        if (!p) return;
        const index = parseInt(p.index, 10) || (i + 1);
        const start = p.start ? String(p.start).slice(0, 5) : '';
        const end = p.end ? String(p.end).slice(0, 5) : '';
        if (index <= 0 || !start || !end) return;
        next[index] = {
            name: p.name ? String(p.name) : ('第' + index + '节'),
            time: start + '-' + end
        };
        applied++;
    });

    if (applied === 0) return false;

    // 就地替换，保持对象引用不变
    Object.keys(timeSlots).forEach(key => { delete timeSlots[key]; });
    Object.keys(next).forEach(key => { timeSlots[key] = next[key]; });
    return true;
};

/**
 * 星期名称数组
 * @description 索引0为空，索引1-7对应周一到周日
 */
const weekDays = ['', '周一', '周二', '周三', '周四', '周五', '周六', '周日'];

// 场景类型名称映射
// 用于推送设置等场景的显示名
const sceneTypeNames = {
    'schedule_reminder': '课程提醒',
    'grade_notify': '成绩通知',
    'power_fee': '电费提醒',
    'power_report': '电费报告',
    'subscription_alert': '额度提醒'
};

export {
    COURSE_COLORS,
    getCourseColor,
    formatTimeRange,
    formatDate,
    formatDateTime,
    timeSlots,
    applyTimeSlots,
    weekDays,
    sceneTypeNames
};

export default {
    COURSE_COLORS,
    getCourseColor,
    formatTimeRange,
    formatDate,
    formatDateTime,
    timeSlots,
    applyTimeSlots,
    weekDays,
    sceneTypeNames
};
