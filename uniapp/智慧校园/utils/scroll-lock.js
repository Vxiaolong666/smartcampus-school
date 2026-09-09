/**
 * 浮层（AI 结果弹窗等）打开时的背景滚动锁定
 *
 * 问题：`.ai-mask` 是 position:fixed 的全屏遮罩，手指在上面滑动时浏览器会把
 * 滚动「链式传递」给背景页面（滚动穿透），表现为页面和 AI 结果浮层一起上下滚动。
 *
 * 方案：H5 端在浮层打开时把 <body> 固定住（position:fixed 方案比单纯
 * overflow:hidden 在 iOS Safari 上更可靠），关闭时还原并恢复原有滚动位置。
 * 这样只会停掉「背景页面」的滚动，不影响浮层内部 scroll-view 自身的滚动。
 *
 * 说明：只有 H5 需要此处理（小程序 / App 端由各自的原生手势逻辑处理），
 * 因此实现用条件编译包裹，其它平台为空操作，不会产生任何副作用。
 */

// 允许嵌套 / 重复调用：首次调用真正加锁，最后一次解锁才真正还原
let lockCount = 0;
// 加锁前的滚动位置，解锁后原样恢复，避免回到页面顶部
let savedScrollTop = 0;

function readScrollTop() {
	return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
}

function restoreScrollTop(top) {
	window.scrollTo(0, top);
	document.documentElement.scrollTop = top;
	document.body.scrollTop = top;
}

/**
 * 锁定背景页面滚动
 */
export function lockPageScroll() {
	// #ifdef H5
	if (lockCount === 0) {
		try {
			savedScrollTop = readScrollTop();
			const html = document.documentElement;
			const body = document.body;
			html.style.overflow = 'hidden';
			body.style.position = 'fixed';
			body.style.top = `-${savedScrollTop}px`;
			body.style.left = '0';
			body.style.right = '0';
			body.style.width = '100%';
			body.style.overflow = 'hidden';
		} catch (e) {
			/* 忽略异常：锁定失败不应影响浮层正常展示 */
		}
	}
	lockCount += 1;
	// #endif
}

/**
 * 解锁背景页面滚动（仅在最后一个浮层关闭时真正还原）
 */
export function unlockPageScroll() {
	// #ifdef H5
	if (lockCount > 0) {
		lockCount -= 1;
	}
	if (lockCount > 0) {
		return;
	}
	try {
		const html = document.documentElement;
		const body = document.body;
		html.style.overflow = '';
		body.style.position = '';
		body.style.top = '';
		body.style.left = '';
		body.style.right = '';
		body.style.width = '';
		body.style.overflow = '';
		restoreScrollTop(savedScrollTop);
	} catch (e) {
		/* 忽略异常 */
	}
	// #endif
}

/**
 * AI 结果浮层专用 mixin
 *
 * 使用时在页面组件中加入 `mixins: [aiScrollLockMixin]` 即可，
 * mixin 中的 watch / 生命周期会与页面自身的同名配置合并执行，互不覆盖。
 */
export const aiScrollLockMixin = {
	watch: {
		showAiResult(val) {
			if (val) {
				lockPageScroll();
				// 浮层是 v-if 渲染，等 DOM 挂载后再绑定真正的非被动监听
				this.$nextTick(() => this._bindAiScrollTouch());
			} else {
				unlockPageScroll();
				this._unbindAiScrollTouch();
			}
		}
	},

	// 兜底：浮层仍打开时离开页面，避免 body / 监听一直处于锁定状态
	onUnload() {
		unlockPageScroll();
		this._unbindAiScrollTouch();
	},

	beforeUnmount() {
		unlockPageScroll();
		this._unbindAiScrollTouch();
	},

	methods: {
		// 取 scroll-view 内部真实滚动元素（uni-app H5 渲染为 .uni-scroll-view）
		_getAiScroller() {
			const root = this.$el;
			if (!root || typeof root.querySelector !== 'function') return null;
			const host = root.querySelector('.ai-modal-body');
			if (!host) return null;
			return host.querySelector('.uni-scroll-view') || host;
		},

		// 真正起作用的 touchmove 处理：必须挂在「非 passive」的监听器上，
		// 否则浏览器会忽略 preventDefault，滚动仍会穿透到背景页面。
		_onAiScrollTouchMove(e) {
			const scroller = this._getAiScroller();
			if (!scroller) return;
			const maxScroll = scroller.scrollHeight - scroller.clientHeight;
			// 内容不足一屏（如「AI 正在分析」阶段）：直接禁止默认行为
			if (maxScroll <= 0) {
				if (e.cancelable !== false && typeof e.preventDefault === 'function') e.preventDefault();
				return;
			}
			const t = e.touches && e.touches[0];
			const curY = t ? t.clientY : 0;
			const delta = curY - (this._aiTouchY || 0);
			const atTop = scroller.scrollTop <= 0;
			const atBottom = scroller.scrollTop >= maxScroll - 1;
			// 在边界继续往同方向拖：阻止其把滚动链到背景页面
			if ((delta > 0 && atTop) || (delta < 0 && atBottom)) {
				if (e.cancelable !== false && typeof e.preventDefault === 'function') e.preventDefault();
			}
			this._aiTouchY = curY;
		},

		// 手动挂载「非 passive」touchmove 监听，确保 preventDefault 生效
		_bindAiScrollTouch() {
			// #ifdef H5
			try {
				const scroller = this._getAiScroller();
				if (!scroller) return;
				this._unbindAiScrollTouch();
				this._aiTouchY = 0;
				this._aiScrollHandler = this._onAiScrollTouchMove.bind(this);
				scroller.addEventListener('touchmove', this._aiScrollHandler, { passive: false });
			} catch (_) {}
			// #endif
		},

		_unbindAiScrollTouch() {
			// #ifdef H5
			try {
				if (this._aiScrollHandler) {
					const scroller = this._getAiScroller();
					if (scroller) {
						scroller.removeEventListener('touchmove', this._aiScrollHandler, { passive: false });
					}
					this._aiScrollHandler = null;
				}
			} catch (_) {}
			// #endif
		},

		// 记录触摸起点（用于方向判断）。此模板绑定为被动监听，仅做记录，
		// 真正拦截由上面的非被动监听完成。
		onAiBodyTouchStart(e) {
			// #ifdef H5
			try {
				const t = e.touches && e.touches[0];
				this._aiTouchY = t ? t.clientY : 0;
			} catch (_) {}
			// #endif
		},

		// 保留以兼容模板绑定；实际拦截已移至非被动监听。
		onAiBodyTouchMove() {}
	}
};
