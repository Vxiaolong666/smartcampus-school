/**
 * 「联系客服」弹窗实例注册表
 *
 * contact-guide 组件挂在 cb-nav-bar（每个页面都有）内，挂载时把自己注册进来，
 * 这样 utils/feature.js 这类「非组件」场景也能打开「当前页面」上的自定义联系客服弹窗，
 * 不必在每个页面手动放置组件、也无需逐页传参。
 */
const registry = [];

// 组件挂载时注册（记录所属页面，多页面同时存在时据此取出「当前页面」的实例）
const registerContactModal = (instance) => {
    const pages = typeof getCurrentPages === 'function' ? getCurrentPages() : [];
    instance._ownerPage = pages.length ? pages[pages.length - 1] : null;
    registry.push(instance);
};

const unregisterContactModal = (instance) => {
    const i = registry.indexOf(instance);
    if (i > -1) registry.splice(i, 1);
};

/**
 * 取「当前页面」上的联系客服弹窗实例
 * @returns {Object|null} 没有则返回 null（调用方回退到原生弹窗）
 */
const getActiveContactModal = () => {
    const pages = typeof getCurrentPages === 'function' ? getCurrentPages() : [];
    const page = pages.length ? pages[pages.length - 1] : null;
    if (!page) return null;
    for (let i = registry.length - 1; i >= 0; i--) {
        if (registry[i]._ownerPage === page) return registry[i];
    }
    return null;
};

module.exports = { registerContactModal, unregisterContactModal, getActiveContactModal };
