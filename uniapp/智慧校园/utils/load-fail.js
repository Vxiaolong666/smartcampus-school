/**
 * 网络请求失败状态（页面级加载失败遮罩驱动）
 *
 * 设计目的：
 * - api.js 在「网络层失败」时调用 show()，所有已挂载 <cb-load-fail /> 的页面自动展示遮罩；
 * - 请求成功后调用 clear()，遮罩自动收起；
 * - 遮罩只对「发生失败的那个页面」展示，避免用户返回上一页时上一页也出现失败遮罩。
 *
 * 与原生小程序端（utils/load-fail.js）保持完全一致。
 */

let state = {
  failed: false,
  message: '',
  route: '' // 发生失败的页面路由，用于只在该页展示
};

const listeners = [];

function emit() {
  listeners.slice().forEach((fn) => {
    try {
      fn(state);
    } catch (e) {
      // 单个订阅者异常不影响其他页面
    }
  });
}

function currentRoute() {
  try {
    const pages = getCurrentPages();
    const page = pages[pages.length - 1];
    if (!page) return '';
    return page.route || (page.$page && page.$page.route) || '';
  } catch (e) {
    return '';
  }
}

export default {
  state,

  // 订阅状态变化，返回取消订阅函数
  subscribe(fn) {
    listeners.push(fn);
    return () => {
      const i = listeners.indexOf(fn);
      if (i > -1) listeners.splice(i, 1);
    };
  },

  // 记录一次网络失败（绑定当前页面路由）
  show(message) {
    state = {
      failed: true,
      message: message || '网络异常，请稍后重试',
      route: currentRoute()
    };
    emit();
  },

  // 请求成功 / 用户重试后清除
  clear() {
    if (!state.failed) return;
    state = { failed: false, message: '', route: '' };
    emit();
  },

  // 读取当前状态（组件挂载时用于判断是否已有失败）
  get() {
    return state;
  },

  // 当前页面路由（组件用于判断遮罩是否属于自己）
  getRoute: currentRoute
};
