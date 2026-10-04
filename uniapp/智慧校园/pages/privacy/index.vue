<template>
	<agreement-view akey="privacy" />
</template>

<script>
/**
 * 隐私协议
 * 内容一律来自后台「协议管理」的 privacy 协议（接口下发），本地不内置任何协议正文；
 * 后台未配置 / 已停用 → 「当前协议未发布」；拉取失败 → 「加载失败 + 重新加载」。
 */
export default {
	name: 'privacy'
};
</script>
