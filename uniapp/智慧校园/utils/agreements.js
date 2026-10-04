/**
 * 协议中心：协议详情统一路径
 * ------------------------------------------------------------------
 * 说明：本模块与各协议页面均不内置任何协议清单、标题、说明或正文等预留信息，
 *       协议清单与内容一律来自后台「协议管理」：
 *         - api.php?act=get_agreements  协议清单
 *         - api.php?act=get_agreement   单份协议内容
 *       后台未配置/已停用 → 「当前协议未发布」；
 *       拉取失败（网络异常、协议表未初始化等）→ 「加载失败 + 重新加载」。
 */

/**
 * 协议详情统一路径
 * 所有协议都走同一个通用详情页：detail/index?key=xxx，页面内按 key 从接口取内容。
 * 好处：新增协议无需再改页面/路由，一处维护。
 */
function resolvePath(key) {
	if (!key) return '';
	return '/pages/agreement/detail/index?key=' + encodeURIComponent(key);
}

export default {
	resolvePath
};
