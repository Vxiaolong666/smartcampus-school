<template>
	<agreement-view :akey="akey" />
</template>

<script>
/**
 * 协议通用详情页（全部协议统一入口）
 * 所有协议（内置协议与后台新建的）都由此页渲染：/pages/agreement/detail/index?key=xxx
 * 内容以后台「协议管理」为准（未发布时展示兜底提示），失败回退内置内容；正文支持图片。
 */
export default {
	data() {
		return {
			akey: ''
		};
	},
	onLoad(options) {
		this.akey = (options && options.key) ? String(options.key) : '';
	}
};
</script>
