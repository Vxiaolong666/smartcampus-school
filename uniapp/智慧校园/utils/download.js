/**
 * 下载地址解析与打开工具
 * ------------------------------------------------------------------
 * 后台「版本管理」的下载地址有三类：
 *   1. 蓝奏云分享链接（后台可在链接末尾追加 &pwd=密码）→ 服务端会转成本站中转直链；
 *   2. 直链文件（apk / ipa / wgt）；
 *   3. 其它页面（应用商店等）。
 * 本模块集中处理「下载密码拼接」与「各端打开方式」，与版本更新弹窗（update-modal）保持一致：
 *   - App 端：交给系统浏览器打开（不使用内置下载器直接下载 / 安装）；
 *   - H5 端：新窗口打开；
 *   - 小程序端：无法唤起外部浏览器，复制链接并提示用户到浏览器打开。
 */

// 本站蓝奏云中转地址（php/lanzhouyun.php?type=file）：属于可下载直链
const LANZOU_PROXY_RE = /lanzhouyun\.php\?[^\s]*type=file/i;
// 蓝奏云分享页域名
const LANZOU_PAGE_RE = /(^|\/\/)([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(\/|$)/i;

/**
 * 是否蓝奏云相关地址（分享页或本站中转直链）
 * @param {string} url
 * @returns {boolean}
 */
const isLanzouUrl = (url) => {
	const u = String(url || '');
	return LANZOU_PROXY_RE.test(u) || LANZOU_PAGE_RE.test(u);
};

/**
 * 是否蓝奏云来源（后台标记为 lanzou，或链接本身就是蓝奏云相关地址）
 * @param {string} source 后台 download_source
 * @param {string} url 下载地址
 * @returns {boolean}
 */
const isLanzouSource = (source, url) => String(source || '') === 'lanzou' || isLanzouUrl(url);

/**
 * 把下载密码注入下载地址：地址里没有 pwd 时补上
 * 仅对蓝奏云相关地址注入（分享页与其服务端中转地址都必须带 pwd），避免污染普通直链
 * @param {string} url
 * @param {string} pwd 下载密码
 * @returns {string}
 */
const withPwd = (url, pwd) => {
	if (!url) return url;
	const p = pwd ? String(pwd).trim() : '';
	if (!p) return url;
	if (/[?&]pwd=/i.test(url)) return url; // 已带密码，不重复注入
	if (!isLanzouUrl(url)) return url;
	// 已含 ? 或 & 时用 & 追加（与后台「链接末尾追加 &pwd=密码」的写法一致）
	const sep = /[?&]/.test(url) ? '&' : '?';
	return url + sep + 'pwd=' + encodeURIComponent(p);
};

/**
 * 复制文本到剪贴板（H5 非安全上下文用临时输入框兜底）
 * @param {string} text
 * @param {Function} [onSuccess] 复制成功回调
 */
const copyText = (text, onSuccess) => {
	const value = text == null ? '' : String(text);
	if (!value) return;
	const done = () => {
		if (typeof onSuccess === 'function') onSuccess();
	};
	const fail = () => {
		// #ifdef H5
		try {
			const ta = document.createElement('textarea');
			ta.value = value;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild(ta);
			ta.select();
			const ok = document.execCommand('copy');
			document.body.removeChild(ta);
			if (ok) { done(); return; }
		} catch (e) {}
		// #endif
		uni.showToast({ title: '复制失败，请长按选择复制', icon: 'none' });
	};
	uni.setClipboardData({ data: value, success: done, fail });
};

/**
 * 打开下载地址（不在应用内直接下载，统一交给浏览器）
 * @param {string} url 已注入下载密码的下载地址
 * @param {Object} [options]
 * @param {string} [options.pwd] 下载密码（小程序端复制链接时一并提示）
 */
const openDownloadUrl = (url, options) => {
	if (!url) {
		uni.showToast({ title: '暂无下载地址', icon: 'none' });
		return;
	}
	const opts = options || {};

	// #ifdef APP-PLUS
	// 系统浏览器打开（不使用内置下载器直接下载 / 安装）
	plus.runtime.openURL(url);
	return;
	// #endif

	// #ifdef H5
	try {
		window.open(url, '_blank');
	} catch (e) {}
	return;
	// #endif

	// #ifndef APP-PLUS
	// #ifndef H5
	// 小程序端无法唤起外部浏览器：复制链接后由用户自行到浏览器打开
	copyText(url, () => {
		uni.showToast({
			title: opts.pwd ? '链接已复制，请在浏览器打开并使用下载密码' : '链接已复制，请在浏览器打开',
			icon: 'none',
			duration: 2600
		});
	});
	// #endif
	// #endif
};

export {
	LANZOU_PROXY_RE,
	LANZOU_PAGE_RE,
	isLanzouUrl,
	isLanzouSource,
	withPwd,
	copyText,
	openDownloadUrl
};

export default {
	isLanzouUrl,
	isLanzouSource,
	withPwd,
	copyText,
	openDownloadUrl
};
